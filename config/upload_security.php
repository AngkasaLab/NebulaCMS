<?php

$allowSvg = filter_var(env('MEDIA_ALLOW_SVG', false), FILTER_VALIDATE_BOOLEAN);

return [

    /*
    |--------------------------------------------------------------------------
    | Media library uploads
    |--------------------------------------------------------------------------
    */

    'media_max_kb' => (int) env('MEDIA_UPLOAD_MAX_KB', 10240),

    'media_allow_svg' => $allowSvg,

    /*
     * Allowed MIME types for the media library (strict). SVG omitted unless media_allow_svg.
     */
    'media_allowed_mimetypes' => array_values(array_filter(array_merge(
        [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/avif',
            'application/pdf',
            'video/mp4',
            'video/webm',
            'audio/mpeg',
            'audio/wav',
            'text/plain',
            'application/zip',
        ],
        $allowSvg ? ['image/svg+xml'] : []
    ))),

    'media_blocked_extensions' => [
        'php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'phps',
        'exe', 'bat', 'cmd', 'com', 'msi',
        'sh', 'bash', 'zsh',
        'dll', 'so', 'dylib',
        'jsp', 'asp', 'aspx', 'cgi', 'pl', 'py', 'rb',
        'htaccess', 'htpasswd',
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme & plugin ZIP uploads
    |--------------------------------------------------------------------------
    */

    'zip_max_kb' => (int) env('EXTENSION_ZIP_MAX_KB', 10240),

    'zip_max_entries' => (int) env('EXTENSION_ZIP_MAX_ENTRIES', 2000),

    'zip_max_uncompressed_kb' => (int) env('EXTENSION_ZIP_MAX_UNCOMPRESSED_KB', 512000),

    /*
    |--------------------------------------------------------------------------
    | Plugin ZIP: allowed file extensions (after safe path checks)
    |--------------------------------------------------------------------------
    |
    | Blocks unexpected binaries inside plugin archives. Entries under __MACOSX/
    | or ending in .DS_Store are skipped.
    |
    */

    'plugin_zip_allowed_extensions' => [
        'php', 'json', 'md', 'txt', 'css', 'js', 'mjs', 'cjs', 'ts', 'tsx',
        'blade.php', 'svg', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'woff', 'woff2', 'ttf', 'eot',
        'map', 'xml', 'yml', 'yaml', 'dist', 'lock', 'html', 'htm', 'vue',
    ],

    'plugin_zip_allowed_extensionless_names' => [
        'LICENSE', 'COPYING', 'README', 'CHANGELOG', 'Makefile',
    ],

    /*
    |--------------------------------------------------------------------------
    | Optional malware scanning (ClamAV)
    |--------------------------------------------------------------------------
    |
    | Enable on servers where `clamscan` or `clamdscan` is installed.
    | Not required for every installation — configure via .env only.
    |
    */

    'scan_enabled' => filter_var(env('UPLOAD_SCAN_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    'scan_driver' => env('UPLOAD_SCAN_DRIVER', 'clamav'),

    /*
     * Full path to clamscan or clamdscan binary.
     */
    'clamav_binary' => env('CLAMAV_BINARY', 'clamscan'),

    /*
     * If true, a ClamAV error (e.g. daemon down) blocks the upload.
     * If false, failed scans are logged and upload proceeds.
     */
    'scan_fail_on_scanner_error' => filter_var(env('UPLOAD_SCAN_FAIL_ON_ERROR', false), FILTER_VALIDATE_BOOLEAN),

];
