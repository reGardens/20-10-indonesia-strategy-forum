<?php

namespace App\Http\Controllers\Api\Isf;

use App\Http\Controllers\Controller;
use App\Models\Isf\Ad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * API publik iklan Beranda (tidak di-cache agar rotasi & jadwal jam tayang selalu akurat).
 *
 * - GET  /api/isf/ads?slot=spanduk → satu spanduk tayang, dipilih acak (atau null).
 * - GET  /api/isf/ads?slot=popup   → semua popup tayang, urut kolom Urutan (antrean popup).
 * - POST /api/isf/ads/{id}/impression | /click → tambah statistik tayangan / klik.
 */
class PublicAdController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slot' => ['required', 'string', 'in:' . implode(',', Ad::PLACEMENTS)],
        ]);

        $live = Ad::query()->live()->placement($validated['slot'])->ordered()->get();

        $data = $validated['slot'] === Ad::PLACEMENT_SPANDUK
            ? Ad::pickRandom($live)?->toPublicArray()
            : $live->map(fn (Ad $ad) => $ad->toPublicArray())->values()->all();

        return response()
            ->json(['data' => $data])
            ->header('Cache-Control', 'no-store, private');
    }

    public function impression(int $id): Response
    {
        return $this->track($id, 'impressions');
    }

    public function click(int $id): Response
    {
        return $this->track($id, 'clicks');
    }

    private function track(int $id, string $column): Response
    {
        // Hanya iklan yang sedang tayang yang dihitung.
        // toBase(): statistik tidak mengubah updated_at.
        Ad::query()->live()->whereKey($id)->toBase()->increment($column);

        return response()->noContent();
    }
}
