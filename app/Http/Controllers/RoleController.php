<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * List roles.
     */
    public function index(): Response
    {
        $roles = Role::where('guard_name', 'web')
            ->withCount('users')
            ->latest('id')
            ->get();

        return Inertia::render('Cms/Roles/Index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Show create form.
     */
    public function create(): Response
    {
        return Inertia::render('Cms/Roles/Create');
    }

    /**
     * Store a new role.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')],
        ]);

        Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        return redirect()->route('cms.roles.index')
            ->with('success', 'Peran berhasil dibuat.');
    }

    /**
     * Show edit form.
     */
    public function edit(Role $role): Response
    {
        return Inertia::render('Cms/Roles/Edit', [
            'role' => $role,
        ]);
    }

    /**
     * Update role name.
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role->id)],
        ]);

        // Prevent renaming the core super-admin role to avoid breaking access control
        if ($role->name === 'super-admin' && $validated['name'] !== 'super-admin') {
            return back()->withErrors([
                'name' => 'Role super-admin tidak dapat diubah namanya.',
            ]);
        }

        $role->update(['name' => $validated['name']]);

        return redirect()->route('cms.roles.index')
            ->with('success', 'Peran berhasil diperbarui.');
    }

    /**
     * Delete a role.
     */
    public function destroy(Role $role)
    {
        // Prevent deleting the core super-admin role
        if ($role->name === 'super-admin') {
            return back()->withErrors([
                'name' => 'Role super-admin tidak dapat dihapus.',
            ]);
        }

        $role->delete();

        return redirect()->route('cms.roles.index')
            ->with('success', 'Peran berhasil dihapus.');
    }
}
