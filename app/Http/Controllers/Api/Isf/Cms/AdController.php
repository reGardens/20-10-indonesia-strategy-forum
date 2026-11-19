<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Controllers\Controller;
use App\Models\Isf\Ad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * CMS API — Kelola Iklan (tabel `isf_ads`).
 * Path tetap sama dengan versi lama: /api/cms/iklan, /iklan/{id}, /iklan/reorder, /iklan/{id}/toggle.
 */
class AdController extends Controller
{
    /** GET /api/cms/iklan */
    public function index(Request $request): JsonResponse
    {
        // Rapikan status tanpa menunggu cron: iklan aktif yang sudah lewat tanggal selesai → expired.
        Ad::expireOverdue();

        $query = Ad::query()->ordered();

        if ($placement = $request->query('placement')) {
            $query->where('placement', $placement);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if (($q = trim((string) $request->query('q', ''))) !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                    ->orWhere('kicker', 'like', "%{$q}%")
                    ->orWhere('target_url', 'like', "%{$q}%");
            });
        }

        $items = $query->get()->map(fn (Ad $ad) => $ad->toCmsArray())->values();

        return response()->json([
            'data' => $items,
            'items' => $items,
            'total' => $items->count(),
        ]);
    }

    /** POST /api/cms/iklan */
    public function store(Request $request): JsonResponse
    {
        $attributes = $this->attributes($this->validated($request));

        if (! array_key_exists('sort_order', $attributes)) {
            $attributes['sort_order'] = (int) Ad::query()->max('sort_order') + 1;
        }

        $ad = Ad::create($attributes);

        return response()->json(['data' => $ad->fresh()->toCmsArray(), 'message' => 'Iklan berhasil ditambahkan.'], 201);
    }

    /** GET /api/cms/iklan/{id} */
    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => $this->find($id)->toCmsArray()]);
    }

    /** PUT /api/cms/iklan/{id} */
    public function update(Request $request, int $id): JsonResponse
    {
        $ad = $this->find($id);
        $ad->update($this->attributes($this->validated($request)));

        return response()->json(['data' => $ad->fresh()->toCmsArray(), 'message' => 'Iklan berhasil diperbarui.']);
    }

    /** DELETE /api/cms/iklan/{id} */
    public function destroy(int $id): JsonResponse
    {
        $this->find($id)->delete();

        return response()->json(['message' => 'Iklan berhasil dihapus.']);
    }

    /** POST /api/cms/iklan/reorder — body: items[{id, order}] */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.order' => ['nullable', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['items'] as $idx => $entry) {
                Ad::query()->whereKey($entry['id'])->update([
                    'sort_order' => $entry['order'] ?? ($idx + 1),
                ]);
            }
        });

        return response()->json(['message' => 'Urutan iklan berhasil diperbarui.']);
    }

    /** POST /api/cms/iklan/{id}/toggle — Aktif ↔ Dijeda. */
    public function toggle(int $id): JsonResponse
    {
        $ad = $this->find($id);

        if ($ad->status === Ad::STATUS_ACTIVE) {
            $ad->status = Ad::STATUS_PAUSED;
        } else {
            if ($ad->end_at && $ad->end_at->lte(now())) {
                return response()->json([
                    'message' => 'Tanggal selesai iklan sudah lewat. Ubah jadwal penayangan terlebih dahulu.',
                    'errors' => ['end_at' => ['Tanggal selesai iklan sudah lewat.']],
                ], 422);
            }
            $ad->status = Ad::STATUS_ACTIVE;
        }
        $ad->save();

        return response()->json([
            'data' => $ad->toCmsArray(),
            'message' => 'Status iklan berhasil diperbarui.',
        ]);
    }

    private function find(int $id): Ad
    {
        $ad = Ad::query()->find($id);
        if (! $ad) {
            abort(404, 'Iklan tidak ditemukan.');
        }

        return $ad;
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'placement' => ['required', 'string', Rule::in(Ad::PLACEMENTS)],
            'type' => ['nullable', 'string', Rule::in(Ad::TYPES)],
            'kicker' => ['nullable', 'string', 'max:100'],
            'image_url' => ['nullable', 'string', 'max:1000'],
            'video_url' => ['nullable', 'string', 'max:1000'],
            'video_duration' => ['nullable', 'integer', 'min:5', 'max:86400'],
            'target_url' => ['nullable', 'string', 'max:1000'],
            'start_at' => ['nullable', 'date_format:Y-m-d H:i'],
            'end_at' => ['nullable', 'date_format:Y-m-d H:i', Rule::when($request->filled('start_at'), ['after:start_at'])],
            'active_hours_start' => ['nullable', 'date_format:H:i', 'required_with:active_hours_end'],
            'active_hours_end' => ['nullable', 'date_format:H:i', 'required_with:active_hours_start', 'different:active_hours_start'],
            'status' => ['nullable', 'string', Rule::in(Ad::STATUSES)],
            'order' => ['nullable', 'integer', 'min:0'],
            // Popup: jeda tampil pertama (detik) & tampil lagi (menit). Kosong = default 5 detik / 30 menit.
            'first_delay_seconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'interval_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'is_skippable' => ['nullable', 'boolean'],
            'skip_delay_seconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
        ], [
            'end_at.after' => 'Waktu selesai harus setelah waktu mulai.',
            'active_hours_end.different' => 'Jam selesai tayang harus berbeda dengan jam mulai.',
            'active_hours_start.required_with' => 'Isi jam mulai dan jam selesai tayang, atau kosongkan keduanya.',
            'active_hours_end.required_with' => 'Isi jam mulai dan jam selesai tayang, atau kosongkan keduanya.',
        ]);
    }

    /** Petakan input tervalidasi ke kolom tabel; field yang tidak relevan dengan penempatan dikosongkan. */
    private function attributes(array $v): array
    {
        $placement = $v['placement'];
        $isPopup = $placement === Ad::PLACEMENT_POPUP;
        $type = $isPopup ? ($v['type'] ?? 'banner') : 'banner';
        $isVideo = $isPopup && $type === 'video';

        $startAt = isset($v['start_at']) ? Carbon::createFromFormat('Y-m-d H:i', $v['start_at']) : null;
        $endAt = isset($v['end_at']) ? Carbon::createFromFormat('Y-m-d H:i', $v['end_at']) : null;

        $status = $v['status'] ?? Ad::STATUS_ACTIVE;
        // Sudah lewat tanggal selesai → langsung kedaluwarsa.
        if ($status === Ad::STATUS_ACTIVE && $endAt && $endAt->lte(now())) {
            $status = Ad::STATUS_EXPIRED;
        }

        $defaultSkipDelay = 10;

        $attributes = [
            'title' => $v['title'],
            'kicker' => ($v['kicker'] ?? null) ?: 'SPONSOR RESMI',
            'placement' => $placement,
            'type' => $type,
            'image_url' => $isVideo ? null : ($v['image_url'] ?? null),
            'video_url' => $isVideo ? ($v['video_url'] ?? null) : null,
            'video_duration' => $isVideo ? (isset($v['video_duration']) && $v['video_duration'] !== '' && $v['video_duration'] !== null ? (int) $v['video_duration'] : null) : null,
            'target_url' => $v['target_url'] ?? '/register',
            'start_at' => $startAt,
            'end_at' => $endAt,
            'active_hours_start' => Ad::normalizeTime($v['active_hours_start'] ?? null),
            'active_hours_end' => Ad::normalizeTime($v['active_hours_end'] ?? null),
            'status' => $status,
            'first_delay_seconds' => $isPopup ? ($v['first_delay_seconds'] ?? null) : null,
            'interval_minutes' => $isPopup ? ($v['interval_minutes'] ?? null) : null,
            'is_skippable' => $isPopup ? (isset($v['is_skippable']) ? (bool) $v['is_skippable'] : true) : true,
            'skip_delay_seconds' => $isPopup ? (isset($v['skip_delay_seconds']) && $v['skip_delay_seconds'] !== null && $v['skip_delay_seconds'] !== '' ? (int) $v['skip_delay_seconds'] : $defaultSkipDelay) : $defaultSkipDelay,
        ];

        if (isset($v['order'])) {
            $attributes['sort_order'] = (int) $v['order'];
        }

        return $attributes;
    }
}
