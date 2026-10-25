<?php

namespace App\Http\Controllers\Api\Isf;

use App\Http\Controllers\Controller;
use App\Http\Resources\Isf\AlertResource;
use App\Http\Resources\Isf\ArticleResource;
use App\Http\Resources\Isf\ChartResource;
use App\Http\Resources\Isf\InfographicResource;
use App\Http\Resources\Isf\IsuResource;
use App\Http\Resources\Isf\TokohResource;
use App\Models\Isf\Alert;
use App\Models\Isf\Article;
use App\Models\Isf\Briefing;
use App\Models\Isf\Chart;
use App\Models\Isf\Infographic;
use App\Models\Isf\Isu;
use App\Models\Isf\Setting;
use App\Models\Isf\Tokoh;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public, read-only ISF content API (GET only). Serves ONLY published
 * ("terbit") content, in the shape used by the public Vue pages.
 */
class PublicContentController extends Controller
{
    /**
     * GET /api/isf/beranda
     */
    public function beranda(Request $request): JsonResponse
    {
        $settings = Setting::get('beranda', [
            'topBar' => ['badge' => '', 'announcement' => '', 'info' => ''],
            'pekanIni' => ['dateStart' => null, 'dateEnd' => null],
        ]);

        $hero = Article::currentHero();

        $terkini = Article::query()->terbit()->where('show_in_terkini', true)
            ->orderByDesc('published_at')->orderByDesc('id')->limit(6)->get();

        $infografik = Infographic::query()->terbit()
            ->orderByDesc('published_at')->orderByDesc('id')->limit(4)->get();

        $chart = Chart::currentBeranda();

        $pekanIniItems = [];
        if ($isu = Isu::currentWeekly()) {
            $pekanIniItems[] = (new IsuResource($isu))->pekanIniItem();
        }
        if ($tokoh = Tokoh::currentFeatured()) {
            $pekanIniItems[] = (new TokohResource($tokoh))->pekanIniItem();
        }

        return response()->json([
            'topBar' => $settings['topBar'] ?? null,
            'hero' => $hero ? (new ArticleResource($hero))->hero() : null,
            'factBlocks' => $hero?->fact_blocks ?? [],
            'chart' => $chart ? (new ChartResource($chart))->resolve($request) : null,
            'terkini' => ArticleResource::collection($terkini)->resolve($request),
            'infografik' => InfographicResource::collection($infografik)->resolve($request),
            'pekanIni' => [
                'dateStart' => $settings['pekanIni']['dateStart'] ?? null,
                'dateEnd' => $settings['pekanIni']['dateEnd'] ?? null,
                'items' => $pekanIniItems,
            ],
        ]);
    }

    /**
     * GET /api/isf/tokoh/{slug}
     */
    public function tokoh(Request $request, string $slug): JsonResponse
    {
        $tokoh = Tokoh::query()->terbit()->where('slug', $slug)->first();

        if (! $tokoh) {
            return $this->notFound('Tokoh tidak ditemukan.');
        }

        return response()->json((new TokohResource($tokoh))->resolve($request));
    }

    /**
     * GET /api/isf/isu/{slug}
     */
    public function isu(Request $request, string $slug): JsonResponse
    {
        $isu = Isu::query()->terbit()->with('visualChart')->where('slug', $slug)->first();

        if (! $isu) {
            return $this->notFound('Isu tidak ditemukan.');
        }

        $populer = Article::query()->terbit()->where('is_popular', true)
            ->orderByRaw('popular_rank IS NULL')->orderBy('popular_rank')
            ->orderByDesc('published_at')->limit(5)->get();

        return response()->json((new IsuResource($isu, $populer))->resolve($request));
    }

    /**
     * GET /api/isf/anggota
     */
    public function anggota(Request $request): JsonResponse
    {
        $alerts = Alert::query()->terbit()->orderByDesc('date')->orderByDesc('id')->get();

        // "Tautan Langsung" is navigation: computed from the current weekly content.
        // The public briefing preview 404s without a published briefing, so link it only when one exists.
        $tautan = [];
        if (Briefing::query()->terbit()->exists()) {
            $tautan[] = ['label' => 'Pratinjau Email Briefing', 'href' => '/anggota/briefing/preview', 'type' => 'external'];
        }
        if ($isu = Isu::currentWeekly()) {
            $tautan[] = ['label' => 'Anatomi Isu Pekan Ini', 'to' => "/isu/{$isu->slug}"];
        }
        if ($tokoh = Tokoh::currentFeatured()) {
            $tautan[] = ['label' => 'Profil Tokoh Pekan Ini', 'to' => "/tokoh/{$tokoh->slug}"];
        }
        $tautan[] = ['label' => 'Beranda ISF', 'to' => '/beranda'];

        return response()->json([
            'user' => Setting::get('anggota', [
                'name' => '', 'role' => '', 'organization' => '', 'plan' => '', 'sektor' => [],
            ]),
            'alerts' => AlertResource::collection($alerts)->resolve($request),
            'tautanLangsung' => $tautan,
        ]);
    }

    private function notFound(string $message): JsonResponse
    {
        return response()->json(['message' => $message], 404);
    }
}
