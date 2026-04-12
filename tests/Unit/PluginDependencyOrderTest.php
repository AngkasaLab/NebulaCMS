<?php

use App\Models\Plugin;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('orders plugins so dependencies load first', function () {
    $b = Plugin::create([
        'name' => 'B',
        'slug' => 'b-pl',
        'folder_name' => 'b-pl',
        'version' => '1.0.0',
        'is_active' => true,
        'requires' => null,
    ]);

    $a = Plugin::create([
        'name' => 'A',
        'slug' => 'a-pl',
        'folder_name' => 'a-pl',
        'version' => '1.0.0',
        'is_active' => true,
        'requires' => ['plugins' => ['b-pl']],
    ]);

    $ordered = Plugin::sortPluginsByDependencyOrder(Plugin::whereIn('id', [$a->id, $b->id])->get());

    expect($ordered->pluck('slug')->all())->toBe(['b-pl', 'a-pl']);
});
