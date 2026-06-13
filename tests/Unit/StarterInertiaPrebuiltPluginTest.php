<?php

use App\Models\Plugin;
use App\Services\PluginRequirementChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

function starter_inertia_prebuilt_plugin_model(): Plugin
{
    return new Plugin([
        'name' => 'Starter Inertia Prebuilt',
        'slug' => 'starter-inertia-prebuilt',
        'folder_name' => 'starter-inertia-prebuilt',
        'version' => '1.0.0',
        'is_active' => false,
        'requires' => ['cms_version' => '^1.0.0'],
    ]);
}

it('membaca kontrak frontend starter plugin dari plugin.json', function () {
    $plugin = starter_inertia_prebuilt_plugin_model();

    expect($plugin->getFrontendTypeFromDisk())->toBe('inertia-prebuilt');
    expect($plugin->getFrontendManifestRelativePathFromDisk())->toBe('dist/manifest.json');
    expect($plugin->getFrontendEntrypointsFromDisk())->toBe([
        'Plugins/StarterInertiaPrebuilt/Admin/Index' => 'resources/js/plugin-app.tsx',
    ]);
    expect($plugin->getAdminNavigationFromDisk())->toBe([
        [
            'title' => 'Starter Plugin',
            'href' => '/admin/starter-inertia-prebuilt',
            'group' => 'admin',
            'icon' => 'Puzzle',
            'match_paths' => ['/admin/starter-inertia-prebuilt'],
        ],
    ]);
});

it('memiliki manifest dist starter plugin yang valid', function () {
    $plugin = starter_inertia_prebuilt_plugin_model();

    $manifestPath = $plugin->getFrontendManifestAbsolutePathFromDisk();
    $manifest = $plugin->getFrontendManifestFromDisk();

    expect($manifestPath)->not->toBeNull();
    expect(is_file($manifestPath))->toBeTrue();
    expect($manifest)->toBeArray();
    expect($manifest)->toHaveKey('resources/js/plugin-app.tsx');
    expect($manifest['resources/js/plugin-app.tsx']['file'] ?? null)->toBeString();
    expect(base_path('plugins/starter-inertia-prebuilt/dist/'.$manifest['resources/js/plugin-app.tsx']['file']))->toBeFile();
});

it('lolos compatibility check sebagai plugin prebuilt yang siap dipakai', function () {
    config(['nebula.version' => '1.0.0']);

    $plugin = starter_inertia_prebuilt_plugin_model();
    $result = app(PluginRequirementChecker::class)->check($plugin);

    expect($result['ok'])->toBeTrue();
    expect($result['errors'])->toBe([]);
});
