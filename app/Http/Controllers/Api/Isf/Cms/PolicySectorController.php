<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\PolicySectorRequest;
use App\Models\Isf\PolicySector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class PolicySectorController extends CmsResourceController
{
    protected string $model = PolicySector::class;

    protected array $listColumns = [
        'id', 'nama', 'slug', 'deskripsi', 'urutan', 'status', 'created_at', 'updated_at',
    ];

    protected array $searchColumns = ['nama', 'slug', 'deskripsi'];

    protected array $filterColumns = ['status'];

    protected array $order = ['urutan' => 'asc', 'nama' => 'asc', 'id' => 'asc'];

    public function show(PolicySector $kategoriSektor): JsonResponse
    {
        return $this->showModel($kategoriSektor);
    }

    public function store(PolicySectorRequest $request): JsonResponse
    {
        return $this->storeModel($request->validated());
    }

    public function update(PolicySectorRequest $request, PolicySector $kategoriSektor): JsonResponse
    {
        return $this->updateModel($kategoriSektor, $request->validated());
    }

    public function destroy(PolicySector $kategoriSektor): Response
    {
        return $this->destroyModel($kategoriSektor);
    }
}

