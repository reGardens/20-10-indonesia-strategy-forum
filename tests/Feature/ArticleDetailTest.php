<?php

namespace Tests\Feature;

use App\Models\Isf\Article;
use App\Services\Isf\IsfCacheService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Halaman detail artikel publik /artikel/{kategori}/{slug} dan tautan default artikel.
 */
class ArticleDetailTest extends TestCase
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

    private function article(array $attrs = []): Article
    {
        return Article::create(array_merge([
            'slug' => 'uji-detail-' . uniqid(),
            'title' => 'Uji Artikel Detail',
            'category' => 'hukum',
            'summary' => 'Ringkasan uji',
            'body' => '<p>Isi naskah uji</p>',
            'author_name' => 'Penulis Uji',
            'author_role' => 'Peneliti ISF',
            'reading_minutes' => 6,
            'published_at' => '2099-05-01',
            'status' => 'terbit',
        ], $attrs));
    }

    public function test_public_url_defaults_to_article_detail(): void
    {
        $plain = $this->article(['link_url' => null]);
        $legacy = $this->article(['link_url' => '/isu/anatomi-lama']);
        $custom = $this->article(['link_url' => 'https://example.test/laporan']);
        $ruu = $this->article(['category' => 'ruu']);
        $ekonomi = $this->article(['category' => 'ekonomi']);
        $politik = $this->article(['category' => 'politik']);

        // URL detail per kategori artikel: /artikel/{kategori}/{slug}.
        $this->assertSame('/artikel/hukum/' . $plain->slug, $plain->publicUrl());
        $this->assertSame('/artikel/hukum/' . $legacy->slug, $legacy->publicUrl());
        $this->assertSame('https://example.test/laporan', $custom->publicUrl());
        $this->assertSame('/artikel/hukum/' . $custom->slug, $custom->detailPath());
        // Nilai lama ruu/ekonomi (sebelum digabung) memakai URL kategori gabungan.
        $this->assertSame('/artikel/hukum/' . $ruu->slug, $ruu->publicUrl());
        $this->assertSame('/artikel/kebijakan/' . $ekonomi->slug, $ekonomi->publicUrl());
        $this->assertSame('KEBIJAKAN PUBLIK', $ekonomi->categoryLabel());
        $this->assertSame('/artikel/politik/' . $politik->slug, $politik->publicUrl());
    }

    public function test_detail_api_returns_published_article_with_related(): void
    {
        $main = $this->article([
            'fact_blocks' => [
                ['claim' => 'Klaim uji', 'sourceUrl' => 'https://example.test/sumber', 'sourceTitle' => 'Sumber Uji', 'sourceDate' => '2099-04-01'],
                ['claim' => '', 'sourceUrl' => ''],
            ],
        ]);
        $sibling = $this->article(['title' => 'Uji Terkait', 'published_at' => '2099-04-30']);
        $this->article(['title' => 'Uji Kategori Lain', 'category' => 'politik']);
        $this->article(['title' => 'Uji Draf Terkait', 'status' => 'draf']);

        $res = $this->getJson('/api/isf/artikel/' . $main->slug)->assertOk();

        $res->assertJsonPath('data.slug', $main->slug)
            ->assertJsonPath('data.title', 'Uji Artikel Detail')
            ->assertJsonPath('data.body', '<p>Isi naskah uji</p>')
            ->assertJsonPath('data.author.name', 'Penulis Uji')
            ->assertJsonPath('data.author.role', 'Peneliti ISF')
            ->assertJsonPath('data.readingMinutes', 6)
            ->assertJsonPath('data.publishedAt', '2099-05-01')
            ->assertJsonPath('data.path', '/artikel/hukum/' . $main->slug)
            ->assertJsonPath('data.kategori.key', 'hukum')
            ->assertJsonPath('data.kategori.title', 'Hukum')
            ->assertJsonPath('data.kategori.to', '/artikel/hukum')
            ->assertJsonCount(1, 'data.factBlocks')
            ->assertJsonPath('data.factBlocks.0.claim', 'Klaim uji');

        $related = collect($res->json('related'));
        $this->assertTrue($related->contains('slug', $sibling->slug));
        $this->assertFalse($related->contains('slug', $main->slug), 'artikel itu sendiri tidak masuk terkait');
        $this->assertFalse($related->contains('title', 'Uji Kategori Lain'));
        $this->assertFalse($related->contains('title', 'Uji Draf Terkait'));
        $this->assertSame('/artikel/hukum/' . $sibling->slug, $related->firstWhere('slug', $sibling->slug)['to']);

        $this->assertFalse(collect($res->json('recentArticles'))->contains('slug', $main->slug));
    }

    public function test_draft_or_unknown_article_returns_404(): void
    {
        $draft = $this->article(['status' => 'draf']);

        $this->getJson('/api/isf/artikel/' . $draft->slug)->assertNotFound();
        $this->getJson('/api/isf/artikel/tidak-ada-' . uniqid())->assertNotFound();
    }

    public function test_detail_page_and_legacy_isu_redirect(): void
    {
        // Artikel tidak ditemukan: SPA tetap dirender (halaman "tidak ditemukan").
        $this->get('/artikel/apa-saja')->assertOk();
        $this->get('/isu/kuhp-baru-tidak-ada')->assertStatus(301)->assertRedirect('/artikel/kuhp-baru-tidak-ada');
    }

    public function test_article_urls_redirect_to_canonical_category_url(): void
    {
        $art = $this->article(['category' => 'hukum']);
        $canonical = '/artikel/hukum/' . $art->slug;

        $this->get($canonical)->assertOk();
        $this->get('/artikel/' . $art->slug)->assertStatus(301)->assertRedirect($canonical);
        $this->get('/artikel/politik/' . $art->slug)->assertStatus(301)->assertRedirect($canonical);
        // URL kategori lama yang sudah digabung.
        $this->get('/artikel/ruu/' . $art->slug)->assertStatus(301)->assertRedirect($canonical);
        $this->get('/isu/' . $art->slug)->assertStatus(301)->assertRedirect($canonical);
        $this->get('/artikel/kebijakan-publik')->assertStatus(301)->assertRedirect('/artikel/kebijakan');
    }

    public function test_one_name_per_category_for_breadcrumb_and_label(): void
    {
        $art = $this->article(['category' => 'kebijakan']);

        // Nama sama dengan menu navbar: breadcrumb, judul, dan label kartu.
        $this->getJson('/api/isf/artikel/' . $art->slug)->assertOk()
            ->assertJsonPath('data.path', '/artikel/kebijakan/' . $art->slug)
            ->assertJsonPath('data.category', 'Kebijakan Publik')
            ->assertJsonPath('data.kategori.title', 'Kebijakan Publik')
            ->assertJsonPath('data.kategori.to', '/artikel/kebijakan');
    }

    public function test_beranda_hukum_section_links_to_article_detail(): void
    {
        $art = $this->article(['title' => 'Uji Hukum Beranda', 'published_at' => '2099-12-31']);

        $items = collect($this->getJson('/api/isf/beranda')->assertOk()->json('sections.hukum.items'));
        $item = $items->firstWhere('title', 'Uji Hukum Beranda');

        $this->assertNotNull($item, 'artikel hukum terbaru harus tampil di section Hukum');
        $this->assertSame('/artikel/hukum/' . $art->slug, $item['to']);
    }

    public function test_beranda_politik_cards_show_cms_category_not_fixed_label(): void
    {
        $this->article(['title' => 'Uji Politik Utama', 'category' => 'politik', 'published_at' => '2099-12-31']);
        $this->article(['title' => 'Uji Politik Kedua', 'category' => 'politik', 'published_at' => '2099-12-30']);

        $sec = $this->getJson('/api/isf/beranda')->assertOk()->json('sections.politik');

        $this->assertSame('POLITIK', $sec['lead']['kicker']);
        $second = collect($sec['items'])->firstWhere('title', 'Uji Politik Kedua');
        $this->assertSame('POLITIK', $second['kicker'], 'tidak ada label "PARLEMEN" yang bukan kategori CMS');
    }
}
