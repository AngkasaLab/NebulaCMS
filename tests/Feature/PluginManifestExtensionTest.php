<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Plugin;
use App\Services\PluginRouteRegistrar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

it('membaca konfigurasi route plugin dari manifest', function () {
    $folder = '__pl_manifest_routes_'.uniqid();
    $path = base_path("plugins/{$folder}");
    File::makeDirectory($path, 0755, true);
    File::put($path.'/plugin.json', json_encode([
        'name' => 'Manifest Routes',
        'slug' => $folder,
        'version' => '1.0.0',
        'routes' => [
            'prefix' => 'admin/changelog',
            'name_prefix' => 'admin.changelog',
            'middleware' => ['web', 'auth'],
        ],
        'admin_navigation' => [
            'title' => 'Changelog',
            'href' => '/admin/changelog',
            'group' => 'content',
            'icon' => 'ScrollText',
            'match_paths' => ['/admin/changelog'],
        ],
    ]));
    File::put($path.'/index.php', "<?php\n");

    try {
        $plugin = new Plugin([
            'name' => 'Manifest Routes',
            'slug' => $folder,
            'folder_name' => $folder,
            'version' => '1.0.0',
        ]);

        expect($plugin->getRouteConfigFromDisk())->toBe([
            'prefix' => 'admin/changelog',
            'name_prefix' => 'admin.changelog',
            'middleware' => ['web', 'auth'],
        ]);

        expect($plugin->getAdminNavigationFromDisk())->toBe([
            [
                'title' => 'Changelog',
                'href' => '/admin/changelog',
                'group' => 'content',
                'icon' => 'ScrollText',
                'match_paths' => ['/admin/changelog'],
            ],
        ]);
    } finally {
        if (File::exists($path)) {
            File::deleteDirectory($path);
        }
    }
});

it('mendaftarkan route plugin dengan prefix dan nama kustom dari manifest', function () {
    $folder = '__pl_manifest_group_'.uniqid();
    $path = base_path("plugins/{$folder}");
    File::makeDirectory($path, 0755, true);
    File::put($path.'/plugin.json', json_encode([
        'name' => 'Manifest Group',
        'slug' => $folder,
        'version' => '1.0.0',
        'routes' => [
            'prefix' => "admin/{$folder}",
            'name_prefix' => "admin.{$folder}",
            'middleware' => ['web'],
        ],
    ]));
    File::put($path.'/index.php', "<?php\n");
    File::put($path.'/routes.php', <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => response('pong'))->name('index');
PHP);

    try {
        $plugin = new Plugin([
            'name' => 'Manifest Group',
            'slug' => $folder,
            'folder_name' => $folder,
            'version' => '1.0.0',
        ]);

        app(PluginRouteRegistrar::class)->registerForPlugins(collect([$plugin]));

        $route = app('router')->getRoutes()->match(Request::create("/admin/{$folder}/ping", 'GET'));

        expect($route->getName())->toBe("admin.{$folder}.index");
        expect($route->run()->getContent())->toBe('pong');
    } finally {
        if (File::exists($path)) {
            File::deleteDirectory($path);
        }
    }
});

it('menyusun admin navigation plugin dari manifest untuk inertia shared props', function () {
    $folder = '__pl_manifest_nav_'.uniqid();
    $path = base_path("plugins/{$folder}");
    File::makeDirectory($path, 0755, true);
    File::put($path.'/plugin.json', json_encode([
        'name' => 'Manifest Nav',
        'slug' => $folder,
        'version' => '1.0.0',
        'admin_navigation' => [
            [
                'title' => 'Changelog',
                'href' => '/admin/changelog',
                'group' => 'content',
                'icon' => 'ScrollText',
                'match_paths' => ['/admin/changelog', '/admin/changelog/*'],
            ],
            [
                'title' => 'Releases',
                'href' => '/admin/changelog/releases',
                'group' => 'admin',
                'items' => [
                    ['title' => 'All Releases', 'href' => '/admin/changelog/releases'],
                ],
            ],
        ],
    ]));
    File::put($path.'/index.php', "<?php\n");

    try {
        $plugin = new Plugin([
            'name' => 'Manifest Nav',
            'slug' => $folder,
            'folder_name' => $folder,
            'version' => '1.0.0',
        ]);

        app()->instance('plugins', collect([$plugin]));

        $middleware = app(HandleInertiaRequests::class);
        $method = new ReflectionMethod($middleware, 'buildAdminNavigation');
        $navigation = $method->invoke($middleware, Request::create('/admin/dashboard', 'GET'));

        expect($navigation)->toBe([
            [
                'title' => 'Changelog',
                'href' => '/admin/changelog',
                'group' => 'content',
                'badge' => null,
                'icon' => 'ScrollText',
                'items' => [],
                'match_paths' => ['/admin/changelog', '/admin/changelog/*'],
            ],
            [
                'title' => 'Releases',
                'href' => '/admin/changelog/releases',
                'group' => 'admin',
                'badge' => null,
                'icon' => null,
                'items' => [
                    ['title' => 'All Releases', 'href' => '/admin/changelog/releases'],
                ],
                'match_paths' => [],
            ],
        ]);
    } finally {
        app()->forgetInstance('plugins');

        if (File::exists($path)) {
            File::deleteDirectory($path);
        }
    }
});
