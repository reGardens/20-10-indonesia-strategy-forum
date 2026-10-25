<?php

namespace Database\Seeders;

use App\Models\Isf\Alert;
use App\Models\Isf\Article;
use App\Models\Isf\Briefing;
use App\Models\Isf\Chart;
use App\Models\Isf\Infographic;
use App\Models\Isf\Isu;
use App\Models\Isf\Setting;
use App\Models\Isf\Tokoh;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Seeds the ISF sample content shown on the public pages (Beranda, Tokoh,
 * Isu, Anggota, Email Briefing) from database/seeders/data/isf/*.json.
 *
 * Idempotent: rows are matched by slug / title / key (updateOrCreate), never truncated.
 * All seeded rows are published (status "terbit").
 */
class IsfContentSeeder extends Seeder
{
    /**
     * Categories for the "Isu Terpopuler" entries that only exist in the sidebar
     * (the sample data has no category for them; it is not displayed there).
     */
    private const POPULER_CATEGORIES = ['ruu', 'kebijakan', 'ekonomi', 'politik'];

    public function run(): void
    {
        $beranda = $this->load('beranda');
        $tokoh = $this->load('tokoh');
        $isu = $this->load('isu');
        $anggota = $this->load('anggota');
        $briefing = $this->load('briefing');

        $visualChart = $this->seedCharts($beranda, $isu);
        $this->seedArticles($beranda, $isu);
        $this->seedInfographics($beranda);
        $this->seedTokoh($tokoh, $beranda);
        $this->seedIsu($isu, $beranda, $visualChart);
        $this->seedAlerts($anggota);
        $this->seedBriefing($briefing);
        $this->seedSettings($beranda, $anggota);
    }

    private function load(string $name): array
    {
        $path = database_path("seeders/data/isf/{$name}.json");
        $data = json_decode(File::get($path), true);

        if (! is_array($data)) {
            throw new RuntimeException("Invalid seed file: {$path}");
        }

        return $data;
    }

    /**
     * Seeds the Beranda chart and the isu "Visual Kunci" chart; returns the latter.
     */
    private function seedCharts(array $beranda, array $isu): Chart
    {
        $chart = $beranda['chart'];
        Chart::updateOrCreate(['title' => $chart['title']], [
            'description' => $chart['description'],
            'unit' => $chart['unit'] !== '' ? $chart['unit'] : null,
            'data' => $chart['data'],
            'source_title' => $chart['sourceTitle'],
            'source_url' => $chart['sourceUrl'] !== '' ? $chart['sourceUrl'] : null,
            'is_beranda' => true,
            'status' => 'terbit',
        ]);

        $visual = $isu['frameworks']['visualKunci'];

        return Chart::updateOrCreate(['title' => $visual['title']], [
            'description' => $visual['description'],
            'unit' => $visual['unit'] !== '' ? $visual['unit'] : null,
            'data' => $visual['data'],
            'source_title' => $visual['sourceTitle'],
            'source_url' => $visual['sourceUrl'] !== '' ? $visual['sourceUrl'] : null,
            'is_beranda' => false,
            'status' => 'terbit',
        ]);
    }

    private function seedArticles(array $beranda, array $isu): void
    {
        // Hero / core article (also #1 in "Isu Terpopuler").
        $hero = $beranda['hero'];
        Article::updateOrCreate(['slug' => $hero['slug']], [
            'title' => $hero['title'],
            'category' => $hero['category'],
            'published_at' => $hero['publishedAt'],
            'summary' => $hero['summary'],
            'author_name' => $hero['author']['name'],
            'author_role' => $hero['author']['role'],
            'reading_minutes' => $hero['readingMinutes'],
            'image_alt' => $hero['infographicAlt'],
            'image_url' => null,
            'link_url' => $hero['to'],
            'fact_blocks' => $beranda['factBlocks'],
            'is_hero' => true,
            'show_in_terkini' => false,
            'is_popular' => true,
            'popular_rank' => 1,
            'status' => 'terbit',
        ]);

        // "Terkini" list.
        foreach ($beranda['terkini'] as $item) {
            Article::updateOrCreate(['slug' => Str::slug($item['title'])], [
                'title' => $item['title'],
                'category' => $item['category'],
                'published_at' => $item['publishedAt'],
                'is_hero' => false,
                'show_in_terkini' => true,
                'is_popular' => false,
                'popular_rank' => null,
                'status' => 'terbit',
            ]);
        }

        // "Isu Terpopuler" entries 2..5 (entry 1 is the hero article above).
        foreach (array_slice($isu['populer'], 1) as $index => $item) {
            Article::updateOrCreate(['slug' => Str::slug($item['title'])], [
                'title' => $item['title'],
                'category' => self::POPULER_CATEGORIES[$index % count(self::POPULER_CATEGORIES)],
                'published_at' => '2026-10-01',
                'link_url' => $item['to'] ?? null,
                'is_hero' => false,
                'show_in_terkini' => false,
                'is_popular' => true,
                'popular_rank' => $index + 2,
                'status' => 'terbit',
            ]);
        }
    }

