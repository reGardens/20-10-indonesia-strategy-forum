<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\ParliamentSeatRequest;
use App\Models\Isf\ParliamentSeat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * CMS API — Peta Kursi Parlemen (feature: kursi_parlemen).
 */
class ParliamentSeatController extends CmsResourceController
{
    protected string $model = ParliamentSeat::class;

    protected array $listColumns = [
        'id', 'periode', 'lembaga', 'partai', 'singkatan',
        'jumlah_kursi', 'warna_partai', 'urutan', 'status', 'updated_at',
    ];

    protected array $searchColumns = ['partai', 'singkatan', 'periode'];

    protected array $filterColumns = ['lembaga', 'periode', 'status'];

    protected array $order = ['urutan' => 'asc', 'jumlah_kursi' => 'desc', 'id' => 'asc'];

    public function show(ParliamentSeat $parliamentSeat): JsonResponse
    {
        return $this->showModel($parliamentSeat);
    }

    public function store(ParliamentSeatRequest $request): JsonResponse
    {
        return $this->storeModel($request->validated());
    }

    public function update(ParliamentSeatRequest $request, ParliamentSeat $parliamentSeat): JsonResponse
    {
        return $this->updateModel($parliamentSeat, $request->validated());
    }

    public function destroy(ParliamentSeat $parliamentSeat): Response
    {
        return $this->destroyModel($parliamentSeat);
    }
}

