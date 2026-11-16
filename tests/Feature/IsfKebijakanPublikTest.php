<?php

namespace Tests\Feature;

use App\Models\Isf\Article;
use App\Models\Isf\PolicyBrief;
use App\Models\Isf\PolicyTracker;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IsfKebijakanPublikTest extends TestCase
{
    use DatabaseTransactions;

    private function createAdminUser(array $features = ['policy_brief', 'monitoring_kebijakan']): User
    {
        return User::create([
            'name' => 'Editor Kebijakan',
            'email' => 'editor-kebijakan-' . uniqid() . '@example.test',
            'password' => Hash::make('secret123'),
            'allowed_features' => $features,
        ]);
    }

    public function test_kebijakan_publik_web_and_api_return_expected_structure(): void
    {
        // 1. Web route
        $web = $this->get('/kebijakan-publik');
        $web->assertOk();

        // 2. Subcategory web route
        $webSub = $this->get('/kebijakan-publik/ekonomi');
        $webSub->assertOk();

        // 3. Public API
        $res = $this->getJson('/api/isf/kebijakan-publik');
        $res->assertOk();
        $res->assertJsonStructure([
            'rubrik' => [
                'key',
                'title',
                'label',
                'description',
                'subkategori',
                'activeSubkategori',
            ],
            'headline',
            'articles',
            'meta' => [
                'currentPage',
                'lastPage',
                'total',
                'perPage',
            ],
            'policyBriefs',
            'dataGrafik',
            'monitoringKebijakan',
        ]);

        $this->assertEquals('kebijakan', $res->json('rubrik.key'));
    }

    public function test_kebijakan_publik_filter_by_subkategori(): void
    {
        $now = now();
        $artEkonomi = Article::create([
            'title' => 'Analisis Kebijakan Fiskal ' . uniqid(),
            'slug' => 'analisis-kebijakan-fiskal-' . uniqid(),
            'category' => 'kebijakan',
            'subcategory' => 'ekonomi',
            'published_at' => $now,
            'summary' => 'Ringkasan fiskal',
            'body' => 'Isi analisis fiskal',
            'status' => 'terbit',
        ]);

        $artKesehatan = Article::create([
            'title' => 'Transformasi Layanan BPJS ' . uniqid(),
            'slug' => 'transformasi-layanan-bpjs-' . uniqid(),
            'category' => 'kebijakan',
            'subcategory' => 'kesehatan',
            'published_at' => $now,
            'summary' => 'Ringkasan kesehatan',
            'body' => 'Isi analisis kesehatan',
            'status' => 'terbit',
        ]);

        // Filter ekonomi
        $resEkonomi = $this->getJson('/api/isf/kebijakan-publik?subkategori=ekonomi');
        $resEkonomi->assertOk();
        $resEkonomi->assertJsonFragment(['slug' => $artEkonomi->slug]);
        $resEkonomi->assertJsonMissing(['slug' => $artKesehatan->slug]);

        // Filter kesehatan
        $resKesehatan = $this->getJson('/api/isf/kebijakan-publik?subkategori=kesehatan');
        $resKesehatan->assertOk();
        $resKesehatan->assertJsonFragment(['slug' => $artKesehatan->slug]);
        $resKesehatan->assertJsonMissing(['slug' => $artEkonomi->slug]);
    }

    public function test_policy_brief_public_api(): void
    {
        $res = $this->getJson('/api/isf/policy-brief');
        $res->assertOk();
        $res->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'slug',
                    'sector',
                    'author',
                    'institution',
                    'executive_summary',
                    'key_recommendations',
                    'pdf_path',
                    'pdf_size',
                    'published_at',
                    'published_at_formatted',
                ]
            ]
        ]);
    }

    public function test_monitoring_kebijakan_public_api(): void
    {
        $res = $this->getJson('/api/isf/monitoring-kebijakan');
        $res->assertOk();
        $res->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'program_name',
                    'slug',
                    'sector',
                    'promise',
                    'realization',
                    'progress_percent',
                    'status_label',
                    'source_title',
                    'source_url',
                    'last_updated_date',
                    'last_updated_formatted',
                ]
            ]
        ]);
    }

    public function test_policy_brief_and_monitoring_cms_acl_and_crud(): void
    {
        // 1. Guest blocked
        $this->getJson('/api/cms/policy-brief')->assertUnauthorized();
        $this->getJson('/api/cms/monitoring-kebijakan')->assertUnauthorized();

        // 2. User without feature blocked with 403
        $unauthorizedUser = $this->createAdminUser(['artikel']);
        $this->actingAs($unauthorizedUser);
        $this->getJson('/api/cms/policy-brief')->assertForbidden();
        $this->getJson('/api/cms/monitoring-kebijakan')->assertForbidden();

        // 3. Authorized user can CRUD policy brief
        $authorizedUser = $this->createAdminUser(['policy_brief', 'monitoring_kebijakan']);
        $this->actingAs($authorizedUser);

        // CREATE Policy Brief
        $postBrief = $this->postJson('/api/cms/policy-brief', [
            'title' => 'Riset Evaluasi Pajak Karbon Indonesia ' . uniqid(),
            'slug' => 'evaluasi-pajak-karbon-' . uniqid(),
            'sector' => 'lingkungan',
            'author' => 'Tim Riset ISF',
            'institution' => 'Indonesia Strategy Forum',
            'executive_summary' => 'Analisis mendalam implementasi pajak karbon pada PLTU.',
            'key_recommendations' => ['Harmonisasi regulasi pasar karbon', 'Insentif fiskal hijau'],
            'pdf_path' => '/storage/policy-briefs/test-brief.pdf',
            'pdf_size' => '1.8 MB PDF',
            'published_at' => '2026-11-16',
            'order' => 1,
            'status' => 'terbit',
        ]);
        $postBrief->assertCreated();
        $briefId = $postBrief->json('data.id');

        // UPDATE Policy Brief
        $putBrief = $this->putJson("/api/cms/policy-brief/{$briefId}", [
            'title' => 'Riset Evaluasi Pajak Karbon Revisi',
            'sector' => 'lingkungan',
            'author' => 'Tim Riset ISF Terbarui',
            'executive_summary' => 'Ringkasan terbarui.',
            'pdf_path' => '/storage/policy-briefs/test-brief-rev.pdf',
            'status' => 'terbit',
        ]);
        $putBrief->assertOk();
        $this->assertEquals('Riset Evaluasi Pajak Karbon Revisi', $putBrief->json('data.title'));

        // DELETE Policy Brief
        $delBrief = $this->deleteJson("/api/cms/policy-brief/{$briefId}");
        $delBrief->assertNoContent();

        // CREATE Monitoring Kebijakan
        $postTracker = $this->postJson('/api/cms/monitoring-kebijakan', [
            'program_name' => 'Program Modernisasi Radar Pertahanan RI ' . uniqid(),
            'slug' => 'radar-pertahanan-ri-' . uniqid(),
            'sector' => 'pertahanan',
            'promise' => 'Pemasangan 25 unit radar GCI baru di kawasan perbatasan.',
            'realization' => '13 unit radar aktif beroperasi dan terintegrasi Kohanudnas.',
            'progress_percent' => 52,
            'status_label' => 'dalam_proses',
            'source_title' => 'Kemhan RI',
            'source_url' => 'https://kemhan.go.id',
            'last_updated_date' => '2026-11-16',
            'order' => 1,
            'status' => 'terbit',
        ]);
        $postTracker->assertCreated();
        $trackerId = $postTracker->json('data.id');
        $this->assertEquals(52, $postTracker->json('data.progress_percent'));

        // UPDATE Monitoring Kebijakan
        $putTracker = $this->putJson("/api/cms/monitoring-kebijakan/{$trackerId}", [
            'program_name' => 'Program Modernisasi Radar Pertahanan Revisi',
            'sector' => 'pertahanan',
            'promise' => 'Pemasangan 25 unit radar.',
            'realization' => '18 unit radar telah selesai.',
            'progress_percent' => 72,
            'status_label' => 'dalam_proses',
            'status' => 'terbit',
        ]);
        $putTracker->assertOk();
        $this->assertEquals(72, $putTracker->json('data.progress_percent'));

        // DELETE Monitoring Kebijakan
        $delTracker = $this->deleteJson("/api/cms/monitoring-kebijakan/{$trackerId}");
        $delTracker->assertNoContent();
    }
}

