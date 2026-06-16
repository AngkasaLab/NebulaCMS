<?php

namespace StarterInertiaPrebuilt\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class StarterAdminController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Plugins/StarterInertiaPrebuilt/Admin/Index', [
            'plugin' => [
                'name' => 'Starter Inertia Prebuilt',
                'slug' => 'starter-inertia-prebuilt',
                'version' => '1.0.0',
            ],
            'features' => [
                'Inertia admin page with its own plugin dist assets',
                'Perfect for shared hosting without requiring npm run build on the server',
                'Can be used as a base for forms, dashboards, or admin tools',
            ],
            'fileMap' => [
                'plugin.json',
                'index.php',
                'routes.php',
                'src/Http/Controllers/StarterAdminController.php',
                'resources/js/plugin-app.tsx',
                'resources/js/StarterAdminIndex.tsx',
                'resources/js/pages/Plugins/StarterInertiaPrebuilt/Admin/Index.tsx',
                'vite.prebuilt.config.ts',
                'dist/manifest.json',
            ],
        ]);
    }
}
