<?php

namespace App\Http\Controllers\Api\Isf;

use App\Http\Controllers\Controller;
use App\Models\Isf\Alert;
use App\Models\Isf\Article;
use App\Models\Isf\Letter;
use App\Models\User;
use App\Services\IsfPushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PushNotificationController extends Controller
{
    public function __construct(
        protected IsfPushNotificationService $pushService
    ) {
    }

    /**
     * Return public VAPID key.
     */
    public function vapidKey(): JsonResponse
    {
        $key = $this->pushService->getVapidPublicKey();

        return response()->json([
            'public_key' => $key,
            'enabled' => !empty($key),
        ]);
    }

    /**
     * Subscribe endpoint to web push notifications.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'url'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
            'content_encoding' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $subscription = $this->pushService->subscribe($validated, $user);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil berlangganan notifikasi web push.',
            'id' => $subscription->id,
        ]);
    }

    /**
     * Unsubscribe endpoint.
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string'],
        ]);

        $this->pushService->unsubscribe($validated['endpoint']);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil berhenti berlangganan notifikasi web push.',
        ]);
    }

    /**
     * Test notification.
     */
    public function sendTest(Request $request): JsonResponse
    {
        $user = $request->user();
        $title = $request->input('title', 'Indonesia Strategy Forum');
        $body = $request->input('body', 'Uji coba notifikasi web push berhasil terhubung!');
        $url = $request->input('url', url('/'));

        if ($user && $user->pushSubscriptions()->exists()) {
            $result = $this->pushService->sendToUser($user, $title, $body, $url);
        } else {
            $result = $this->pushService->broadcast($title, $body, $url);
        }

        return response()->json([
            'success' => true,
            'result' => $result,
        ]);
    }

    /**
     * Mark single notification or all notifications as read.
     */
    public function markRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $id = $request->input('id');
        $ids = (array) $request->input('ids', []);
        $all = $request->boolean('all');

        $readIds = $this->getReadNotificationIds($user, $request);
        $allReadAt = null;

        if ($all) {
            $allReadAt = time();
            $this->storeAllReadAt($user, $request, $allReadAt);
        }

        $toAdd = array_filter(array_merge([$id], $ids));
        foreach ($toAdd as $singleId) {
            $strId = (string) $singleId;
            if ($strId !== '' && !in_array($strId, $readIds, true)) {
                $readIds[] = $strId;
            }
        }
        $this->storeReadNotificationIds($user, $request, $readIds);

        return response()->json([
            'success' => true,
            'read_ids' => $readIds,
            'all_read_at' => $allReadAt,
        ]);
    }

    /**
     * Get recent notifications/alerts for CMS & Landing Page.
     */
    public function notifications(Request $request): JsonResponse
    {
        $user = $request->user();
        $isMember = $user && $user->hasRole('member') && ! $user->hasAnyRole(['admin', 'super-admin', 'editor']);

        $readIds = $this->getReadNotificationIds($user, $request);
        $allReadAt = $this->getAllReadAt($user, $request);

        if ($request->filled('client_read_ids')) {
            $clientIds = array_filter(explode(',', (string) $request->input('client_read_ids')));
            $readIds = array_values(array_unique(array_merge($readIds, $clientIds)));
        }

        $notifications = [];

        if ($isMember) {
            // Role member: HANYA notifikasi yang berkaitan dengan artikel/naskah yang dibuat oleh member tersebut
            try {
                $letters = Letter::where('user_id', $user->id)
                    ->orderByDesc('updated_at')
                    ->take(10)
                    ->get();

                foreach ($letters as $letter) {
                    if ($letter->moderation_status === 'disetujui') {
                        $notifications[] = [
                            'id' => 'letter-approved-' . $letter->id,
                            'type' => 'approved',
                            'title' => 'Naskah Disetujui & Terbit',
                            'message' => 'Naskah "' . Str::limit($letter->title, 45) . '" telah disetujui dewan redaksi dan terbit.',
                            'url' => $letter->link_url ?: ('/artikel/' . ($letter->category ?: 'opini') . '/' . $letter->slug),
                            'created_at' => $letter->moderated_at?->diffForHumans() ?? $letter->updated_at?->diffForHumans() ?? 'Baru saja',
                            'timestamp' => $letter->moderated_at?->timestamp ?? $letter->updated_at?->timestamp ?? time(),
                        ];
                    } elseif ($letter->moderation_status === 'ditolak') {
                        $notifications[] = [
                            'id' => 'letter-rejected-' . $letter->id,
                            'type' => 'rejected',
                            'title' => 'Naskah Belum Disetujui',
                            'message' => 'Naskah "' . Str::limit($letter->title, 40) . '" belum disetujui: ' . Str::limit($letter->moderation_notes ?: 'Periksa catatan redaksi.', 45),
                            'url' => '/cms/artikel',
                            'created_at' => $letter->moderated_at?->diffForHumans() ?? $letter->updated_at?->diffForHumans() ?? 'Baru saja',
                            'timestamp' => $letter->moderated_at?->timestamp ?? $letter->updated_at?->timestamp ?? time(),
                        ];
                    } else {
                        $notifications[] = [
                            'id' => 'letter-pending-' . $letter->id,
                            'type' => 'pending',
                            'title' => 'Naskah Dalam Tinjauan Redaksi',
                            'message' => 'Naskah "' . Str::limit($letter->title, 45) . '" sedang menunggu antrean moderasi.',
                            'url' => '/cms/artikel',
                            'created_at' => $letter->created_at?->diffForHumans() ?? 'Baru saja',
                            'timestamp' => $letter->created_at?->timestamp ?? time(),
                        ];
                    }
                }
            } catch (\Throwable) {}

            try {
                $articles = Article::where('user_id', $user->id)
                    ->where('is_by_member', true)
                    ->where('status', 'terbit')
                    ->orderByDesc('updated_at')
                    ->take(5)
                    ->get();

                foreach ($articles as $article) {
                    $already = collect($notifications)->first(fn($n) => str_contains($n['message'], Str::limit($article->title, 25)));
                    if (! $already) {
                        $notifications[] = [
                            'id' => 'article-pub-' . $article->id,
                            'type' => 'approved',
                            'title' => 'Naskah Anda Telah Terbit',
                            'message' => 'Naskah "' . Str::limit($article->title, 45) . '" telah tayang di Indonesia Strategy Forum.',
                            'url' => $article->link_url ?: ('/artikel/' . $article->category . '/' . $article->slug),
                            'created_at' => $article->updated_at?->diffForHumans() ?? 'Baru saja',
                            'timestamp' => $article->updated_at?->timestamp ?? time(),
                        ];
                    }
                }
            } catch (\Throwable) {}
        } else {
            // Redaksi / Admin / Super Admin
            // 1. Pending member articles / letters needing moderation
            try {
                $pendingLetters = Letter::where('moderation_status', 'pending')
                    ->orderByDesc('created_at')
                    ->take(5)
                    ->get();

                foreach ($pendingLetters as $letter) {
                    $notifications[] = [
                        'id' => 'letter-' . $letter->id,
                        'type' => 'moderation',
                        'title' => 'Review Naskah Member',
                        'message' => 'Naskah "' . Str::limit($letter->title, 45) . '" oleh ' . ($letter->sender_name ?? 'Member'),
                        'url' => '/cms/surat-pembaca/' . $letter->id,
                        'created_at' => $letter->created_at?->diffForHumans() ?? 'Baru saja',
                        'timestamp' => $letter->created_at?->timestamp ?? time(),
                    ];
                }
            } catch (\Throwable) {}

            // 2. High priority alerts
            try {
                $alerts = Alert::where('priority', 'Tinggi')
                    ->orderByDesc('id')
                    ->take(3)
                    ->get();

                foreach ($alerts as $alert) {
                    $notifications[] = [
                        'id' => 'alert-' . $alert->id,
                        'type' => 'alert',
                        'title' => 'Peringatan Regulasi Prioritas',
                        'message' => Str::limit($alert->title, 55),
                        'url' => '/cms/alert',
                        'created_at' => $alert->created_at?->diffForHumans() ?? 'Baru saja',
                        'timestamp' => $alert->created_at?->timestamp ?? time(),
                    ];
                }
            } catch (\Throwable) {}

            // 3. Latest published articles
            try {
                $recentArticles = Article::where('status', 'terbit')
                    ->orderByDesc('published_at')
                    ->orderByDesc('id')
                    ->take(5)
                    ->get();

                foreach ($recentArticles as $article) {
                    $notifications[] = [
                        'id' => 'article-' . $article->id,
                        'type' => 'article',
                        'title' => 'Artikel Terbit',
                        'message' => Str::limit($article->title, 55),
                        'url' => '/cms/artikel/' . $article->id . '/edit',
                        'created_at' => $article->created_at?->diffForHumans() ?? 'Baru saja',
                        'timestamp' => $article->created_at?->timestamp ?? time(),
                    ];
                }
            } catch (\Throwable) {}
        }

        // Apply unread status based on persisted read state
        foreach ($notifications as &$item) {
            $itemId = (string) ($item['id'] ?? '');
            $itemTime = (int) ($item['timestamp'] ?? 0);
            $isRead = in_array($itemId, $readIds, true) || ($allReadAt && $itemTime <= $allReadAt);
            $item['unread'] = ! $isRead;
        }
        unset($item);

        usort($notifications, fn($a, $b) => ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0));

        $unreadCount = count(array_filter($notifications, fn($n) => $n['unread'] ?? false));

        return response()->json([
            'notifications' => array_slice($notifications, 0, 10),
            'unread_count' => $unreadCount,
        ], 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    protected function getReadNotificationIds(?User $user, Request $request): array
    {
        if ($user) {
            $key = "isf_notif_read_user_{$user->id}";
            return Cache::get($key, []);
        }
        return $request->session()->get('isf_notif_read_ids', []);
    }

    protected function storeReadNotificationIds(?User $user, Request $request, array $ids): void
    {
        $ids = array_values(array_slice(array_unique($ids), -100));
        if ($user) {
            $key = "isf_notif_read_user_{$user->id}";
            Cache::put($key, $ids, now()->addDays(30));
        } else {
            $request->session()->put('isf_notif_read_ids', $ids);
        }
    }

    protected function getAllReadAt(?User $user, Request $request): ?int
    {
        if ($user) {
            $key = "isf_notif_all_read_user_{$user->id}";
            return Cache::get($key);
        }
        return $request->session()->get('isf_notif_all_read_at');
    }

    protected function storeAllReadAt(?User $user, Request $request, int $timestamp): void
    {
        if ($user) {
            $key = "isf_notif_all_read_user_{$user->id}";
            Cache::put($key, $timestamp, now()->addDays(30));
        } else {
            $request->session()->put('isf_notif_all_read_at', $timestamp);
        }
    }
}
