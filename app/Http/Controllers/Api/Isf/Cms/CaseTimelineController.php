<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\CaseTimelineRequest;
use App\Models\Isf\CaseEvent;
use App\Models\Isf\CaseTimeline;
use App\Services\Isf\IsfCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * CMS API — Timeline Kasus Hukum (feature: timeline_kasus).
 */
class CaseTimelineController extends CmsResourceController
{
    protected string $model = CaseTimeline::class;

    protected array $listColumns = [
        'id', 'judul_kasus', 'slug', 'kategori_kasus', 'status_kasus',
        'gambar', 'urutan', 'status', 'created_at', 'updated_at',
    ];

    protected array $searchColumns = ['judul_kasus', 'kategori_kasus', 'deskripsi'];

    protected array $filterColumns = ['kategori_kasus', 'status_kasus', 'status'];

    protected array $order = ['urutan' => 'asc', 'id' => 'desc'];

    public function show(CaseTimeline $caseTimeline): JsonResponse
    {
        $caseTimeline->load(['events' => function ($q) {
            $q->orderBy('tanggal_peristiwa', 'asc')->orderBy('urutan', 'asc');
        }]);

        return response()->json(['data' => $caseTimeline]);
    }

    public function store(CaseTimelineRequest $request): JsonResponse
    {
        $data = $request->validated();
        $eventsData = $data['events'] ?? [];
        unset($data['events']);

        $timeline = DB::transaction(function () use ($data, $eventsData) {
            $timeline = CaseTimeline::create($data);

            if (!empty($eventsData)) {
                foreach ($eventsData as $idx => $event) {
                    $timeline->events()->create([
                        'tanggal_peristiwa' => $event['tanggal_peristiwa'],
                        'tahap' => $event['tahap'],
                        'judul' => $event['judul'],
                        'deskripsi' => $event['deskripsi'] ?? null,
                        'sumber_tautan' => $event['sumber_tautan'] ?? null,
                        'urutan' => $event['urutan'] ?? ($idx + 1),
                        'status' => $event['status'] ?? 'terbit',
                    ]);
                }
            }

            return $timeline;
        });

        IsfCacheService::clearAll();
        $timeline->load('events');

        return response()->json(['data' => $timeline], Response::HTTP_CREATED);
    }

    public function update(CaseTimelineRequest $request, CaseTimeline $caseTimeline): JsonResponse
    {
        $data = $request->validated();
        $eventsData = $data['events'] ?? null;
        unset($data['events']);

        DB::transaction(function () use ($caseTimeline, $data, $eventsData) {
            $caseTimeline->update($data);

            if ($eventsData !== null) {
                // Delete removed events
                $keptIds = array_filter(array_column($eventsData, 'id'));
                $caseTimeline->events()->whereNotIn('id', $keptIds)->delete();

                foreach ($eventsData as $idx => $event) {
                    $eventId = $event['id'] ?? null;
                    $payload = [
                        'tanggal_peristiwa' => $event['tanggal_peristiwa'],
                        'tahap' => $event['tahap'],
                        'judul' => $event['judul'],
                        'deskripsi' => $event['deskripsi'] ?? null,
                        'sumber_tautan' => $event['sumber_tautan'] ?? null,
                        'urutan' => $event['urutan'] ?? ($idx + 1),
                        'status' => $event['status'] ?? 'terbit',
                    ];

                    if ($eventId && $existing = $caseTimeline->events()->find($eventId)) {
                        $existing->update($payload);
                    } else {
                        $caseTimeline->events()->create($payload);
                    }
                }
            }
        });

        IsfCacheService::clearAll();
        $caseTimeline->load(['events' => function ($q) {
            $q->orderBy('tanggal_peristiwa', 'asc')->orderBy('urutan', 'asc');
        }]);

        return response()->json(['data' => $caseTimeline]);
    }

    public function destroy(CaseTimeline $caseTimeline): Response
    {
        return $this->destroyModel($caseTimeline);
    }
}