    private function seedInfographics(array $beranda): void
    {
        foreach ($beranda['infografik'] as $item) {
            Infographic::updateOrCreate(['slug' => Str::slug($item['title'])], [
                'title' => $item['title'],
                'category' => $item['category'],
                'published_at' => $item['publishedAt'],
                'image_alt' => $item['alt'],
                'image_url' => null,
                'status' => 'terbit',
            ]);
        }
    }

    private function seedTokoh(array $tokoh, array $beranda): void
    {
        Tokoh::updateOrCreate(['slug' => $tokoh['slug']], [
            'name' => $tokoh['name'],
            'position' => $tokoh['position'],
            'photo_alt' => $tokoh['photoAlt'],
            'photo_url' => null,
            'stats' => $tokoh['stats'],
            'skor_value' => $tokoh['skorSorotan']['value'],
            'skor_period' => $tokoh['skorSorotan']['period'],
            'skor_explanation' => $tokoh['skorSorotan']['explanation'],
            'matrix_columns' => $tokoh['policyMatrix']['columns'],
            'matrix_rows' => $tokoh['policyMatrix']['rows'],
            'related_articles' => $tokoh['relatedArticles'],
            'calendar_year' => $tokoh['calendar']['year'],
            'calendar_month' => $tokoh['calendar']['month'],
            'calendar_events' => $tokoh['calendar']['events'],
            'teaser' => $this->pekanIniSummary($beranda, 'tokoh'),
            'is_featured' => true,
            'status' => 'terbit',
        ]);
    }

    private function seedIsu(array $isu, array $beranda, Chart $visualChart): void
    {
        $fw = $isu['frameworks'];

        Isu::updateOrCreate(['slug' => $isu['slug']], [
            'title' => $isu['title'],
            'category' => $isu['category'],
            'published_at' => $isu['publishedAt'],
            'summary' => $isu['summary'],
            'author' => $isu['author'],
            'teaser' => $this->pekanIniSummary($beranda, 'isu'),
            'is_weekly' => true,
            'ringkasan' => $fw['ringkasan'],
            'visual_chart_id' => $visualChart->id,
            'visual_image_alt' => $fw['visualKunci']['imageAlt'],
            'visual_image_url' => null,
            'fakta_kunci' => $fw['faktaKunci'],
            'linimasa' => $fw['linimasa'],
            'aktor' => $fw['aktor'],
            'grc_governance' => $fw['grc']['governance'],
            'grc_risk_level' => $fw['grc']['risk']['level'],
            'grc_risk_description' => $fw['grc']['risk']['description'],
            'grc_compliance' => $fw['grc']['compliance'],
            'dampak_sektoral' => $fw['dampak']['sektoral'],
            'dampak_reaksi' => $fw['dampak']['reaksi'],
            'selanjutnya_tahapan' => $fw['selanjutnya']['tahapan'],
            'selanjutnya_pantau' => $fw['selanjutnya']['pantau'],
            'metodologi_text' => $isu['metodologi']['text'],
            'metodologi_signals' => $isu['metodologi']['signals'],
            'faq' => $isu['faq'],
            'related' => $isu['related'],
            'status' => 'terbit',
        ]);
    }

    private function seedAlerts(array $anggota): void
    {
        foreach ($anggota['alerts'] as $alert) {
            Alert::updateOrCreate(['title' => $alert['title']], [
                'summary' => $alert['summary'],
                'priority' => $alert['priority'],
                'date' => $alert['date'],
                'sektor' => $alert['sektor'],
                'link_url' => null,
                'status' => 'terbit',
            ]);
        }
    }

    private function seedBriefing(array $briefing): void
    {
        Briefing::updateOrCreate(['tanggal' => $briefing['tanggal']], [
            'penerima' => $briefing['penerima'],
            'organisasi' => $briefing['organisasi'],
            'ringkasan_judul' => $briefing['ringkasan']['judul'],
            'ringkasan_catatan' => $briefing['ringkasan']['catatan'],
            'ringkasan_data' => $briefing['ringkasan']['data'],
            'pembuka' => $briefing['pembuka'],
            'poin' => $briefing['poin'],
            'penutup' => $briefing['penutup'],
            'cta_url' => $briefing['ctaUrl'],
            'tautan' => $briefing['tautan'],
            'status' => 'terbit',
        ]);
    }

    private function seedSettings(array $beranda, array $anggota): void
    {
        Setting::put('beranda', [
            'topBar' => $beranda['topBar'],
            'pekanIni' => [
                'dateStart' => $beranda['pekanIni']['dateStart'],
                'dateEnd' => $beranda['pekanIni']['dateEnd'],
            ],
        ]);

        Setting::put('anggota', $anggota['user']);
    }

    private function pekanIniSummary(array $beranda, string $type): ?string
    {
        foreach ($beranda['pekanIni']['items'] as $item) {
            if ($item['type'] === $type) {
                return $item['summary'];
            }
        }

        return null;
    }
}
