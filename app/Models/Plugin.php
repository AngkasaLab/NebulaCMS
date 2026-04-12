<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class Plugin extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'folder_name',
        'description',
        'version',
        'author',
        'author_url',
        'is_active',
        'settings',
        'requires',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'requires' => 'array',
    ];

    /**
     * Get all active plugins (unordered).
     */
    public static function getActive(): Collection
    {
        return static::where('is_active', true)->get();
    }

    /**
     * Active plugins ordered so dependencies load before dependents (requires.plugins).
     */
    public static function getActiveForBoot(): Collection
    {
        return static::sortPluginsByDependencyOrder(static::getActive());
    }

    /**
     * @param  Collection<int, Plugin>  $plugins
     * @return Collection<int, Plugin>
     */
    public static function sortPluginsByDependencyOrder(Collection $plugins): Collection
    {
        if ($plugins->isEmpty()) {
            return $plugins;
        }

        $bySlug = $plugins->keyBy('slug');
        $slugs = $plugins->pluck('slug')->all();
        $inDegree = array_fill_keys($slugs, 0);
        $dependents = [];

        foreach ($plugins as $p) {
            $reqs = $p->requires['plugins'] ?? [];
            if (! is_array($reqs)) {
                continue;
            }
            foreach ($reqs as $depSlug) {
                if (! is_string($depSlug) || $depSlug === '' || ! $bySlug->has($depSlug)) {
                    continue;
                }
                $dependents[$depSlug][] = $p->slug;
                $inDegree[$p->slug]++;
            }
        }

        $queue = [];
        foreach ($inDegree as $slug => $deg) {
            if ($deg === 0) {
                $queue[] = $slug;
            }
        }
        sort($queue);

        $ordered = collect();
        while ($queue !== []) {
            $slug = array_shift($queue);
            $plugin = $bySlug->get($slug);
            if ($plugin) {
                $ordered->push($plugin);
            }
            foreach ($dependents[$slug] ?? [] as $dependentSlug) {
                $inDegree[$dependentSlug]--;
                if ($inDegree[$dependentSlug] === 0) {
                    $queue[] = $dependentSlug;
                }
            }
            // Sort for deterministic ordering among peers at the same dependency depth.
            // Acceptable O(n log n) per iteration for typical plugin counts (< 50).
            sort($queue);
        }

        if ($ordered->count() < $plugins->count()) {
            Log::warning('Plugin dependency cycle or unresolved requires.plugins; appending remaining plugins by id.');

            foreach ($plugins->sortBy('id') as $p) {
                if (! $ordered->contains(fn ($x) => (int) $x->id === (int) $p->id)) {
                    $ordered->push($p);
                }
            }
        }

        return $ordered;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function readPluginJson(): ?array
    {
        $path = base_path("plugins/{$this->folder_name}/plugin.json");
        if (! File::exists($path)) {
            return null;
        }

        $data = json_decode(File::get($path), true);

        return is_array($data) ? $data : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSettingsSchemaFromDisk(): ?array
    {
        $json = $this->readPluginJson();
        $schema = $json['settings_schema'] ?? null;

        return is_array($schema) ? $schema : null;
    }

    /**
     * Activate this plugin
     */
    public function activate(): self
    {
        $this->is_active = true;
        $this->save();

        return $this;
    }

    /**
     * Deactivate this plugin
     */
    public function deactivate(): self
    {
        $this->is_active = false;
        $this->save();

        return $this;
    }

    /**
     * Get the plugin's main file path
     */
    public function getIndexPath(): string
    {
        return base_path("plugins/{$this->folder_name}/index.php");
    }

    /**
     * Check if plugin's main file exists
     */
    public function hasValidStructure(): bool
    {
        return file_exists($this->getIndexPath());
    }
}
