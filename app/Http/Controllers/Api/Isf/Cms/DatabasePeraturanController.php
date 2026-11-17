<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\DatabasePeraturanRequest;
use App\Models\Isf\DatabasePeraturan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class DatabasePeraturanController extends CmsResourceController
{
    protected string $model = DatabasePeraturan::class;

    protected array $listColumns = [
        'id', 'judul', 'nomor', 'tahun', 'kategori',
        'tentang', 'url_dokumen', 'lembaga', 'urutan', 'status', 'created_at', 'updated_at',
    ];

    protected array $searchColumns = ['judul', 'nomor', 'tahun', 'kategori', 'tentang', 'lembaga'];

    protected array $filterColumns = ['kategori', 'status'];

    protected array $order = ['urutan' => 'asc', 'id' => 'desc'];

    public function show(DatabasePeraturan $databasePeraturan): JsonResponse
    {
        return $this->showModel($databasePeraturan);
    }

    public function store(DatabasePeraturanRequest $request): JsonResponse
    {
        return $this->storeModel($request->validated());
    }

    public function update(DatabasePeraturanRequest $request, DatabasePeraturan $databasePeraturan): JsonResponse
    {
        return $this->updateModel($databasePeraturan, $request->validated());
    }

    public function destroy(DatabasePeraturan $databasePeraturan): Response
    {
        return $this->destroyModel($databasePeraturan);
    }
}

