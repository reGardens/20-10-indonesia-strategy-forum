<?php

use App\Http\Controllers\Api\Isf\Cms\AlertController;
use App\Http\Controllers\Api\Isf\Cms\ArticleController;
use App\Http\Controllers\Api\Isf\Cms\BannerController;
use App\Http\Controllers\Api\Isf\Cms\BriefingController;
use App\Http\Controllers\Api\Isf\Cms\ChartController;
use App\Http\Controllers\Api\Isf\Cms\InfographicController;
use App\Http\Controllers\Api\Isf\Cms\IsuController;
use App\Http\Controllers\Api\Isf\Cms\SettingController;
use App\Http\Controllers\Api\Isf\Cms\TokohController;
use App\Http\Controllers\Api\Isf\PublicContentController;
use Illuminate\Support\Facades\Route;

// Public ISF content API (read-only, published content only)
Route::middleware(['web'])->prefix('isf')->group(function () {
    Route::get('/beranda', [PublicContentController::class, 'beranda'])->name('api.isf.beranda');
    Route::get('/tokoh/{slug}', [PublicContentController::class, 'tokoh'])->name('api.isf.tokoh');
    Route::get('/isu/{slug}', [PublicContentController::class, 'isu'])->name('api.isf.isu');
    Route::get('/anggota', [PublicContentController::class, 'anggota'])->name('api.isf.anggota');
});

// ISF CMS API (authenticated session + per-module feature access)
Route::middleware(['web', 'auth'])->prefix('cms')->name('api.cms.')->group(function () {
    Route::apiResource('banner', BannerController::class)
        ->parameters(['banner' => 'banner'])->middleware('feature:banner');
    Route::apiResource('artikel', ArticleController::class)
        ->parameters(['artikel' => 'article'])->middleware('feature:artikel');
    Route::apiResource('infografik', InfographicController::class)
        ->parameters(['infografik' => 'infographic'])->middleware('feature:infografik');
    Route::apiResource('tokoh', TokohController::class)
        ->parameters(['tokoh' => 'tokoh'])->middleware('feature:tokoh');
    Route::apiResource('isu', IsuController::class)
        ->parameters(['isu' => 'isu'])->middleware('feature:isu');
    Route::apiResource('data-grafik', ChartController::class)
        ->parameters(['data-grafik' => 'chart'])->middleware('feature:data_grafik');
    Route::apiResource('alert', AlertController::class)
        ->parameters(['alert' => 'alert'])->middleware('feature:alert');
    Route::apiResource('email-briefing', BriefingController::class)
        ->parameters(['email-briefing' => 'briefing'])->middleware('feature:email_briefing');

    Route::get('/pengaturan-halaman', [SettingController::class, 'show'])
        ->middleware('feature:pengaturan_halaman')->name('pengaturan-halaman.show');
    Route::put('/pengaturan-halaman', [SettingController::class, 'update'])
        ->middleware('feature:pengaturan_halaman')->name('pengaturan-halaman.update');

    // Chart picker for the isu "02 Visual Kunci" field
    Route::get('/options/charts', [ChartController::class, 'options'])
        ->middleware('feature:isu')->name('options.charts');
});
