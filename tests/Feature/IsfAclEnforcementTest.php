<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IsfAclEnforcementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);

        $this->superAdmin = User::create([
            'name' => 'ACL Super Admin',
            'email' => 'acl_superadmin_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->superAdmin->assignRole('super-admin');
    }

    private function createAdminWithFeatures(array $features): User
    {
        $user = User::create([
            'name' => 'ACL Admin ' . uniqid(),
            'email' => 'acl_admin_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'allowed_features' => $features,
        ]);
        $user->assignRole('admin');

        return $user;
    }

    public function test_web_routes_return_403_when_feature_is_disabled(): void
    {
        $restrictedAdmin = $this->createAdminWithFeatures([]);

        $this->actingAs($restrictedAdmin)->get('/cms/kategori')->assertForbidden();
        $this->actingAs($restrictedAdmin)->get('/cms/media-sorotan')->assertForbidden();
        $this->actingAs($restrictedAdmin)->get('/cms/media-sorotan/create')->assertForbidden();
        $this->actingAs($restrictedAdmin)->get('/cms/hot-news')->assertForbidden();
        $this->actingAs($restrictedAdmin)->get('/cms/hot-news/create')->assertForbidden();
        $this->actingAs($restrictedAdmin)->get('/cms/iklan')->assertForbidden();
        $this->actingAs($restrictedAdmin)->get('/cms/iklan/create')->assertForbidden();
        $this->actingAs($restrictedAdmin)->get('/cms/footer')->assertForbidden();
    }

    public function test_web_routes_allow_access_when_feature_is_enabled_or_super_admin(): void
    {
        $kategoriAdmin = $this->createAdminWithFeatures(['kategori']);
        $this->actingAs($kategoriAdmin)->get('/cms/kategori')->assertOk();
        $this->actingAs($kategoriAdmin)->get('/cms/media-sorotan')->assertForbidden();

        $settingsAdmin = $this->createAdminWithFeatures(['pengaturan_halaman']);
        $this->actingAs($settingsAdmin)->get('/cms/media-sorotan')->assertOk();
        $this->actingAs($settingsAdmin)->get('/cms/hot-news')->assertOk();
        $this->actingAs($settingsAdmin)->get('/cms/iklan')->assertOk();

        $this->actingAs($this->superAdmin)->get('/cms/kategori')->assertOk();
        $this->actingAs($this->superAdmin)->get('/cms/media-sorotan')->assertOk();
        $this->actingAs($this->superAdmin)->get('/cms/hot-news')->assertOk();
        $this->actingAs($this->superAdmin)->get('/cms/iklan')->assertOk();
    }

    public function test_api_routes_return_403_when_feature_is_disabled(): void
    {
        $restrictedAdmin = $this->createAdminWithFeatures([]);

        // Kategori API
        $this->actingAs($restrictedAdmin)->getJson('/api/cms/kategori')->assertForbidden();
        $this->actingAs($restrictedAdmin)->postJson('/api/cms/kategori', ['name' => 'Uji'])->assertForbidden();

        // Running Text API
        $this->actingAs($restrictedAdmin)->getJson('/api/cms/running-text')->assertForbidden();
        $this->actingAs($restrictedAdmin)->postJson('/api/cms/running-text', ['text' => 'Uji'])->assertForbidden();
        $this->actingAs($restrictedAdmin)->putJson('/api/cms/running-text', ['items' => []])->assertForbidden();

        // Media Sorotan API
        $this->actingAs($restrictedAdmin)->getJson('/api/cms/media-sorotan')->assertForbidden();
        $this->actingAs($restrictedAdmin)->postJson('/api/cms/media-sorotan', ['title' => 'Uji'])->assertForbidden();
        $this->actingAs($restrictedAdmin)->putJson('/api/cms/media-sorotan', ['items' => []])->assertForbidden();

        // Hot News API
        $this->actingAs($restrictedAdmin)->getJson('/api/cms/hot-news')->assertForbidden();
        $this->actingAs($restrictedAdmin)->postJson('/api/cms/hot-news', ['title' => 'Uji'])->assertForbidden();

        // Iklan API
        $this->actingAs($restrictedAdmin)->getJson('/api/cms/iklan')->assertForbidden();
        $this->actingAs($restrictedAdmin)->postJson('/api/cms/iklan', ['title' => 'Uji'])->assertForbidden();

        // Top Rank API
        $this->actingAs($restrictedAdmin)->getJson('/api/cms/top-rank')->assertForbidden();
    }

    public function test_api_routes_allow_access_when_feature_is_enabled(): void
    {
        $kategoriAdmin = $this->createAdminWithFeatures(['kategori']);
        $this->actingAs($kategoriAdmin)->getJson('/api/cms/kategori')->assertOk();
        $this->actingAs($kategoriAdmin)->getJson('/api/cms/iklan')->assertForbidden();

        $settingsAdmin = $this->createAdminWithFeatures(['pengaturan_halaman']);
        $this->actingAs($settingsAdmin)->getJson('/api/cms/running-text')->assertOk();
        $this->actingAs($settingsAdmin)->getJson('/api/cms/media-sorotan')->assertOk();
        $this->actingAs($settingsAdmin)->getJson('/api/cms/hot-news')->assertOk();
        $this->actingAs($settingsAdmin)->getJson('/api/cms/iklan')->assertOk();

        $artikelAdmin = $this->createAdminWithFeatures(['artikel']);
        $this->actingAs($artikelAdmin)->getJson('/api/cms/top-rank')->assertOk();
    }

    public function test_acl_index_includes_all_non_super_admin_users_and_toggles_features(): void
    {
        $adminUser = $this->createAdminWithFeatures(['artikel']);
        $memberUser = User::create([
            'name' => 'ACL Member ' . uniqid(),
            'email' => 'acl_member_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'allowed_features' => ['artikel'],
        ]);
        $memberUser->assignRole('member');

        $response = $this->actingAs($this->superAdmin)->get('/cms/acl');
        $response->assertOk();

        $pageUsers = collect($response->viewData('page')['props']['users']);
        $this->assertTrue($pageUsers->contains('id', $adminUser->id));
        $this->assertTrue($pageUsers->contains('id', $memberUser->id));
        $this->assertFalse($pageUsers->contains('id', $this->superAdmin->id));

        // Toggle kategori & media_kit for adminUser
        $this->actingAs($this->superAdmin)
            ->post("/cms/acl/{$adminUser->id}/toggle", ['feature' => 'kategori'])
            ->assertRedirect(route('cms.acl.index'));

        $this->actingAs($this->superAdmin)
            ->post("/cms/acl/{$adminUser->id}/toggle", ['feature' => 'media_kit'])
            ->assertRedirect(route('cms.acl.index'));

        $adminUser->refresh();
        $this->assertTrue($adminUser->hasFeature('kategori'));
        $this->assertTrue($adminUser->hasFeature('media_kit'));
    }
}
