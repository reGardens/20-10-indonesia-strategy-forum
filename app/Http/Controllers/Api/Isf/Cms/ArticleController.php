<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\ArticleRequest;
use App\Models\Isf\Article;
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

    protected array $order = ['published_at' => 'desc', 'id' => 'desc'];

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

    public function destroy(Article $article): Response
    {
        return $this->destroyModel($article);
    }
}
