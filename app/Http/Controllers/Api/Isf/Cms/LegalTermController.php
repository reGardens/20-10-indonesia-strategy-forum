<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\LegalTermRequest;
use App\Models\Isf\LegalTerm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class LegalTermController extends CmsResourceController
{
    protected string $model = LegalTerm::class;

    protected array $listColumns = [
        'id', 'istilah', 'slug', 'abjad', 'kategori',
        'keterangan', 'urutan', 'status', 'created_at', 'updated_at',
    ];

    protected array $searchColumns = ['istilah', 'kategori', 'keterangan'];

    protected array $filterColumns = ['abjad', 'kategori', 'status'];

    protected array $order = ['abjad' => 'asc', 'istilah' => 'asc', 'id' => 'asc'];

    public function show(LegalTerm $legalTerm): JsonResponse
    {
        return $this->showModel($legalTerm);
    }

    public function store(LegalTermRequest $request): JsonResponse
    {
        return $this->storeModel($request->validated());
    }

    public function update(LegalTermRequest $request, LegalTerm $legalTerm): JsonResponse
    {
        return $this->updateModel($legalTerm, $request->validated());
    }

    public function destroy(LegalTerm $legalTerm): Response
    {
        return $this->destroyModel($legalTerm);
    }
}

