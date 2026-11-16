<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\LegalGlossaryRequest;
use App\Models\Isf\LegalGlossary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * CMS API — Glosarium Hukum (feature: glosarium_hukum).
 */
class LegalGlossaryController extends CmsResourceController
{
    protected string $model = LegalGlossary::class;

    protected array $listColumns = [
        'id', 'istilah', 'slug', 'abjad', 'definisi',
        'dasar_hukum', 'istilah_terkait', 'urutan', 'status', 'updated_at',
    ];

    protected array $searchColumns = ['istilah', 'definisi', 'dasar_hukum'];

    protected array $filterColumns = ['abjad', 'status'];

    protected array $order = ['abjad' => 'asc', 'istilah' => 'asc', 'urutan' => 'asc', 'id' => 'asc'];

    public function show(LegalGlossary $legalGlossary): JsonResponse
    {
        return $this->showModel($legalGlossary);
    }

    public function store(LegalGlossaryRequest $request): JsonResponse
    {
        return $this->storeModel($request->validated());
    }

    public function update(LegalGlossaryRequest $request, LegalGlossary $legalGlossary): JsonResponse
    {
        return $this->updateModel($legalGlossary, $request->validated());
    }

    public function destroy(LegalGlossary $legalGlossary): Response
    {
        return $this->destroyModel($legalGlossary);
    }
}

