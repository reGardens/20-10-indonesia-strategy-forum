<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Cms\IsfPageController;
use App\Http\Controllers\Isf\ArticlePageController;
use App\Http\Controllers\Isf\BriefingPreviewController;
use App\Support\IsfRubrik;
use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ISF (Indonesia Strategy Forum) public pages — Vue SPA, content from the public API (/api/isf/*)
Route::redirect('/', '/beranda');
Route::get('/beranda', [SpaController::class, 'index'])->name('isf.beranda');
Route::get('/register', [SpaController::class, 'index'])->name('isf.register');
Route::get('/verifikasi-email/{id}/{hash}', [\App\Http\Controllers\Auth\IsfEmailVerificationController::class, 'verify'])
    ->name('isf.verification.verify');
Route::get('/infografik', [SpaController::class, 'index'])->name('isf.infografik');
Route::get('/infografik/{slug}', [SpaController::class, 'index'])->name('isf.infografik.show');
Route::redirect('/tokoh/{slug}', '/sosok/{slug}', 301);
Route::get('/media/{id}', [SpaController::class, 'index'])->name('isf.media.show');
Route::get('/politik', [SpaController::class, 'index'])->name('isf.politik');
Route::get('/politik/{subkategori}', [SpaController::class, 'index'])->whereIn('subkategori', ['pemilu', 'parlemen', 'pemerintahan', 'partai'])->name('isf.politik.subkategori');
Route::get('/hukum', [SpaController::class, 'index'])->name('isf.hukum');
Route::get('/hukum/{subkategori}', [SpaController::class, 'index'])->whereIn('subkategori', ['regulasi', 'putusan', 'kasus', 'glosarium'])->name('isf.hukum.subkategori');
Route::get('/kebijakan-publik', [SpaController::class, 'index'])->name('isf.kebijakan-publik');
Route::get('/kebijakan-publik/{subkategori}', [SpaController::class, 'index'])->whereIn('subkategori', ['ekonomi', 'kesehatan', 'pendidikan', 'pertahanan', 'lingkungan', 'policy-brief', 'monitoring', 'data-statistik', 'isf-insight', 'database-peraturan'])->name('isf.kebijakan-publik.subkategori');
Route::get('/sosok', [SpaController::class, 'index'])->name('isf.sosok');
Route::get('/sosok/{kategori}', [SpaController::class, 'index'])->whereIn('kategori', ['eksekutif', 'legislatif', 'yudikatif', 'pakar', 'militer', 'pengusaha'])->name('isf.sosok.kategori');
Route::get('/sosok/{slug}', [SpaController::class, 'index'])->name('isf.sosok.show');
Route::get('/sosok/{slug}/{tab}', [SpaController::class, 'index'])->whereIn('tab', ['profil', 'analisis', 'jejaring', 'berita', 'kontroversi'])->name('isf.sosok.tab');
Route::get('/opini-publik', [SpaController::class, 'index'])->name('isf.opini-publik');
Route::get('/opini-publik/{subkategori}', [SpaController::class, 'index'])->whereIn('subkategori', ['op-ed', 'editorial', 'surat-pembaca', 'polling', 'kutipan'])->name('isf.opini-publik.subkategori');
Route::get('/penulis/{slug}', [SpaController::class, 'index'])->name('isf.penulis.show');
Route::get('/survei', [SpaController::class, 'index'])->name('isf.survei');
Route::get('/survei/{slug}', [SpaController::class, 'index'])->name('isf.survei.show');
Route::get('/media-kit', [SpaController::class, 'index'])->name('isf.media-kit');
Route::redirect('/advertise', '/media-kit', 301);
// Artikel per kategori: /artikel/{kategori} = daftar (mis. /artikel/ruu), /artikel/{kategori}/{slug} = detail.
Route::get('/artikel/{kategori}', [SpaController::class, 'index'])->whereIn('kategori', IsfRubrik::keys())->name('isf.kategori');
Route::get('/artikel/{kategori}/{slug}', [ArticlePageController::class, 'show'])->whereIn('kategori', IsfRubrik::keys())->name('isf.artikel.show');
// Kategori lama yang digabung (ruu → hukum, ekonomi → kebijakan): 301 ke URL kanonis.
Route::get('/artikel/{lama}/{slug}', [ArticlePageController::class, 'legacyCategory'])->whereIn('lama', array_keys(IsfRubrik::LEGACY))->name('isf.artikel.legacy-category');
// Tautan lama /artikel/{slug} (tanpa kategori) dialihkan (301) ke URL kategori kanonis.
Route::get('/artikel/{slug}', [ArticlePageController::class, 'plain'])->name('isf.artikel.plain');
// Tautan lama Anatomi Isu (/isu/{slug}, dihapus di #34) diarahkan ke detail artikel.
Route::get('/isu/{slug}', [ArticlePageController::class, 'legacyIsu'])->name('isf.isu.legacy');
Route::get('/pencarian', [SpaController::class, 'index'])->name('isf.pencarian');
Route::get('/search', [SpaController::class, 'index'])->name('isf.search');
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

    // /cms root redirects to dashboard (atau /cms/artikel untuk role member)
    Route::get('/cms', function () {
        $user = auth()->user();
        if ($user && $user->hasRole('member') && ! $user->hasAnyRole(['admin', 'editor', 'super-admin'])) {
            return redirect()->route('cms.isf.artikel.index');
        }
        return redirect('/cms/dashboard');
    });

    // ISF content modules (Inertia page shells; data via /api/cms/*), gated per feature (ACL)
    $isfModules = [
        'banner' => 'banner',
        'artikel' => 'artikel',
        'infografik' => 'infografik',
        'tokoh' => 'tokoh',
        'kategori-lembaga' => 'tokoh',
        'data-grafik' => 'data_grafik',
        'alert' => 'alert',
        'email-briefing' => 'email_briefing',
        'kategori' => 'kategori',
        'kursi-parlemen' => 'kursi_parlemen',
        'glosarium-hukum' => 'glosarium_hukum',
        'istilah-hukum' => 'glosarium_hukum',
        'timeline-kasus' => 'timeline_kasus',
        'policy-brief' => 'policy_brief',
        'kategori-sektor' => 'policy_brief',
        'database-peraturan' => 'policy_brief',
        'monitoring-kebijakan' => 'monitoring_kebijakan',
        'reader-poll' => 'reader_poll',
        'kutipan-publik' => 'kutipan_publik',
        'surat-pembaca' => 'surat_pembaca',
        'survei' => 'survei',
        'kemitraan' => 'media_kit',
    ];
    foreach ($isfModules as $segment => $feature) {
        Route::middleware("feature:{$feature}")->prefix("cms/{$segment}")->group(function () use ($segment) {
            Route::get('/', [IsfPageController::class, 'index'])->defaults('module', $segment)->name("cms.isf.{$segment}.index");
            Route::get('/create', [IsfPageController::class, 'create'])->defaults('module', $segment)->name("cms.isf.{$segment}.create");
            Route::get('/{id}', [IsfPageController::class, 'show'])->defaults('module', $segment)->whereNumber('id')->name("cms.isf.{$segment}.show");
            Route::get('/{id}/edit', [IsfPageController::class, 'edit'])->defaults('module', $segment)->whereNumber('id')->name("cms.isf.{$segment}.edit");
        });
    }

    Route::get('/cms/kemitraan/sections', [IsfPageController::class, 'kemitraanSections'])
        ->middleware('feature:media_kit')->name('cms.isf.kemitraan.sections');
    Route::get('/cms/kemitraan/sections/create', [IsfPageController::class, 'kemitraanSectionForm'])
        ->middleware('feature:media_kit')->name('cms.isf.kemitraan.sections.create');
    Route::get('/cms/kemitraan/sections/{id}/edit', [IsfPageController::class, 'kemitraanSectionForm'])
        ->middleware('feature:media_kit')->name('cms.isf.kemitraan.sections.edit');
    Route::get('/cms/kemitraan/sections/edit', [IsfPageController::class, 'kemitraanSectionForm'])
        ->middleware('feature:media_kit')->name('cms.isf.kemitraan.sections.edit.generic');

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

    Route::prefix('cms/hot-news')->group(function () {
        Route::get('/', [IsfPageController::class, 'index'])->defaults('module', 'hot-news')->name('cms.isf.hot-news.index');
        Route::get('/create', [IsfPageController::class, 'create'])->defaults('module', 'hot-news')->name('cms.isf.hot-news.create');
        Route::get('/{id}/edit', [IsfPageController::class, 'edit'])->defaults('module', 'hot-news')->whereNumber('id')->name('cms.isf.hot-news.edit');
    });

    Route::prefix('cms/iklan')->group(function () {
        Route::get('/', [IsfPageController::class, 'index'])->defaults('module', 'iklan')->name('cms.isf.iklan.index');
        Route::get('/create', [IsfPageController::class, 'create'])->defaults('module', 'iklan')->name('cms.isf.iklan.create');
        Route::get('/{id}/edit', [IsfPageController::class, 'edit'])->defaults('module', 'iklan')->whereNumber('id')->name('cms.isf.iklan.edit');
    });

    Route::prefix('cms/footer')->middleware('feature:pengaturan_halaman')->group(function () {
        Route::get('/', [IsfPageController::class, 'footer'])->name('cms.isf.footer.index');
    });

    Route::get('/cms/pengaturan-halaman', fn () => redirect()->route('cms.isf.running-text.index'))
        ->name('cms.isf.pengaturan-halaman');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
