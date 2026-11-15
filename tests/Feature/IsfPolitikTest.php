<?php

namespace Tests\Feature;

use App\Models\Isf\Article;
use App\Models\Isf\ParliamentSeat;
use App\Models\Isf\Tokoh;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IsfPolitikTest extends TestCase
{
    use DatabaseTransactions;

    private function createAdminUser(array $features = ['kursi_parlemen']): User
    {
        return User::create([
            'name' => 'Editor Politik',
            'email' => 'editor-politik-' . uniqid() . '@example.test',
            'password' => Hash::make('secret123'),
            'allowed_features' => $features,
        ]);
    }

    private function createTokoh(array $attrs = []): Tokoh
    {
        return Tokoh::create(array_merge([
            'slug' => 'tokoh-politik-' . uniqid(),
            'name' => 'Tokoh Politik Uji',
            'position' => 'Anggota DPR RI Fraksi Uji',
            'bio' => 'Biodata singkat tokoh politik uji.',
            'stats' => [],
            'skor_value' => 75,
            'skor_period' => '30 hari',
            'skor_explanation' => 'Penilaian kinerja legislasi',
            'matrix_columns' => [],
            'matrix_rows' => [],
            'related_articles' => [],
            'calendar_year' => 2026,
            'calendar_month' => 11,
            'calendar_events' => [],
            'status' => 'terbit',
            'show_in_politik' => true,
        ], $attrs));
    }

    public function test_politik_api_and_page_return_expected_structure(): void
    {
        $article = Article::create([
            'slug' => 'uji-politik-headline-' . uniqid(),
            'title' => 'Uji Politik Sorotan Utama',
            'category' => 'politik',
            'subcategory' => 'pemilu',
            'summary' => 'Ringkasan dinamika politik nasional.',
            'published_at' => '2099-09-01',
            'status' => 'terbit',
        ]);

        $tokoh = $this->createTokoh();

        ParliamentSeat::create([
            'periode' => '2024-2029',
            'lembaga' => 'DPR',
            'partai' => 'Partai Solidaritas Uji',
            'singkatan' => 'PSU',
            'jumlah_kursi' => 50,
            'warna_partai' => '#3b82f6',
            'urutan' => 1,
            'status' => 'terbit',
        ]);

        $res = $this->getJson('/api/isf/politik')->assertOk();
        $res->assertJsonStructure([
            'rubrik' => ['key', 'title', 'label', 'description', 'subkategori', 'activeSubkategori'],
            'headline',
            'articles',
            'meta' => ['currentPage', 'lastPage', 'total', 'perPage'],
            'petaKursi' => ['periode', 'lembaga', 'totalKursi', 'items'],
            'tokohPolitik',
        ]);

        $this->assertNull($res->json('rubrik.activeSubkategori'));
        $this->assertSame('politik', $res->json('rubrik.key'));
        $this->assertNotEmpty($res->json('rubrik.subkategori'));

        // HTML landing page web route returns 200
        $this->get('/politik')->assertOk();
    }

    public function test_politik_api_filters_by_subcategory(): void
    {
        $pemilu = Article::create([
            'slug' => 'uji-pemilu-' . uniqid(),
            'title' => 'Uji Artikel Pemilu',
            'category' => 'politik',
            'subcategory' => 'pemilu',
            'summary' => 'Ringkasan pemilu.',
            'published_at' => '2099-09-02',
            'status' => 'terbit',
        ]);

        $partai = Article::create([
            'slug' => 'uji-partai-' . uniqid(),
            'title' => 'Uji Artikel Partai',
            'category' => 'politik',
            'subcategory' => 'partai',
            'summary' => 'Ringkasan dinamika partai.',
            'published_at' => '2099-09-03',
            'status' => 'terbit',
        ]);

        $res = $this->getJson('/api/isf/politik?subkategori=pemilu')->assertOk();
        $this->assertSame('pemilu', $res->json('rubrik.activeSubkategori'));

        $titles = collect($res->json('articles'))->pluck('title');
        if ($res->json('headline')) {
            $titles->push($res->json('headline.title'));
        }
        $this->assertTrue($titles->contains('Uji Artikel Pemilu'));

        // Web routes for subcategories
        $this->get('/politik/pemilu')->assertOk();
        $this->get('/politik/parlemen')->assertOk();
        $this->get('/politik/pemerintahan')->assertOk();
        $this->get('/politik/partai')->assertOk();
        $this->get('/politik/kategori-ngawur')->assertNotFound();
    }

    public function test_peta_kursi_api_returns_seats_and_filters(): void
    {
        ParliamentSeat::create([
            'periode' => '2024-2029',
            'lembaga' => 'DPR',
            'partai' => 'Partai Merah Uji',
            'singkatan' => 'PMU',
            'jumlah_kursi' => 100,
            'warna_partai' => '#ef4444',
            'urutan' => 1,
            'status' => 'terbit',
        ]);

        ParliamentSeat::create([
            'periode' => '2024-2029',
            'lembaga' => 'DPD',
            'partai' => 'Non-Partai Uji',
            'singkatan' => 'NPU',
            'jumlah_kursi' => 152,
            'warna_partai' => '#10b981',
            'urutan' => 1,
            'status' => 'terbit',
        ]);

        $resDpr = $this->getJson('/api/isf/peta-kursi?lembaga=DPR')->assertOk();
        $this->assertSame('DPR', $resDpr->json('lembaga'));
        $this->assertSame('2024-2029', $resDpr->json('periode'));
        $this->assertTrue(collect($resDpr->json('items'))->contains('singkatan', 'PMU'));

        $resDpd = $this->getJson('/api/isf/peta-kursi?lembaga=DPD')->assertOk();
        $this->assertSame('DPD', $resDpd->json('lembaga'));
        $this->assertTrue(collect($resDpd->json('items'))->contains('singkatan', 'NPU'));
    }

    public function test_kursi_parlemen_cms_acl_and_crud(): void
    {
        // Unauthenticated access
        $this->getJson('/api/cms/kursi-parlemen')->assertUnauthorized();

        // Authenticated user without kursi_parlemen feature
        $userWithoutFeature = User::create([
            'name' => 'Regular User',
            'email' => 'regular-' . uniqid() . '@example.test',
            'password' => Hash::make('secret123'),
            'allowed_features' => ['artikel'],
        ]);
        $this->actingAs($userWithoutFeature)
            ->getJson('/api/cms/kursi-parlemen')
            ->assertForbidden();

        // Authenticated user with kursi_parlemen feature
        $userWithFeature = $this->createAdminUser(['kursi_parlemen']);
        $this->actingAs($userWithFeature);

        // List
        $this->getJson('/api/cms/kursi-parlemen')->assertOk();

        // Create
        $seatPayload = [
            'periode' => '2024-2029',
            'lembaga' => 'DPR',
            'partai' => 'Partai Hijau Uji',
            'singkatan' => 'PHU',
            'jumlah_kursi' => 35,
            'warna_partai' => '#22c55e',
            'urutan' => 10,
            'status' => 'terbit',
        ];

        $created = $this->postJson('/api/cms/kursi-parlemen', $seatPayload)->assertCreated();
        $seatId = $created->json('data.id');
        $this->assertSame('PHU', $created->json('data.singkatan'));
        $this->assertSame(35, $created->json('data.jumlah_kursi'));

        // Update
        $this->putJson("/api/cms/kursi-parlemen/{$seatId}", array_merge($seatPayload, [
            'jumlah_kursi' => 40,
        ]))->assertOk()->assertJsonPath('data.jumlah_kursi', 40);

        // Delete
        $this->deleteJson("/api/cms/kursi-parlemen/{$seatId}")->assertNoContent();
        $this->assertDatabaseMissing('isf_parliament_seats', ['id' => $seatId]);
    }

    public function test_category_article_and_tokoh_support_politik_extensions(): void
    {
        $user = $this->createAdminUser(['kategori', 'artikel', 'tokoh']);
        $this->actingAs($user);

        // Category with parent_id
        $parentCat = $this->postJson('/api/cms/kategori', [
            'name' => 'Politik Induk Test',
            'slug' => 'politik-induk-' . uniqid(),
            'description' => 'Induk',
            'status' => 'terbit',
        ])->assertCreated()->json('data');

        $subCat = $this->postJson('/api/cms/kategori', [
            'name' => 'Subkategori Test',
            'slug' => 'sub-test-' . uniqid(),
            'parent_id' => $parentCat['id'],
            'description' => 'Anak',
            'status' => 'terbit',
        ])->assertCreated()->json('data');

        $this->assertSame($parentCat['id'], $subCat['parent_id']);

        // Article with subcategory
        $art = $this->postJson('/api/cms/artikel', [
            'title' => 'Artikel Ekstensi Politik',
            'slug' => 'art-politik-' . uniqid(),
            'category' => 'politik',
            'subcategory' => 'pemilu',
            'published_at' => '2026-11-15',
            'summary' => 'Ringkasan',
            'status' => 'terbit',
        ])->assertCreated()->json('data');

        $this->assertSame('pemilu', $art['subcategory']);

        // Tokoh with show_in_politik
        $t = $this->postJson('/api/cms/tokoh', [
            'name' => 'Tokoh Ekstensi Politik',
            'slug' => 'tokoh-ekstensi-' . uniqid(),
            'position' => 'Ketua Fraksi',
            'stats' => [['label' => 'RUU Selesai', 'value' => 5]],
            'skor_value' => 80,
            'skor_period' => '30 hari',
            'skor_explanation' => 'Kinerja fraksi',
            'matrix_columns' => ['Kriteria'],
            'matrix_rows' => [],
            'related_articles' => [],
            'calendar_year' => 2026,
            'calendar_month' => 11,
            'calendar_events' => [],
            'status' => 'terbit',
            'show_in_politik' => true,
        ])->assertCreated()->json('data');

        $this->assertTrue((bool) $t['show_in_politik']);
    }
}
