<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\AlertRequest;
use App\Models\Isf\Alert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * CMS API — Alert Peringatan Dini (feature: alert).
 */
class AlertController extends CmsResourceController
{
    protected string $model = Alert::class;

    protected array $listColumns = ['id', 'title', 'priority', 'date', 'sektor', 'status', 'updated_at'];

    protected array $searchColumns = ['title', 'sektor'];

    protected array $order = ['date' => 'desc', 'id' => 'desc'];

    public function show(Alert $alert): JsonResponse
    {
        return $this->showModel($alert);
    }

    public function store(AlertRequest $request): JsonResponse
    {
        return $this->storeModel($request->validated());
    }

    public function update(AlertRequest $request, Alert $alert): JsonResponse
    {
        return $this->updateModel($alert, $request->validated());
    }

    public function destroy(Alert $alert): Response
    {
        return $this->destroyModel($alert);
    }
}
