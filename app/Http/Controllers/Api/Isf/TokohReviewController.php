<?php

namespace App\Http\Controllers\Api\Isf;

use App\Http\Controllers\Controller;
use App\Http\Requests\Isf\TokohReviewRequest;
use App\Models\Isf\Tokoh;
use App\Models\Isf\TokohReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Indeks Tokoh: rating & ulasan publik (gaya Play Store) di /tokoh/{slug}.
 *
 * - GET  /api/isf/tokoh/{slug}/ulasan   publik: skor agregat, sebaran bintang, daftar ulasan per tab, status viewer.
 * - POST /api/isf/tokoh/{slug}/ulasan   wajib login: buat/perbarui ulasan milik user (satu per tokoh).
 *
 * Sengaja TIDAK di-cache (beda dengan GET /api/isf/tokoh/{slug}) agar skor langsung berubah setelah submit.
 * Ulasan anonim tidak pernah mengirim nama/identitas user ke publik.
 */
class TokohReviewController extends Controller
{
    private const PER_PAGE = 10;

    private const TABS = ['suka', 'tidak-suka'];

    public function index(Request $request, string $slug): JsonResponse
    {
        $tokoh = $this->findTokoh($slug);
        if (! $tokoh) {
            return response()->json(['message' => 'Tokoh tidak ditemukan.'], 404);
        }

        return response()->json($this->payload($request, $tokoh));
    }

    public function store(TokohReviewRequest $request, string $slug): JsonResponse
    {
        $tokoh = $this->findTokoh($slug);
        if (! $tokoh) {
            return response()->json(['message' => 'Tokoh tidak ditemukan.'], 404);
        }

        $validated = $request->validated();

        $review = TokohReview::updateOrCreate(
            ['tokoh_id' => $tokoh->id, 'user_id' => $request->user()->id],
            [
                'rating' => (int) $validated['rating'],
                'body' => $validated['body'],
                'is_anonymous' => (bool) ($validated['is_anonymous'] ?? false),
            ],
        );

        return response()->json(
            ['message' => 'Ulasan tersimpan.'] + $this->payload($request, $tokoh),
            $review->wasRecentlyCreated ? 201 : 200,
        );
    }

    private function findTokoh(string $slug): ?Tokoh
    {
        return Tokoh::query()->terbit()->where('slug', $slug)->first();
    }

    private function payload(Request $request, Tokoh $tokoh): array
    {
        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'suka';
        $user = $request->user();

        $listQuery = TokohReview::query()->where('tokoh_id', $tokoh->id)->with('user:id,name');
        $tab === 'suka' ? $listQuery->suka() : $listQuery->tidakSuka();
        $page = $listQuery->orderByDesc('updated_at')->orderByDesc('id')->paginate(self::PER_PAGE);

        $mine = $user
            ? TokohReview::query()->where('tokoh_id', $tokoh->id)->where('user_id', $user->id)->first()
            : null;

        return [
            'summary' => $this->summary($tokoh),
            'tab' => $tab,
            'reviews' => [
                'data' => collect($page->items())->map(fn (TokohReview $r) => $this->publicReview($r, $user?->id))->values(),
                'currentPage' => $page->currentPage(),
                'lastPage' => $page->lastPage(),
                'total' => $page->total(),
            ],
            'viewer' => [
                'authenticated' => (bool) $user,
                'name' => $user?->name,
                'myReview' => $mine ? [
                    'rating' => $mine->rating,
                    'body' => $mine->body,
                    'isAnonymous' => $mine->is_anonymous,
                    'updatedAt' => $mine->updated_at?->toIso8601String(),
                ] : null,
            ],
        ];
    }

    /**
     * Skor agregat (rata-rata 1 desimal), jumlah ulasan, sebaran bintang 5→1, jumlah per tab.
     */
    private function summary(Tokoh $tokoh): array
    {
        $byRating = TokohReview::query()->where('tokoh_id', $tokoh->id)
            ->selectRaw('rating, COUNT(*) as total')->groupBy('rating')->pluck('total', 'rating');

        $distribution = [];
        $total = 0;
        $sum = 0;
        foreach ([5, 4, 3, 2, 1] as $star) {
            $count = (int) ($byRating[$star] ?? 0);
            $distribution[(string) $star] = $count;
            $total += $count;
            $sum += $star * $count;
        }

        $suka = 0;
        $tidakSuka = 0;
        foreach ($distribution as $star => $count) {
            if ((int) $star >= TokohReview::SUKA_MIN) {
                $suka += $count;
            } elseif ((int) $star <= TokohReview::TIDAK_SUKA_MAX) {
                $tidakSuka += $count;
            }
        }

        return [
            'average' => $total ? round($sum / $total, 1) : 0,
            'total' => $total,
            'distribution' => $distribution,
            'suka' => $suka,
            'tidakSuka' => $tidakSuka,
        ];
    }

    private function publicReview(TokohReview $review, ?int $viewerId): array
    {
        $name = $review->is_anonymous ? 'Anonim' : ($review->user?->name ?: 'Pengguna ISF');

        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'body' => $review->body,
            'isAnonymous' => $review->is_anonymous,
            // Anonim: tidak ada nama/inisial asli yang dikirim.
            'author' => [
                'name' => $name,
                'initials' => $review->is_anonymous ? null : $this->initials($name),
            ],
            'isMine' => $viewerId !== null && (int) $review->user_id === $viewerId,
            'createdAt' => $review->created_at?->toIso8601String(),
            'updatedAt' => $review->updated_at?->toIso8601String(),
        ];
    }

    private function initials(string $name): string
    {
        return collect(preg_split('/\s+/', trim($name)))->filter()->take(2)
            ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') ?: 'U';
    }
}
