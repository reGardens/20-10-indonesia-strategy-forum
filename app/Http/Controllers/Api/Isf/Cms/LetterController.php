<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\LetterModerationRequest;
use App\Models\Isf\Letter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * CMS API — Moderasi Surat Pembaca (feature: surat_pembaca).
 */
class LetterController extends CmsResourceController
{
    protected string $model = Letter::class;

    protected array $listColumns = [
        'id', 'sender_name', 'sender_email', 'sender_city', 'title', 'slug',
        'moderation_status', 'moderated_at', 'moderated_by', 'published_at',
        'order', 'status', 'created_at', 'updated_at',
    ];

    protected array $searchColumns = ['sender_name', 'sender_email', 'title', 'body', 'sender_city'];

    protected array $filterColumns = ['moderation_status', 'status'];

    protected array $order = ['created_at' => 'desc', 'id' => 'desc'];

    public function show(Letter $suratPembaca): JsonResponse
    {
        return $this->showModel($suratPembaca);
    }

    public function update(LetterModerationRequest $request, Letter $suratPembaca): JsonResponse
    {
        $validated = $request->validated();
        $newStatus = $validated['moderation_status'] ?? $suratPembaca->moderation_status;

        $payload = [
            ...$validated,
            'moderated_at' => now(),
            'moderated_by' => $request->user()?->id,
        ];

        if ($newStatus === 'disetujui' && empty($suratPembaca->published_at)) {
            $payload['published_at'] = now();
        }

        return $this->updateModel($suratPembaca, $payload);
    }

    public function destroy(Letter $suratPembaca): Response
    {
        return $this->destroyModel($suratPembaca);
    }

    public function approve(Request $request, Letter $suratPembaca): JsonResponse
    {
        $suratPembaca->update([
            'moderation_status' => 'disetujui',
            'moderation_notes' => $request->input('notes') ?? $request->input('catatan_redaksi') ?? $suratPembaca->moderation_notes,
            'moderated_at' => now(),
            'moderated_by' => $request->user()?->id,
            'published_at' => $suratPembaca->published_at ?? now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Surat pembaca berhasil disetujui.',
            'data' => $suratPembaca->fresh(),
        ]);
    }

    public function reject(Request $request, Letter $suratPembaca): JsonResponse
    {
        $suratPembaca->update([
            'moderation_status' => 'ditolak',
            'moderation_notes' => $request->input('notes') ?? $request->input('catatan_redaksi') ?? $suratPembaca->moderation_notes,
            'moderated_at' => now(),
            'moderated_by' => $request->user()?->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Surat pembaca ditolak.',
            'data' => $suratPembaca->fresh(),
        ]);
    }
}
