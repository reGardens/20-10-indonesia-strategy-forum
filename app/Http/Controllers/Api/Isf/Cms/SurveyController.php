<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\SurveyRequest;
use App\Models\Isf\Survey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * CMS API — Manajemen Survei (feature: survei).
 */
class SurveyController extends CmsResourceController
{
    protected string $model = Survey::class;

    protected array $listColumns = [
        'id', 'title', 'slug', 'institution', 'is_isf', 'topic', 'region',
        'survey_period', 'release_date', 'sample_size', 'margin_of_error',
        'headline_stat_value', 'headline_stat_label', 'is_featured', 'order', 'status', 'created_at', 'updated_at',
    ];

    protected array $searchColumns = ['title', 'institution', 'summary', 'expert_analysis', 'headline_stat_label'];

    protected array $filterColumns = ['topic', 'region', 'is_isf', 'is_featured', 'status'];

    protected array $order = ['is_featured' => 'desc', 'order' => 'asc', 'release_date' => 'desc', 'id' => 'desc'];

    public function show(Survey $survei): JsonResponse
    {
        return $this->showModel($survei);
    }

    public function store(SurveyRequest $request): JsonResponse
    {
        return $this->storeModel($request->validated());
    }

    public function update(SurveyRequest $request, Survey $survei): JsonResponse
    {
        return $this->updateModel($survei, $request->validated());
    }

    public function destroy(Survey $survei): Response
    {
        return $this->destroyModel($survei);
    }
}

