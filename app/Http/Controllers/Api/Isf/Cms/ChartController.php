<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\ChartRequest;
use App\Models\Isf\Chart;
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

    protected array $order = ['id' => 'desc'];

    /** Beranda chart currently shown on the public page. */
    protected function liveId(): ?int
    {
        return Chart::currentBeranda()?->id;
    }

    public function show(Chart $chart): JsonResponse
    {
        return $this->showModel($chart);
    }

    public function store(ChartRequest $request): JsonResponse
    {
        return $this->storeModel($request->validated());
    }

    public function update(ChartRequest $request, Chart $chart): JsonResponse
    {
        return $this->updateModel($chart, $request->validated());
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
