<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\UploadScanner;
use App\Exceptions\UploadScanException;
use App\Http\Controllers\Controller;
use App\Models\Plugin;
use App\Services\PluginRequirementChecker;
use App\Services\SecureZipInspector;
use App\Support\InertiaUploadSecurity;
use App\Support\PluginHooks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use ZipArchive;

class PluginController extends Controller
{
    public function __construct(
        protected PluginRequirementChecker $requirementChecker,
    ) {
        $this->middleware('permission:manage plugins');
    }

    public function index()
    {
        $plugins = Plugin::query()
            ->orderBy('name')
            ->get()
            ->map(function (Plugin $plugin) {
                $check = $this->requirementChecker->check($plugin);

                return [
                    'id' => $plugin->id,
                    'name' => $plugin->name,
                    'slug' => $plugin->slug,
                    'folder_name' => $plugin->folder_name,
                    'description' => $plugin->description,
                    'version' => $plugin->version,
                    'author' => $plugin->author,
                    'author_url' => $plugin->author_url,
                    'is_active' => $plugin->is_active,
                    'settings' => $plugin->settings,
                    'requires' => $plugin->requires,
                    'compatibility' => $check,
                    'settings_schema' => $plugin->getSettingsSchemaFromDisk(),
                ];
            });

        return Inertia::render('Admin/Plugins/Index', [
            'plugins' => $plugins,
            'uploadSecurity' => InertiaUploadSecurity::extensionZip(),
        ]);
    }

    public function activate(Plugin $plugin)
    {
        if (! $plugin->hasValidStructure()) {
            return redirect()->back()->with('error', 'Plugin structure is invalid. Missing index.php file.');
        }

        $check = $this->requirementChecker->check($plugin);
        if (! $check['ok']) {
            return redirect()->back()->with('error', implode(' ', $check['errors']));
        }

        $plugin->activate();
        $fresh = $plugin->fresh();

        if (function_exists('activity') && auth()->check()) {
            activity()
                ->causedBy(auth()->user())
                ->withProperties(['plugin_id' => $fresh->id, 'slug' => $fresh->slug])
                ->log('plugin_activated');
        }

        do_action(PluginHooks::PLUGIN_ACTIVATE, $fresh);

        $msg = 'Plugin activated successfully';
        if ($check['warnings'] !== []) {
            return redirect()->back()->with('success', $msg)->with('warning', implode(' ', $check['warnings']));
        }

        return redirect()->back()->with('success', $msg);
    }

    public function deactivate(Plugin $plugin)
    {
        $plugin->deactivate();
        $fresh = $plugin->fresh();

        do_action(PluginHooks::PLUGIN_DEACTIVATE, $fresh);

        if (function_exists('activity') && auth()->check()) {
            activity()
                ->causedBy(auth()->user())
                ->withProperties(['plugin_id' => $fresh->id, 'slug' => $fresh->slug])
                ->log('plugin_deactivated');
        }

        return redirect()->back()->with('success', 'Plugin deactivated successfully');
    }

    public function updateSettings(Request $request, Plugin $plugin)
    {
        $schema = $plugin->getSettingsSchemaFromDisk();
        if (! $schema || empty($schema['fields']) || ! is_array($schema['fields'])) {
            return redirect()->back()->with('error', 'This plugin does not define settings_schema.fields in plugin.json.');
        }

        $rules = [];
        foreach ($schema['fields'] as $field) {
            if (! is_array($field) || empty($field['key']) || ! is_string($field['key'])) {
                continue;
            }
            $key = $field['key'];
            $type = $field['type'] ?? 'text';
            $rules[$key] = match ($type) {
                'boolean', 'bool' => ['nullable', 'boolean'],
                'number', 'integer', 'int' => ['nullable', 'numeric'],
                default => ['nullable', 'string', 'max:65535'],
            };
        }

        if ($rules === []) {
            return redirect()->back()->with('error', 'Invalid settings schema: no field keys.');
        }

        $validated = $request->validate($rules);
        $merged = array_merge($plugin->settings ?? [], $validated);
        $plugin->update(['settings' => $merged]);

        return redirect()->back()->with('success', 'Plugin settings saved.');
    }

