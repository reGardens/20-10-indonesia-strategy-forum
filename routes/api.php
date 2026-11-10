<?php

use App\Http\Controllers\Api\Isf\Cms\AlertController;
use App\Http\Controllers\Api\Isf\Cms\ArticleController;
use App\Http\Controllers\Api\Isf\Cms\BannerController;
use App\Http\Controllers\Api\Isf\Cms\BriefingController;
use App\Http\Controllers\Api\Isf\Cms\CategoryController;
use App\Http\Controllers\Api\Isf\Cms\ChartController;
use App\Http\Controllers\Api\Isf\Cms\InfographicController;
use App\Http\Controllers\Api\Isf\Cms\MediaUploadController;
use App\Http\Controllers\Api\Isf\Cms\SettingController;
use App\Http\Controllers\Api\Isf\Cms\TokohController;
use App\Http\Controllers\Api\Isf\PublicContentController;
use App\Http\Controllers\Auth\IsfCaptchaController;
use App\Http\Controllers\Auth\IsfRegisterController;
use Illuminate\Support\Facades\Route;

// Public ISF content API (read-only, published content only)
Route::middleware(['web'])->prefix('isf')->group(function () {
    Route::get('/beranda', [PublicContentController::class, 'beranda'])->name('api.isf.beranda');
    Route::get('/tokoh/{slug}', [PublicContentController::class, 'tokoh'])->name('api.isf.tokoh');
    Route::get('/infografik', [PublicContentController::class, 'infografik'])->name('api.isf.infografik');
    Route::get('/anggota', [PublicContentController::class, 'anggota'])->name('api.isf.anggota');
    Route::get('/cache-heal', [PublicContentController::class, 'cacheHeal'])->name('api.isf.cache-heal');
    Route::get('/captcha', [IsfCaptchaController::class, 'generate'])->name('api.isf.captcha');
    Route::post('/register', [IsfRegisterController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('api.isf.register');
});

// ISF CMS API (authenticated session + per-module feature access)
Route::middleware(['web', 'auth'])->prefix('cms')->name('api.cms.')->group(function () {
    Route::post('banner/reorder', [BannerController::class, 'reorder'])
        ->middleware('feature:banner')->name('banner.reorder');
    Route::apiResource('banner', BannerController::class)
        ->parameters(['banner' => 'banner'])->middleware('feature:banner');
    Route::post('artikel/{article}/hero', [ArticleController::class, 'setHero'])
        ->middleware('feature:artikel')->name('artikel.hero');
    Route::apiResource('artikel', ArticleController::class)
        ->parameters(['artikel' => 'article'])->middleware('feature:artikel');
    Route::apiResource('infografik', InfographicController::class)
        ->parameters(['infografik' => 'infographic'])->middleware('feature:infografik');
    Route::apiResource('tokoh', TokohController::class)
        ->parameters(['tokoh' => 'tokoh'])->middleware('feature:tokoh');
    Route::post('data-grafik/reorder', [ChartController::class, 'reorder'])
        ->middleware('feature:data_grafik')->name('data-grafik.reorder');
    Route::apiResource('data-grafik', ChartController::class)
        ->parameters(['data-grafik' => 'chart'])->middleware('feature:data_grafik');
    Route::apiResource('alert', AlertController::class)
        ->parameters(['alert' => 'alert'])->middleware('feature:alert');
    Route::apiResource('email-briefing', BriefingController::class)
        ->parameters(['email-briefing' => 'briefing'])->middleware('feature:email_briefing');
    Route::apiResource('kategori', CategoryController::class)
        ->parameters(['kategori' => 'category']);

    Route::get('/pengaturan-halaman', [SettingController::class, 'show'])
        ->middleware('feature:pengaturan_halaman')->name('pengaturan-halaman.show');
    Route::put('/pengaturan-halaman', [SettingController::class, 'update'])
        ->middleware('feature:pengaturan_halaman')->name('pengaturan-halaman.update');

    Route::get('/running-text', [SettingController::class, 'getRunningText'])->name('running-text.index');
    Route::post('/running-text', [SettingController::class, 'createRunningText'])->name('running-text.store');
    Route::get('/running-text/{id}', [SettingController::class, 'showRunningText'])->whereNumber('id')->name('running-text.show');
    Route::put('/running-text/{id}', [SettingController::class, 'updateRunningTextItem'])->whereNumber('id')->name('running-text.update-item');
    Route::delete('/running-text/{id}', [SettingController::class, 'deleteRunningTextItem'])->whereNumber('id')->name('running-text.destroy');
    Route::put('/running-text', [SettingController::class, 'updateRunningText'])->name('running-text.update');

    Route::get('/media-sorotan', [SettingController::class, 'getMediaSorotan'])->name('media-sorotan.index');
    Route::post('/media-sorotan', [SettingController::class, 'createMediaSorotan'])->name('media-sorotan.store');
    Route::get('/media-sorotan/{id}', [SettingController::class, 'showMediaSorotan'])->whereNumber('id')->name('media-sorotan.show');
    Route::put('/media-sorotan/{id}', [SettingController::class, 'updateMediaSorotanItem'])->whereNumber('id')->name('media-sorotan.update-item');
    Route::delete('/media-sorotan/{id}', [SettingController::class, 'deleteMediaSorotanItem'])->whereNumber('id')->name('media-sorotan.destroy');
    Route::put('/media-sorotan', [SettingController::class, 'updateMediaSorotan'])->name('media-sorotan.update');

    Route::get('/hot-news/article-options', [SettingController::class, 'getHotNewsArticleOptions'])->name('hot-news.article-options');
    Route::post('/hot-news/sync-articles', [SettingController::class, 'syncHotNewsArticles'])->name('hot-news.sync-articles');
    Route::get('/hot-news', [SettingController::class, 'getHotNews'])->name('hot-news.index');
    Route::post('/hot-news', [SettingController::class, 'createHotNews'])->name('hot-news.store');
    Route::get('/hot-news/{id}', [SettingController::class, 'showHotNews'])->whereNumber('id')->name('hot-news.show');
    Route::put('/hot-news/{id}', [SettingController::class, 'updateHotNewsItem'])->whereNumber('id')->name('hot-news.update-item');
    Route::delete('/hot-news/{id}', [SettingController::class, 'deleteHotNewsItem'])->whereNumber('id')->name('hot-news.destroy');
    Route::put('/hot-news', [SettingController::class, 'updateHotNews'])->name('hot-news.update');

    // Media image upload for CMS modules
    Route::post('/media/upload', [MediaUploadController::class, 'upload'])->name('media.upload');


    // Category options for dropdown pickers across CMS modules
    Route::get('/options/categories', [CategoryController::class, 'options'])->name('options.categories');
});
