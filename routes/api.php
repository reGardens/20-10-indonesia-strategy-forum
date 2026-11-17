<?php

use App\Http\Controllers\Api\Isf\Cms\AdController;
use App\Http\Controllers\Api\Isf\Cms\AlertController;
use App\Http\Controllers\Api\Isf\Cms\ArticleController;
use App\Http\Controllers\Api\Isf\Cms\BannerController;
use App\Http\Controllers\Api\Isf\Cms\BriefingController;
use App\Http\Controllers\Api\Isf\Cms\CaseTimelineController;
use App\Http\Controllers\Api\Isf\Cms\CategoryController;
use App\Http\Controllers\Api\Isf\Cms\ChartController;
use App\Http\Controllers\Api\Isf\Cms\DatabasePeraturanController;
use App\Http\Controllers\Api\Isf\Cms\InfographicController;
use App\Http\Controllers\Api\Isf\Cms\LegalGlossaryController;
use App\Http\Controllers\Api\Isf\Cms\LegalTermController;
use App\Http\Controllers\Api\Isf\Cms\MediaUploadController;
use App\Http\Controllers\Api\Isf\Cms\ParliamentSeatController;
use App\Http\Controllers\Api\Isf\Cms\PolicyBriefController;
use App\Http\Controllers\Api\Isf\Cms\PolicyMonitoringController;
use App\Http\Controllers\Api\Isf\Cms\PolicySectorController;
use App\Http\Controllers\Api\Isf\Cms\ReaderPollController;
use App\Http\Controllers\Api\Isf\Cms\PublicQuoteController;
use App\Http\Controllers\Api\Isf\Cms\LetterController;
use App\Http\Controllers\Api\Isf\Cms\SurveyController;
use App\Http\Controllers\Api\Isf\Cms\SettingController;
use App\Http\Controllers\Api\Isf\Cms\TokohController;
use App\Http\Controllers\Api\Isf\PublicAdController;
use App\Http\Controllers\Api\Isf\PublicContentController;
use App\Http\Controllers\Api\Isf\SessionController;
use App\Http\Controllers\Api\Isf\TokohReviewController;
use App\Http\Controllers\Api\Isf\WhatsAppVerificationController;
use App\Http\Controllers\Auth\IsfCaptchaController;
use App\Http\Controllers\Auth\IsfRegisterController;
use Illuminate\Support\Facades\Route;

