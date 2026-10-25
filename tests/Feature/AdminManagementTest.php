<?php

namespace Tests\Feature;

use App\Models\User;
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
     * Test super admin can access admin dashboard, roles, users, and ACL pages.
     */
    public function test_super_admin_can_access_admin_pages(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('cms.dashboard'));
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
        $this->assertTrue($this->standardUser->hasFeature('artikel'));

        // Toggle 'artikel' off
        $response = $this->actingAs($this->superAdmin)
            ->post(route('cms.acl.toggle', $this->standardUser), [
                'feature' => 'artikel'
            ]);

        $response->assertRedirect(route('cms.acl.index'));
        $this->standardUser->refresh();
        $this->assertFalse($this->standardUser->hasFeature('artikel'));

        // Toggle 'artikel' back on
        $response = $this->actingAs($this->superAdmin)
            ->post(route('cms.acl.toggle', $this->standardUser), [
                'feature' => 'artikel'
            ]);

        $this->standardUser->refresh();
        $this->assertTrue($this->standardUser->hasFeature('artikel'));
    }

}
