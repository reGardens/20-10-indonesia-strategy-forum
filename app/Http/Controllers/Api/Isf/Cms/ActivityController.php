<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Controllers\Controller;
use App\Models\Isf\Letter;
use App\Models\Isf\MemberReport;
use App\Models\Isf\SurveyResponse;
use App\Models\Isf\TokohReview;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    /**
     * GET /api/cms/aktifitas
     * - Member: hanya melihat aktifitasnya sendiri.
     * - Admin: melihat aktifitas dirinya sendiri + semua user dengan role member.
     * - Super Admin: melihat semua aktifitas tanpa batasan.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $isSuperAdmin = $user->hasRole('super-admin');
        $isAdmin = !$isSuperAdmin && $user->hasRole('admin');

        $allowedUserIds = null; // null = tanpa batasan (super-admin)
        if ($isAdmin) {
            $memberIds = User::role('member')->pluck('id')->all();
            $allowedUserIds = array_values(array_unique(array_merge([$user->id], $memberIds)));
        } elseif (!$isSuperAdmin) {
            $allowedUserIds = [$user->id];
        }

        $type = $request->query('type', 'semua');
        $category = trim((string) $request->query('category', ''));
        $date = trim((string) $request->query('date', ''));
        $search = trim((string) $request->query('search', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(50, (int) $request->query('per_page', 10)));

        $items = collect();

        // 1. Artikel / Naskah (Letter)
        if ($type === 'semua' || $type === 'artikel') {
            $q = Letter::with(['user:id,name,email', 'user.roles:id,name']);
            if ($allowedUserIds !== null) {
                $q->whereIn('user_id', $allowedUserIds);
            }
            foreach ($q->orderByDesc('created_at')->take(200)->get() as $l) {
                $actor = ($l->user_id === $user->id) ? 'Anda' : ($l->user?->name ?: ($l->sender_name ?: 'Pengguna'));
                $items->push([
                    'uid' => 'artikel-' . $l->id,
                    'type' => 'artikel',
                    'type_label' => 'Artikel / Naskah',
                    'user_name' => $l->user?->name ?: ($l->sender_name ?: 'Pengguna'),
                    'user_role' => $l->user?->roles?->first()?->name ?: 'member',
                    'actor_label' => $actor,
                    'action_label' => 'membuat artikel terbaru',
                    'target_label' => $l->title,
                    'title' => "{$actor} membuat artikel terbaru \"{$l->title}\"",
                    'detail' => 'Kategori: ' . ($l->category ?: 'Opini'),
                    'category' => strtolower($l->category ?: 'opini'),
                    'date_iso' => $l->created_at?->format('Y-m-d'),
                    'status' => $l->moderation_status ?: 'pending',
                    'created_at' => $l->created_at?->format('d M Y, H:i') ?? '-',
                    'timestamp' => $l->created_at?->timestamp ?? 0,
                ]);
            }
        }

        // 2. Rating & Ulasan Tokoh
        if ($type === 'semua' || $type === 'rating') {
            $q = TokohReview::with(['user:id,name,email', 'user.roles:id,name', 'tokoh:id,name,slug']);
            if ($allowedUserIds !== null) {
                $q->whereIn('user_id', $allowedUserIds);
            }
            foreach ($q->orderByDesc('created_at')->take(200)->get() as $r) {
                $actor = ($r->user_id === $user->id) ? 'Anda' : ($r->user?->name ?: 'Pengguna');
                $tokohName = $r->tokoh?->name ?: 'Tokoh ISF';
                $items->push([
                    'uid' => 'rating-' . $r->id,
                    'type' => 'rating',
                    'type_label' => 'Rating Sosok',
                    'user_name' => $r->user?->name ?: 'Pengguna',
                    'user_role' => $r->user?->roles?->first()?->name ?: 'member',
                    'actor_label' => $actor,
                    'action_label' => "memberi rating ★ {$r->rating}/5 untuk",
                    'target_label' => $tokohName,
                    'title' => "{$actor} memberi rating ★ {$r->rating}/5 untuk {$tokohName}",
                    'detail' => $r->body ?: 'Tanpa ulasan teks',
                    'category' => 'sosok',
                    'date_iso' => $r->created_at?->format('Y-m-d'),
                    'status' => 'tercatat',
                    'created_at' => $r->created_at?->format('d M Y, H:i') ?? '-',
                    'timestamp' => $r->created_at?->timestamp ?? 0,
                ]);
            }
        }

        // 3. Partisipasi Survei
        if ($type === 'semua' || $type === 'survei') {
            $q = SurveyResponse::with(['user:id,name,email', 'user.roles:id,name', 'survey:id,title,slug']);
            if ($allowedUserIds !== null) {
                $q->whereIn('user_id', $allowedUserIds);
            }
            foreach ($q->orderByDesc('updated_at')->take(200)->get() as $s) {
                $actor = ($s->user_id === $user->id) ? 'Anda' : ($s->user?->name ?: 'Pengguna');
                $surveyTitle = $s->survey?->title ?: 'Survei Opini Publik';
                $actionText = $s->status === 'completed' ? 'telah mengisi survei' : 'menyimpan draft survei';
                $items->push([
                    'uid' => 'survei-' . $s->id,
                    'type' => 'survei',
                    'type_label' => 'Survei',
                    'user_name' => $s->user?->name ?: 'Pengguna',
                    'user_role' => $s->user?->roles?->first()?->name ?: 'member',
                    'actor_label' => $actor,
                    'action_label' => $actionText,
                    'target_label' => $surveyTitle,
                    'title' => "{$actor} {$actionText} \"{$surveyTitle}\"",
                    'detail' => $s->status === 'completed' ? 'Jawaban sudah dikirim' : 'Draft tersimpan',
                    'category' => strtolower($s->survey?->topic ?: 'survei'),
                    'date_iso' => $s->updated_at?->format('Y-m-d'),
                    'status' => $s->status === 'completed' ? 'selesai' : 'draft',
                    'created_at' => $s->updated_at?->format('d M Y, H:i') ?? '-',
                    'timestamp' => $s->updated_at?->timestamp ?? 0,
                ]);
            }
        }

        // 4. Report / Pengaduan
        if ($type === 'semua' || $type === 'report') {
            $q = MemberReport::with(['user:id,name,email', 'user.roles:id,name']);
            if ($allowedUserIds !== null) {
                $q->whereIn('user_id', $allowedUserIds);
            }
            foreach ($q->orderByDesc('created_at')->take(200)->get() as $rep) {
                $actor = ($rep->user_id === $user->id) ? 'Anda' : ($rep->user?->name ?: 'Pengguna');
                $items->push([
                    'uid' => 'report-' . $rep->id,
                    'type' => 'report',
                    'type_label' => 'Report',
                    'user_name' => $rep->user?->name ?: 'Pengguna',
                    'user_role' => $rep->user?->roles?->first()?->name ?: 'member',
                    'actor_label' => $actor,
                    'action_label' => 'mengirim report',
                    'target_label' => $rep->subject,
                    'title' => "{$actor} mengirim report \"{$rep->subject}\"",
                    'detail' => $rep->category_label ?: $rep->category,
                    'category' => strtolower($rep->category ?: 'lainnya'),
                    'date_iso' => $rep->created_at?->format('Y-m-d'),
                    'status' => $rep->status ?: 'pending',
                    'created_at' => $rep->created_at?->format('d M Y, H:i') ?? '-',
                    'timestamp' => $rep->created_at?->timestamp ?? 0,
                ]);
            }
        }

        if ($date !== '') {
            $items = $items->filter(fn ($it) => ($it['date_iso'] ?? '') === $date);
        }

        if ($category !== '' && $category !== 'semua') {
            $catNeedle = mb_strtolower($category);
            $items = $items->filter(function ($it) use ($catNeedle) {
                return str_contains(mb_strtolower((string) ($it['category'] ?? '')), $catNeedle)
                    || str_contains(mb_strtolower((string) ($it['detail'] ?? '')), $catNeedle);
            });
        }

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $items = $items->filter(function ($it) use ($needle) {
                return str_contains(mb_strtolower((string) $it['title']), $needle)
                    || str_contains(mb_strtolower((string) $it['user_name']), $needle)
                    || str_contains(mb_strtolower((string) $it['detail']), $needle);
            });
        }

        $sorted = $items->sortByDesc('timestamp')->values();
        $total = $sorted->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $sliced = $sorted->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'data' => $sliced,
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
            ],
            'scope' => $isSuperAdmin ? 'all' : ($isAdmin ? 'self_and_members' : 'self'),
        ]);
    }
}
