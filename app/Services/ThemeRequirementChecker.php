<?php

namespace App\Services;

use App\Models\Theme;
use Composer\Semver\Semver;
use InvalidArgumentException;

class ThemeRequirementChecker
{
    /**
     * Validate declared requirements without throwing.
     *
     * @return array{ok: bool, errors: array<int, string>, warnings: array<int, string>}
     */
    public function check(Theme $theme): array
    {
        $errors = [];
        $warnings = [];

        // Check requires from theme.json (we can read it directly from disk or assume it's in settings)
        $requires = $this->getRequiresFromDisk($theme);

        if (! is_array($requires) || $requires === []) {
            return ['ok' => true, 'errors' => $errors, 'warnings' => $warnings];
        }

        // Check CMS version constraint
        $cmsConstraint = $requires['cms_version'] ?? null;
        if (is_string($cmsConstraint) && $cmsConstraint !== '') {
            $cmsVersion = (string) config('nebula.version', '0.0.0');
            $error = $this->checkSemverSatisfies('NebulaCMS', $cmsVersion, $cmsConstraint);
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        // Check PHP version constraint
        $phpConstraint = $requires['php'] ?? null;
        if (is_string($phpConstraint) && $phpConstraint !== '') {
            $error = $this->checkSemverSatisfies('PHP', PHP_VERSION, $phpConstraint);
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        // Check theme dependencies on plugins
        $depSlugs = $requires['plugins'] ?? [];
        if (is_array($depSlugs) && $depSlugs !== []) {
            foreach ($depSlugs as $slug) {
                if (! is_string($slug) || $slug === '') {
                    $warnings[] = 'Invalid entry in requires.plugins; expected non-empty strings.';
                    continue;
                }
                $other = \App\Models\Plugin::query()->where('slug', $slug)->first();
                if (! $other) {
                    $errors[] = "Required plugin \"{$slug}\" is not installed.";
                    continue;
                }
                if (! $other->is_active) {
                    $errors[] = "Required plugin \"{$slug}\" must be activated before this theme can run.";
                }
            }
        }

        return ['ok' => count($errors) === 0, 'errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Non-throwing version: returns error message or null.
     */
    protected function checkSemverSatisfies(string $label, string $version, string $constraint): ?string
    {
        try {
            $ok = Semver::satisfies($version, $constraint);
        } catch (\Throwable $e) {
            return "Invalid version or constraint for {$label}: ".$e->getMessage();
        }

        if (! $ok) {
            return "{$label} version {$version} does not satisfy constraint \"{$constraint}\".";
        }

        return null;
    }

    /**
     * Read requires block from theme.json
     */
    protected function getRequiresFromDisk(Theme $theme): ?array
    {
        $path = base_path("themes/{$theme->folder_name}/theme.json");

        if (! file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        
        if ($content === false) {
            return [];
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded) || ! isset($decoded['requires'])) {
            return [];
        }

        return $decoded['requires'];
    }
}
