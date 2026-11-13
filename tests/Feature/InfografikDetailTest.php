<?php

namespace Tests\Feature;

use App\Models\Isf\Infographic;
use App\Services\Isf\IsfCacheService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Halaman detail infografik publik /infografik/{slug} dan tautan kartu infografik.
 */
class InfografikDetailTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        IsfCacheService::clearBeranda();
        IsfCacheService::clearInfografik();
    }

    protected function tearDown(): void
    {
        // Jangan tinggalkan respons berisi data uji di cache lokal.
        IsfCacheService::clearBeranda();
        IsfCacheService::clearInfografik();
        parent::tearDown();
    }

    private function infografik(array $attrs = []): Infographic
    {
        return Infographic::create(array_merge([
            'slug' => 'uji-info-' . uniqid(),
            'title' => 'Uji Infografik',
            'category' => 'hukum',
            'published_at' => '2099-06-01',
            'image_alt' => 'Deskripsi uji',
            'image_url' => 'https://example.test/info.png',
            'status' => 'terbit',
        ], $attrs));
    }

    public function test_detail_api_returns_published_item_with_related(): void
    {
        $main = $this->infografik();
        $sibling = $this->infografik(['title' => 'Uji Info Terkait', 'published_at' => '2099-05-30']);
        $draft = $this->infografik(['title' => 'Uji Info Draf', 'status' => 'draf']);

        $res = $this->getJson('/api/isf/infografik/' . $main->slug)->assertOk()
            ->assertJsonPath('data.slug', $main->slug)
            ->assertJsonPath('data.title', 'Uji Infografik')
            ->assertJsonPath('data.alt', 'Deskripsi uji')
            ->assertJsonPath('data.imageUrl', 'https://example.test/info.png')
            ->assertJsonPath('data.to', '/infografik/' . $main->slug);

        $related = collect($res->json('related'));
        $this->assertLessThanOrEqual(4, $related->count());
        $this->assertSame($sibling->slug, $related->first()['slug'], 'kategori sama didahulukan');
        $this->assertFalse($related->contains('slug', $main->slug), 'infografik itu sendiri tidak masuk lainnya');
        $this->assertFalse($related->contains('slug', $draft->slug));
    }

    public function test_draft_or_unknown_item_returns_404(): void
    {
        $draft = $this->infografik(['status' => 'draf']);

        $this->getJson('/api/isf/infografik/' . $draft->slug)->assertNotFound();
        $this->getJson('/api/isf/infografik/tidak-ada-' . uniqid())->assertNotFound();
    }

    public function test_list_beranda_and_search_link_to_detail(): void
    {
        $item = $this->infografik(['title' => 'Uji Info Tautan Unik', 'published_at' => '2099-12-31']);
        $url = '/infografik/' . $item->slug;

        $list = collect($this->getJson('/api/isf/infografik')->assertOk()->json('data'));
        $this->assertSame($url, $list->firstWhere('slug', $item->slug)['to']);

        $beranda = collect($this->getJson('/api/isf/beranda')->assertOk()->json('infografik'));
        $this->assertSame($url, $beranda->firstWhere('slug', $item->slug)['to'] ?? null);

        $search = collect($this->getJson('/api/isf/search?q=' . urlencode('Uji Info Tautan Unik') . '&type=infografik')->assertOk()->json('data'));
        $this->assertSame($url, $search->firstWhere('title', 'Uji Info Tautan Unik')['to'] ?? null);
    }

    public function test_detail_page_route(): void
    {
        $this->get('/infografik/apa-saja')->assertOk();
        $this->get('/infografik')->assertOk();
    }
}
