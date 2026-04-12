<?php

use App\Models\Plugin;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

it('melewati plugin dengan plugin.json tidak valid saat scan', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $folder = '__pl_scan_invalid_'.uniqid();
    $path = base_path("plugins/{$folder}");
    File::makeDirectory($path, 0755, true);
    File::put($path.'/plugin.json', '{bukan json');

    try {
        $this->actingAs($user)
            ->post(route('admin.plugins.scan'))
            ->assertRedirect();

        expect(Plugin::where('folder_name', $folder)->exists())->toBeFalse();
    } finally {
        if (File::exists($path)) {
            File::deleteDirectory($path);
        }
    }
});

it('mendaftarkan plugin saat scan dan plugin.json valid', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $folder = '__pl_scan_ok_'.uniqid();
    $path = base_path("plugins/{$folder}");
    File::makeDirectory($path, 0755, true);
    File::put($path.'/plugin.json', json_encode([
        'name' => 'Lifecycle Scan',
        'slug' => $folder,
        'version' => '1.0.0',
    ]));
    File::put($path.'/index.php', "<?php\n// ok\n");

    try {
        $this->actingAs($user)
            ->post(route('admin.plugins.scan'))
            ->assertRedirect();

        $p = Plugin::where('folder_name', $folder)->first();
        expect($p)->not->toBeNull();
        expect($p->name)->toBe('Lifecycle Scan');
    } finally {
        Plugin::where('folder_name', $folder)->delete();
        if (File::exists($path)) {
            File::deleteDirectory($path);
        }
    }
});

it('menolak aktivasi jika index.php hilang', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $folder = '__pl_no_index_'.uniqid();
    $path = base_path("plugins/{$folder}");
    File::makeDirectory($path, 0755, true);
    File::put($path.'/plugin.json', json_encode([
        'name' => 'No Index',
        'slug' => $folder,
        'version' => '1.0.0',
    ]));

    $plugin = Plugin::create([
        'name' => 'No Index',
        'slug' => $folder,
        'folder_name' => $folder,
        'version' => '1.0.0',
        'is_active' => false,
    ]);

    try {
        $this->actingAs($user)
            ->post(route('admin.plugins.activate', $plugin))
            ->assertRedirect()
            ->assertSessionHas('error');

        expect($plugin->fresh()->is_active)->toBeFalse();
    } finally {
        $plugin->delete();
        if (File::exists($path)) {
            File::deleteDirectory($path);
        }
    }
});

it('mengaktifkan dan menonaktifkan plugin yang valid', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $folder = '__pl_toggle_'.uniqid();
    $path = base_path("plugins/{$folder}");
    File::makeDirectory($path, 0755, true);
    File::put($path.'/plugin.json', json_encode([
        'name' => 'Toggle PL',
        'slug' => $folder,
        'version' => '1.0.0',
    ]));
    File::put($path.'/index.php', "<?php\n");

    $plugin = Plugin::create([
        'name' => 'Toggle PL',
        'slug' => $folder,
        'folder_name' => $folder,
        'version' => '1.0.0',
        'is_active' => false,
    ]);

    try {
        $this->actingAs($user)
            ->post(route('admin.plugins.activate', $plugin))
            ->assertRedirect()
            ->assertSessionHas('success');

        expect($plugin->fresh()->is_active)->toBeTrue();

        $this->actingAs($user)
            ->post(route('admin.plugins.deactivate', $plugin->fresh()))
            ->assertRedirect()
            ->assertSessionHas('success');

        expect($plugin->fresh()->is_active)->toBeFalse();
    } finally {
        Plugin::where('folder_name', $folder)->delete();
        if (File::exists($path)) {
            File::deleteDirectory($path);
        }
    }
});

