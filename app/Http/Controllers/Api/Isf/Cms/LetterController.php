<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\LetterModerationRequest;
use App\Mail\ArticleRejectionMail;
use App\Models\Isf\Article;
use App\Models\Isf\Letter;
use App\Services\Isf\IsfCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * CMS API — Moderasi Surat Pembaca (feature: surat_pembaca).
 */
class LetterController extends CmsResourceController
{
    protected string $model = Letter::class;

    protected array $listColumns = [
        'id', 'user_id', 'sender_name', 'sender_email', 'sender_city', 'title', 'slug',
        'category', 'subcategory', 'summary', 'body', 'author_role', 'author_avatar',
        'reading_minutes', 'image_url', 'image_alt', 'link_url', 'fact_blocks',
        'moderation_status', 'moderated_at', 'moderated_by', 'published_at',
        'order', 'status', 'created_at', 'updated_at',
    ];

    protected array $searchColumns = ['sender_name', 'sender_email', 'title', 'body', 'summary', 'sender_city', 'author_role', 'category'];

    protected array $filterColumns = ['moderation_status', 'status', 'category'];

    protected array $order = ['created_at' => 'desc', 'id' => 'desc'];

    public function show(Letter $suratPembaca): JsonResponse
    {
        return $this->showModel($suratPembaca);
    }

    public function update(LetterModerationRequest $request, Letter $suratPembaca): JsonResponse
    {
        $validated = $request->validated();
        $newStatus = $validated['moderation_status'] ?? $suratPembaca->moderation_status;

        if ($suratPembaca->moderation_status === 'ditolak' && $newStatus !== 'ditolak') {
            return response()->json([
                'success' => false,
                'message' => 'Artikel yang sudah ditolak tidak dapat disetujui atau diubah statusnya lagi.',
            ], 422);
        }

        if ($newStatus === 'disetujui') {
            $user = $request->user();
            if (! $user || (! $user->hasRole('admin') && ! $user->hasRole('super-admin'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya pengguna dengan role Admin atau Super Admin yang berwenang menyetujui artikel member.',
                ], 403);
            }
        }

        $payload = [
            ...$validated,
            'moderated_at' => now(),
            'moderated_by' => $request->user()?->id,
        ];

        if ($newStatus === 'disetujui' && empty($suratPembaca->published_at)) {
            $payload['published_at'] = now();
        }

        if ($newStatus === 'ditolak') {
            // Hapus dari isf_articles jika sebelumnya ada
            Article::where(function ($q) use ($suratPembaca) {
                if ($suratPembaca->slug) {
                    $q->where('slug', $suratPembaca->slug);
                }
                $q->orWhere('title', $suratPembaca->title);
            })->where('is_by_member', true)->delete();
            IsfCacheService::clearBeranda();

            $reason = $payload['moderation_notes'] ?? $suratPembaca->moderation_notes;
            if ($suratPembaca->sender_email && $reason) {
                try {
                    Mail::to($suratPembaca->sender_email)->send(new ArticleRejectionMail($suratPembaca, (string) $reason));
                } catch (\Throwable $e) {
                    Log::warning('Gagal kirim email penolakan: '.$e->getMessage());
                }
            }
        }

        return $this->updateModel($suratPembaca, $payload);
    }

    public function destroy(Letter $suratPembaca): Response
    {
        return $this->destroyModel($suratPembaca);
    }

    public function approve(Request $request, Letter $suratPembaca): JsonResponse
    {
        $user = $request->user();
        if (! $user || (! $user->hasRole('admin') && ! $user->hasRole('super-admin'))) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pengguna dengan role Admin atau Super Admin yang berwenang menyetujui artikel member.',
            ], 403);
        }

        if ($suratPembaca->moderation_status === 'ditolak') {
            return response()->json([
                'success' => false,
                'message' => 'Artikel yang sudah ditolak tidak dapat disetujui lagi.',
            ], 422);
        }

        $publishedAt = $suratPembaca->published_at ?? now();

        $suratPembaca->update([
            'moderation_status' => 'disetujui',
            'moderation_notes' => $request->input('notes') ?? $request->input('catatan_redaksi') ?? $suratPembaca->moderation_notes,
            'moderated_at' => now(),
            'moderated_by' => $user->id,
            'published_at' => $publishedAt,
        ]);

        // Masukkan ke tabel artikel (isf_articles) dan ke landing page dengan kategorinya
        $slug = $suratPembaca->slug ?: Str::slug($suratPembaca->title);
        if (! $slug) {
            $slug = 'artikel-member-'.$suratPembaca->id;
        }

        $existingArticle = Article::where('slug', $slug)->first();
        if ($existingArticle && ! $existingArticle->is_by_member && $existingArticle->id !== $suratPembaca->id) {
            $slug = $slug.'-member-'.$suratPembaca->id;
        }

        $category = $suratPembaca->category ?: 'opini';
        $subcategory = $suratPembaca->subcategory ?: 'surat-pembaca';

        Article::updateOrCreate(
            ['slug' => $slug],
            [
                'title' => $suratPembaca->title,
                'category' => $category,
                'subcategory' => $subcategory,
                'published_at' => $publishedAt,
                'summary' => $suratPembaca->summary ?: Str::limit(strip_tags($suratPembaca->body), 200),
                'body' => $suratPembaca->body,
                'author_name' => $suratPembaca->author_name ?: $suratPembaca->sender_name,
                'author_role' => $suratPembaca->author_role,
                'author_avatar' => $suratPembaca->author_avatar,
                'reading_minutes' => $suratPembaca->reading_minutes ?: 5,
                'image_url' => $suratPembaca->image_url,
                'image_alt' => $suratPembaca->image_alt,
                'link_url' => $suratPembaca->link_url,
                'fact_blocks' => $suratPembaca->fact_blocks,
                'status' => 'terbit',
                'is_by_member' => true,
            ]
        );

        IsfCacheService::clearBeranda();

        return response()->json([
            'success' => true,
            'message' => 'Artikel by member berhasil disetujui untuk tayang.',
            'data' => $suratPembaca->fresh(),
        ]);
    }

    public function reject(Request $request, Letter $suratPembaca): JsonResponse
    {
        $reason = $request->input('notes') ?? $request->input('reason') ?? $request->input('catatan_redaksi') ?? $suratPembaca->moderation_notes;

        $suratPembaca->update([
            'moderation_status' => 'ditolak',
            'status' => 'draft',
            'moderation_notes' => $reason,
            'moderated_at' => now(),
            'moderated_by' => $request->user()?->id,
        ]);

        // Hapus dari isf_articles jika sebelumnya sempat disetujui / masuk ke tabel artikel
        Article::where(function ($q) use ($suratPembaca) {
            if ($suratPembaca->slug) {
                $q->where('slug', $suratPembaca->slug);
            }
            $q->orWhere('title', $suratPembaca->title);
        })->where('is_by_member', true)->delete();

        IsfCacheService::clearBeranda();

        // Kirim email penolakan dengan alasan evaluasi redaksi
        if ($suratPembaca->sender_email) {
            try {
                Mail::to($suratPembaca->sender_email)->send(new ArticleRejectionMail($suratPembaca, (string) $reason));
            } catch (\Throwable $e) {
                Log::warning('Gagal kirim email penolakan artikel ke '.$suratPembaca->sender_email.': '.$e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Artikel by member ditolak dan catatan evaluasi telah dikirimkan ke email penulis.',
            'data' => $suratPembaca->fresh(),
        ]);
    }
}
