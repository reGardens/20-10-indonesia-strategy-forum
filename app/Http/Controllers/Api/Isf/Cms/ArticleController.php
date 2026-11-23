<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\ArticleRequest;
use App\Models\Isf\Article;
use App\Models\Isf\Letter;
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
        'id', 'user_id', 'slug', 'title', 'category', 'subcategory', 'image_url', 'published_at',
        'is_hero', 'show_in_terkini', 'is_popular', 'status', 'is_by_member', 'updated_at',
    ];

    protected array $searchColumns = ['title', 'slug'];

    /** Filter daftar CMS: ?category=politik|hukum|kebijakan & ?subcategory=... & ?is_by_member=1 */
    protected array $filterColumns = ['category', 'subcategory', 'is_by_member'];

    private function isOnlyMember(?\App\Models\User $user): bool
    {
        return $user && $user->hasRole('member') && ! $user->hasAnyRole(['admin', 'editor', 'super-admin']);
    }

    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:'.implode(',', \App\Support\IsfOptions::STATUSES)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:500'],
            'category' => ['nullable', 'string', 'max:100'],
            'subcategory' => ['nullable', 'string', 'max:100'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'is_by_member' => ['nullable'],
        ]);

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = $this->model::query()->select($this->listColumns);

        // RBAC: Member hanya melihat artikel miliknya sendiri
        if ($this->isOnlyMember($request->user())) {
            $query->where('user_id', $request->user()->id);
        } else {
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
        }

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

        if ($date = $request->query('date')) {
            $query->whereDate('published_at', $date);
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

    public function show(\Illuminate\Http\Request $request, Article $article): JsonResponse
    {
        if ($this->isOnlyMember($request->user()) && $article->user_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki hak untuk melihat artikel ini.');
        }

        return $this->showModel($article);
    }

    public function store(ArticleRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (empty($data['link_url']) && ! empty($data['slug'])) {
            $cat = $data['category'] ?? 'kebijakan';
            $data['link_url'] = '/artikel/' . $cat . '/' . $data['slug'];
        }

        if ($this->isOnlyMember($request->user())) {
            $user = $request->user();
            $data['user_id'] = $user->id;
            $data['is_by_member'] = true;
            $data['status'] = 'draf';
            $data['is_hero'] = false;
            $data['show_in_terkini'] = false;
            $data['is_popular'] = false;
            $data['popular_rank'] = null;
            $data['is_expert_opinion'] = false;
            $data['author_name'] = $user->name;
            $data['author_role'] = $user->author_role ?? $data['author_role'] ?? 'Member ISF';
            $data['author_avatar'] = $user->author_avatar ?? $user->avatar ?? null;
        }

        $res = $this->storeModel($data);

        // Jika dibuat oleh member, sinkronkan ke antrean moderasi Artikel by Member (isf_letters)
        if (! empty($data['is_by_member']) || $this->isOnlyMember($request->user())) {
            $user = $request->user();
            Letter::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'user_id' => $user?->id ?? $data['user_id'] ?? null,
                    'sender_name' => $data['author_name'] ?? $user?->name ?? 'Member ISF',
                    'sender_email' => $user?->email ?? null,
                    'title' => $data['title'],
                    'category' => $data['category'] ?? 'kebijakan',
                    'subcategory' => $data['subcategory'] ?? null,
                    'summary' => $data['summary'] ?? null,
                    'body' => $data['body'] ?? '',
                    'author_role' => $data['author_role'] ?? null,
                    'author_avatar' => $data['author_avatar'] ?? null,
                    'reading_minutes' => $data['reading_minutes'] ?? 5,
                    'image_url' => $data['image_url'] ?? null,
                    'image_alt' => $data['image_alt'] ?? null,
                    'link_url' => $data['link_url'] ?? null,
                    'fact_blocks' => $data['fact_blocks'] ?? null,
                    'moderation_status' => 'pending',
                    'status' => 'draft',
                ]
            );
        }

        return $res;
    }

    public function update(ArticleRequest $request, Article $article): JsonResponse
    {
        // Artikel yang dibuat member tidak bisa diedit setelah diajukan
        if ($this->isOnlyMember($request->user()) || $article->is_by_member) {
            abort(403, 'Artikel yang dibuat member tidak dapat diedit setelah diajukan.');
        }

        $data = $request->validated();
        if (empty($data['link_url']) && ! empty($article->slug)) {
            $cat = $data['category'] ?? $article->category ?? 'kebijakan';
            $data['link_url'] = '/artikel/' . $cat . '/' . ($data['slug'] ?? $article->slug);
        }

        return $this->updateModel($article, $data);
    }

    public function setHero(\Illuminate\Http\Request $request, Article $article): JsonResponse
    {
        if ($this->isOnlyMember($request->user())) {
            abort(403, 'Hanya tim redaksi yang berwenang menetapkan artikel hero.');
        }

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

    public function destroy(\Illuminate\Http\Request $request, Article $article): Response
    {
        // Artikel yang dibuat member tidak bisa dihapus
        if ($this->isOnlyMember($request->user()) || $article->is_by_member) {
            abort(403, 'Artikel yang dibuat member tidak dapat dihapus.');
        }

        return $this->destroyModel($article);
    }
}
