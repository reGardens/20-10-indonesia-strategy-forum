<?php

namespace App\Http\Controllers\Isf;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SpaController;
use App\Models\Isf\Article;
use App\Support\IsfRubrik;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman web artikel (SPA) dengan URL kanonis /artikel/{kategori}/{slug}.
 *
 * URL lain yang menunjuk artikel yang sama — /artikel/{slug}, kategori yang salah,
 * atau tautan lama /isu/{slug} — dialihkan permanen (301) ke URL kanonis.
 * Artikel yang tidak ditemukan tetap dirender SPA (halaman "Artikel tidak ditemukan").
 */
class ArticlePageController extends Controller
{
    /** GET /artikel/{kategori}/{slug} */
    public function show(Request $request, string $kategori, string $slug): View|RedirectResponse
    {
        return $this->canonical($request, $slug) ?? app(SpaController::class)->index();
    }

    /** GET /artikel/{slug} — tautan lama tanpa kategori. */
    public function plain(Request $request, string $slug): View|RedirectResponse
    {
        // Halaman kategori lama: /artikel/ruu → /artikel/hukum, /artikel/ekonomi & /artikel/kebijakan-publik → /artikel/kebijakan.
        $alias = IsfRubrik::LEGACY[$slug] ?? ($slug === 'kebijakan-publik' ? 'kebijakan' : null);
        if ($alias) {
            return redirect()->to(IsfRubrik::path($alias), 301);
        }

        return $this->canonical($request, $slug) ?? app(SpaController::class)->index();
    }

    /** GET /artikel/{ruu|ekonomi}/{slug} — kategori lama yang sudah digabung. */
    public function legacyCategory(Request $request, string $lama, string $slug): RedirectResponse
    {
        return $this->canonical($request, $slug)
            ?? redirect()->to(IsfRubrik::path(IsfRubrik::LEGACY[$lama]) . '/' . rawurlencode($slug), 301);
    }

    /** GET /isu/{slug} — tautan lama Anatomi Isu (dihapus di #34). */
    public function legacyIsu(Request $request, string $slug): RedirectResponse
    {
        return $this->canonical($request, $slug)
            ?? redirect()->to('/artikel/' . rawurlencode($slug), 301);
    }

    private function canonical(Request $request, string $slug): ?RedirectResponse
    {
        $article = Article::query()->terbit()->where('slug', $slug)->first();
        if (!$article) {
            return null;
        }

        $path = $article->detailPath();
        if ('/' . trim($request->path(), '/') === $path) {
            return null;
        }

        $query = $request->getQueryString();

        return redirect()->to($path . ($query ? '?' . $query : ''), 301);
    }
}
