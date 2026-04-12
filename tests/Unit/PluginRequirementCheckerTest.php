<?php

use App\Models\Plugin;
use App\Services\PluginRequirementChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('passes when requires is empty', function () {
    config(['nebula.version' => '1.2.0']);

    $plugin = new Plugin(['requires' => null]);

    expect(app(PluginRequirementChecker::class)->check($plugin)['ok'])->toBeTrue();
});

it('passes when cms_version and php constraints match', function () {
    config(['nebula.version' => '1.2.0']);

    $plugin = new Plugin([
        'requires' => [
            'cms_version' => '^1.0.0',
            'php' => '^8.3',
        ],
    ]);

    expect(app(PluginRequirementChecker::class)->check($plugin)['ok'])->toBeTrue();
});

it('fails when cms_version does not satisfy', function () {
    config(['nebula.version' => '0.9.0']);

    $plugin = new Plugin([
        'requires' => [
            'cms_version' => '^1.0.0',
        ],
    ]);

    $result = app(PluginRequirementChecker::class)->check($plugin);

    expect($result['ok'])->toBeFalse();
    expect($result['errors'])->not->toBeEmpty();
});

it('fails when required plugin slug is missing', function () {
    config(['nebula.version' => '1.2.0']);

    $plugin = Plugin::create([
        'name' => 'Needs Other',
        'slug' => 'needs-other',
        'folder_name' => 'needs-other',
        'version' => '1.0.0',
        'is_active' => false,
        'requires' => [
            'cms_version' => '^1.0.0',
            'plugins' => ['nonexistent-dep'],
        ],
    ]);

    $result = app(PluginRequirementChecker::class)->check($plugin);

    expect($result['ok'])->toBeFalse();
    expect(implode(' ', $result['errors']))->toContain('not installed');
});

it('fails when required plugin is inactive', function () {
    config(['nebula.version' => '1.2.0']);

    $dep = Plugin::create([
        'name' => 'Dep',
        'slug' => 'dep-pl',
        'folder_name' => 'dep-pl',
        'version' => '1.0.0',
        'is_active' => false,
    ]);

    $plugin = Plugin::create([
        'name' => 'Consumer',
        'slug' => 'consumer-pl',
        'folder_name' => 'consumer-pl',
        'version' => '1.0.0',
        'is_active' => false,
        'requires' => [
            'plugins' => ['dep-pl'],
        ],
    ]);

    $result = app(PluginRequirementChecker::class)->check($plugin);

    expect($result['ok'])->toBeFalse();
    expect(implode(' ', $result['errors']))->toContain('activated');
});
