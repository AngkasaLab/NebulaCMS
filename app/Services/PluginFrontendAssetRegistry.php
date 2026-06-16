<?php

namespace App\Services;

use App\Models\Plugin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PluginFrontendAssetRegistry
{
    /**
     * @return array{scripts: array<int, string>, styles: array<int, string>}
     */
    public function resolveForComponent(?string $component): array
    {
        if (! is_string($component) || $component === '' || ! str_starts_with($component, 'Plugins/')) {
            return ['scripts' => [], 'styles' => []];
        }

        foreach ($this->activePlugins() as $plugin) {
            if (! $plugin instanceof Plugin) {
                continue;
            }

            $resolved = $this->resolveForPlugin($plugin, $component);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return ['scripts' => [], 'styles' => []];
    }

    /**
     * @return Collection<int, Plugin>
     */
    protected function activePlugins(): Collection
    {
        $plugins = app()->bound('plugins') ? app('plugins') : Plugin::getActiveForBoot();

        return $plugins instanceof Collection ? $plugins : collect($plugins);
    }

    /**
     * @return array{scripts: array<int, string>, styles: array<int, string>}|null
     */
    protected function resolveForPlugin(Plugin $plugin, string $component): ?array
    {
        $entryKey = $plugin->getFrontendEntrypointsFromDisk()[$component] ?? null;
        if (! is_string($entryKey) || $entryKey === '') {
            return null;
        }

        $manifest = $plugin->getFrontendManifestFromDisk();
        if (! is_array($manifest)) {
            Log::warning("Plugin frontend manifest missing or invalid for {$plugin->slug}.");

            return ['scripts' => [], 'styles' => []];
        }

        $entry = $manifest[$entryKey] ?? null;
        if (! is_array($entry)) {
            Log::warning("Plugin frontend entry \"{$entryKey}\" missing in manifest for {$plugin->slug}.");

            return ['scripts' => [], 'styles' => []];
        }

        $scripts = [];
        $styles = [];

        $entryFile = $this->normalizeAssetPath($entry['file'] ?? null);
        if ($entryFile) {
            $scripts[] = url("/plugin-assets/{$plugin->slug}/{$entryFile}");
        }

        foreach ($entry['css'] ?? [] as $cssFile) {
            $normalizedCss = $this->normalizeAssetPath($cssFile);
            if (! $normalizedCss) {
                continue;
            }

            $styles[] = url("/plugin-assets/{$plugin->slug}/{$normalizedCss}");
        }

        return [
            'scripts' => array_values(array_unique($scripts)),
            'styles' => array_values(array_unique($styles)),
        ];
    }

    protected function normalizeAssetPath(mixed $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        $normalized = ltrim(str_replace('\\', '/', $path), '/');
        if ($normalized === '' || str_contains($normalized, '..')) {
            return null;
        }

        if (str_starts_with($normalized, 'dist/')) {
            $normalized = substr($normalized, 5);
        }

        return $normalized !== '' ? $normalized : null;
    }
}
