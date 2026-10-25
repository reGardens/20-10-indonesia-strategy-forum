<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\IsuRequest;
use App\Models\Isf\Isu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * CMS API — Isu Mingguan (feature: isu).
 */
class IsuController extends CmsResourceController
{
    protected string $model = Isu::class;

    protected array $listColumns = ['id', 'slug', 'title', 'category', 'published_at', 'is_weekly', 'status', 'updated_at'];

    protected array $searchColumns = ['title', 'slug'];

    protected array $order = ['published_at' => 'desc', 'id' => 'desc'];

    /** "Anatomi Isu" of the week currently shown on the public pages. */
    protected function liveId(): ?int
    {
        return Isu::currentWeekly()?->id;
    }

    public function show(Isu $isu): JsonResponse
    {
        return $this->showModel($isu);
    }

    public function store(IsuRequest $request): JsonResponse
    {
        return $this->storeModel($request->validated());
    }

    public function update(IsuRequest $request, Isu $isu): JsonResponse
    {
        return $this->updateModel($isu, $request->validated());
    }

    public function destroy(Isu $isu): Response
    {
        return $this->destroyModel($isu);
    }
}
