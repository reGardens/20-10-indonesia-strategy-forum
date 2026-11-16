<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\TokohRequest;
use App\Models\Isf\Tokoh;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * CMS API — Tokoh (feature: tokoh).
 */
class TokohController extends CmsResourceController
{
    protected string $model = Tokoh::class;

    protected array $listColumns = ['id', 'slug', 'name', 'position', 'role_category', 'influence_score', 'is_featured', 'is_influential_weekly', 'show_in_politik', 'status', 'updated_at'];

    protected array $searchColumns = ['name', 'slug', 'position', 'summary_analysis'];

    protected array $filterColumns = ['status', 'role_category', 'show_in_politik', 'is_influential_weekly'];

    protected array $order = ['id' => 'desc'];

    /** "Tokoh Pekan Ini" currently shown on the public pages. */
    protected function liveId(): ?int
    {
        return Tokoh::currentFeatured()?->id;
    }

    public function show(Tokoh $tokoh): JsonResponse
    {
        return $this->showModel($tokoh);
    }

    public function store(TokohRequest $request): JsonResponse
    {
        return $this->storeModel($request->validated());
    }

    public function update(TokohRequest $request, Tokoh $tokoh): JsonResponse
    {
        return $this->updateModel($tokoh, $request->validated());
    }

    public function destroy(Tokoh $tokoh): Response
    {
        return $this->destroyModel($tokoh);
    }
}
