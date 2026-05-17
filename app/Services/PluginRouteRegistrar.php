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
 * Plugins may override the default route group via `plugin.json > routes`.
 * Dynamic registration may not be included in `php artisan route:cache` output;
 * avoid route caching or document limitations.
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

            $routeConfig = $this->resolveRouteConfig($plugin);
            $group = Route::middleware($routeConfig['middleware']);

            if ($routeConfig['prefix'] !== '') {
                $group->prefix($routeConfig['prefix']);
            }

            if ($routeConfig['name_prefix'] !== '') {
                $group->name($routeConfig['name_prefix']);
            }

            $group->group(function () use ($routesFile) {
                require $routesFile;
            });
        }
    }

    /**
     * @return array{prefix: string, name_prefix: string, middleware: array<int, string>}
     */
    protected function resolveRouteConfig(Plugin $plugin): array
    {
        $config = array_merge([
            'prefix' => '_plugin/'.$plugin->slug,
            'name_prefix' => 'plugin.'.$plugin->slug.'.',
            'middleware' => ['web'],
        ], $plugin->getRouteConfigFromDisk() ?? []);

        $config = apply_filters(PluginHooks::PLUGIN_ROUTE_CONFIG, $config, $plugin);

        $middleware = array_values(array_filter(
            is_array($config['middleware'] ?? null) ? $config['middleware'] : ['web'],
            fn ($value) => is_string($value) && $value !== ''
        ));

        if ($middleware === []) {
            $middleware = ['web'];
        }

        return [
            'prefix' => $this->normalizePrefix($config['prefix'] ?? '_plugin/'.$plugin->slug),
            'name_prefix' => $this->normalizeNamePrefix($config['name_prefix'] ?? 'plugin.'.$plugin->slug.'.'),
            'middleware' => $middleware,
        ];
    }

    protected function normalizePrefix(mixed $prefix): string
    {
        if (! is_string($prefix)) {
            return '';
        }

        return trim($prefix, '/');
    }

    protected function normalizeNamePrefix(mixed $namePrefix): string
    {
        if (! is_string($namePrefix) || $namePrefix === '') {
            return '';
        }

        return str_ends_with($namePrefix, '.') ? $namePrefix : $namePrefix.'.';
    }
}
