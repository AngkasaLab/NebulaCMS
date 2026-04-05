<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Services\UpdateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class UpdateController extends Controller
{
    /**
     * How long (seconds) a "check for updates" result may be used to authorize apply().
     */
    private const PENDING_UPDATE_TTL = 3600;

    public function __construct()
    {
        $this->middleware('permission:manage settings');
    }

    /**
     * Show the update settings page.
     */
    public function index(UpdateService $service)
    {
        return Inertia::render('Admin/Settings/Update', [
            'currentVersion' => config('nebula.version'),
            'backups' => $service->getBackups(),
        ]);
    }

    /**
     * Check GitHub for the latest release.
     */
    public function check(UpdateService $service)
    {
        $result = $service->checkForUpdate();

        if (($result['available'] ?? false) && ! empty($result['download_url'])) {
            session([
                'pending_update' => [
                    'download_url' => $result['download_url'],
                    'latest' => $result['latest'] ?? '',
                    'checked_at' => now()->timestamp,
                ],
            ]);
        } else {
            session()->forget('pending_update');
        }

        return back()->with('updateCheck', $result);
    }

    /**
     * Create backup, download, and apply the update.
     */
    public function apply(Request $request, UpdateService $service)
    {
        $request->validate([
            'download_url' => 'required|url',
        ]);

        $pending = session('pending_update');
        $downloadUrl = $request->input('download_url');

        if (! is_array($pending)
            || ($pending['download_url'] ?? '') !== $downloadUrl
            || $downloadUrl === ''
        ) {
            return back()->with('error', 'Invalid or expired update session. Please check for updates again.');
        }

        if (now()->timestamp - (int) ($pending['checked_at'] ?? 0) > self::PENDING_UPDATE_TTL) {
            session()->forget('pending_update');

            return back()->with('error', 'Update session expired. Please check for updates again.');
        }

        // Step 1: Create backup
        try {
            $service->createBackup();
        } catch (\Exception $e) {
            return back()->with('error', 'Backup failed: '.$e->getMessage());
        }

        // Step 2: Download and apply (URL must match the last successful check in this session)
        try {
            $result = $service->applyUpdate($downloadUrl);
        } catch (\Throwable $e) {
            Log::error('System update apply crashed: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Update failed unexpectedly. Check storage/logs/laravel.log. '.$e->getMessage());
        }

        if ($result['success']) {
            session()->forget('pending_update');
            $service->forgetCachedUpdateAvailability();

            return back()->with('success', $result['message'].' (from '.$result['from'].' to '.$result['to'].')');
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Rollback to a previous backup.
     */
    public function rollback(Request $request, UpdateService $service)
    {
        $request->validate([
            'backup_path' => 'required|string',
        ]);

        $backupPath = $request->input('backup_path');

        // Security: ensure the path is within the backups directory
        $backupsDir = realpath(storage_path('app/backups'));
        $resolvedPath = realpath($backupPath);

        if (! $resolvedPath || ! str_starts_with($resolvedPath, $backupsDir)) {
            return back()->with('error', 'Invalid backup path.');
        }

        $result = $service->rollback($resolvedPath);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }
}
