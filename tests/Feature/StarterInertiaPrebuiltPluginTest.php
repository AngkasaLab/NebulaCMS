<?php

use App\Models\Plugin;
use App\Services\PluginFrontendAssetRegistry;
use Tests\TestCase;

it('menyusun URL asset starter plugin dan melayani file dist javascript', function () {
    /** @var TestCase $this */
    $plugin = Plugin::create([
        'name' => 'Starter Inertia Prebuilt',
        'slug' => 'starter-inertia-prebuilt',
        'folder_name' => 'starter-inertia-prebuilt',
        'version' => '1.0.0',
        'is_active' => true,
        'requires' => ['cms_version' => '^1.0.0'],
    ]);

    try {
        app()->instance('plugins', collect([$plugin]));

        $assets = app(PluginFrontendAssetRegistry::class)
            ->resolveForComponent('Plugins/StarterInertiaPrebuilt/Admin/Index');

        expect($assets['scripts'])->toHaveCount(1);
        expect($assets['styles'])->toBe([]);
        expect($assets['scripts'][0])->toContain('/plugin-assets/starter-inertia-prebuilt/assets/plugin-app-');

        $this->get($assets['scripts'][0])
            ->assertOk()
            ->assertHeader('Content-Type', 'text/javascript; charset=UTF-8');
    } finally {
        app()->forgetInstance('plugins');
        $plugin->delete();
    }
});
