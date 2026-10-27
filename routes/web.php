<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Cms\IsfPageController;
use App\Http\Controllers\Isf\BriefingPreviewController;
use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ISF (Indonesia Strategy Forum) public pages — Vue SPA, content from the public API (/api/isf/*)
Route::get('/', [SpaController::class, 'index'])->name('isf.home');
Route::get('/beranda', [SpaController::class, 'index'])->name('isf.beranda');
Route::get('/infografik', [SpaController::class, 'index'])->name('isf.infografik');
Route::get('/tokoh/{slug}', [SpaController::class, 'index'])->name('isf.tokoh.show');
Route::get('/isu/{slug}', [SpaController::class, 'index'])->name('isf.isu.show');
// ISF member portal (B2B) — NOT protected by auth yet (UI preview only)
Route::get('/anggota', [SpaController::class, 'index'])->name('isf.anggota.dashboard');
Route::get('/anggota/briefing/preview', [BriefingPreviewController::class, 'show'])->name('isf.anggota.briefing.preview');

// Guest Authentication Routes (using the template's Inertia authentication system)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

// Authenticated Admin Routes (super-admin only)
Route::middleware(['auth', 'role:super-admin'])->group(function () {

    // Roles CRUD
    Route::prefix('cms/roles')->group(function () {
        Route::get('/', [\App\Http\Controllers\RoleController::class, 'index'])->name('cms.roles.index');
        Route::get('/create', [\App\Http\Controllers\RoleController::class, 'create'])->name('cms.roles.create');
        Route::post('/', [\App\Http\Controllers\RoleController::class, 'store'])->name('cms.roles.store');
        Route::get('/{role}/edit', [\App\Http\Controllers\RoleController::class, 'edit'])->name('cms.roles.edit');
        Route::put('/{role}', [\App\Http\Controllers\RoleController::class, 'update'])->name('cms.roles.update');
        Route::delete('/{role}', [\App\Http\Controllers\RoleController::class, 'destroy'])->name('cms.roles.destroy');
    });

    // Users CRUD
    Route::prefix('cms/users')->group(function () {
        Route::get('/', [\App\Http\Controllers\UserController::class, 'index'])->name('cms.users.index');
        Route::get('/create', [\App\Http\Controllers\UserController::class, 'create'])->name('cms.users.create');
        Route::post('/', [\App\Http\Controllers\UserController::class, 'store'])->name('cms.users.store');
        Route::get('/{user}/edit', [\App\Http\Controllers\UserController::class, 'edit'])->name('cms.users.edit');
        Route::put('/{user}', [\App\Http\Controllers\UserController::class, 'update'])->name('cms.users.update');
        Route::delete('/{user}', [\App\Http\Controllers\UserController::class, 'destroy'])->name('cms.users.destroy');
    });

    // ACL (Access Control List)
    Route::prefix('cms/acl')->group(function () {
        Route::get('/', [\App\Http\Controllers\AclController::class, 'index'])->name('cms.acl.index');
        Route::post('/{user}/toggle', [\App\Http\Controllers\AclController::class, 'toggle'])->name('cms.acl.toggle');
    });
});

// Authenticated General Routes (Accessible by all roles)
Route::middleware('auth')->group(function () {
    // Dashboard (accessible to all — data is filtered server-side by feature access)
    Route::get('/cms/dashboard', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])->name('cms.dashboard');

    // /cms root redirects to dashboard
    Route::get('/cms', function () {
        return redirect('/cms/dashboard');
    });

    // ISF content modules (Inertia page shells; data via /api/cms/*), gated per feature (ACL)
    $isfModules = [
        'banner' => 'banner',
        'artikel' => 'artikel',
        'infografik' => 'infografik',
        'tokoh' => 'tokoh',
        'isu' => 'isu',
        'data-grafik' => 'data_grafik',
        'alert' => 'alert',
        'email-briefing' => 'email_briefing',
        'kategori' => 'kategori',
    ];
    foreach ($isfModules as $segment => $feature) {
        Route::middleware("feature:{$feature}")->prefix("cms/{$segment}")->group(function () use ($segment) {
            Route::get('/', [IsfPageController::class, 'index'])->defaults('module', $segment)->name("cms.isf.{$segment}.index");
            Route::get('/create', [IsfPageController::class, 'create'])->defaults('module', $segment)->name("cms.isf.{$segment}.create");
            Route::get('/{id}/edit', [IsfPageController::class, 'edit'])->defaults('module', $segment)->whereNumber('id')->name("cms.isf.{$segment}.edit");
        });
    }

    Route::get('/cms/email-briefing/{briefing}/preview', [BriefingPreviewController::class, 'cmsPreview'])
        ->middleware('feature:email_briefing')->whereNumber('briefing')->name('cms.isf.email-briefing.preview');

    Route::prefix('cms/running-text')->group(function () {
        Route::get('/', [IsfPageController::class, 'index'])->defaults('module', 'running-text')->name('cms.isf.running-text.index');
        Route::get('/create', [IsfPageController::class, 'create'])->defaults('module', 'running-text')->name('cms.isf.running-text.create');
        Route::get('/{id}/edit', [IsfPageController::class, 'edit'])->defaults('module', 'running-text')->whereNumber('id')->name('cms.isf.running-text.edit');
    });

    Route::prefix('cms/media-sorotan')->group(function () {
        Route::get('/', [IsfPageController::class, 'index'])->defaults('module', 'media-sorotan')->name('cms.isf.media-sorotan.index');
        Route::get('/create', [IsfPageController::class, 'create'])->defaults('module', 'media-sorotan')->name('cms.isf.media-sorotan.create');
        Route::get('/{id}/edit', [IsfPageController::class, 'edit'])->defaults('module', 'media-sorotan')->whereNumber('id')->name('cms.isf.media-sorotan.edit');
    });

    Route::get('/cms/pengaturan-halaman', fn () => redirect()->route('cms.isf.running-text.index'))
        ->name('cms.isf.pengaturan-halaman');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
