<?php

use App\Http\Controllers\InstallerController;
use App\Http\Middleware\ForceFileSession;
use App\Http\Middleware\SetInstallerLocale;
use Illuminate\Support\Facades\Route;

Route::prefix('install')
    ->middleware([ForceFileSession::class, SetInstallerLocale::class])
    ->group(function () {
        Route::get('/lang/{locale}', [InstallerController::class, 'setLocale'])
            ->where('locale', 'en|id')
            ->name('installer.lang');
        Route::get('/', [InstallerController::class, 'welcome'])->name('installer.welcome');
        Route::get('/database', [InstallerController::class, 'database'])->name('installer.database');
        Route::post('/database', [InstallerController::class, 'saveDatabase'])->name('installer.database.save');
        Route::post('/database/test', [InstallerController::class, 'testDatabase'])->name('installer.database.test');
        Route::get('/site', [InstallerController::class, 'site'])->name('installer.site');
        Route::post('/site', [InstallerController::class, 'saveSite'])->name('installer.site.save');
        Route::get('/account', [InstallerController::class, 'account'])->name('installer.account');
        Route::post('/account', [InstallerController::class, 'saveAccount'])->name('installer.account.save');
        Route::get('/installing', [InstallerController::class, 'installing'])->name('installer.installing');
        Route::post('/run', [InstallerController::class, 'run'])->name('installer.run');
        Route::get('/done', [InstallerController::class, 'done'])->name('installer.done');
    });
