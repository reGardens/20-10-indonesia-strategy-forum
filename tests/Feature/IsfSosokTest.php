<?php

namespace Tests\Feature;

use App\Models\Isf\Article;
use App\Models\Isf\Tokoh;
use App\Models\Isf\TokohReview;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class IsfSosokTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Tokoh::updateOrCreate(
            ['slug' => 'budi-santoso'],
            [
                'name' => 'Dr. Budi Santoso, S.H., M.H.',
                'position' => 'Ketua Komisi Hukum DPR RI',
                'role_category' => 'legislatif',
                'issues' => ['hukum', 'politik'],
                'summary_analysis' => 'Figur kunci dalam perumusan RUU Perampasan Aset dan pengawasan reformasi peradilan nasional di Parlemen.',
                'stats' => [['label' => 'Rapat', 'value' => 10]],
                'skor_value' => 84,
                'skor_period' => 'Pekan 42 / 2026',
                'skor_explanation' => 'Tingkat kepemimpinan aktif dalam perumusan undang-undang strategis.',
                'matrix_columns' => ['Inisiasi', 'Pembahasan', 'Implementasi'],
                'matrix_rows' => [['kebijakan' => 'RUU Perampasan Aset', 'tahap' => 'Pembahasan', 'involvement' => [true, true, false]]],
                'calendar_year' => 2026,
                'calendar_month' => 11,
                'calendar_events' => [],
                'status' => 'terbit',
                'is_influential_weekly' => true,
                'is_featured' => true,
            ]
        );
    }

    public function test_legacy_tokoh_url_redirects_301_to_sosok(): void
    {
        $response = $this->get('/tokoh/budi-santoso');
        $response->assertRedirect('/sosok/budi-santoso');
        $this->assertEquals(301, $response->getStatusCode());
    }

    public function test_sosok_web_routes_render_spa_skeleton(): void
    {
        $response = $this->get('/sosok');
        $response->assertStatus(200);

        $responseSub = $this->get('/sosok/eksekutif');
        $responseSub->assertStatus(200);

        $responseDetail = $this->get('/sosok/budi-santoso');
        $responseDetail->assertStatus(200);

        $responseTab = $this->get('/sosok/budi-santoso/analisis');
        $responseTab->assertStatus(200);
    }

    public function test_api_sosok_index_returns_hero_and_filters(): void
    {
        $response = $this->getJson('/api/isf/sosok');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'rubrik' => ['title', 'label', 'description', 'kategoriOptions', 'isuOptions'],
                'hero',
                'items' => [
                    '*' => [
                        'id', 'slug', 'name', 'position', 'role_category', 'role_category_label',
                        'issues', 'summary_analysis', 'influence_score', 'photo_url', 'to'
                    ]
                ],
                'meta' => ['currentPage', 'lastPage', 'total', 'perPage']
            ]);

        // Filter by category
        $resKategori = $this->getJson('/api/isf/sosok?kategori=eksekutif');
        $resKategori->assertStatus(200);
        foreach ($resKategori->json('items') as $item) {
            $this->assertEquals('eksekutif', $item['role_category']);
        }

        // Filter by issue
        $resIsu = $this->getJson('/api/isf/sosok?isu=hukum');
        $resIsu->assertStatus(200);
        foreach ($resIsu->json('items') as $item) {
            $this->assertContains('hukum', $item['issues']);
        }

        // Filter by alphabet
        $resAbjad = $this->getJson('/api/isf/sosok?abjad=B');
        $resAbjad->assertStatus(200);
        foreach ($resAbjad->json('items') as $item) {
            $this->assertStringStartsWith('B', $item['name']);
        }

        // Search
        $resSearch = $this->getJson('/api/isf/sosok?q=Hartati');
        $resSearch->assertStatus(200);
        $this->assertNotEmpty($resSearch->json('items'));
    }

    public function test_api_sosok_detail_returns_5_tabs_and_data(): void
    {
        // Ensure an article exists mentioning Budi Santoso for automated news feed
        Article::create([
            'slug' => 'uji-berita-budi-' . uniqid(),
            'title' => 'Kajian Hukum Bersama Budi Santoso di DPR',
            'summary' => 'Ringkasan artikel uji.',
            'body' => '<p>Konten pembahasan kebijakan bersama Budi Santoso.</p>',
            'category' => 'hukum',
            'status' => 'terbit',
            'published_at' => now(),
            'reading_time' => 5,
        ]);

        $response = $this->getJson('/api/isf/sosok/budi-santoso');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'tokoh' => [
                    'id', 'slug', 'name', 'position', 'role_category', 'influence_score', 'to'
                ],
                'tabs' => [
                    'profil' => ['bio', 'career_timeline', 'education', 'positions_history'],
                    'analisis' => ['influence_score', 'skorSorotan', 'policyMatrix', 'policyBriefs', 'ratingSummary'],
                    'jejaring' => ['affiliations', 'allies', 'critics'],
                    'berita' => ['articles'],
                    'kontroversi' => ['quotes', 'controversies'],
                ],
                'calendar' => ['year', 'month', 'events'],
                'relatedTokoh',
            ]);

        $this->assertNotEmpty($response->json('tabs.berita.articles'));
    }

    public function test_api_sosok_reviews_compatibility(): void
    {
        $response = $this->getJson('/api/isf/sosok/budi-santoso/ulasan');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'reviews' => ['data'],
                'summary' => ['average', 'total', 'distribution', 'suka', 'tidakSuka']
            ]);
    }

    public function test_cms_validation_blocks_reserved_category_slug(): void
    {
        $admin = User::first() ?? User::create([
            'name' => 'Admin Test',
            'email' => 'admin-test-' . uniqid() . '@example.test',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($admin)->postJson('/api/cms/tokoh', [
            'slug' => 'eksekutif', // reserved category slug!
            'name' => 'Pejabat Eksekutif',
            'position' => 'Menteri',
            'role_category' => 'eksekutif',
            'stats' => [['label' => 'Metrik', 'value' => 10]],
            'skor_value' => 80,
            'skor_period' => 'Pekan 42',
            'skor_explanation' => 'Penjelasan skor uji',
            'matrix_columns' => ['Inisiasi'],
            'matrix_rows' => [['kebijakan' => 'UU Uji', 'tahap' => 'Draft', 'involvement' => [true]]],
            'calendar_year' => 2026,
            'calendar_month' => 11,
            'status' => 'draf',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }
}
