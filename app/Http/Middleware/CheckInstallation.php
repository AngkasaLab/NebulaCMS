<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInstallation
{
    public function handle(Request $request, Closure $next): Response
    {
        $isInstalled = file_exists(storage_path('installed.lock'));
        $isInstallerRoute = $request->is('install') || $request->is('install/*');

        if (! $isInstalled && ! $isInstallerRoute) {
            // Bootstrap minimum config so the redirect itself can work without APP_KEY
            $this->bootstrapInstallerEnvironment();

            return redirect()->route('installer.welcome');
        }

        if ($isInstalled && $isInstallerRoute && ! $request->is('install/done')) {
            return redirect('/');
        }

        if ($isInstallerRoute) {
            $this->bootstrapInstallerEnvironment();
        }

        return $next($request);
    }

    /**
     * Ensure APP_KEY and session are usable before .env is fully configured.
     *
     * - Generates a stable temporary key stored in a temp file so encrypted
     *   session cookies remain valid across requests during installation.
     * - Forces the file session driver so no database connection is needed.
     * - Disables session encryption so the temp key only needs to sign cookies,
     *   not decrypt file-stored session data.
     */
    private function bootstrapInstallerEnvironment(): void
    {
        if (empty(config('app.key'))) {
            $tempKeyFile = storage_path('framework/installer_key.tmp');

            if (file_exists($tempKeyFile)) {
                $tempKey = trim(file_get_contents($tempKeyFile));
            } else {
                $tempKey = 'base64:'.base64_encode(random_bytes(32));
                @file_put_contents($tempKeyFile, $tempKey);
            }

            config(['app.key' => $tempKey]);
        }

        config([
            'session.driver' => 'file',
            'session.encrypt' => false,
            'session.files' => storage_path('framework/sessions'),
        ]);
    }
}
