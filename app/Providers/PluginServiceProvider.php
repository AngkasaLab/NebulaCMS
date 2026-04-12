<?php

namespace App\Providers;

use App\Models\Plugin;
use App\Services\PluginRouteRegistrar;
use App\Support\PluginHooks;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register plugins collection as singleton
        $this->app->singleton('plugins', function ($app) {
            try {
                if (! Schema::hasTable('plugins')) {
                    return collect();
                }

                return Plugin::getActiveForBoot();
            } catch (\Exception $e) {
                return collect();
            }
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        try {
            if (! Schema::hasTable('plugins')) {
                return;
            }
        } catch (\Exception $e) {
            return;
        }

        // Load active plugins
        $plugins = app('plugins');

        foreach ($plugins as $plugin) {
            $this->loadPlugin($plugin);
        }

        // Fire hook after all plugins are loaded
        do_action(PluginHooks::PLUGINS_LOADED, $plugins);

        app(PluginRouteRegistrar::class)->registerForPlugins($plugins);
    }

    /**
     * Load a single plugin
     */
    protected function loadPlugin(Plugin $plugin): void
    {
        $indexPath = $plugin->getIndexPath();

        if (! file_exists($indexPath)) {
            return;
        }

        // Fire hook before loading plugin
        do_action(PluginHooks::PLUGIN_LOADING, $plugin);

        try {
            require_once $indexPath;

            // Fire hook after loading plugin
            do_action(PluginHooks::PLUGIN_LOADED, $plugin);
        } catch (\Exception $e) {
            // Log error but don't break the application
            \Log::error("Failed to load plugin {$plugin->name}: ".$e->getMessage());
        }
    }
}
