<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Root redirect to Dashboard
Route::get('/', function () {
    return redirect('/cms/dashboard');
});

// Public Invitation Page (loads the Vue SPA)
Route::get('/wedding/{slug}', [SpaController::class, 'index'])->name('wedding.invitation');

// Guest Authentication Routes (using the template's Inertia authentication system)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

// Authenticated Admin Routes (super-admin only)
Route::middleware(['auth', 'role:super-admin'])->group(function () {

    // Templates CRUD (Admin Write Actions)
    Route::prefix('cms/templates')->group(function () {
        Route::get('/create', [\App\Http\Controllers\Admin\TemplateController::class, 'create'])->name('cms.templates.create');
        Route::post('/', [\App\Http\Controllers\Admin\TemplateController::class, 'store'])->name('cms.templates.store');
        Route::post('/quick-create', [\App\Http\Controllers\Admin\TemplateController::class, 'quickCreate'])->name('cms.templates.quick-create');
        Route::get('/{template}/developer', [\App\Http\Controllers\Admin\TemplateController::class, 'developerStatus'])->name('cms.templates.developer-status');
        Route::put('/{template}/release', [\App\Http\Controllers\Admin\TemplateController::class, 'release'])->name('cms.templates.release');
        Route::get('/{template}/edit', [\App\Http\Controllers\Admin\TemplateController::class, 'edit'])->name('cms.templates.edit');
        Route::put('/{template}', [\App\Http\Controllers\Admin\TemplateController::class, 'update'])->name('cms.templates.update');
        Route::delete('/{template}', [\App\Http\Controllers\Admin\TemplateController::class, 'destroy'])->name('cms.templates.destroy');
    });

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
    // Dashboard (accessible to all — data is filtered server-side by role)
    Route::get('/cms/dashboard', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])->name('cms.dashboard');
    Route::get('/cms/templates', [\App\Http\Controllers\Admin\TemplateController::class, 'index'])->name('cms.templates.index');

    // /cms root redirects to dashboard
    Route::get('/cms', function () {
        return redirect('/cms/dashboard');
    });
});

// Authenticated CMS Wildcard Route (loads the Vue SPA for CMS)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/cms/{any?}', [SpaController::class, 'index'])->where('any', '.*')->name('cms');
});
