<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\CustomTemplate;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;
    protected User $standardUser;
    protected Role $adminRole;
    protected Role $userRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Retrieve or create roles
        $this->adminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // Create a unique super admin for testing
        $this->superAdmin = User::create([
            'name' => 'Test Super Admin',
            'email' => 'test_superadmin_' . uniqid() . '@example.com',
            'password' => bcrypt('abcd1234'),
        ]);
        $this->superAdmin->assignRole('super-admin');

        // Create a unique standard user for testing
        $this->standardUser = User::create([
            'name' => 'Test Standard User',
            'email' => 'test_user_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->standardUser->assignRole('user');
    }

    /**
     * Test super admin can access admin dashboard, templates, roles, users, and ACL pages.
     */
    public function test_super_admin_can_access_admin_pages(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('cms.dashboard'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('cms.templates.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('cms.roles.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('cms.users.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('cms.acl.index'));
        $response->assertStatus(200);
    }

    /**
     * Test standard user is blocked from admin pages with a 403 response.
     */
    public function test_standard_user_is_blocked_from_admin_pages(): void
    {
        $response = $this->actingAs($this->standardUser)
            ->get(route('cms.dashboard'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->standardUser)
            ->get(route('cms.templates.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->standardUser)
            ->get(route('cms.roles.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->standardUser)
            ->get(route('cms.users.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->standardUser)
            ->get(route('cms.acl.index'));
        $response->assertStatus(403);
    }

    /**
     * Test custom templates CRUD operations.
     */
    public function test_super_admin_can_crud_templates(): void
    {
        $templateData = [
            'name' => 'Custom Gothic',
            'slug' => 'custom-gothic-' . uniqid(),
            'description' => 'Dark gothic theme',
            'config' => [
                'primary_color' => '#1a1a1a',
                'secondary_color' => '#800000',
                'accent_color' => '#ffd700',
                'font_family' => 'Playfair Display',
                'animation_style' => 'fade-in',
                'custom_css' => 'body { background-color: #000; }',
            ],
        ];

        // Store
        $response = $this->actingAs($this->superAdmin)
            ->post(route('cms.templates.store'), $templateData);

        $response->assertRedirect(route('cms.templates.index'));
        $this->assertDatabaseHas('custom_templates', [
            'slug' => $templateData['slug'],
            'name' => 'Custom Gothic',
        ]);

        $template = CustomTemplate::where('slug', $templateData['slug'])->firstOrFail();

        // Update
        $updatedData = $templateData;
        $updatedData['name'] = 'Custom Gothic Dark';
        $updatedData['config']['primary_color'] = '#000000';

        $response = $this->actingAs($this->superAdmin)
            ->put(route('cms.templates.update', $template), $updatedData);

        $response->assertRedirect(route('cms.templates.index'));
        $this->assertDatabaseHas('custom_templates', [
            'id' => $template->id,
            'name' => 'Custom Gothic Dark',
        ]);

        // Destroy
        $response = $this->actingAs($this->superAdmin)
            ->delete(route('cms.templates.destroy', $template));

        $response->assertRedirect(route('cms.templates.index'));
        $this->assertDatabaseMissing('custom_templates', [
            'id' => $template->id,
        ]);
    }

    /**
     * Test role CRUD operations.
     */
    public function test_super_admin_can_crud_roles(): void
    {
        $roleName = 'test-role-' . uniqid();

        // Create role
        $response = $this->actingAs($this->superAdmin)
            ->post(route('cms.roles.store'), [
                'name' => $roleName,
            ]);

        $response->assertRedirect(route('cms.roles.index'));
        $this->assertDatabaseHas('roles', [
            'name' => $roleName,
        ]);

        $role = Role::where('name', $roleName)->firstOrFail();

        // Edit/Update role
        $updatedName = 'updated-role-' . uniqid();
        $response = $this->actingAs($this->superAdmin)
            ->put(route('cms.roles.update', $role), [
                'name' => $updatedName,
            ]);

        $response->assertRedirect(route('cms.roles.index'));
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => $updatedName,
        ]);

        // Destroy role
        $response = $this->actingAs($this->superAdmin)
            ->delete(route('cms.roles.destroy', $role));

        $response->assertRedirect(route('cms.roles.index'));
        $this->assertDatabaseMissing('roles', [
            'id' => $role->id,
        ]);
    }

    /**
     * Test user CRUD operations.
     */
    public function test_super_admin_can_crud_users(): void
    {
        $email = 'johndoe_' . uniqid() . '@example.com';

        // Store user
        $response = $this->actingAs($this->superAdmin)
            ->post(route('cms.users.store'), [
                'name' => 'John Doe',
                'email' => $email,
                'password' => 'SecurePass123',
                'role' => 'user',
            ]);

        $response->assertRedirect(route('cms.users.index'));
        $this->assertDatabaseHas('users', [
            'email' => $email,
        ]);

        $user = User::where('email', $email)->firstOrFail();
        $this->assertTrue($user->hasRole('user'));

        // Update user
        $updatedEmail = 'johndoe_updated_' . uniqid() . '@example.com';
        $response = $this->actingAs($this->superAdmin)
            ->put(route('cms.users.update', $user), [
                'name' => 'John Doe Updated',
                'email' => $updatedEmail,
                'role' => 'user',
            ]);

        $response->assertRedirect(route('cms.users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $updatedEmail,
            'name' => 'John Doe Updated',
        ]);

        // Destroy user
        $response = $this->actingAs($this->superAdmin)
            ->delete(route('cms.users.destroy', $user));

        $response->assertRedirect(route('cms.users.index'));
        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);
    }

    /**
     * Test super-admin user records cannot be edited or deleted.
     */
    public function test_super_admin_is_protected_from_edit_and_delete(): void
    {
        // Try to edit the super admin user
        $response = $this->actingAs($this->superAdmin)
            ->get("/cms/users/{$this->superAdmin->id}/edit");
        $response->assertRedirect(route('cms.users.index'));

        // Try to update the super admin user
        $response = $this->actingAs($this->superAdmin)
            ->put("/cms/users/{$this->superAdmin->id}", [
                'name' => 'Hacked Name',
                'email' => 'hacked@example.com',
                'role' => 'user'
            ]);
        $response->assertRedirect(route('cms.users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $this->superAdmin->id,
            'email' => $this->superAdmin->email
        ]);

        // Try to delete the super admin user
        $response = $this->actingAs($this->superAdmin)
            ->delete("/cms/users/{$this->superAdmin->id}");
        $response->assertRedirect(route('cms.users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $this->superAdmin->id,
            'deleted_at' => null
        ]);
    }

    /**
     * Test ACL feature toggling.
     */
    public function test_super_admin_can_toggle_user_features(): void
    {
        // Initial state: standard user should have default features
        $this->assertTrue($this->standardUser->hasFeature('live_streaming'));

        // Toggle 'live_streaming' off
        $response = $this->actingAs($this->superAdmin)
            ->post(route('cms.acl.toggle', $this->standardUser), [
                'feature' => 'live_streaming'
            ]);

        $response->assertRedirect(route('cms.acl.index'));
        $this->standardUser->refresh();
        $this->assertFalse($this->standardUser->hasFeature('live_streaming'));

        // Toggle 'live_streaming' back on
        $response = $this->actingAs($this->superAdmin)
            ->post(route('cms.acl.toggle', $this->standardUser), [
                'feature' => 'live_streaming'
            ]);

        $this->standardUser->refresh();
        $this->assertTrue($this->standardUser->hasFeature('live_streaming'));
    }

    /**
     * Test templates API endpoint listing custom and default templates.
     */
    public function test_templates_api_returns_correct_response(): void
    {
        $customTemplate = CustomTemplate::create([
            'name' => 'Custom Elegant',
            'slug' => 'custom-elegant-' . uniqid(),
            'description' => 'Fine line art',
            'config' => [
                'primary_color' => '#ffffff',
                'secondary_color' => '#000000',
                'accent_color' => '#e5c158',
                'font_family' => 'Cinzel',
                'animation_style' => 'fade-in',
            ],
        ]);

        $response = $this->getJson('/api/templates');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $customTemplate->slug,
            'name' => 'Custom Elegant',
            'is_custom' => true,
        ]);

        $response->assertJsonFragment([
            'id' => 'jawa-klasik',
            'name' => 'Jawa Klasik',
            'is_custom' => false,
        ]);
    }

    /**
     * Test user profile API.
     */
    public function test_user_profile_api_returns_correct_data(): void
    {
        $response = $this->actingAs($this->standardUser)
            ->getJson('/api/user/profile');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'email' => $this->standardUser->email
        ]);
        $response->assertJsonStructure([
            'allowed_features'
        ]);
    }

    /**
     * Test user only sees their own weddings in the registry.
     */
    public function test_user_only_sees_their_own_weddings(): void
    {
        // Wedding for super admin
        $weddingAdmin = Wedding::create([
            'slug' => 'wedding-admin-' . uniqid(),
            'label' => 'Wedding Admin',
            'user_id' => $this->superAdmin->id,
        ]);

        // Wedding for standard user
        $weddingUser = Wedding::create([
            'slug' => 'wedding-user-' . uniqid(),
            'label' => 'Wedding User',
            'user_id' => $this->standardUser->id,
        ]);

        // Standard user fetches registry
        $response = $this->actingAs($this->standardUser)
            ->getJson('/api/wedding/registry');

        $response->assertStatus(200);
        $response->assertJsonFragment(['slug' => $weddingUser->slug]);
        $response->assertJsonMissing(['slug' => $weddingAdmin->slug]);

        // Super admin fetches registry (sees all)
        $response = $this->actingAs($this->superAdmin)
            ->getJson('/api/wedding/registry');

        $response->assertStatus(200);
        $response->assertJsonFragment(['slug' => $weddingUser->slug]);
        $response->assertJsonFragment(['slug' => $weddingAdmin->slug]);
    }
}
