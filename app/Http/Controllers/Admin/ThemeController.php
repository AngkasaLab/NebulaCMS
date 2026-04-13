<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\UploadScanner;
use App\Exceptions\UploadScanException;
use App\Http\Controllers\Controller;
use App\Models\Theme;
use App\Services\ThemeRequirementChecker;
use App\Support\ThemeHooks;
use App\Services\SecureZipInspector;
use App\Support\InertiaUploadSecurity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use ZipArchive;

class ThemeController extends Controller
{
    public function __construct(
        protected ThemeRequirementChecker $requirementChecker
    ) {
        $this->middleware('permission:manage themes');
    }

    public function index()
    {
        $themes = Theme::all()->map(function ($theme) {
            // Tambahkan preview URL jika ada
            if ($theme->preview) {
                $theme->preview_url = asset("themes/{$theme->folder_name}/{$theme->preview}");
            }

            $check = $this->requirementChecker->check($theme);

            return [
                'id' => $theme->id,
                'name' => $theme->name,
                'slug' => $theme->slug,
                'folder_name' => $theme->folder_name,
                'description' => $theme->description,
                'version' => $theme->version,
                'author' => $theme->author,
                'is_active' => $theme->is_active,
                'settings' => $theme->settings,
                'preview_url' => $theme->preview_url,
                'has_preview' => $theme->has_preview,
                'compatibility' => $check,
                'settings_schema' => $theme->getSettingsSchemaFromDisk(),
            ];
        });

        return Inertia::render('Admin/Themes/Index', [
            'themes' => $themes,
            'uploadSecurity' => InertiaUploadSecurity::extensionZip(),
        ]);
    }

    public function activate(Theme $theme)
    {
        if (! $theme->hasValidStructure()) {
            return redirect()->back()->with('error', 'Theme structure is invalid. Missing theme.json file.');
        }

        $check = $this->requirementChecker->check($theme);
        if (! $check['ok']) {
            return redirect()->back()->with('error', implode(' ', $check['errors']));
        }

        do_action('theme.before_activate', $theme);

        $theme->activate();
        $fresh = $theme->fresh();

        if (function_exists('activity') && auth()->check()) {
            activity()
                ->causedBy(auth()->user())
                ->withProperties(['theme_id' => $fresh->id, 'slug' => $fresh->slug])
                ->log('theme_activated');
        }

        do_action('theme.after_activate', $fresh);

        $this->publishAssets($fresh);

        $msg = 'Theme activated successfully';
        if ($check['warnings'] !== []) {
            return redirect()->back()->with('success', $msg)->with('warning', implode(' ', $check['warnings']));
        }

        return redirect()->back()->with('success', $msg);
    }

    public function scan()
    {
        $themesPath = base_path('themes');

        if (! File::exists($themesPath)) {
            File::makeDirectory($themesPath, 0755, true);

            return redirect()->back()->with('info', 'Themes directory created. No themes found.');
        }

        $directories = File::directories($themesPath);
        $registered = 0;
        $incompatible = 0;

        foreach ($directories as $directory) {
            $folderName = basename($directory);
            $configPath = $directory.'/theme.json';

            if (! File::exists($configPath)) {
                continue;
            }

            $config = json_decode(File::get($configPath), true);

            if (! $config || ! isset($config['name'], $config['slug'], $config['version'])) {
                continue;
            }

            $model = Theme::updateOrCreate(
                ['folder_name' => $folderName],
                [
                    'name' => $config['name'],
                    'slug' => $config['slug'],
                    'description' => $config['description'] ?? null,
                    'version' => $config['version'],
                    'author' => $config['author'] ?? null,
                    'settings' => $config['settings'] ?? null,
                    'preview' => $config['preview'] ?? null,
                ]
            );

            if (! $this->requirementChecker->check($model)['ok']) {
                $incompatible++;
            }

            $registered++;
        }

        $message = "Themes scanned successfully. {$registered} theme(s) registered.";
        if ($incompatible > 0) {
            return redirect()->back()
                ->with('success', $message)
                ->with('warning', "{$incompatible} theme(s) have unmet requirements and cannot be activated until resolved.");
        }

        return redirect()->back()->with('success', $message);
    }

