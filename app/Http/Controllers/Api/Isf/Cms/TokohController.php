<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\TokohRequest;
use App\Models\Isf\Tokoh;
use App\Services\Isf\IsfCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * CMS API — Tokoh / Sosok (feature: tokoh).
 */
class TokohController extends CmsResourceController
{
    protected string $model = Tokoh::class;

    protected array $listColumns = ['id', 'slug', 'name', 'position', 'role_category', 'photo_url', 'influence_score', 'is_featured', 'is_influential_weekly', 'show_in_politik', 'status', 'updated_at'];

    protected array $searchColumns = ['name', 'slug', 'position', 'summary_analysis'];

    protected array $filterColumns = ['status', 'role_category', 'show_in_politik', 'is_influential_weekly'];

    /** Sosok aktif (Pekan Ini & Widget Politik) selalu berada di paling atas daftar */
    protected array $order = [
        'is_featured' => 'desc',
        'show_in_politik' => 'desc',
        'id' => 'desc',
    ];

    /** "Sosok Pekan Ini" currently shown on the public pages. */
    protected function liveId(): ?int
    {
        return Tokoh::currentFeatured()?->id;
    }

    public function index(Request $request): JsonResponse
    {
        $response = parent::index($request);
        $data = $response->getData(true);
        $data['politik_count'] = Tokoh::where('show_in_politik', true)->count();
        $data['featured_id'] = Tokoh::where('is_featured', true)->value('id');

        return response()->json($data);
    }

    public function show(Tokoh $tokoh): JsonResponse
    {
        return $this->showModel($tokoh);
    }

    public function store(TokohRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (!empty($data['is_featured'])) {
            Tokoh::query()->update([
                'is_featured' => false,
                'is_influential_weekly' => false,
            ]);
            $data['is_influential_weekly'] = true;
            IsfCacheService::clearBeranda();
            IsfCacheService::clearAll();
            Cache::flush();
        }

        if (!empty($data['show_in_politik'])) {
            $count = Tokoh::where('show_in_politik', true)->count();
            if ($count >= 4) {
                $oldest = Tokoh::where('show_in_politik', true)->orderBy('updated_at', 'asc')->orderBy('id', 'asc')->first();
                if ($oldest) {
                    $oldest->update(['show_in_politik' => false]);
                }
            }
            Cache::flush();
        }

        return $this->storeModel($data);
    }

    public function update(TokohRequest $request, Tokoh $tokoh): JsonResponse
    {
        $data = $request->validated();

        if (!empty($data['is_featured'])) {
            Tokoh::query()->where('id', '!=', $tokoh->id)->update([
                'is_featured' => false,
                'is_influential_weekly' => false,
            ]);
            $data['is_influential_weekly'] = true;
            IsfCacheService::clearBeranda();
            IsfCacheService::clearAll();
            Cache::flush();
        }

        if (!empty($data['show_in_politik'])) {
            $count = Tokoh::where('id', '!=', $tokoh->id)->where('show_in_politik', true)->count();
            if ($count >= 4) {
                $oldest = Tokoh::where('id', '!=', $tokoh->id)->where('show_in_politik', true)->orderBy('updated_at', 'asc')->orderBy('id', 'asc')->first();
                if ($oldest) {
                    $oldest->update(['show_in_politik' => false]);
                }
            }
            Cache::flush();
        }

        return $this->updateModel($tokoh, $data);
    }

    public function destroy(Tokoh $tokoh): Response
    {
        return $this->destroyModel($tokoh);
    }

    /**
     * Jadikan satu-satunya sosok sebagai Sosok Pekan Ini.
     */
    public function setFeatured(Tokoh $tokoh): JsonResponse
    {
        Tokoh::query()->where('id', '!=', $tokoh->id)->update([
            'is_featured' => false,
            'is_influential_weekly' => false,
        ]);

        $tokoh->update([
            'is_featured' => true,
            'is_influential_weekly' => true,
        ]);

        // Bersihkan cache agar langsung update di Beranda
        IsfCacheService::clearBeranda();
        IsfCacheService::clearAll();
        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Sosok berhasil ditetapkan sebagai Sosok Pekan Ini.',
            'data' => $tokoh->fresh(),
            'featured_id' => $tokoh->id,
            'politik_count' => Tokoh::where('show_in_politik', true)->count(),
        ]);
    }

    /**
     * Toggle penempatan pada Profil Tokoh Politik (maksimal 4).
     */
    public function togglePolitik(Tokoh $tokoh): JsonResponse
    {
        if ($tokoh->show_in_politik) {
            $tokoh->update(['show_in_politik' => false]);
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'Sosok berhasil dilepas dari Profil Tokoh Politik.',
                'data' => $tokoh->fresh(),
                'featured_id' => Tokoh::where('is_featured', true)->value('id'),
                'politik_count' => Tokoh::where('show_in_politik', true)->count(),
            ]);
        }

        $currentCount = Tokoh::where('show_in_politik', true)->count();
        if ($currentCount >= 4) {
            return response()->json([
                'success' => false,
                'message' => 'Harap nonaktifkan salah satu sosok yang dipilih terlebih dahulu.',
            ], 422);
        }

        $tokoh->update(['show_in_politik' => true]);
        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Sosok berhasil dipilih untuk Profil Tokoh Politik.',
            'data' => $tokoh->fresh(),
            'featured_id' => Tokoh::where('is_featured', true)->value('id'),
            'politik_count' => Tokoh::where('show_in_politik', true)->count(),
        ]);
    }
}
