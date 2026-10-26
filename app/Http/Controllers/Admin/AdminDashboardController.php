<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Isf\Alert;
use App\Models\Isf\Article;
use App\Models\Isf\Banner;
use App\Models\Isf\Briefing;
use App\Models\Isf\Chart;
use App\Models\Isf\Infographic;
use App\Models\Isf\Isu;
use App\Models\Isf\Tokoh;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class AdminDashboardController extends Controller
{
    /**
     * ISF content modules shown on the dashboard.
     * feature => [label, model, URL segment, icon, title column]
     */
    private const MODULES = [
        'banner' => ['Banner Sorotan', Banner::class, 'banner', 'Slideshow', 'title'],
        'artikel' => ['Artikel', Article::class, 'artikel', 'Article', 'title'],
        'infografik' => ['Infografik', Infographic::class, 'infografik', 'ChartInfographic', 'title'],
        'tokoh' => ['Tokoh', Tokoh::class, 'tokoh', 'UserStar', 'name'],
        'isu' => ['Isu Mingguan', Isu::class, 'isu', 'Bulb', 'title'],
        'data_grafik' => ['Data Grafik', Chart::class, 'data-grafik', 'ChartBar', 'title'],
        'alert' => ['Alert Peringatan Dini', Alert::class, 'alert', 'AlertTriangle', 'title'],
        'email_briefing' => ['Email Briefing', Briefing::class, 'email-briefing', 'Mail', 'ringkasan_judul'],
    ];

    /**
     * Display the CMS dashboard with ISF content stats.
     * Each user only sees the modules they have the feature for (ACL);
     * user/role stats are for super-admin only.
     */
    public function index(): Response
    {
        $user = Auth::user();
        $isSuperAdmin = $user->hasRole('super-admin');

        $contentStats = [];
        $recentContent = collect();

        foreach (self::MODULES as $feature => [$label, $model, $segment, $icon, $titleColumn]) {
            if (! $user->hasFeature($feature)) {
                continue;
            }

            $total = $model::query()->count();
            $terbit = $model::query()->terbit()->count();

            $contentStats[] = [
                'key' => $feature,
                'label' => $label,
                'total' => $total,
                'terbit' => $terbit,
                'draf' => $total - $terbit,
                'href' => "/cms/{$segment}",
                'icon' => $icon,
            ];

            $recentContent = $recentContent->concat(
                $model::query()
                    ->select(['id', $titleColumn, 'status', 'updated_at'])
                    ->latest('updated_at')
                    ->limit(8)
                    ->get()
                    ->map(fn ($row) => [
                        'type' => $feature,
                        'label' => $label,
                        'title' => $row->{$titleColumn},
                        'status' => $row->status,
                        'updated_at' => $row->updated_at?->toISOString(),
                        'href' => "/cms/{$segment}/{$row->id}/edit",
                    ])
            );
        }

        $recentContent = $recentContent->sortByDesc('updated_at')->take(8)->values();

        $publicLinks = [
            ['label' => 'Beranda ISF', 'href' => '/'],
        ];
        if ($tokoh = Tokoh::currentFeatured()) {
            $publicLinks[] = ['label' => 'Tokoh Pekan Ini', 'href' => "/tokoh/{$tokoh->slug}"];
        }
        if ($isu = Isu::currentWeekly()) {
            $publicLinks[] = ['label' => 'Anatomi Isu Pekan Ini', 'href' => "/isu/{$isu->slug}"];
        }
        $publicLinks[] = ['label' => 'Dasbor Anggota', 'href' => '/anggota'];
        // The public briefing preview 404s without a published briefing.
        if (Briefing::query()->terbit()->exists()) {
            $publicLinks[] = ['label' => 'Pratinjau Email Briefing', 'href' => '/anggota/briefing/preview'];
        }

        return Inertia::render('Cms/Dashboard', [
            'isSuperAdmin' => $isSuperAdmin,
            'totalUsers' => $isSuperAdmin ? User::count() : null,
            'totalRoles' => $isSuperAdmin ? Role::count() : null,
            'contentStats' => $contentStats,
            'recentContent' => $recentContent,
            'publicLinks' => $publicLinks,
        ]);
    }
}
