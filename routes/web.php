<?php

use App\Http\Controllers\HestiaServerController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::post('/webhook/{site}/{token}', [SiteController::class, 'deployWebhook'])->name('webhook.deploy')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/sites', [SiteController::class, 'index'])->name('sites.index');
    Route::get('/sites/create', [SiteController::class, 'create'])->name('sites.create');
    Route::get('/sites/attachable-users', [SiteController::class, 'attachableUsers'])->name('sites.attachable-users');
    Route::get('/sites/branches', [SiteController::class, 'listBranches'])->name('sites.branches');
    Route::post('/sites', [SiteController::class, 'store'])->name('sites.store');
    Route::get('/sites/{site}', [SiteController::class, 'show'])->name('sites.show');
    Route::get('/sites/{site}/logs', [SiteController::class, 'getLogs'])->name('sites.logs');
    Route::post('/sites/{site}/renew-ssl', [SiteController::class, 'renewSSL'])->name('sites.renew-ssl')->middleware('role:admin');
    Route::post('/sites/{site}/change-branch', [SiteController::class, 'changeBranch'])->name('sites.change-branch')->middleware('role:admin');
    Route::post('/sites/{site}/generate-token', [SiteController::class, 'generateToken'])->name('sites.generate-token');
    Route::post('/sites/{site}/change-status', [SiteController::class, 'changeStatus'])->name('sites.change-status');
    Route::post('/sites/{site}/retry', [SiteController::class, 'retry'])->name('sites.retry');
    Route::post('/sites/{site}/backups/database', [SiteController::class, 'backupDatabase'])->name('sites.backups.database');
    Route::post('/sites/{site}/backups/images', [SiteController::class, 'backupImages'])->name('sites.backups.images');
    Route::get('/sites/{site}/backups', [SiteController::class, 'backups'])->name('sites.backups.index');
    Route::get('/sites/{site}/backups/{backup}/download', [SiteController::class, 'downloadBackup'])->name('sites.backups.download');
    Route::delete('/sites/{site}/backups/{backup}', [SiteController::class, 'destroyBackup'])->name('sites.backups.destroy');
    Route::get('/sites/{site}/server-log', [SiteController::class, 'serverLog'])->name('sites.server-log');
    Route::delete('/sites/{site}', [SiteController::class, 'destroy'])->name('sites.destroy')->middleware('role:admin');

    Route::get('/ssh-key', [SiteController::class, 'getSSHKey'])->name('ssh-key.get');

    Route::middleware(['role:admin'])->group(function () {
        Route::get('/servers', [HestiaServerController::class, 'index'])->name('servers.index');
        Route::get('/servers/create', [HestiaServerController::class, 'create'])->name('servers.create');
        Route::post('/servers', [HestiaServerController::class, 'store'])->name('servers.store');
        Route::get('/servers/{server}/edit', [HestiaServerController::class, 'edit'])->name('servers.edit');
        Route::put('/servers/{server}', [HestiaServerController::class, 'update'])->name('servers.update');
        Route::delete('/servers/{server}', [HestiaServerController::class, 'destroy'])->name('servers.destroy');
    });
});
