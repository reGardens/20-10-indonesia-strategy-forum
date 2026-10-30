<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\ArticleRequest;
use App\Models\Isf\Article;
use App\Services\Isf\IsfCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * CMS API — Artikel (feature: artikel).
 */
class ArticleController extends CmsResourceController
{
    protected string $model = Article::class;

    protected array $listColumns = [
        'id', 'slug', 'title', 'category', 'published_at',
        'is_hero', 'show_in_terkini', 'is_popular', 'status', 'updated_at',
    ];

    protected array $searchColumns = ['title', 'slug'];

    protected array $order = ['id' => 'desc'];

    /** Beranda hero currently shown on the public page. */
    protected function liveId(): ?int
    {
        return Article::currentHero()?->id;
    }

    public function show(Article $article): JsonResponse
    {
        return $this->showModel($article);
    }

    public function store(ArticleRequest $request): JsonResponse
    {
        return $this->storeModel($request->validated());
    }

    public function update(ArticleRequest $request, Article $article): JsonResponse
    {
        return $this->updateModel($article, $request->validated());
    }

    public function setHero(Article $article): JsonResponse
    {
        Article::query()->where('id', '!=', $article->id)->update(['is_hero' => false]);
        $article->update([
            'is_hero' => true,
            'status' => 'terbit',
            'published_at' => now()->format('Y-m-d'),
        ]);

        IsfCacheService::clearBeranda();

        return response()->json([
            'message' => 'Artikel berhasil ditayangkan sebagai Hero Beranda.',
            'live_id' => $article->id,
            'data' => $article->fresh(),
        ]);
    }

    public function destroy(Article $article): Response
    {
        return $this->destroyModel($article);
    }
}
