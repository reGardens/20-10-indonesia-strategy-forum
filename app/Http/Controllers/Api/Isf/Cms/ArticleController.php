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
        'is_hero', 'show_in_terkini', 'show_in_running_text', 'running_text_order',
        'show_in_top_rank', 'top_rank_order', 'is_popular', 'status', 'is_by_member', 'updated_at',
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
        $this->resequenceRunningTexts();
        $this->resequenceTopRanks();

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
        if (! $this->isOnlyMember($request->user())) {
            // Prioritaskan artikel dengan priority (Tayang Hero, Running Text, Top Artikel) di baris teratas tabel
            $heroClause = ($this->liveFirst && $liveId) ? 'WHEN id = ' . (int) $liveId . ' THEN 0' : '';
            $query->orderByRaw("
                CASE
                    {$heroClause}
                    WHEN show_in_running_text = 1 THEN 1
                    WHEN show_in_top_rank = 1 THEN 2
                    ELSE 3
                END ASC
            ")->orderByRaw("
                CASE
                    WHEN show_in_running_text = 1 THEN running_text_order
                    WHEN show_in_top_rank = 1 THEN top_rank_order
                    ELSE 999
                END ASC
            ");
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
            [
                'live_id' => $liveId,
                'running_text_count' => Article::where('show_in_running_text', true)->count(),
                'top_rank_count' => Article::where('show_in_top_rank', true)->count(),
            ],
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
            $data['show_in_running_text'] = false;
            $data['running_text_order'] = null;
            $data['show_in_top_rank'] = false;
            $data['top_rank_order'] = null;
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

        $this->resequenceRunningTexts();
        $this->resequenceTopRanks();

        return $res;
    }

    public function update(ArticleRequest $request, Article $article): JsonResponse
    {
        // Member tidak bisa mengedit artikel setelah diajukan; admin dan super-admin bisa mengedit semua artikel
        if ($this->isOnlyMember($request->user())) {
            abort(403, 'Artikel yang dibuat member tidak dapat diedit setelah diajukan.');
        }

        $data = $request->validated();
        if (empty($data['link_url']) && ! empty($article->slug)) {
            $cat = $data['category'] ?? $article->category ?? 'kebijakan';
            $data['link_url'] = '/artikel/' . $cat . '/' . ($data['slug'] ?? $article->slug);
        }

        $res = $this->updateModel($article, $data);
        $this->resequenceRunningTexts();
        $this->resequenceTopRanks();

        return $res;
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

    /**
     * Resequence running text order sequentially (1..N, max 5) without any gaps.
     */
    protected function resequenceRunningTexts(): void
    {
        $articles = Article::where('show_in_running_text', true)
            ->orderByRaw('CASE WHEN running_text_order IS NULL THEN 999 ELSE running_text_order END ASC')
            ->orderBy('id', 'asc')
            ->get();

        $index = 1;
        foreach ($articles as $art) {
            if ($index > 5) {
                $art->update([
                    'show_in_running_text' => false,
                    'running_text_order' => null,
                ]);
            } else {
                if ($art->running_text_order !== $index) {
                    $art->update(['running_text_order' => $index]);
                }
                $index++;
            }
        }
    }

    /**
     * Resequence top rank order sequentially (1..N, max 3) without any gaps.
     */
    protected function resequenceTopRanks(): void
    {
        $articles = Article::where('show_in_top_rank', true)
            ->orderByRaw('CASE WHEN top_rank_order IS NULL THEN 999 ELSE top_rank_order END ASC')
            ->orderBy('id', 'asc')
            ->get();

        $index = 1;
        foreach ($articles as $art) {
            if ($index > 3) {
                $art->update([
                    'show_in_top_rank' => false,
                    'top_rank_order' => null,
                ]);
            } else {
                if ($art->top_rank_order !== $index) {
                    $art->update(['top_rank_order' => $index]);
                }
                $index++;
            }
        }
    }

    /**
     * Toggle / Atur Urutan Teks Berjalan (Running Text) — maksimal 5 artikel aktif.
     */
    public function toggleRunningText(\Illuminate\Http\Request $request, Article $article): JsonResponse
    {
        if ($this->isOnlyMember($request->user())) {
            abort(403, 'Hanya tim redaksi yang berwenang mengatur running text.');
        }

        $action = $request->input('action');
        $order = $request->input('order');

        if ($action === 'off') {
            $article->update([
                'show_in_running_text' => false,
                'running_text_order' => null,
            ]);
            $this->resequenceRunningTexts();
            IsfCacheService::clearBeranda();

            return response()->json([
                'success' => true,
                'message' => 'Artikel berhasil dilepas dari Running Text.',
                'data' => $article->fresh(),
                'running_text_count' => Article::where('show_in_running_text', true)->count(),
            ]);
        }

        if ($order !== null) {
            $targetOrder = max(1, min(5, (int) $order));
            $oldOrder = $article->running_text_order;

            if ($oldOrder && $oldOrder !== $targetOrder) {
                if ($targetOrder < $oldOrder) {
                    Article::where('id', '!=', $article->id)
                        ->where('show_in_running_text', true)
                        ->whereBetween('running_text_order', [$targetOrder, $oldOrder - 1])
                        ->increment('running_text_order');
                } else {
                    Article::where('id', '!=', $article->id)
                        ->where('show_in_running_text', true)
                        ->whereBetween('running_text_order', [$oldOrder + 1, $targetOrder])
                        ->decrement('running_text_order');
                }
            }

            $article->update([
                'show_in_running_text' => true,
                'running_text_order' => $targetOrder,
            ]);
            $this->resequenceRunningTexts();
            IsfCacheService::clearBeranda();

            return response()->json([
                'success' => true,
                'message' => "Urutan Running Text berhasil diubah ke Running Text {$article->fresh()->running_text_order}.",
                'data' => $article->fresh(),
                'running_text_count' => Article::where('show_in_running_text', true)->count(),
            ]);
        }

        // Toggle biasa (jika sudah aktif -> nonaktifkan; jika belum -> aktifkan ke slot kosong)
        if ($article->show_in_running_text) {
            $article->update([
                'show_in_running_text' => false,
                'running_text_order' => null,
            ]);
            $this->resequenceRunningTexts();
            IsfCacheService::clearBeranda();

            return response()->json([
                'success' => true,
                'message' => 'Artikel berhasil dilepas dari Running Text.',
                'data' => $article->fresh(),
                'running_text_count' => Article::where('show_in_running_text', true)->count(),
            ]);
        }

        $currentCount = Article::where('show_in_running_text', true)->count();
        if ($currentCount >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Maksimal 5 running text. Harap nonaktifkan salah satu yang dipilih terlebih dahulu.',
            ], 422);
        }

        $nextOrder = $currentCount + 1;

        $article->update([
            'show_in_running_text' => true,
            'running_text_order' => $nextOrder,
        ]);
        $this->resequenceRunningTexts();
        IsfCacheService::clearBeranda();

        return response()->json([
            'success' => true,
            'message' => "Artikel berhasil ditambahkan ke Running Text {$article->fresh()->running_text_order}.",
            'data' => $article->fresh(),
            'running_text_count' => Article::where('show_in_running_text', true)->count(),
        ]);
    }

    /**
     * Toggle / Atur Urutan Top Artikel (Top Rank) — maksimal 3 artikel aktif.
     */
    public function toggleTopRank(\Illuminate\Http\Request $request, Article $article): JsonResponse
    {
        if ($this->isOnlyMember($request->user())) {
            abort(403, 'Hanya tim redaksi yang berwenang mengatur Top Artikel.');
        }

        $action = $request->input('action');
        $order = $request->input('order');

        if ($action === 'off') {
            $article->update([
                'show_in_top_rank' => false,
                'top_rank_order' => null,
            ]);
            $this->resequenceTopRanks();
            IsfCacheService::clearBeranda();

            return response()->json([
                'success' => true,
                'message' => 'Artikel berhasil dilepas dari Top Artikel.',
                'data' => $article->fresh(),
                'top_rank_count' => Article::where('show_in_top_rank', true)->count(),
            ]);
        }

        if ($order !== null) {
            $targetOrder = max(1, min(3, (int) $order));
            $oldOrder = $article->top_rank_order;

            if ($oldOrder && $oldOrder !== $targetOrder) {
                if ($targetOrder < $oldOrder) {
                    Article::where('id', '!=', $article->id)
                        ->where('show_in_top_rank', true)
                        ->whereBetween('top_rank_order', [$targetOrder, $oldOrder - 1])
                        ->increment('top_rank_order');
                } else {
                    Article::where('id', '!=', $article->id)
                        ->where('show_in_top_rank', true)
                        ->whereBetween('top_rank_order', [$oldOrder + 1, $targetOrder])
                        ->decrement('top_rank_order');
                }
            }

            $article->update([
                'show_in_top_rank' => true,
                'top_rank_order' => $targetOrder,
            ]);
            $this->resequenceTopRanks();
            IsfCacheService::clearBeranda();

            return response()->json([
                'success' => true,
                'message' => "Peringkat Top Artikel berhasil diubah ke Top Artikel {$article->fresh()->top_rank_order}.",
                'data' => $article->fresh(),
                'top_rank_count' => Article::where('show_in_top_rank', true)->count(),
            ]);
        }

        if ($article->show_in_top_rank) {
            $article->update([
                'show_in_top_rank' => false,
                'top_rank_order' => null,
            ]);
            $this->resequenceTopRanks();
            IsfCacheService::clearBeranda();

            return response()->json([
                'success' => true,
                'message' => 'Artikel berhasil dilepas dari Top Artikel.',
                'data' => $article->fresh(),
                'top_rank_count' => Article::where('show_in_top_rank', true)->count(),
            ]);
        }

        $currentCount = Article::where('show_in_top_rank', true)->count();
        if ($currentCount >= 3) {
            return response()->json([
                'success' => false,
                'message' => 'Maksimal 3 Top Artikel. Harap nonaktifkan salah satu yang dipilih terlebih dahulu.',
            ], 422);
        }

        $nextOrder = $currentCount + 1;

        $article->update([
            'show_in_top_rank' => true,
            'top_rank_order' => $nextOrder,
        ]);
        $this->resequenceTopRanks();
        IsfCacheService::clearBeranda();

        return response()->json([
            'success' => true,
            'message' => "Artikel berhasil ditambahkan ke Top Artikel {$article->fresh()->top_rank_order}.",
            'data' => $article->fresh(),
            'top_rank_count' => Article::where('show_in_top_rank', true)->count(),
        ]);
    }

    public function destroy(\Illuminate\Http\Request $request, Article $article): Response
    {
        // Member tidak bisa menghapus artikel; admin dan super-admin bisa
        if ($this->isOnlyMember($request->user())) {
            abort(403, 'Anda tidak memiliki hak untuk menghapus artikel ini.');
        }

        $wasRunningText = (bool) $article->show_in_running_text;
        $wasTopRank = (bool) $article->show_in_top_rank;
        $slug = $article->slug;
        $isByMember = (bool) $article->is_by_member;

        $res = $this->destroyModel($article);

        if ($isByMember && $slug) {
            Letter::where('slug', $slug)->delete();
        }

        if ($wasRunningText) {
            $this->resequenceRunningTexts();
        }
        if ($wasTopRank) {
            $this->resequenceTopRanks();
        }

        return $res;
    }
}
