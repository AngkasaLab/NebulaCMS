<?php

use Illuminate\Support\Facades\Route;
use StarterInertiaPrebuilt\Http\Controllers\StarterAdminController;

Route::middleware(['auth', 'verified'])->prefix('admin/starter-inertia-prebuilt')->group(function () {
    Route::get('/', [StarterAdminController::class, 'index'])->name('starter-inertia-prebuilt.admin.index');
});
