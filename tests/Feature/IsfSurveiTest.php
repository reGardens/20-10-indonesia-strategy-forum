<?php

namespace Tests\Feature;

use App\Models\Isf\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IsfSurveiTest extends TestCase
{
    use DatabaseTransactions;

    private function createAdminUser(array $features = ['survei']): User
    {
        return User::create([
            'name' => 'Editor Survei',
            'email' => 'editor-survei-' . uniqid() . '@example.test',
            'password' => Hash::make('secret123'),
            'allowed_features' => $features,
        ]);
    }

    public function test_survei_web_routes_return_ok(): void
    {
        $survey = Survey::create([
            'title' => 'Survei Kepuasan Publik ' . uniqid(),
            'slug' => 'survei-kepuasan-publik-' . uniqid(),
            'institution' => 'ISF Riset Strategis',
            'is_isf' => true,
            'topic' => 'politik',
            'region' => 'nasional',
            'sample_size' => 1200,
            'status' => 'terbit',
        ]);

        $resIndex = $this->get('/survei');
        $resIndex->assertOk();

        $resDetail = $this->get("/survei/{$survey->slug}");
        $resDetail->assertOk();
    }

    public function test_public_survei_api_structure_and_filtering(): void
    {
        $surveyPolitik = Survey::create([
            'title' => 'Survei Politik Nasional ' . uniqid(),
            'slug' => 'survei-politik-nasional-' . uniqid(),
            'institution' => 'Litbang Kebijakan',
            'is_isf' => false,
            'topic' => 'politik',
            'region' => 'nasional',
            'sample_size' => 1200,
            'margin_of_error' => '±2.8%',
            'headline_stat_value' => '72.4%',
            'headline_stat_label' => 'Kepuasan Kabinet',
            'status' => 'terbit',
            'order' => 1,
        ]);

        $surveyEkonomiJawa = Survey::create([
            'title' => 'Survei Daya Beli Jawa ' . uniqid(),
            'slug' => 'survei-daya-beli-jawa-' . uniqid(),
            'institution' => 'ISF Riset',
            'is_isf' => true,
            'topic' => 'ekonomi',
            'region' => 'jawa',
            'sample_size' => 1500,
            'margin_of_error' => '±2.5%',
            'headline_stat_value' => '61.8%',
            'headline_stat_label' => 'Sentimen Konsumen',
            'status' => 'terbit',
            'order' => 2,
        ]);

        // 1. General list
        $res = $this->getJson('/api/isf/survei');
        $res->assertOk();
        $res->assertJsonStructure([
            'rubrik' => [
                'key',
                'title',
                'label',
                'description',
                'topics',
                'regions',
                'activeTopic',
                'activeRegion',
            ],
            'featuredSurvey',
            'surveys',
            'meta' => [
                'currentPage',
                'lastPage',
                'total',
                'perPage',
            ],
        ]);

        // 2. Filter by topic (ekonomi)
        $resEkonomi = $this->getJson('/api/isf/survei?isu=ekonomi');
        $resEkonomi->assertOk();
        $resEkonomi->assertJsonFragment(['slug' => $surveyEkonomiJawa->slug]);
        $resEkonomi->assertJsonMissing(['slug' => $surveyPolitik->slug]);

        // 3. Filter by region (nasional)
        $resNasional = $this->getJson('/api/isf/survei?wilayah=nasional');
        $resNasional->assertOk();
        $resNasional->assertJsonFragment(['slug' => $surveyPolitik->slug]);
        $resNasional->assertJsonMissing(['slug' => $surveyEkonomiJawa->slug]);

        // 4. Search query (q=Daya Beli)
        $resSearch = $this->getJson('/api/isf/survei?q=Daya+Beli');
        $resSearch->assertOk();
        $resSearch->assertJsonFragment(['slug' => $surveyEkonomiJawa->slug]);
        $resSearch->assertJsonMissing(['slug' => $surveyPolitik->slug]);
    }

    public function test_public_survei_detail_api(): void
    {
        $chartData = [
            'segments' => [
                [
                    'key' => 'overall',
                    'label' => 'Keseluruhan',
                    'items' => [
                        ['label' => 'Puas', 'value' => 74.0, 'color' => '#16a34a'],
                        ['label' => 'Tidak Puas', 'value' => 26.0, 'color' => '#dc2626'],
                    ],
                ],
            ],
        ];

        $survey = Survey::create([
            'title' => 'Survei Reformasi Hukum ' . uniqid(),
            'slug' => 'survei-reformasi-hukum-' . uniqid(),
            'institution' => 'ISF Riset Strategis',
            'is_isf' => true,
            'topic' => 'hukum',
            'region' => 'nasional',
            'sample_size' => 1200,
            'margin_of_error' => '±2.8%',
            'confidence_level' => '95%',
            'sampling_method' => 'Multistage Random Sampling',
            'interview_method' => 'Wawancara Tatap Muka',
            'headline_stat_value' => '58.5%',
            'headline_stat_label' => 'Optimisme Reformasi Peradilan',
            'headline_stat_comparison' => '+5.1% YoY',
            'summary' => 'Ringkasan survei hukum.',
            'expert_analysis' => 'Analisis komprehensif oleh peneliti ISF.',
            'chart_data' => $chartData,
            'pdf_path' => '/docs/survei/hukum-2026.pdf',
            'pdf_size' => '2.5 MB',
            'status' => 'terbit',
        ]);

        $res = $this->getJson("/api/isf/survei/{$survey->slug}");
        $res->assertOk();
        $res->assertJsonStructure([
            'survey' => [
                'id',
                'title',
                'slug',
                'institution',
                'is_isf',
                'topic',
                'region',
                'headline_stat_value',
                'headline_stat_label',
                'sample_size',
                'margin_of_error',
                'confidence_level',
                'sampling_method',
                'summary',
                'expert_analysis',
                'chart_data',
                'pdf_path',
                'pdf_size',
            ],
            'relatedSurveys',
        ]);

        $this->assertEquals('58.5%', $res->json('survey.headline_stat_value'));
        $this->assertEquals(1200, $res->json('survey.sample_size'));
        $this->assertEquals('2.5 MB', $res->json('survey.pdf_size'));

        // Non-existent slug returns 404
        $res404 = $this->getJson('/api/isf/survei/slug-palsu-' . uniqid());
        $res404->assertNotFound();
    }

    public function test_cms_survey_acl_and_crud(): void
    {
        $unauthorizedUser = $this->createAdminUser(['artikel']); // No 'survei' feature
        $authorizedUser = $this->createAdminUser(['survei']);

        // 1. Unauthorized user cannot access CMS survei API
        $this->actingAs($unauthorizedUser)
            ->getJson('/api/cms/survei')
            ->assertForbidden();

        // 2. Authorized user can list surveys
        $this->actingAs($authorizedUser)
            ->getJson('/api/cms/survei')
            ->assertOk();

        // 3. Authorized user creates a new survey
        $createPayload = [
            'title' => 'Survei Tata Kelola Baru ' . uniqid(),
            'slug' => 'survei-tata-kelola-' . uniqid(),
            'institution' => 'Lembaga Riset Independen',
            'is_isf' => false,
            'topic' => 'politik',
            'region' => 'nasional',
            'survey_period' => '1 - 10 November 2026',
            'release_date' => '2026-11-12',
            'sample_size' => 1200,
            'margin_of_error' => '±2.83%',
            'confidence_level' => '95%',
            'sampling_method' => 'Multistage Random',
            'interview_method' => 'Tatap Muka',
            'headline_stat_value' => '65.2%',
            'headline_stat_label' => 'Tingkat Kepercayaan',
            'headline_stat_comparison' => '+3.0%',
            'summary' => 'Ringkasan temuan',
            'expert_analysis' => 'Analisis mendalam',
            'chart_data' => [
                'segments' => [
                    [
                        'key' => 'overall',
                        'label' => 'Keseluruhan',
                        'items' => [
                            ['label' => 'Percaya', 'value' => 65.2],
                            ['label' => 'Kurang Percaya', 'value' => 34.8],
                        ],
                    ],
                ],
            ],
            'pdf_path' => '/docs/survei/tata-kelola.pdf',
            'pdf_size' => '1.8 MB',
            'order' => 5,
            'is_featured' => true,
            'status' => 'terbit',
        ];

        $createRes = $this->actingAs($authorizedUser)
            ->postJson('/api/cms/survei', $createPayload);
        $createRes->assertCreated();
        $newId = $createRes->json('data.id');
        $this->assertNotNull($newId);

        // 4. Authorized user gets the survey
        $getRes = $this->actingAs($authorizedUser)
            ->getJson("/api/cms/survei/{$newId}");
        $getRes->assertOk();
        $this->assertEquals($createPayload['title'], $getRes->json('data.title'));

        // 5. Authorized user updates the survey
        $updateRes = $this->actingAs($authorizedUser)
            ->putJson("/api/cms/survei/{$newId}", array_merge($createPayload, [
                'title' => 'Survei Tata Kelola Diperbarui',
                'headline_stat_value' => '68.0%',
            ]));
        $updateRes->assertOk();
        $this->assertEquals('Survei Tata Kelola Diperbarui', $updateRes->json('data.title'));

        // 6. Authorized user deletes the survey
        $deleteRes = $this->actingAs($authorizedUser)
            ->deleteJson("/api/cms/survei/{$newId}");
        $deleteRes->assertNoContent();

        $this->assertNull(Survey::find($newId));
    }
}
