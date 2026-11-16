<?php

namespace Tests\Feature;

use App\Models\Isf\Article;
use App\Models\Isf\CaseTimeline;
use App\Models\Isf\CaseEvent;
use App\Models\Isf\LegalGlossary;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IsfHukumTest extends TestCase
{
    use DatabaseTransactions;

    private function createAdminUser(array $features = ['glosarium_hukum', 'timeline_kasus']): User
    {
        return User::create([
            'name' => 'Editor Hukum',
            'email' => 'editor-hukum-' . uniqid() . '@example.test',
            'password' => Hash::make('secret123'),
            'allowed_features' => $features,
        ]);
    }

    public function test_hukum_public_api_and_page_return_expected_structure(): void
    {
        // 1. Web route
        $web = $this->get('/hukum');
        $web->assertOk();

        // 2. Public API endpoint
        $res = $this->getJson('/api/isf/hukum');
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
            'opiniPakar',
            'timelineKasus',
            'glosarium',
        ]);

        $this->assertEquals('hukum', $res->json('rubrik.key'));
    }

    public function test_hukum_filter_by_subcategory(): void
    {
        $now = now();
        $artRegulasi = Article::create([
            'title' => 'Analisis UU Baru ' . uniqid(),
            'slug' => 'analisis-uu-baru-' . uniqid(),
            'category' => 'hukum',
            'subcategory' => 'regulasi',
            'published_at' => $now,
            'summary' => 'Ringkasan regulasi baru',
            'body' => 'Isi analisis regulasi',
            'status' => 'terbit',
        ]);

        $artPutusan = Article::create([
            'title' => 'Analisis Putusan MK ' . uniqid(),
            'slug' => 'analisis-putusan-mk-' . uniqid(),
            'category' => 'hukum',
            'subcategory' => 'putusan',
            'published_at' => $now,
            'summary' => 'Ringkasan putusan MK',
            'body' => 'Isi analisis putusan',
            'status' => 'terbit',
        ]);

        $resRegulasi = $this->getJson('/api/isf/hukum?subkategori=regulasi');
        $resRegulasi->assertOk();
        $resRegulasi->assertJsonFragment(['slug' => $artRegulasi->slug]);
        $resRegulasi->assertJsonMissing(['slug' => $artPutusan->slug]);

        $resPutusan = $this->getJson('/api/isf/hukum?subkategori=putusan');
        $resPutusan->assertOk();
        $resPutusan->assertJsonFragment(['slug' => $artPutusan->slug]);
        $resPutusan->assertJsonMissing(['slug' => $artRegulasi->slug]);
    }

    public function test_glosarium_hukum_api_filters_by_letter_and_search(): void
    {
        // 1. Query by letter
        $resLetter = $this->getJson('/api/isf/glosarium-hukum?letter=K');
        $resLetter->assertOk();
        $resLetter->assertJsonStructure([
            'query',
            'letter',
            'total',
            'availableLetters',
            'items',
        ]);
        $itemsK = $resLetter->json('items');
        foreach ($itemsK as $it) {
            $this->assertEquals('K', $it['abjad']);
        }

        // 2. Query by search text
        $resSearch = $this->getJson('/api/isf/glosarium-hukum?q=Kasasi');
        $resSearch->assertOk();
        $this->assertNotEmpty($resSearch->json('items'));
        $this->assertEquals('Kasasi', $resSearch->json('items.0.istilah'));
    }

    public function test_timeline_kasus_public_api(): void
    {
        $res = $this->getJson('/api/isf/timeline-kasus');
        $res->assertOk();
        $res->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'judul_kasus',
                    'slug',
                    'kategori_kasus',
                    'deskripsi',
                    'status_kasus',
                    'gambar',
                    'events' => [
                        '*' => [
                            'id',
                            'tanggal',
                            'tahap',
                            'judul',
                            'deskripsi',
                            'sumber_tautan',
                        ]
                    ]
                ]
            ]
        ]);
    }

    public function test_opini_pakar_is_filtered_correctly(): void
    {
        $now = now();
        $expertArt = Article::create([
            'title' => 'Pandangan Yuridis Pakar ' . uniqid(),
            'slug' => 'pandangan-yuridis-pakar-' . uniqid(),
            'category' => 'hukum',
            'is_expert_opinion' => true,
            'expert_name' => 'Prof. Dr. Bagir Manan, S.H.',
            'expert_title' => 'Guru Besar Hukum Tata Negara',
            'published_at' => $now,
            'summary' => 'Ringkasan pandangan pakar',
            'status' => 'terbit',
        ]);

        $regArt = Article::create([
            'title' => 'Berita Hukum Reguler ' . uniqid(),
            'slug' => 'berita-hukum-reguler-' . uniqid(),
            'category' => 'hukum',
            'is_expert_opinion' => false,
            'published_at' => $now,
            'summary' => 'Ringkasan berita',
            'status' => 'terbit',
        ]);

        $res = $this->getJson('/api/isf/hukum');
        $res->assertOk();
        $opiniList = $res->json('opiniPakar');

        $this->assertTrue(collect($opiniList)->contains('slug', $expertArt->slug));
        $this->assertFalse(collect($opiniList)->contains('slug', $regArt->slug));

        $found = collect($opiniList)->firstWhere('slug', $expertArt->slug);
        $this->assertEquals('Prof. Dr. Bagir Manan, S.H.', $found['expert_name']);
        $this->assertEquals('Guru Besar Hukum Tata Negara', $found['expert_title']);
    }

    public function test_glosarium_and_timeline_cms_acl_and_crud(): void
    {
        // 1. Guest blocked
        $this->getJson('/api/cms/glosarium-hukum')->assertUnauthorized();
        $this->getJson('/api/cms/timeline-kasus')->assertUnauthorized();

        // 2. User without feature blocked with 403
        $unauthorizedUser = $this->createAdminUser(['artikel']);
        $this->actingAs($unauthorizedUser);
        $this->getJson('/api/cms/glosarium-hukum')->assertForbidden();
        $this->getJson('/api/cms/timeline-kasus')->assertForbidden();

        // 3. Authorized user can CRUD glosarium
        $authorizedUser = $this->createAdminUser(['glosarium_hukum', 'timeline_kasus']);
        $this->actingAs($authorizedUser);

        // CREATE Glosarium
        $postGlossary = $this->postJson('/api/cms/glosarium-hukum', [
            'istilah' => 'Adat Recht Uji',
            'slug' => 'adat-recht-uji-' . uniqid(),
            'definisi' => 'Sistem hukum kebiasaan tradisional masyarakat adat.',
            'dasar_hukum' => 'Pasal 18B ayat (2) UUD 1945',
            'istilah_terkait' => ['Hukum Adat', 'Masyarakat Adat'],
            'urutan' => 10,
            'status' => 'terbit',
        ]);
        $postGlossary->assertCreated();
        $glossaryId = $postGlossary->json('data.id');
        $this->assertEquals('A', $postGlossary->json('data.abjad'));

        // UPDATE Glosarium
        $putGlossary = $this->putJson("/api/cms/glosarium-hukum/{$glossaryId}", [
            'istilah' => 'Adat Recht Terbarui',
            'definisi' => 'Definisi terbarui hukum kebiasaan.',
            'status' => 'terbit',
        ]);
        $putGlossary->assertOk();
        $this->assertEquals('Adat Recht Terbarui', $putGlossary->json('data.istilah'));

        // DELETE Glosarium
        $delGlossary = $this->deleteJson("/api/cms/glosarium-hukum/{$glossaryId}");
        $delGlossary->assertNoContent();

        // CREATE Timeline Kasus & Events
        $postTimeline = $this->postJson('/api/cms/timeline-kasus', [
            'judul_kasus' => 'Perkara Sengketa Tata Ruang Kawasan Pesisir ' . uniqid(),
            'slug' => 'sengketa-tata-ruang-pesisir-' . uniqid(),
            'kategori_kasus' => 'Hukum Lingkungan & Agraria',
            'status_kasus' => 'Proses Sidang PTUN',
            'status' => 'terbit',
            'events' => [
                [
                    'tanggal_peristiwa' => '2024-03-15',
                    'tahap' => 'Pendaftaran Gugatan',
                    'judul' => 'Koalisi Warga Mendaftarkan Gugatan Izin Lingkungan ke PTUN',
                    'deskripsi' => 'Penggugat mendalilkan ketiadaan AMDAL yang sah.',
                    'status' => 'terbit',
                ],
                [
                    'tanggal_peristiwa' => '2024-06-20',
                    'tahap' => 'Putusan Sela',
                    'judul' => 'Majelis Hakim Menolak Eksepsi Tergugat',
                    'deskripsi' => 'Sidang dilanjutkan ke tahap pembuktian saksi ahli.',
                    'status' => 'terbit',
                ]
            ],
        ]);
        $postTimeline->assertCreated();
        $timelineId = $postTimeline->json('data.id');
        $this->assertCount(2, $postTimeline->json('data.events'));

        // DELETE Timeline Kasus
        $delTimeline = $this->deleteJson("/api/cms/timeline-kasus/{$timelineId}");
        $delTimeline->assertNoContent();
    }
}
