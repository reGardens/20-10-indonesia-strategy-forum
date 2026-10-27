<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\CategoryRequest;
use App\Models\Isf\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * CMS API — Kategori Konten.
 */
class CategoryController extends CmsResourceController
{
    protected string $model = Category::class;

    protected array $listColumns = ['id', 'name', 'slug', 'description', 'color', 'order', 'status', 'updated_at'];

    protected array $searchColumns = ['name', 'slug', 'description'];

    protected array $order = ['order' => 'asc', 'id' => 'asc'];

    public function show(Category $category): JsonResponse
    {
        return $this->showModel($category);
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        return $this->storeModel($request->validated());
    }

    public function update(CategoryRequest $request, Category $category): JsonResponse
    {
        return $this->updateModel($category, $request->validated());
    }

    public function destroy(Category $category): Response
    {
        return $this->destroyModel($category);
    }

    /**
     * Active categories for dropdown pickers in other CMS modules and frontend.
     */
    public function options(): JsonResponse
    {
        $categories = Category::query()
            ->terbit()
            ->orderBy('order', 'asc')
            ->orderBy('id', 'asc')
            ->get(['id', 'name', 'slug', 'color', 'description'])
            ->map(fn (Category $c) => [
                'id' => $c->id,
                'value' => $c->slug,
                'label' => $c->name,
                'color' => $c->color,
                'description' => $c->description,
            ]);

        return response()->json(['data' => $categories]);
    }
}
