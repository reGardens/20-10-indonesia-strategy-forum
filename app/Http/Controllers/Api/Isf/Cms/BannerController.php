<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\BannerRequest;
use App\Models\Isf\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * CMS API — Banner Sorotan (feature: banner).
 */
class BannerController extends CmsResourceController
{
    protected string $model = Banner::class;

    protected array $listColumns = ['id', 'kicker', 'title', 'image_url', 'order', 'status', 'updated_at'];

    protected array $searchColumns = ['title', 'kicker', 'deck'];

    protected array $order = ['order' => 'asc', 'id' => 'desc'];

    public function show(Banner $banner): JsonResponse
    {
        return $this->showModel($banner);
    }

    public function store(BannerRequest $request): JsonResponse
    {
        return $this->storeModel($request->validated());
    }

    public function update(BannerRequest $request, Banner $banner): JsonResponse
    {
        return $this->updateModel($banner, $request->validated());
    }

    public function destroy(Banner $banner): Response
    {
        return $this->destroyModel($banner);
    }
}
