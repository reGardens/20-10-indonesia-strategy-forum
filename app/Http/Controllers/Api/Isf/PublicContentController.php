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
use App\Models\Isf\Banner;
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
        $topBar = $settings['topBar'] ?? ['badge' => 'Sorotan', 'announcement' => '', 'info' => ''];
        $topBars = $settings['topBars'] ?? null;
        if ($topBars === null) {
            $topBars = [
                [
                    'badge' => !empty($topBar['badge']) ? $topBar['badge'] : 'Sorotan',
                    'announcement' => !empty($topBar['announcement']) ? $topBar['announcement'] : 'KUHP baru resmi berlaku sejak 2 Januari 2026 — simak dampaknya bagi dunia usaha.',
                    'info' => !empty($topBar['info']) ? $topBar['info'] : 'Edisi Pekan Ini',
                    'status' => 'terbit',
                ],
                [
                    'badge' => 'Regulasi',
                    'announcement' => 'DPR jadwalkan rapat kerja lanjutan pembahasan RUU Perampasan Aset bersama pemerintah.',
                    'info' => 'Pembaruan Terkini',
                    'status' => 'terbit',
                ],
                [
                    'badge' => 'Kebijakan',
                    'announcement' => 'Pedoman tata kelola dan kepatuhan ESG korporasi mulai diimplementasikan bertahap.',
                    'info' => 'Sorotan Industri',
                    'status' => 'terbit',
                ],
            ];
        }
        $activeTopBars = array_values(array_filter($topBars, fn ($item) => ($item['status'] ?? 'terbit') === 'terbit'));
        if (empty($activeTopBars) && !empty($topBar['announcement'])) {
            $activeTopBars = [$topBar];
        }

        $banners = Banner::query()->terbit()
            ->orderBy('order', 'asc')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Banner $b) => [
                'id' => (string) $b->id,
                'kicker' => $b->kicker,
                'title' => $b->title,
                'deck' => $b->deck,
                'to' => $b->to ?: '#',
                'imageUrl' => $b->image_url,
                'imageLabel' => $b->image_label ?: $b->title,
            ]);

        $hero = Article::currentHero();

        $terkini = Article::query()->terbit()->where('show_in_terkini', true)
            ->orderByDesc('published_at')->orderByDesc('id')->limit(6)->get();

        $infografik = Infographic::query()->terbit()
            ->orderByDesc('published_at')->orderByDesc('id')->limit(4)->get();

        $charts = Chart::query()->terbit()
            ->orderByDesc('is_beranda')
            ->orderBy('order')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();
        $chart = $charts->first();

        $pekanIniItems = [];
        if ($isu = Isu::currentWeekly()) {
            $pekanIniItems[] = (new IsuResource($isu))->pekanIniItem();
        }
        if ($tokoh = Tokoh::currentFeatured()) {
            $pekanIniItems[] = (new TokohResource($tokoh))->pekanIniItem();
        }

        $mediaSorotan = $settings['mediaSorotan'] ?? [];
        $activeMedia = array_values(array_filter($mediaSorotan, fn ($item) => ($item['status'] ?? 'terbit') === 'terbit'));
        if (empty($activeMedia)) {
            $activeMedia = [
                [
                    'id' => 1,
                    'title' => 'Diskusi Panel: Transformasi Digital dan Kepatuhan Regulasi Perbankan',
                    'imageUrl' => '',
                    'mediaUrl' => 'https://www.youtube.com',
                    'category' => 'YouTube',
                    'publishedAt' => '2026-10-20',
                    'order' => 1,
                    'status' => 'terbit',
                ],
                [
                    'id' => 2,
                    'title' => 'Sorotan Visual: Implikasi UU Energi Terbarukan bagi Sektor Industri',
                    'imageUrl' => '',
                    'mediaUrl' => 'https://www.instagram.com',
                    'category' => 'Instagram',
                    'publishedAt' => '2026-10-18',
                    'order' => 2,
                    'status' => 'terbit',
                ],
                [
                    'id' => 3,
                    'title' => 'Kupas Tuntas: RUU Perampasan Aset dan Efek Jeranya bagi Koruptor',
                    'imageUrl' => '',
                    'mediaUrl' => 'https://www.tiktok.com',
                    'category' => 'TikTok',
                    'publishedAt' => '2026-10-16',
                    'order' => 3,
                    'status' => 'terbit',
                ],
            ];
        }
        usort($activeMedia, fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
        $mediaSorotan3 = array_slice($activeMedia, 0, 3);

        return response()->json([
            'topBar' => $settings['topBar'] ?? null,
            'topBars' => $activeTopBars,
            'banners' => $banners,
            'hero' => $hero ? (new ArticleResource($hero))->hero() : null,
            'factBlocks' => $hero?->fact_blocks ?? [],
            'chart' => $chart ? (new ChartResource($chart))->resolve($request) : null,
            'charts' => ChartResource::collection($charts)->resolve($request),
            'mediaSorotan' => $mediaSorotan3,
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
     * GET /api/isf/infografik
     */
    public function infografik(Request $request): JsonResponse
    {
        $infografik = Infographic::query()->terbit()
            ->orderByDesc('published_at')->orderByDesc('id')->get();

        return response()->json([
            'data' => InfographicResource::collection($infografik)->resolve($request),
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
