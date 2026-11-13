<?php

namespace Tests\Feature;

use App\Models\Isf\Article;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Halaman kategori artikel /artikel/{kategori} (politik, hukum, kebijakan) dan API /api/isf/kategori/{kategori}.
 */
class RubrikPageTest extends TestCase
{
    use DatabaseTransactions;

    private function article(string $title, string $category, array $attrs = []): Article
    {
        return Article::create(array_merge([
            'slug' => 'uji-kategori-' . uniqid(),
            'title' => $title,
            'category' => $category,
            'summary' => "Ringkasan {$title}",
            'published_at' => '2099-07-01',
            'status' => 'terbit',
        ], $attrs));
    }

    public function test_category_lists_only_published_articles_of_that_category(): void
    {
        $hukum = $this->article('Uji Kategori Hukum', 'hukum', ['published_at' => '2099-07-02']);
        // Nilai lama "ruu" (sebelum migration digabung) tetap masuk Hukum.
        $legacyRuu = $this->article('Uji Kategori RUU Lama', 'ruu');
        $this->article('Uji Kategori Politik', 'politik');
        $this->article('Uji Kategori Draf', 'hukum', ['status' => 'draf']);

        $res = $this->getJson('/api/isf/kategori/hukum')->assertOk()
            ->assertJsonPath('kategori.key', 'hukum')
            ->assertJsonPath('kategori.title', 'Hukum')
            ->assertJsonPath('kategori.label', 'Hukum')
            ->assertJsonPath('kategori.to', '/artikel/hukum')
            ->assertJsonPath('meta.offset', 0);

        $items = collect($res->json('data'));
        $this->assertSame($hukum->slug, $items->first()['slug'], 'terbaru di atas');
        $this->assertSame('/artikel/hukum/' . $hukum->slug, $items->first()['to']);
        $this->assertSame('Hukum', $items->first()['category']);
        $this->assertTrue($items->contains('slug', $legacyRuu->slug));
        $this->assertSame('/artikel/hukum/' . $legacyRuu->slug, $items->firstWhere('slug', $legacyRuu->slug)['to']);
        $this->assertFalse($items->contains('title', 'Uji Kategori Politik'));
        $this->assertFalse($items->contains('title', 'Uji Kategori Draf'));
    }

    public function test_load_more_returns_six_next_articles_by_offset(): void
    {
        for ($i = 1; $i <= 14; $i++) {
            $this->article("Uji Kategori Politik {$i}", 'politik', ['published_at' => sprintf('2099-08-%02d', $i)]);
        }

        // Halaman awal: 1 Sorotan Utama + 6 Artikel Terbaru.
        $first = $this->getJson('/api/isf/kategori/politik')->assertOk();
        $this->assertCount(7, $first->json('data'));
        $this->assertSame(7, $first->json('meta.nextOffset'));
        $this->assertSame('Uji Kategori Politik 14', $first->json('data.0.title'));

        // "Lihat lainnya": 6 artikel berikutnya.
        $more = $this->getJson('/api/isf/kategori/politik?offset=7&limit=6')->assertOk();
        $this->assertCount(6, $more->json('data'));
        $this->assertSame('Uji Kategori Politik 7', $more->json('data.0.title'));
        $this->assertSame(13, $more->json('meta.nextOffset'));
    }

    public function test_unknown_category_returns_404_and_pages_render(): void
    {
        $this->getJson('/api/isf/kategori/opini-publik')->assertNotFound();
        $this->getJson('/api/isf/kategori/ruu')->assertNotFound();

        foreach (['/artikel/politik', '/artikel/hukum', '/artikel/kebijakan'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            // Navbar shell awal menautkan halaman kategori (bukan '#').
            $this->assertStringContainsString('href="/artikel/politik"', $html);
            $this->assertStringContainsString('href="/artikel/hukum"', $html);
            $this->assertStringContainsString('href="/artikel/kebijakan"', $html);
        }
    }

    public function test_merged_category_pages_redirect(): void
    {
        $this->get('/artikel/ruu')->assertStatus(301)->assertRedirect('/artikel/hukum');
        $this->get('/artikel/ekonomi')->assertStatus(301)->assertRedirect('/artikel/kebijakan');
        $this->get('/artikel/kebijakan-publik')->assertStatus(301)->assertRedirect('/artikel/kebijakan');
    }
}
