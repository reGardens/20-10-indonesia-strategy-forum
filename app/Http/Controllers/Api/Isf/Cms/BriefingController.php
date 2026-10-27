<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\BriefingRequest;
use App\Models\Isf\Briefing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * CMS API — Email Briefing (feature: email_briefing).
 */
class BriefingController extends CmsResourceController
{
    protected string $model = Briefing::class;

    protected array $listColumns = ['id', 'tanggal', 'penerima', 'organisasi', 'ringkasan_judul', 'status', 'updated_at'];

    protected array $searchColumns = ['ringkasan_judul', 'penerima', 'organisasi'];

    protected array $order = ['id' => 'desc'];

    public function show(Briefing $briefing): JsonResponse
    {
        return $this->showModel($briefing);
    }

    public function store(BriefingRequest $request): JsonResponse
    {
        return $this->storeModel($request->validated());
    }

    public function update(BriefingRequest $request, Briefing $briefing): JsonResponse
    {
        return $this->updateModel($briefing, $request->validated());
    }

    public function destroy(Briefing $briefing): Response
    {
        return $this->destroyModel($briefing);
    }
}
