<?php

namespace Tests\Feature;

use App\Models\Isf\Article;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberArticleCmsWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    }

    private function createUser(string $name, string $role = 'member'): User
    {
        $user = User::create([
            'name' => $name,
            'email' => 'user-' . uniqid() . '@example.test',
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole($role);
        return $user;
    }

    public function test_member_role_has_only_artikel_feature(): void
    {
        $member = $this->createUser('Member User', 'member');

        $this->assertEquals(['artikel'], $member->features());
        $this->assertTrue($member->hasFeature('artikel'));
        $this->assertFalse($member->hasFeature('banner'));
        $this->assertFalse($member->hasFeature('tokoh'));
    }

    public function test_member_sees_only_own_articles_in_cms_api(): void
    {
        $member1 = $this->createUser('Member Satu', 'member');
        $member2 = $this->createUser('Member Dua', 'member');

        Article::create([
            'user_id' => $member1->id,
            'title' => 'Artikel Member 1',
            'slug' => 'artikel-member-1-' . uniqid(),
            'category' => 'kebijakan',
            'published_at' => now()->format('Y-m-d'),
            'status' => 'draf',
            'is_by_member' => true,
        ]);

        Article::create([
            'user_id' => $member2->id,
            'title' => 'Artikel Member 2',
            'slug' => 'artikel-member-2-' . uniqid(),
            'category' => 'kebijakan',
            'published_at' => now()->format('Y-m-d'),
            'status' => 'draf',
            'is_by_member' => true,
        ]);

        $response = $this->actingAs($member1)->getJson('/api/cms/artikel');
        $response->assertOk();

        $titles = collect($response->json('data'))->pluck('title')->all();
        $this->assertContains('Artikel Member 1', $titles);
        $this->assertNotContains('Artikel Member 2', $titles);
    }

    public function test_member_creating_article_forces_user_id_and_member_flag_and_draf(): void
    {
        $member = $this->createUser('Penulis Member', 'member');

        $slug = 'artikel-baru-' . uniqid();
        $response = $this->actingAs($member)->postJson('/api/cms/artikel', [
            'title' => 'Analisis Opini Kebijakan Baru',
            'slug' => $slug,
            'category' => 'kebijakan',
            'published_at' => now()->format('Y-m-d'),
            'status' => 'terbit', // Member tries to publish directly
            'is_hero' => true,     // Member tries to set hero
        ]);

        $response->assertCreated();

        $article = Article::where('slug', $slug)->first();
        $this->assertNotNull($article);
        $this->assertEquals($member->id, $article->user_id);
        $this->assertTrue((bool) $article->is_by_member);
        $this->assertEquals('draf', $article->status); // Forced to draf
        $this->assertFalse((bool) $article->is_hero);  // Forced to false
        $this->assertEquals('Penulis Member', $article->author_name);
    }

    public function test_member_cannot_modify_or_delete_other_users_articles(): void
    {
        $member1 = $this->createUser('Member Pemilik', 'member');
        $member2 = $this->createUser('Member Penyerang', 'member');

        $article = Article::create([
            'user_id' => $member1->id,
            'title' => 'Artikel Rahasia Member 1',
            'slug' => 'artikel-rahasia-' . uniqid(),
            'category' => 'politik',
            'published_at' => now()->format('Y-m-d'),
            'status' => 'draf',
            'is_by_member' => true,
        ]);

        // Member 2 attempts to view Member 1's article
        $this->actingAs($member2)->getJson('/api/cms/artikel/' . $article->id)->assertForbidden();

        // Member 2 attempts to update Member 1's article
        $this->actingAs($member2)->putJson('/api/cms/artikel/' . $article->id, [
            'title' => 'Hacked Title',
            'slug' => $article->slug,
            'category' => 'politik',
            'published_at' => now()->format('Y-m-d'),
            'status' => 'draf',
        ])->assertForbidden();

        // Member 2 attempts to delete Member 1's article
        $this->actingAs($member2)->deleteJson('/api/cms/artikel/' . $article->id)->assertForbidden();

        // Member attempts to set hero
        $this->actingAs($member1)->postJson('/api/cms/artikel/' . $article->id . '/hero')->assertForbidden();
    }
}
