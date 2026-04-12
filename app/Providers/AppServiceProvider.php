<?php

namespace App\Providers;

use App\Contracts\UploadScanner;
use App\Services\PluginRequirementChecker;
use App\Services\PluginRouteRegistrar;
use App\Services\UploadScan\ClamAvUploadScanner;
use App\Services\UploadScan\NullUploadScanner;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use PragmaRX\Google2FA\Google2FA;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Google2FA::class, fn () => new Google2FA);

        $this->app->singleton(UploadScanner::class, function ($app) {
            if (! config('upload_security.scan_enabled')) {
                return new NullUploadScanner;
            }

            if (config('upload_security.scan_driver') === 'clamav') {
                return new ClamAvUploadScanner(
                    (string) config('upload_security.clamav_binary'),
                    (bool) config('upload_security.scan_fail_on_scanner_error'),
                );
            }

            return new NullUploadScanner;
        });

        $this->app->singleton(PluginRequirementChecker::class, fn () => new PluginRequirementChecker);

        $this->app->singleton(PluginRouteRegistrar::class, fn () => new PluginRouteRegistrar);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('Super Admin')) {
                return true;
            }
        });

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user) {
                activity()
                    ->causedBy($event->user)
                    ->useLog('auth')
                    ->withProperties(['ip' => request()->ip()])
                    ->log('login');
            }
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user) {
                activity()
                    ->causedBy($event->user)
                    ->useLog('auth')
                    ->withProperties(['ip' => request()->ip()])
                    ->log('logout');
            }
        });
    }
}