// Public ISF content API (read-only, published content only)
Route::middleware(['web'])->prefix('isf')->group(function () {
    Route::get('/beranda', [PublicContentController::class, 'beranda'])->name('api.isf.beranda');
    // Iklan Beranda (tabel isf_ads): spanduk acak berbobot / antrean popup + statistik tayangan & klik.
    Route::get('/ads', [PublicAdController::class, 'index'])->name('api.isf.ads');
    Route::middleware('throttle:120,1')->group(function () {
        Route::post('/ads/{id}/impression', [PublicAdController::class, 'impression'])->whereNumber('id')->name('api.isf.ads.impression');
        Route::post('/ads/{id}/click', [PublicAdController::class, 'click'])->whereNumber('id')->name('api.isf.ads.click');
    });
    // Status login untuk header publik (nama & inisial saja, no-store).
    Route::get('/me', [SessionController::class, 'me'])->name('api.isf.me');
    // Nomor WhatsApp Aktif milik user login + verifikasi OTP (Issue #42).
    Route::middleware(['auth', 'throttle:10,1'])->prefix('/me/whatsapp')->group(function () {
        Route::put('/', [WhatsAppVerificationController::class, 'update'])->name('api.isf.me.whatsapp.update');
        Route::post('/kirim-kode', [WhatsAppVerificationController::class, 'resend'])->name('api.isf.me.whatsapp.resend');
        Route::post('/verifikasi', [WhatsAppVerificationController::class, 'verify'])->name('api.isf.me.whatsapp.verify');
    });
    Route::get('/search', [PublicContentController::class, 'search'])->name('api.isf.search');
    Route::get('/tokoh/{slug}', [PublicContentController::class, 'tokoh'])->name('api.isf.tokoh');
    // Indeks Tokoh (rating & ulasan): baca publik, kirim wajib login akun ISF.
    Route::get('/tokoh/{slug}/ulasan', [TokohReviewController::class, 'index'])->name('api.isf.tokoh.ulasan');
    Route::post('/tokoh/{slug}/ulasan', [TokohReviewController::class, 'store'])
        ->middleware(['auth', 'throttle:10,1'])
        ->name('api.isf.tokoh.ulasan.store');

    // Rubrik Sosok (/sosok)
    Route::get('/sosok', [PublicContentController::class, 'sosok'])->name('api.isf.sosok');
    Route::get('/sosok/{slug}', [PublicContentController::class, 'sosokDetail'])->name('api.isf.sosok.show');
    Route::get('/sosok/{slug}/ulasan', [TokohReviewController::class, 'index'])->name('api.isf.sosok.ulasan');
    Route::post('/sosok/{slug}/ulasan', [TokohReviewController::class, 'store'])
        ->middleware(['auth', 'throttle:10,1'])
        ->name('api.isf.sosok.ulasan.store');
    Route::get('/media/{id}', [PublicContentController::class, 'mediaDetail'])->name('api.isf.media');
    Route::get('/artikel/{slug}', [PublicContentController::class, 'artikel'])->name('api.isf.artikel');
    Route::get('/politik', [PublicContentController::class, 'politik'])->name('api.isf.politik');
    Route::get('/peta-kursi', [PublicContentController::class, 'petaKursi'])->name('api.isf.peta-kursi');
    Route::get('/hukum', [PublicContentController::class, 'hukum'])->name('api.isf.hukum');
    Route::get('/glosarium-hukum', [PublicContentController::class, 'glosariumHukum'])->name('api.isf.glosarium-hukum');
    Route::get('/timeline-kasus', [PublicContentController::class, 'timelineKasus'])->name('api.isf.timeline-kasus');
    Route::get('/kebijakan-publik', [PublicContentController::class, 'kebijakanPublik'])->name('api.isf.kebijakan-publik');
    Route::get('/policy-brief', [PublicContentController::class, 'policyBrief'])->name('api.isf.policy-brief');
    Route::get('/monitoring-kebijakan', [PublicContentController::class, 'monitoringKebijakan'])->name('api.isf.monitoring-kebijakan');
    Route::get('/opini-publik', [PublicContentController::class, 'opiniPublik'])->name('api.isf.opini-publik');
    Route::get('/reader-poll', [PublicContentController::class, 'readerPoll'])->name('api.isf.reader-poll');
    Route::post('/reader-poll/{id}/vote', [PublicContentController::class, 'voteReaderPoll'])->middleware('throttle:10,1')->name('api.isf.reader-poll.vote');
    Route::get('/kutipan-publik', [PublicContentController::class, 'kutipanPublik'])->name('api.isf.kutipan-publik');
    Route::get('/surat-pembaca', [PublicContentController::class, 'suratPembaca'])->name('api.isf.surat-pembaca');
    Route::post('/surat-pembaca', [PublicContentController::class, 'submitSuratPembaca'])->middleware('throttle:6,1')->name('api.isf.surat-pembaca.submit');
    Route::get('/survei', [PublicContentController::class, 'survei'])->name('api.isf.survei');
    Route::get('/survei/{slug}', [PublicContentController::class, 'surveiDetail'])->name('api.isf.survei.show');
    Route::get('/kategori/{kategori}', [PublicContentController::class, 'kategori'])->name('api.isf.kategori');
    Route::get('/infografik', [PublicContentController::class, 'infografik'])->name('api.isf.infografik');
    Route::get('/infografik/{slug}', [PublicContentController::class, 'infografikDetail'])->name('api.isf.infografik.show');
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
    Route::post('kursi-parlemen/reorder', [ParliamentSeatController::class, 'reorder'])
        ->middleware('feature:kursi_parlemen')->name('kursi-parlemen.reorder');
    Route::apiResource('kursi-parlemen', ParliamentSeatController::class)
        ->parameters(['kursi-parlemen' => 'parliamentSeat'])->middleware('feature:kursi_parlemen');
    Route::apiResource('glosarium-hukum', LegalGlossaryController::class)
        ->parameters(['glosarium-hukum' => 'legalGlossary'])->middleware('feature:glosarium_hukum');
    Route::apiResource('istilah-hukum', LegalTermController::class)
        ->parameters(['istilah-hukum' => 'legalTerm'])->middleware('feature:glosarium_hukum');
    Route::apiResource('timeline-kasus', CaseTimelineController::class)
        ->parameters(['timeline-kasus' => 'caseTimeline'])->middleware('feature:timeline_kasus');
    Route::apiResource('policy-brief', PolicyBriefController::class)
        ->parameters(['policy-brief' => 'policyBrief'])->middleware('feature:policy_brief');
    Route::apiResource('kategori-sektor', PolicySectorController::class)
        ->parameters(['kategori-sektor' => 'kategoriSektor'])->middleware('feature:policy_brief');
    Route::apiResource('database-peraturan', DatabasePeraturanController::class)
        ->parameters(['database-peraturan' => 'databasePeraturan'])->middleware('feature:policy_brief');
    Route::apiResource('monitoring-kebijakan', PolicyMonitoringController::class)
        ->parameters(['monitoring-kebijakan' => 'policyMonitoring'])->middleware('feature:monitoring_kebijakan');
    Route::apiResource('reader-poll', ReaderPollController::class)
        ->parameters(['reader-poll' => 'readerPoll'])->middleware('feature:reader_poll');
    Route::apiResource('kutipan-publik', PublicQuoteController::class)
        ->parameters(['kutipan-publik' => 'kutipanPublik'])->middleware('feature:kutipan_publik');
    Route::apiResource('surat-pembaca', LetterController::class)
        ->parameters(['surat-pembaca' => 'suratPembaca'])->middleware('feature:surat_pembaca');
    Route::post('/surat-pembaca/{suratPembaca}/approve', [LetterController::class, 'approve'])
        ->middleware('feature:surat_pembaca')->name('api.cms.surat-pembaca.approve');
    Route::post('/surat-pembaca/{suratPembaca}/reject', [LetterController::class, 'reject'])
        ->middleware('feature:surat_pembaca')->name('api.cms.surat-pembaca.reject');
    Route::apiResource('survei', SurveyController::class)
        ->parameters(['survei' => 'survei'])->middleware('feature:survei');

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

    Route::get('/iklan', [AdController::class, 'index'])->name('iklan.index');
    Route::post('/iklan', [AdController::class, 'store'])->name('iklan.store');
    Route::post('/iklan/reorder', [AdController::class, 'reorder'])->name('iklan.reorder');
    Route::post('/iklan/{id}/toggle', [AdController::class, 'toggle'])->whereNumber('id')->name('iklan.toggle');
    Route::get('/iklan/{id}', [AdController::class, 'show'])->whereNumber('id')->name('iklan.show');
    Route::put('/iklan/{id}', [AdController::class, 'update'])->whereNumber('id')->name('iklan.update-item');
    Route::delete('/iklan/{id}', [AdController::class, 'destroy'])->whereNumber('id')->name('iklan.destroy');

    // Media image upload for CMS modules
    Route::post('/media/upload', [MediaUploadController::class, 'upload'])->name('media.upload');


    // Category options for dropdown pickers across CMS modules
    Route::get('/options/categories', [CategoryController::class, 'options'])->name('options.categories');
});
