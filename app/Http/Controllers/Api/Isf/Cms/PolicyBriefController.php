<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\PolicyBriefRequest;
use App\Models\Isf\PolicyBrief;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * CMS API — Policy Brief (feature: policy_brief).
 */
class PolicyBriefController extends CmsResourceController
{
    protected string $model = PolicyBrief::class;

    protected array $listColumns = [
        'id', 'title', 'slug', 'sector', 'author', 'institution',
        'pdf_path', 'pdf_size', 'published_at', 'order', 'status', 'created_at', 'updated_at',
    ];

    protected array $searchColumns = ['title', 'author', 'institution', 'executive_summary'];

    protected array $filterColumns = ['sector', 'status'];

    protected array $order = ['order' => 'asc', 'published_at' => 'desc', 'id' => 'desc'];

    public function show(PolicyBrief $policyBrief): JsonResponse
    {
        return $this->showModel($policyBrief);
    }

    public function store(PolicyBriefRequest $request): JsonResponse
    {
        return $this->storeModel($request->validated());
    }

    public function update(PolicyBriefRequest $request, PolicyBrief $policyBrief): JsonResponse
    {
        return $this->updateModel($policyBrief, $request->validated());
    }

    public function destroy(PolicyBrief $policyBrief): Response
    {
        return $this->destroyModel($policyBrief);
    }
}

