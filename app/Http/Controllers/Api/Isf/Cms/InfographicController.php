<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\InfographicRequest;
use App\Models\Isf\Infographic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * CMS API — Infografik (feature: infografik).
 */
class InfographicController extends CmsResourceController
{
    protected string $model = Infographic::class;

    protected array $listColumns = ['id', 'slug', 'title', 'category', 'published_at', 'status', 'updated_at'];

    protected array $searchColumns = ['title', 'slug'];

    protected array $order = ['id' => 'desc'];

    public function show(Infographic $infographic): JsonResponse
    {
        return $this->showModel($infographic);
    }

    public function store(InfographicRequest $request): JsonResponse
    {
        return $this->storeModel($request->validated());
    }

    public function update(InfographicRequest $request, Infographic $infographic): JsonResponse
    {
        return $this->updateModel($infographic, $request->validated());
    }

    public function destroy(Infographic $infographic): Response
    {
        return $this->destroyModel($infographic);
    }
}