    public function destroy(Theme $theme)
    {
        // Jangan izinkan menghapus tema yang sedang aktif
        if ($theme->is_active) {
            return redirect()->back()->with('error', 'Cannot delete active theme');
        }

        $themePath = base_path("themes/{$theme->folder_name}");
        $uninstall = $themePath.'/uninstall.php';

        if (File::exists($uninstall) && File::isFile($uninstall)) {
            try {
                putenv('NEBULA_THEME_UNINSTALL=1');
                require $uninstall;
                putenv('NEBULA_THEME_UNINSTALL');
            } catch (\Throwable $e) {
                \Log::error('Theme uninstall script failed: '.$e->getMessage());

                return redirect()->back()->with('error', 'Uninstall script failed: '.$e->getMessage());
            }
        }

        do_action('theme.before_delete', $theme);

        $snapshot = [
            'id' => $theme->id,
            'name' => $theme->name,
            'slug' => $theme->slug,
            'folder_name' => $theme->folder_name,
        ];

        // Hapus folder tema jika ada
        $themePath = base_path("themes/{$theme->folder_name}");
        if (File::exists($themePath)) {
            File::deleteDirectory($themePath);
        }

        // Hapus published assets di public
        $publicPath = public_path("themes/{$theme->folder_name}");
        if (File::exists($publicPath)) {
            File::deleteDirectory($publicPath);
        }

        // Hapus dari database
        $theme->delete();

        do_action('theme.after_delete', $snapshot);

        if (function_exists('activity') && auth()->check()) {
            activity()
                ->causedBy(auth()->user())
                ->withProperties(['theme_id' => $snapshot['id'], 'slug' => $snapshot['slug']])
                ->log('theme_deleted');
        }

        return redirect()->back()->with('success', 'Theme deleted successfully');
    }

    public function upload(Request $request)
    {
        $maxZipKb = (int) config('upload_security.zip_max_kb', 10240);

        $request->validate([
            'theme' => ['required', 'file', 'mimes:zip', 'max:'.$maxZipKb],
        ]);

        $zip = new ZipArchive;
        $file = $request->file('theme');
        $themesPath = base_path('themes');

        try {
            app(UploadScanner::class)->scanPath($file->getRealPath());
        } catch (UploadScanException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        try {
            app(SecureZipInspector::class)->assertSafeArchive($file->getRealPath());
            app(SecureZipInspector::class)->assertThemeZipExtensions($file->getRealPath());
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', __('Invalid ZIP: :msg', ['msg' => $e->getMessage()]));
        }

        // Buat temporary file untuk extract
        $tempPath = storage_path('app/temp/'.uniqid());
        File::makeDirectory($tempPath, 0755, true);

        try {
            // Extract zip ke temporary folder
            if ($zip->open($file->path()) === true) {
                $zip->extractTo($tempPath);
                $zip->close();

                // Cari theme.json di root folder atau subfolder pertama
                $themeJson = null;
                $themeFolder = null;

                // Cek di root folder
                if (File::exists($tempPath.'/theme.json')) {
                    $themeJson = $tempPath.'/theme.json';
                    $themeFolder = $tempPath;
                } else {
                    // Cek di subfolder pertama
                    $directories = File::directories($tempPath);
                    if (count($directories) > 0) {
                        $firstDir = $directories[0];
                        if (File::exists($firstDir.'/theme.json')) {
                            $themeJson = $firstDir.'/theme.json';
                            $themeFolder = $firstDir;
                        }
                    }
                }

                if (! $themeJson) {
                    throw new \Exception('Invalid theme structure: theme.json not found');
                }

                // Baca dan validasi theme.json
                $config = json_decode(File::get($themeJson), true);
                if (! isset($config['name']) || ! isset($config['slug']) || ! isset($config['version'])) {
                    throw new \Exception('Invalid theme.json structure');
                }

                // Pindahkan ke folder themes
                $targetPath = $themesPath.'/'.$config['slug'];
                if (File::exists($targetPath)) {
                    File::deleteDirectory($targetPath);
                }
                File::moveDirectory($themeFolder, $targetPath);

                // Daftarkan tema di database
                Theme::updateOrCreate(
                    ['folder_name' => $config['slug']],
                    [
                        'name' => $config['name'],
                        'slug' => $config['slug'],
                        'description' => $config['description'] ?? null,
                        'version' => $config['version'],
                        'author' => $config['author'] ?? null,
                        'settings' => $config['settings'] ?? null,
                        'preview' => $config['preview'] ?? null,
                    ]
                );

                $uploadedTheme = Theme::where('folder_name', $config['slug'])->first();
                if ($uploadedTheme) {
                    $this->publishAssets($uploadedTheme);
                }

                return redirect()->back()->with('success', 'Theme uploaded successfully');
            }

            throw new \Exception('Failed to open zip file');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to upload theme: '.$e->getMessage());
        } finally {
            // Bersihkan temporary files
            if (File::exists($tempPath)) {
                File::deleteDirectory($tempPath);
            }
        }
    }

    public function updateSettings(Request $request, Theme $theme)
    {
        $schema = $theme->getSettingsSchemaFromDisk();
        if (! $schema || empty($schema['fields']) || ! is_array($schema['fields'])) {
            return redirect()->back()->with('error', 'This theme does not define settings_schema.fields in theme.json.');
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
        $merged = array_merge($theme->settings ?? [], $validated);
        $theme->update(['settings' => $merged]);

        return redirect()->back()->with('success', 'Theme settings saved.');
    }

    /**
     * Copy theme assets from themes/{folder}/assets to public/themes/{folder}/assets.
     */
    private function publishAssets(Theme $theme): void
    {
        $source = base_path("themes/{$theme->folder_name}/assets");
        $target = public_path("themes/{$theme->folder_name}/assets");

        if (File::exists($source)) {
            if (File::exists($target)) {
                File::deleteDirectory($target);
            }

            File::copyDirectory($source, $target);
        }
    }
}
