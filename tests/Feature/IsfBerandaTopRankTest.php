<?php

namespace Tests\Feature;

use App\Models\Isf\Article;
use App\Models\Isf\Setting;
use App\Models\User;
use App\Services\Isf\IsfCacheService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IsfBerandaTopRankTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        IsfCacheService::clearAll();
    }

    protected function tearDown(): void
    {
        IsfCacheService::clearAll();
        parent::tearDown();
    }

    private function superAdmin(): User
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $user = User::create([
            'name' => 'Admin Top Rank',
            'email' => 'admin-toprank-' . uniqid() . '@example.com',
            'password' => Hash::make('secret123'),
        ]);
        $user->assignRole('super-admin');

        return $user;
    }

    private function memberUser(): User
    {
        Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);

        $user = User::create([
            'name' => 'Member Test',
            'first_name' => 'Budi',
            'last_name' => 'Santoso',
            'email' => 'member-' . uniqid() . '@example.com',
            'whatsapp' => '6281234567890',
            'password' => Hash::make('secret123'),
        ]);
        $user->assignRole('member');

        return $user;
    }

    public function test_public_beranda_api_returns_top_rank_1_to_3(): void
    {
        $response = $this->getJson(route('api.isf.beranda'));

        $response->assertOk()
            ->assertJsonStructure([
                'topRank' => [
                    '*' => [
                        'rank',
                        'title',
                        'summary',
                        'author',
                        'image_url',
                        'to',
                        'status',
                    ],
                ],
            ]);

        $topRank = $response->json('topRank');
        $this->assertNotEmpty($topRank);
        $this->assertLessThanOrEqual(3, count($topRank));
        $this->assertEquals(1, $topRank[0]['rank']);
    }

    public function test_cms_top_rank_endpoints_work_for_admin(): void
    {
        $admin = $this->superAdmin();

        // 1. Get article options
        $optionsResponse = $this->actingAs($admin)->getJson('/api/cms/top-rank/article-options');
        $optionsResponse->assertOk()
            ->assertJsonStructure(['data']);

        // 2. Get current top rank
        $getResponse = $this->actingAs($admin)->getJson('/api/cms/top-rank');
        $getResponse->assertOk()
            ->assertJsonStructure(['data']);

        // 3. Update top rank
        $payload = [
            'items' => [
                [
                    'rank' => 1,
                    'title' => 'Top 1: Kebijakan Transformasi Fiskal 2026',
                    'summary' => 'Ringkasan artikel pertama yang dikurasi.',
                    'author' => 'Tim Riset ISF',
                    'image_url' => 'https://example.com/img1.jpg',
                    'url' => '/artikel/kebijakan/top-1',
                    'status' => 'terbit',
                ],
                [
                    'rank' => 2,
                    'title' => 'Top 2: Kepastian Hukum Berusaha',
                    'summary' => 'Ringkasan artikel kedua yang dikurasi.',
                    'author' => 'Dewan Pakar Hukum',
                    'image_url' => 'https://example.com/img2.jpg',
                    'url' => '/artikel/hukum/top-2',
                    'status' => 'terbit',
                ],
                [
                    'rank' => 3,
                    'title' => 'Top 3: Peta Koalisi Parlemen',
                    'summary' => 'Ringkasan artikel ketiga yang dikurasi.',
                    'author' => 'Analis Politik',
                    'image_url' => 'https://example.com/img3.jpg',
                    'url' => '/artikel/politik/top-3',
                    'status' => 'terbit',
                ],
            ],
        ];

        $putResponse = $this->actingAs($admin)->putJson('/api/cms/top-rank', $payload);
        $putResponse->assertOk()
            ->assertJsonPath('data.0.title', 'Top 1: Kebijakan Transformasi Fiskal 2026')
            ->assertJsonPath('data.1.title', 'Top 2: Kepastian Hukum Berusaha')
            ->assertJsonPath('data.2.title', 'Top 3: Peta Koalisi Parlemen');

        // 4. Verify Beranda reflects curated top rank
        $berandaResponse = $this->getJson(route('api.isf.beranda'));
        $berandaResponse->assertOk()
            ->assertJsonPath('topRank.0.title', 'Top 1: Kebijakan Transformasi Fiskal 2026')
            ->assertJsonPath('topRank.1.title', 'Top 2: Kepastian Hukum Berusaha')
            ->assertJsonPath('topRank.2.title', 'Top 3: Peta Koalisi Parlemen');
    }

    public function test_user_can_view_and_update_profile_api(): void
    {
        $member = $this->memberUser();

        // 1. Get profile
        $getResponse = $this->actingAs($member)->getJson('/api/isf/profile');
        $getResponse->assertOk()
            ->assertJsonPath('data.email', $member->email)
            ->assertJsonPath('data.first_name', 'Budi')
            ->assertJsonPath('data.last_name', 'Santoso')
            ->assertJsonPath('data.role_label', 'Member');

        // 2. Update profile (Lengkapi Akun)
        $updatePayload = [
            'first_name' => 'Budi Wijaya',
            'last_name' => 'Pratama',
            'whatsapp' => '081299998888',
        ];

        $putResponse = $this->actingAs($member)->putJson('/api/isf/profile', $updatePayload);
        $putResponse->assertOk()
            ->assertJsonPath('data.name', 'Budi Wijaya Pratama')
            ->assertJsonPath('data.first_name', 'Budi Wijaya')
            ->assertJsonPath('data.last_name', 'Pratama')
            ->assertJsonPath('data.whatsapp', '6281299998888');

        // 3. Verify changes persisted in fresh GET
        $freshResponse = $this->actingAs($member)->getJson('/api/isf/profile');
        $freshResponse->assertOk()
            ->assertJsonPath('data.name', 'Budi Wijaya Pratama')
            ->assertJsonPath('data.whatsapp', '6281299998888');
    }

    public function test_closing_running_text_shifts_subsequent_orders_down(): void
    {
        $admin = $this->superAdmin();

        Article::query()->update(['show_in_running_text' => false, 'running_text_order' => null]);

        // Create 4 published articles
        $a1 = Article::create([
            'title' => 'Article RT 1',
            'slug' => 'art-rt-1-' . uniqid(),
            'category' => 'kebijakan',
            'status' => 'terbit',
            'published_at' => now()->format('Y-m-d'),
            'show_in_running_text' => true,
            'running_text_order' => 1,
        ]);
        $a2 = Article::create([
            'title' => 'Article RT 2',
            'slug' => 'art-rt-2-' . uniqid(),
            'category' => 'kebijakan',
            'status' => 'terbit',
            'published_at' => now()->format('Y-m-d'),
            'show_in_running_text' => true,
            'running_text_order' => 2,
        ]);
        $a3 = Article::create([
            'title' => 'Article RT 3',
            'slug' => 'art-rt-3-' . uniqid(),
            'category' => 'kebijakan',
            'status' => 'terbit',
            'published_at' => now()->format('Y-m-d'),
            'show_in_running_text' => true,
            'running_text_order' => 3,
        ]);
        $a4 = Article::create([
            'title' => 'Article RT 4',
            'slug' => 'art-rt-4-' . uniqid(),
            'category' => 'kebijakan',
            'status' => 'terbit',
            'published_at' => now()->format('Y-m-d'),
            'show_in_running_text' => true,
            'running_text_order' => 4,
        ]);

        // Close/turn off running text 2
        $response = $this->actingAs($admin)->postJson("/api/cms/artikel/{$a2->id}/toggle-running-text", [
            'action' => 'off',
        ]);
        $response->assertOk();

        $a1->refresh();
        $a2->refresh();
        $a3->refresh();
        $a4->refresh();

        $this->assertEquals(1, $a1->running_text_order);
        $this->assertFalse((bool) $a2->show_in_running_text);
        $this->assertNull($a2->running_text_order);
        $this->assertEquals(2, $a3->running_text_order); // RT 3 became RT 2
        $this->assertEquals(3, $a4->running_text_order); // RT 4 became RT 3
    }

    public function test_closing_top_rank_shifts_subsequent_orders_down(): void
    {
        $admin = $this->superAdmin();

        Article::query()->update(['show_in_top_rank' => false, 'top_rank_order' => null]);

        $t1 = Article::create([
            'title' => 'Top 1 Art',
            'slug' => 'top-1-art-' . uniqid(),
            'category' => 'politik',
            'status' => 'terbit',
            'published_at' => now()->format('Y-m-d'),
            'show_in_top_rank' => true,
            'top_rank_order' => 1,
        ]);
        $t2 = Article::create([
            'title' => 'Top 2 Art',
            'slug' => 'top-2-art-' . uniqid(),
            'category' => 'politik',
            'status' => 'terbit',
            'published_at' => now()->format('Y-m-d'),
            'show_in_top_rank' => true,
            'top_rank_order' => 2,
        ]);
        $t3 = Article::create([
            'title' => 'Top 3 Art',
            'slug' => 'top-3-art-' . uniqid(),
            'category' => 'politik',
            'status' => 'terbit',
            'published_at' => now()->format('Y-m-d'),
            'show_in_top_rank' => true,
            'top_rank_order' => 3,
        ]);

        // Close Top 2
        $response = $this->actingAs($admin)->postJson("/api/cms/artikel/{$t2->id}/toggle-top-rank", [
            'action' => 'off',
        ]);
        $response->assertOk();

        $t1->refresh();
        $t2->refresh();
        $t3->refresh();

        $this->assertEquals(1, $t1->top_rank_order);
        $this->assertFalse((bool) $t2->show_in_top_rank);
        $this->assertNull($t2->top_rank_order);
        $this->assertEquals(2, $t3->top_rank_order); // Top 3 became Top 2
    }

    public function test_admin_and_super_admin_can_edit_and_delete_member_articles(): void
    {
        $admin = $this->superAdmin();
        $member = $this->memberUser();

        $article = Article::create([
            'user_id' => $member->id,
            'title' => 'Artikel Original Member',
            'slug' => 'artikel-orig-member-' . uniqid(),
            'category' => 'kebijakan',
            'status' => 'draf',
            'published_at' => now()->format('Y-m-d'),
            'is_by_member' => true,
            'author_name' => $member->name,
        ]);

        // 1. Admin updates the member article
        $updateRes = $this->actingAs($admin)->putJson("/api/cms/artikel/{$article->id}", [
            'title' => 'Artikel Member yang Diedit Admin',
            'slug' => $article->slug,
            'category' => 'kebijakan',
            'status' => 'terbit',
            'published_at' => now()->format('Y-m-d'),
        ]);
        $updateRes->assertOk();
        $this->assertEquals('Artikel Member yang Diedit Admin', $article->fresh()->title);
        $this->assertEquals('terbit', $article->fresh()->status);

        // 2. Member cannot delete member article
        $this->actingAs($member)->deleteJson("/api/cms/artikel/{$article->id}")->assertForbidden();

        // 3. Admin can delete member article
        $delRes = $this->actingAs($admin)->deleteJson("/api/cms/artikel/{$article->id}");
        $delRes->assertNoContent();
        $this->assertNull(Article::find($article->id));
    }

    public function test_articles_with_priority_appear_at_the_top_of_cms_table(): void
    {
        $admin = $this->superAdmin();

        Article::query()->update([
            'is_hero' => false,
            'show_in_running_text' => false,
            'running_text_order' => null,
            'show_in_top_rank' => false,
            'top_rank_order' => null,
        ]);

        // Regular article published today
        $regular = Article::create([
            'title' => 'Artikel Reguler Terbaru',
            'slug' => 'art-reguler-' . uniqid(),
            'category' => 'politik',
            'status' => 'terbit',
            'published_at' => now()->format('Y-m-d'),
        ]);

        // Running Text article published yesterday
        $rtArticle = Article::create([
            'title' => 'Artikel Running Text Kemarin',
            'slug' => 'art-rt-kemarin-' . uniqid(),
            'category' => 'politik',
            'status' => 'terbit',
            'published_at' => now()->subDay()->format('Y-m-d'),
            'show_in_running_text' => true,
            'running_text_order' => 1,
        ]);

        // Top Artikel published 2 days ago
        $topArticle = Article::create([
            'title' => 'Artikel Top Rank Dua Hari Lalu',
            'slug' => 'art-top-rank-' . uniqid(),
            'category' => 'politik',
            'status' => 'terbit',
            'published_at' => now()->subDays(2)->format('Y-m-d'),
            'show_in_top_rank' => true,
            'top_rank_order' => 1,
        ]);

        // Hero article published 3 days ago
        $heroArticle = Article::create([
            'title' => 'Artikel Hero Tiga Hari Lalu',
            'slug' => 'art-hero-' . uniqid(),
            'category' => 'politik',
            'status' => 'terbit',
            'is_hero' => true,
            'published_at' => now()->subDays(3)->format('Y-m-d'),
        ]);

        $res = $this->actingAs($admin)->getJson('/api/cms/artikel');
        $res->assertOk();

        $items = $res->json('data');
        $itemIds = collect($items)->pluck('id')->all();

        // Hero should be first
        $this->assertEquals($heroArticle->id, $itemIds[0]);

        $rtPos = array_search($rtArticle->id, $itemIds, true);
        $topPos = array_search($topArticle->id, $itemIds, true);
        $regPos = array_search($regular->id, $itemIds, true);

        $this->assertNotFalse($rtPos);
        $this->assertNotFalse($topPos);
        $this->assertNotFalse($regPos);

        $this->assertLessThan($regPos, $rtPos, 'Running Text article should appear before regular article');
        $this->assertLessThan($regPos, $topPos, 'Top Artikel should appear before regular article');
    }
}
