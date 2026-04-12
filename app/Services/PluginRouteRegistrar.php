<?php

namespace App\Services;

use App\Models\Plugin;
use App\Support\PluginHooks;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/**
 * Registers optional per-plugin routes from plugins/{slug}/routes.php.
 *
 * Routes are prefixed with /_plugin/{slug} and named plugin.{slug}.* to avoid
 * collisions with core routes. Dynamic registration may not be included in
 * `php artisan route:cache` output; avoid route caching or document limitations.
 */
class PluginRouteRegistrar
{
    /**
     * @param  Collection<int, Plugin>  $plugins
     */
    public function registerForPlugins(Collection $plugins): void
    {
        if ($plugins->isEmpty()) {
            return;
        }

        if (app()->routesAreCached()) {
            Log::warning('Plugin routes are not included in the route cache. Run "php artisan route:clear" or avoid route:cache when using plugins.');

            return;
        }

        foreach ($plugins as $plugin) {
            do_action(PluginHooks::PLUGIN_REGISTER_ROUTES, $plugin);

            $routesFile = base_path("plugins/{$plugin->folder_name}/routes.php");
            if (! is_file($routesFile)) {
                continue;
            }

            Route::middleware('web')
                ->prefix('_plugin/'.$plugin->slug)
                ->name('plugin.'.$plugin->slug.'.')
                ->group(function () use ($routesFile) {
                    require $routesFile;
                });
        }
    }
}
