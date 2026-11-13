<?php

namespace Tests\Feature;

use App\Models\Isf\Article;
use App\Models\User;
use App\Services\Isf\IsfCacheService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Section Beranda "Kebijakan Publik & Fiskal" dari CMS Artikel, dan label/durasi video dari CMS Media Sorotan.
 */
class BerandaSectionsCmsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        IsfCacheService::clearBeranda();
    }

    protected function tearDown(): void
    {
        // Jangan tinggalkan respons Beranda berisi data uji di cache lokal.
        IsfCacheService::clearBeranda();
        parent::tearDown();
    }

    private function article(string $title, string $category, string $date, array $extra = []): Article
    {
        return Article::create(array_merge([
            'slug' => 'uji-kbj-' . uniqid(),
            'title' => $title,
            'category' => $category,
            'summary' => "Ringkasan {$title}",
            'body' => '<p>Isi</p>',
            'author_name' => 'Penulis Uji',
            'author_role' => 'Peneliti ISF',
            'published_at' => $date,
            'status' => 'terbit',
        ], $extra));
    }

    public function test_kebijakan_section_uses_latest_kebijakan_articles(): void
    {
        // Tanggal di masa depan agar pasti menjadi 3 artikel terbaru.
        $lead = $this->article('Uji Kebijakan Utama', 'kebijakan', '2099-01-03', ['link_url' => '/isu/uji-utama']);
        $this->article('Uji Ekonomi Dampak', 'kebijakan', '2099-01-02', ['fact_blocks' => [
            ['claim' => 'Poin satu', 'sourceUrl' => 'https://example.test/1'],
            ['claim' => 'Poin dua', 'sourceUrl' => 'https://example.test/2'],
        ]]);
        $this->article('Uji Kebijakan Brief', 'kebijakan', '2099-01-01');
        $this->article('Uji Politik Tidak Ikut', 'politik', '2099-01-04');

        $sec = $this->getJson('/api/isf/beranda')->assertOk()->json('sections.kebijakan');

        $this->assertSame('Uji Kebijakan Utama', $sec['lead']['title']);
        // Tautan lama /isu/... (rute sudah dihapus) diarahkan ke detail artikel.
        $this->assertSame('/artikel/kebijakan/' . $lead->slug, $sec['lead']['to']);
        $this->assertSame('Penulis Uji, Peneliti ISF', $sec['lead']['author']);
        // Label kartu = kategori CMS artikel (bukan teks tetap).
        $this->assertSame('KEBIJAKAN PUBLIK', $sec['lead']['kicker']);
        $this->assertSame('KEBIJAKAN PUBLIK', $sec['impact']['kicker']);

        $this->assertSame('Uji Ekonomi Dampak', $sec['impact']['title']);
        $this->assertSame(['Poin satu', 'Poin dua'], $sec['impact']['keyPoints']);

        $this->assertSame('Uji Kebijakan Brief', $sec['brief']['title']);
        $this->assertSame('Ringkasan Uji Kebijakan Brief', $sec['brief']['quote']);
        $this->assertSame('Peneliti ISF', $sec['brief']['authorRole']);
    }

    public function test_impact_without_fact_blocks_uses_summary_as_single_point(): void
    {
        $this->article('Uji Utama 2', 'kebijakan', '2099-02-02');
        $this->article('Uji Dampak Tanpa Fact', 'kebijakan', '2099-02-01');

        $impact = $this->getJson('/api/isf/beranda')->assertOk()->json('sections.kebijakan.impact');

        $this->assertSame(['Ringkasan Uji Dampak Tanpa Fact'], $impact['keyPoints']);
    }

    public function test_article_without_summary_does_not_show_sample_text(): void
    {
        $this->article('Uji Utama Kosong', 'kebijakan', '2099-03-03', ['summary' => null]);
        $this->article('Uji Dampak Kosong', 'kebijakan', '2099-03-02', ['summary' => null]);
        $this->article('Uji Brief Kosong', 'kebijakan', '2099-03-01', ['summary' => null, 'author_role' => null]);

        $sec = $this->getJson('/api/isf/beranda')->assertOk()->json('sections.kebijakan');

        // Judul artikel asli tidak dicampur dengan teks contoh.
        $this->assertSame('', $sec['lead']['summary']);
        $this->assertSame([], $sec['impact']['keyPoints']);
        $this->assertSame('', $sec['brief']['quote']);
        $this->assertSame('', $sec['brief']['authorRole']);
    }

    public function test_media_sorotan_label_and_duration_are_saved_and_shown_in_video_section(): void
    {
        $this->actingAs(User::create([
            'name' => 'Admin Uji', 'email' => 'uji-media-' . uniqid() . '@example.test', 'password' => Hash::make('rahasia-123'),
        ]));

        $this->postJson('/api/cms/media-sorotan', ['title' => 'Uji Video', 'duration' => '18:4x'])
            ->assertUnprocessable()->assertJsonValidationErrors('duration');

        // Field dikirim sama seperti form CMS (useCmsForm mengirim semua field defaults).
        $form = [
            'imageUrl' => '', 'mediaUrl' => '', 'category' => 'YouTube', 'publishedAt' => '2099-01-01',
            'order' => 0, 'status' => 'terbit', 'author' => 'Redaksi Uji', 'summary' => '', 'content' => '', 'is_popular' => false,
        ];

        $created = $this->postJson('/api/cms/media-sorotan', $form + [
            'title' => 'Uji Video Label', 'label' => 'Wawancara Eksklusif', 'duration' => '1:02:30',
        ])->assertCreated()->json('data');
        $this->assertSame('Wawancara Eksklusif', $created['label']);
        $this->assertSame('1:02:30', $created['duration']);

        // Update tanpa mengirim label/durasi → nilai lama dipertahankan.
        $updated = $this->putJson('/api/cms/media-sorotan/' . $created['id'], $form + ['title' => 'Uji Video Label 2'])
            ->assertOk()->json('data');
        $this->assertSame('Wawancara Eksklusif', $updated['label']);
        $this->assertSame('1:02:30', $updated['duration']);

        $video = $this->getJson('/api/isf/beranda')->assertOk()->json('sections.video');
        $card = collect(array_merge([$video['featured']], $video['playlist']))->firstWhere('title', 'Uji Video Label 2');

        $this->assertNotNull($card, 'video uji harus tampil di section Multimedia & Video');
        $this->assertSame('Wawancara Eksklusif', $card['category']);
        $this->assertSame('1:02:30', $card['duration']);
    }
}