    public function scan()
    {
        $pluginsPath = base_path('plugins');

        if (! File::exists($pluginsPath)) {
            File::makeDirectory($pluginsPath, 0755, true);

            return redirect()->back()->with('info', 'Plugins directory created. No plugins found.');
        }

        $directories = File::directories($pluginsPath);
        $registered = 0;
        $incompatible = 0;

        foreach ($directories as $directory) {
            $folderName = basename($directory);
            $configPath = $directory.'/plugin.json';

            if (File::exists($configPath)) {
                $config = json_decode(File::get($configPath), true);

                if (! $config || ! isset($config['name']) || ! isset($config['slug']) || ! isset($config['version'])) {
                    continue;
                }

                $model = Plugin::updateOrCreate(
                    ['folder_name' => $folderName],
                    [
                        'name' => $config['name'],
                        'slug' => $config['slug'],
                        'description' => $config['description'] ?? null,
                        'version' => $config['version'],
                        'author' => $config['author'] ?? null,
                        'author_url' => $config['author_url'] ?? null,
                        'settings' => $config['settings'] ?? null,
                        'requires' => $config['requires'] ?? null,
                    ]
                );

                if (! $this->requirementChecker->check($model)['ok']) {
                    $incompatible++;
                }

                $registered++;
            }
        }

        $message = "Plugins scanned successfully. {$registered} plugin(s) found.";
        if ($incompatible > 0) {
            return redirect()->back()
                ->with('success', $message)
                ->with('warning', "{$incompatible} plugin(s) have unmet requirements and cannot be activated until resolved.");
        }

        return redirect()->back()->with('success', $message);
    }

    public function destroy(Plugin $plugin)
    {
        // Don't allow deleting active plugins
        if ($plugin->is_active) {
            return redirect()->back()->with('error', 'Cannot delete active plugin. Please deactivate it first.');
        }

        $pluginPath = base_path("plugins/{$plugin->folder_name}");
        $uninstall = $pluginPath.'/uninstall.php';

        if (File::exists($uninstall) && File::isFile($uninstall)) {
            try {
                putenv('NEBULA_PLUGIN_UNINSTALL=1');
                require $uninstall;
                putenv('NEBULA_PLUGIN_UNINSTALL');
            } catch (\Throwable $e) {
                \Log::error('Plugin uninstall script failed: '.$e->getMessage());

                return redirect()->back()->with('error', 'Uninstall script failed: '.$e->getMessage());
            }
        }

        if (File::exists($pluginPath)) {
            File::deleteDirectory($pluginPath);
        }

        $plugin->delete();

        return redirect()->back()->with('success', 'Plugin deleted successfully');
    }

    public function upload(Request $request)
    {
        $maxZipKb = (int) config('upload_security.zip_max_kb', 10240);

        $request->validate([
            'plugin' => ['required', 'file', 'mimes:zip', 'max:'.$maxZipKb],
        ]);

        $zip = new ZipArchive;
        $file = $request->file('plugin');
        $pluginsPath = base_path('plugins');

        try {
            app(UploadScanner::class)->scanPath($file->getRealPath());
        } catch (UploadScanException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        $inspector = app(SecureZipInspector::class);

        try {
            $inspector->assertSafeArchive($file->getRealPath());
            $inspector->assertPluginZipExtensions($file->getRealPath());
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', __('Invalid ZIP: :msg', ['msg' => $e->getMessage()]));
        }

        // Create temporary file for extraction
        $tempPath = storage_path('app/temp/'.uniqid());
        File::makeDirectory($tempPath, 0755, true);

        try {
            // Extract zip to temporary folder
            if ($zip->open($file->path()) === true) {
                $zip->extractTo($tempPath);
                $zip->close();

                // Find plugin.json in root folder or first subfolder
                $pluginJson = null;
                $pluginFolder = null;

                // Check in root folder
                if (File::exists($tempPath.'/plugin.json')) {
                    $pluginJson = $tempPath.'/plugin.json';
                    $pluginFolder = $tempPath;
                } else {
                    // Check in first subfolder
                    $directories = File::directories($tempPath);
                    if (count($directories) > 0) {
                        $firstDir = $directories[0];
                        if (File::exists($firstDir.'/plugin.json')) {
                            $pluginJson = $firstDir.'/plugin.json';
                            $pluginFolder = $firstDir;
                        }
                    }
                }

                if (! $pluginJson) {
                    throw new \Exception('Invalid plugin structure: plugin.json not found');
                }

                // Read and validate plugin.json
                $config = json_decode(File::get($pluginJson), true);
                if (! isset($config['name']) || ! isset($config['slug']) || ! isset($config['version'])) {
                    throw new \Exception('Invalid plugin.json structure');
                }

                // Move to plugins folder
                $targetPath = $pluginsPath.'/'.$config['slug'];
                if (File::exists($targetPath)) {
                    File::deleteDirectory($targetPath);
                }
                File::moveDirectory($pluginFolder, $targetPath);

                // Register plugin in database
                Plugin::updateOrCreate(
                    ['folder_name' => $config['slug']],
                    [
                        'name' => $config['name'],
                        'slug' => $config['slug'],
                        'description' => $config['description'] ?? null,
                        'version' => $config['version'],
                        'author' => $config['author'] ?? null,
                        'author_url' => $config['author_url'] ?? null,
                        'settings' => $config['settings'] ?? null,
                        'requires' => $config['requires'] ?? null,
                    ]
                );

                return redirect()->back()->with('success', 'Plugin uploaded successfully');
            }

            throw new \Exception('Failed to open zip file');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to upload plugin: '.$e->getMessage());
        } finally {
            // Clean up temporary files
            if (File::exists($tempPath)) {
                File::deleteDirectory($tempPath);
            }
        }
    }
}
