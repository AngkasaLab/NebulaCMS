<?php

namespace App\Services;

use InvalidArgumentException;
use ZipArchive;

class SecureZipInspector
{
    /**
     * Validate archive before extraction (zip slip / zip bomb limits).
     *
     * @throws InvalidArgumentException
     */
    public function assertSafeArchive(string $zipPath): void
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new InvalidArgumentException('Cannot open ZIP archive.');
        }

        try {
            $maxEntries = config('upload_security.zip_max_entries', 2000);
            if ($zip->numFiles > $maxEntries) {
                throw new InvalidArgumentException("ZIP contains too many files (max {$maxEntries}).");
            }

            $maxUncompressed = (int) config('upload_security.zip_max_uncompressed_kb', 512000) * 1024;
            $totalUncompressed = 0;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if ($name === false) {
                    throw new InvalidArgumentException('Invalid ZIP entry name.');
                }

                $this->assertSafeEntryName($name);

                $stat = $zip->statIndex($i);
                if ($stat !== false && isset($stat['size'])) {
                    $totalUncompressed += $stat['size'];
                }
            }

            if ($totalUncompressed > $maxUncompressed) {
                throw new InvalidArgumentException('ZIP uncompressed size exceeds allowed limit.');
            }
        } finally {
            $zip->close();
        }
    }

    protected function assertSafeEntryName(string $name): void
    {
        if ($name === '') {
            throw new InvalidArgumentException('ZIP contains an empty path.');
        }

        if (str_contains($name, '..') || str_contains($name, "\0")) {
            throw new InvalidArgumentException('ZIP contains invalid path segments.');
        }

        if (str_starts_with($name, '/') || preg_match('#^[a-zA-Z]:[\\\\/]#', $name) === 1) {
            throw new InvalidArgumentException('ZIP contains absolute paths.');
        }
    }

    /**
     * Enforce allowed extensions for plugin ZIP uploads (in addition to assertSafeArchive).
     *
     * @throws InvalidArgumentException
     */
    public function assertPluginZipExtensions(string $zipPath): void
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new InvalidArgumentException('Cannot open ZIP archive.');
        }

        $allowed = array_map('strtolower', config('upload_security.plugin_zip_allowed_extensions', []));
        $allowedNames = array_map('strtolower', config('upload_security.plugin_zip_allowed_extensionless_names', []));

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if ($name === false) {
                    throw new InvalidArgumentException('Invalid ZIP entry name.');
                }

                if (str_starts_with($name, '__MACOSX/') || str_ends_with($name, '.DS_Store')) {
                    continue;
                }

                $base = basename($name);
                if ($base === '' || str_ends_with($name, '/')) {
                    continue;
                }

                $lowerBase = strtolower($base);

                if (str_contains($lowerBase, '..')) {
                    throw new InvalidArgumentException('ZIP contains invalid path segments.');
                }

                if (! str_contains($base, '.')) {
                    if (in_array($lowerBase, $allowedNames, true)) {
                        continue;
                    }

                    throw new InvalidArgumentException("ZIP entry \"{$name}\" has no allowed extension.");
                }

                if (str_ends_with($lowerBase, '.blade.php')) {
                    $ext = 'blade.php';
                } else {
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                }

                if ($ext === '' || ! in_array($ext, $allowed, true)) {
                    throw new InvalidArgumentException("ZIP entry \"{$name}\" uses a disallowed file type.");
                }
            }
        } finally {
            $zip->close();
        }
    }
}
