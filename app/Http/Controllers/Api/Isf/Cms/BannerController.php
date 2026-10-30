<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\BannerRequest;
use App\Models\Isf\Banner;
use App\Services\Isf\IsfCacheService;
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

    protected array $order = ['order' => 'asc', 'id' => 'asc'];

    public function show(Banner $banner): JsonResponse
    {
        return $this->showModel($banner);
    }

    public function store(BannerRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (empty($data['kicker'])) {
            $data['kicker'] = 'Sorotan';
        }
        if (!isset($data['order']) || $data['order'] === null || $data['order'] === '') {
            $data['order'] = ((int) Banner::max('order')) + 1;
        }
        return $this->storeModel($data);
    }

    public function update(BannerRequest $request, Banner $banner): JsonResponse
    {
        $data = $request->validated();
        if (empty($data['kicker'])) {
            $data['kicker'] = $banner->kicker ?: 'Sorotan';
        }
        return $this->updateModel($banner, $data);
    }

    public function reorder(\Illuminate\Http\Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer',
            'items.*.order' => 'required|integer',
        ]);

        foreach ($validated['items'] as $item) {
            Banner::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        IsfCacheService::clearBeranda();

        return response()->json(['message' => 'Urutan banner berhasil diperbarui.']);
    }

    public function destroy(Banner $banner): Response
    {
        return $this->destroyModel($banner);
    }
}
