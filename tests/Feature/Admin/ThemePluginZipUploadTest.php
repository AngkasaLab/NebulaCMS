<?php

use App\Models\Plugin;
use App\Models\Theme;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\Support\ZipTestHelper;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

describe('upload tema (ZIP)', function () {
    it('gagal jika ZIP tidak berisi theme.json', function () {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $zipPath = ZipTestHelper::createZipFile([
            'readme.txt' => 'hanya teks',
        ]);

        try {
            $upload = new UploadedFile($zipPath, 'tema.zip', 'application/zip', null, true);

            $this->actingAs($user)
                ->post(route('admin.themes.upload'), ['theme' => $upload])
                ->assertRedirect()
                ->assertSessionHas('error');
        } finally {
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
        }
    });

    it('berhasil mengunggah tema ZIP valid dan mendaftarkan di database', function () {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $slug = 'zip-theme-'.uniqid();
        $themeJson = json_encode([
            'name' => 'Zip Upload Theme',
            'slug' => $slug,
            'version' => '2.0.0',
            'description' => 'dari tes',
        ]);

        $zipPath = ZipTestHelper::createZipFile([
            'theme.json' => $themeJson,
        ]);

        try {
            $upload = new UploadedFile($zipPath, 'tema.zip', 'application/zip', null, true);

            $this->actingAs($user)
                ->post(route('admin.themes.upload'), ['theme' => $upload])
                ->assertRedirect()
                ->assertSessionHas('success');

            $theme = Theme::where('folder_name', $slug)->first();
            expect($theme)->not->toBeNull();
            expect($theme->name)->toBe('Zip Upload Theme');
        } finally {
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
            $themeDir = base_path("themes/{$slug}");
            if (File::exists($themeDir)) {
                File::deleteDirectory($themeDir);
            }
            Theme::where('folder_name', $slug)->delete();
        }
    });
});

describe('upload plugin (ZIP)', function () {
    it('gagal jika ZIP tidak berisi plugin.json', function () {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $zipPath = ZipTestHelper::createZipFile([
            'index.php' => '<?php',
        ]);

        try {
            $upload = new UploadedFile($zipPath, 'plugin.zip', 'application/zip', null, true);

            $this->actingAs($user)
                ->post(route('admin.plugins.upload'), ['plugin' => $upload])
                ->assertRedirect()
                ->assertSessionHas('error');
        } finally {
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
        }
    });

    it('gagal jika ZIP berisi ekstensi yang tidak diizinkan', function () {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $slug = 'zip-bad-'.uniqid();
        $pluginJson = json_encode([
            'name' => 'Bad Plugin',
            'slug' => $slug,
            'version' => '1.0.0',
        ]);

        $zipPath = ZipTestHelper::createZipFile([
            'plugin.json' => $pluginJson,
            'index.php' => "<?php\n",
            'malware.exe' => 'MZ',
        ]);

        try {
            $upload = new UploadedFile($zipPath, 'plugin.zip', 'application/zip', null, true);

            $this->actingAs($user)
                ->post(route('admin.plugins.upload'), ['plugin' => $upload])
                ->assertRedirect()
                ->assertSessionHas('error');
        } finally {
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
        }
    });

    it('berhasil mengunggah plugin ZIP valid', function () {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $slug = 'zip-pl-'.uniqid();
        $pluginJson = json_encode([
            'name' => 'Zip Upload Plugin',
            'slug' => $slug,
            'version' => '1.2.3',
        ]);

        $zipPath = ZipTestHelper::createZipFile([
            'plugin.json' => $pluginJson,
            'index.php' => "<?php\n// zip plugin test\n",
        ]);

        try {
            $upload = new UploadedFile($zipPath, 'plugin.zip', 'application/zip', null, true);

            $this->actingAs($user)
                ->post(route('admin.plugins.upload'), ['plugin' => $upload])
                ->assertRedirect()
                ->assertSessionHas('success');

            $plugin = Plugin::where('folder_name', $slug)->first();
            expect($plugin)->not->toBeNull();
            expect($plugin->name)->toBe('Zip Upload Plugin');
        } finally {
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
            $pluginDir = base_path("plugins/{$slug}");
            if (File::exists($pluginDir)) {
                File::deleteDirectory($pluginDir);
            }
            Plugin::where('folder_name', $slug)->delete();
        }
    });
});
