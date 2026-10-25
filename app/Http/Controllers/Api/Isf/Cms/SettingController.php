<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Isf\SettingRequest;
use App\Models\Isf\Setting;
use Illuminate\Http\JsonResponse;

/**
 * CMS API — Pengaturan Halaman (feature: pengaturan_halaman).
 * Page-level copy: Beranda top bar + "Pekan Ini" period, sample member profile.
 */
class SettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(['data' => $this->current()]);
    }

    public function update(SettingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        Setting::put('beranda', $validated['beranda']);
        Setting::put('anggota', $validated['anggota']);

        return response()->json(['data' => $this->current()]);
    }

    private function current(): array
    {
        return [
            'beranda' => Setting::get('beranda', [
                'topBar' => ['badge' => '', 'announcement' => '', 'info' => ''],
                'pekanIni' => ['dateStart' => '', 'dateEnd' => ''],
            ]),
            'anggota' => Setting::get('anggota', [
                'name' => '', 'role' => '', 'organization' => '', 'plan' => '', 'sektor' => [],
            ]),
        ];
    }
}