it('menolak hapus plugin yang masih aktif', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $folder = '__pl_active_del_'.uniqid();
    $path = base_path("plugins/{$folder}");
    File::makeDirectory($path, 0755, true);
    File::put($path.'/plugin.json', json_encode([
        'name' => 'Active Del',
        'slug' => $folder,
        'version' => '1.0.0',
    ]));
    File::put($path.'/index.php', "<?php\n");

    $plugin = Plugin::create([
        'name' => 'Active Del',
        'slug' => $folder,
        'folder_name' => $folder,
        'version' => '1.0.0',
        'is_active' => true,
    ]);

    try {
        $this->actingAs($user)
            ->delete(route('admin.plugins.destroy', $plugin))
            ->assertRedirect()
            ->assertSessionHas('error');

        expect(Plugin::whereKey($plugin->id)->exists())->toBeTrue();
    } finally {
        $plugin->delete();
        if (File::exists($path)) {
            File::deleteDirectory($path);
        }
    }
});

it('menolak aktivasi jika requires cms_version tidak terpenuhi', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $prev = config('nebula.version');
    config(['nebula.version' => '0.1.0']);

    $folder = '__pl_bad_req_'.uniqid();
    $path = base_path("plugins/{$folder}");
    File::makeDirectory($path, 0755, true);
    File::put($path.'/plugin.json', json_encode([
        'name' => 'Bad Req',
        'slug' => $folder,
        'version' => '1.0.0',
        'requires' => ['cms_version' => '^1.0.0'],
    ]));
    File::put($path.'/index.php', "<?php\n");

    $plugin = Plugin::create([
        'name' => 'Bad Req',
        'slug' => $folder,
        'folder_name' => $folder,
        'version' => '1.0.0',
        'is_active' => false,
        'requires' => ['cms_version' => '^1.0.0'],
    ]);

    try {
        $this->actingAs($user)
            ->post(route('admin.plugins.activate', $plugin))
            ->assertRedirect()
            ->assertSessionHas('error');

        expect($plugin->fresh()->is_active)->toBeFalse();
    } finally {
        config(['nebula.version' => $prev]);
        Plugin::where('folder_name', $folder)->delete();
        if (File::exists($path)) {
            File::deleteDirectory($path);
        }
    }
});

it('menjalankan uninstall.php sebelum menghapus folder', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $folder = '__pl_uninstall_'.uniqid();
    $path = base_path("plugins/{$folder}");
    $marker = storage_path('app/plugin_uninstall_test_'.uniqid());
    File::makeDirectory($path, 0755, true);
    File::put($path.'/plugin.json', json_encode([
        'name' => 'Uninstall PL',
        'slug' => $folder,
        'version' => '1.0.0',
    ]));
    File::put($path.'/index.php', "<?php\n");
    File::put($path.'/uninstall.php', "<?php\nfile_put_contents(".var_export($marker, true).", 'ok');\n");

    $plugin = Plugin::create([
        'name' => 'Uninstall PL',
        'slug' => $folder,
        'folder_name' => $folder,
        'version' => '1.0.0',
        'is_active' => false,
    ]);

    try {
        $this->actingAs($user)
            ->delete(route('admin.plugins.destroy', $plugin))
            ->assertRedirect()
            ->assertSessionHas('success');

        expect(File::exists($marker))->toBeTrue();
        expect(File::exists($path))->toBeFalse();
    } finally {
        if (File::exists($marker)) {
            unlink($marker);
        }
        Plugin::where('folder_name', $folder)->delete();
        if (File::exists($path)) {
            File::deleteDirectory($path);
        }
    }
});

it('menghapus plugin tidak aktif beserta foldernya', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $folder = '__pl_destroy_'.uniqid();
    $path = base_path("plugins/{$folder}");
    File::makeDirectory($path, 0755, true);
    File::put($path.'/plugin.json', json_encode([
        'name' => 'Destroy PL',
        'slug' => $folder,
        'version' => '1.0.0',
    ]));
    File::put($path.'/index.php', "<?php\n");

    $plugin = Plugin::create([
        'name' => 'Destroy PL',
        'slug' => $folder,
        'folder_name' => $folder,
        'version' => '1.0.0',
        'is_active' => false,
    ]);

    $this->actingAs($user)
        ->delete(route('admin.plugins.destroy', $plugin))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Plugin::whereKey($plugin->id)->exists())->toBeFalse();
    expect(File::exists($path))->toBeFalse();
});
