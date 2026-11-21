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
        'id', 'slug', 'title', 'category', 'subcategory', 'image_url', 'published_at',
        'is_hero', 'show_in_terkini', 'is_popular', 'status', 'is_by_member', 'updated_at',
    ];

    protected array $searchColumns = ['title', 'slug'];

    /** Filter daftar CMS: ?category=politik|hukum|kebijakan & ?subcategory=... & ?is_by_member=1 */
    protected array $filterColumns = ['category', 'subcategory', 'is_by_member'];

    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:'.implode(',', \App\Support\IsfOptions::STATUSES)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:500'],
            'category' => ['nullable', 'string', 'max:100'],
            'subcategory' => ['nullable', 'string', 'max:100'],
            'is_by_member' => ['nullable'],
        ]);

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = $this->model::query()->select($this->listColumns);

        if ($keyword = trim((string) $request->query('q', ''))) {
            $like = '%'.addcslashes($keyword, '\\%_').'%';
            $query->where(function ($q) use ($like) {
                foreach ($this->searchColumns as $column) {
                    $q->orWhere($column, 'like', $like);
                }
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($subcategory = $request->query('subcategory')) {
            $query->where('subcategory', $subcategory);
        }

        if ($request->has('is_by_member')) {
            $val = $request->input('is_by_member');
            if ($val === '1' || $val === 1 || $val === 'true' || $val === true) {
                $query->where('is_by_member', true);
            } elseif ($val === '0' || $val === 0 || $val === 'false' || $val === false) {
                $query->where(function ($q) {
                    $q->where('is_by_member', false)->orWhereNull('is_by_member');
                });
            }
        }

        $liveId = $this->liveId();
        if ($this->liveFirst && $liveId) {
            $query->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$liveId]);
        }

        foreach ($this->order as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        $perPage = (int) $request->query('per_page', 10);
        if ($perPage < 1 || $perPage > 500) {
            $perPage = 10;
        }

        return response()->json(array_merge(
            $query->paginate($perPage)->withQueryString()->toArray(),
            ['live_id' => $liveId],
        ));
    }

    /** Hero yang sedang tayang selalu paling atas. */
    protected bool $liveFirst = true;

    /** Terbaru di atas: tanggal terbit terbaru, lalu yang terakhir diunggah. */
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
