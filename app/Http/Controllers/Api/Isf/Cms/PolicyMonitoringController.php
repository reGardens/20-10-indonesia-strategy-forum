<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\PolicyTrackerRequest;
use App\Models\Isf\PolicyTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * CMS API — Monitoring Kebijakan (feature: monitoring_kebijakan).
 */
class PolicyMonitoringController extends CmsResourceController
{
    protected string $model = PolicyTracker::class;

    protected array $listColumns = [
        'id', 'program_name', 'slug', 'sector', 'progress_percent',
        'status_label', 'last_updated_date', 'source_title', 'order', 'status', 'created_at', 'updated_at',
    ];

    protected array $searchColumns = ['program_name', 'promise', 'realization', 'source_title'];

    protected array $filterColumns = ['sector', 'status_label', 'status'];

    protected array $order = ['order' => 'asc', 'last_updated_date' => 'desc', 'id' => 'desc'];

    public function show(PolicyTracker $policyMonitoring): JsonResponse
    {
        return $this->showModel($policyMonitoring);
    }

    public function store(PolicyTrackerRequest $request): JsonResponse
    {
        return $this->storeModel($request->validated());
    }

    public function update(PolicyTrackerRequest $request, PolicyTracker $policyMonitoring): JsonResponse
    {
        return $this->updateModel($policyMonitoring, $request->validated());
    }

    public function destroy(PolicyTracker $policyMonitoring): Response
    {
        return $this->destroyModel($policyMonitoring);
    }
}

