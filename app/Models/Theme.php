<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Theme extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'folder_name',
        'description',
        'version',
        'author',
        'preview',
        'is_active',
        'settings'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array'
    ];

    protected $appends = [
        'preview_url',
        'has_preview'
    ];

    public function getHasPreviewAttribute()
    {
        if (!$this->preview) {
            return false;
        }

        $previewPath = base_path("themes/{$this->folder_name}/{$this->preview}");
        return File::exists($previewPath);
    }

    public function getPreviewUrlAttribute()
    {
        if (!$this->has_preview) {
            return null;
        }
        return url("themes/{$this->folder_name}/{$this->preview}");
    }

    public static function getActive()
    {
        return static::where('is_active', true)->first();
    }

    public function activate()
    {
        // Deactivate other themes
        static::where('is_active', true)->update(['is_active' => false]);

        // Activate this theme
        $this->is_active = true;
        $this->save();

        return $this;
    }

    /**
     * Getting view path for specific template
     * Using hook to allow modification of path
     *
     * @param string $view
     * @return string
     */
    public function getViewPath($view)
    {
        return apply_filters('theme.view_path', "themes.{$this->folder_name}.views.{$view}", $this, $view);
    }

    /**
     * Getting asset path for specific file
     * Using hook to allow modification of path
     *
     * @param string $path
     * @return string
     */
    public function getAssetPath($path)
    {
        return apply_filters('theme.asset_path', url("themes/{$this->folder_name}/assets/{$path}"), $this, $path);
    }

    /**
     * Checking if this theme is a child theme
     *
     * @return bool
     */
    public function isChildTheme(): bool
    {
        $settings = $this->settings ?? [];
        return isset($settings['parent']) && !empty($settings['parent']);
    }

    /**
     * Getting parent theme if this theme is a child theme
     *
     * @return Theme|null
     */
    public function getParentTheme(): ?Theme
    {
        if (!$this->isChildTheme()) {
            return null;
        }

        $parentSlug = $this->settings['parent'];
        return static::where('folder_name', $parentSlug)->first();
    }

    /**
     * Getting view path from parent theme
     *
     * @param string $view
     * @return string|null
     */
    public function getParentViewPath($view): ?string
    {
        $parentTheme = $this->getParentTheme();
        if (!$parentTheme) {
            return null;
        }

        return apply_filters('theme.parent_view_path', "parent_theme::{$view}", $parentTheme, $view);
    }

    /**
     * Check if theme structure is valid (has theme.json).
     */
    public function hasValidStructure(): bool
    {
        $themeJsonPath = base_path("themes/{$this->folder_name}/theme.json");
        return File::exists($themeJsonPath);
    }

    /**
     * Get settings schema directly from theme.json.
     */
    public function getSettingsSchemaFromDisk(): ?array
    {
        $jsonPath = base_path("themes/{$this->folder_name}/theme.json");

        if (! file_exists($jsonPath)) {
            return null;
        }

        $content = file_get_contents($jsonPath);
        if ($content === false) {
            return null;
        }

        $decoded = json_decode($content, true);
        if (! is_array($decoded) || ! isset($decoded['settings_schema'])) {
            return null;
        }

        return $decoded['settings_schema'];
    }
}
