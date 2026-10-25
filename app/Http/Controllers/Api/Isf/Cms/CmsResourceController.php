<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Controllers\Controller;
use App\Support\IsfOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Shared list/show/delete behaviour for the ISF CMS JSON API.
 * Concrete controllers define the model, list columns, search column(s)
 * and ordering, plus typed store()/update() with their FormRequest.
 */
abstract class CmsResourceController extends Controller
{
    /** @var class-string<Model> */
    protected string $model;

    /** Columns returned by index(). */
    protected array $listColumns = ['id', 'status', 'updated_at'];

    /** Columns matched by ?q= */
    protected array $searchColumns = ['title'];

    /** [column => direction] ordering for index(). */
    protected array $order = ['id' => 'desc'];

    /**
     * GET  ?q=keyword&status=draf|terbit&page=N  → paginated list (15 per page).
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:'.implode(',', IsfOptions::STATUSES)],
        ]);

        /** @var Builder $query */
        $query = $this->model::query()->select($this->listColumns);

        if ($keyword = trim((string) $request->query('q', ''))) {
            $like = '%'.addcslashes($keyword, '\\%_').'%';
            $query->where(function (Builder $q) use ($like) {
                foreach ($this->searchColumns as $column) {
                    $q->orWhere($column, 'like', $like);
                }
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        foreach ($this->order as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        return response()->json(array_merge(
            $query->paginate(15)->withQueryString()->toArray(),
            ['live_id' => $this->liveId()],
        ));
    }

    /**
     * Id of the row the public pages currently show for this module's
     * placement flag (e.g. Beranda hero), so the CMS list can mark it.
     * Null for modules without a single-pick placement.
     */
    protected function liveId(): ?int
    {
        return null;
    }

    /**
     * Full record for the edit form.
     */
    protected function showModel(Model $model): JsonResponse
    {
        return response()->json(['data' => $model]);
    }

    protected function storeModel(array $validated): JsonResponse
    {
        $model = $this->model::query()->create($validated);

        return response()->json(['data' => $model->fresh()], 201);
    }

    protected function updateModel(Model $model, array $validated): JsonResponse
    {
        $model->update($validated);

        return response()->json(['data' => $model->fresh()]);
    }

    protected function destroyModel(Model $model): Response
    {
        $model->delete();

        return response()->noContent();
    }
}
