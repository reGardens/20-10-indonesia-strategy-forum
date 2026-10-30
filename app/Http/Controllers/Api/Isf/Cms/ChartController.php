<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\ChartRequest;
use App\Models\Isf\Chart;
use App\Services\Isf\IsfCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * CMS API — Data Grafik (feature: data_grafik).
 */
class ChartController extends CmsResourceController
{
    protected string $model = Chart::class;

    protected array $listColumns = ['id', 'title', 'author_name', 'unit', 'order', 'is_beranda', 'status', 'updated_at'];

    protected array $searchColumns = ['title', 'author_name'];

    protected array $order = ['order' => 'asc', 'id' => 'asc'];

    /** Beranda chart currently shown on the public page. */
    protected function liveId(): ?int
    {
        return Chart::query()->where('is_beranda', true)->value('id');
    }

    public function show(Chart $chart): JsonResponse
    {
        return $this->showModel($chart);
    }

    public function store(ChartRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (!isset($data['order']) || $data['order'] === null || $data['order'] === '') {
            $data['order'] = ((int) Chart::max('order')) + 1;
        }
        return $this->storeModel($data);
    }

    public function update(ChartRequest $request, Chart $chart): JsonResponse
    {
        return $this->updateModel($chart, $request->validated());
    }

    public function reorder(\Illuminate\Http\Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer',
            'items.*.order' => 'required|integer',
        ]);

        foreach ($validated['items'] as $item) {
            Chart::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        IsfCacheService::clearBeranda();

        return response()->json(['message' => 'Urutan grafik berhasil diperbarui.']);
    }

    public function destroy(Chart $chart): Response
    {
        return $this->destroyModel($chart);
    }

    /**
     * GET /api/cms/options/charts — {id, title, status} list for the isu "Visual Kunci" select.
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'data' => Chart::query()->orderBy('title')->get(['id', 'title', 'status']),
        ]);
    }
}
