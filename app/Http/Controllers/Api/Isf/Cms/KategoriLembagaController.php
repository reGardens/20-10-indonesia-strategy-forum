<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\KategoriLembagaRequest;
use App\Models\Isf\KategoriLembaga;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class KategoriLembagaController extends CmsResourceController
{
    protected string $model = KategoriLembaga::class;

    protected array $listColumns = [
        'id', 'nama', 'slug', 'deskripsi', 'urutan', 'status', 'created_at', 'updated_at',
    ];

    protected array $searchColumns = ['nama', 'slug', 'deskripsi'];

    protected array $filterColumns = ['status'];

    protected array $order = ['urutan' => 'asc', 'nama' => 'asc', 'id' => 'asc'];

    public function show(KategoriLembaga $kategoriLembaga): JsonResponse
    {
        return $this->showModel($kategoriLembaga);
    }

    public function store(KategoriLembagaRequest $request): JsonResponse
    {
        return $this->storeModel($request->validated());
    }

    public function update(KategoriLembagaRequest $request, KategoriLembaga $kategoriLembaga): JsonResponse
    {
        return $this->updateModel($kategoriLembaga, $request->validated());
    }

    public function destroy(KategoriLembaga $kategoriLembaga): Response
    {
        return $this->destroyModel($kategoriLembaga);
    }
}

