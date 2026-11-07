<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a paginated list of users.
     */
    public function index(): Response
    {
        $users = User::with('roles')
            ->latest()
            ->paginate(10);

        return Inertia::render('Cms/Users/Index', [
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        $roles = Role::whereIn('name', ['super-admin', 'user'])
            ->where('guard_name', 'web')
            ->get();

        return Inertia::render('Cms/Users/Create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created user in the database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'min:2'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-zA-Z])(?=.*\d)/',
            ],
            'role' => ['required', 'string', Rule::in(['super-admin', 'user'])],
        ], [
            'email.unique' => 'Email sudah digunakan',
            'password.regex' => 'Password harus mengandung huruf dan angka',
        ]);

        $name = $validated['name'] ?? null;
        if (! $name && (! empty($validated['first_name']) || ! empty($validated['last_name']))) {
            $name = trim(($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? ''));
        }
        if (! $name) {
            $name = $validated['email'];
        }

        $user = User::create([
            'name' => $name,
            'first_name' => $validated['first_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
            'zip_code' => $validated['zip_code'] ?? null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        return redirect()->route('cms.users.index')
            ->with('success', 'Pengguna berhasil dibuat.');
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(User $user)
    {
        if ($user->email === 'superadmin@gmail.com' || $user->hasRole('super-admin')) {
            return redirect()->route('cms.users.index')
                ->with('error', 'Super Admin tidak dapat diubah.');
        }

        $user->load('roles');
        $roles = Role::whereIn('name', ['super-admin', 'user'])
            ->where('guard_name', 'web')
            ->get();

        return Inertia::render('Cms/Users/Edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * Update the specified user in the database.
     */
    public function update(Request $request, User $user)
    {
        if ($user->email === 'superadmin@gmail.com' || $user->hasRole('super-admin')) {
            return redirect()->route('cms.users.index')
                ->with('error', 'Super Admin tidak dapat diubah.');
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'min:2'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'regex:/^(?=.*[a-zA-Z])(?=.*\d)/',
            ],
            'role' => ['required', 'string', Rule::in(['super-admin', 'user'])],
        ], [
            'email.unique' => 'Email sudah digunakan',
            'password.regex' => 'Password harus mengandung huruf dan angka',
        ]);

        $name = $validated['name'] ?? null;
        if (! $name && (! empty($validated['first_name']) || ! empty($validated['last_name']))) {
            $name = trim(($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? ''));
        }
        if (! $name) {
            $name = $user->name ?: $validated['email'];
        }

        $user->update([
            'name' => $name,
            'first_name' => $validated['first_name'] ?? $user->first_name,
            'last_name' => $validated['last_name'] ?? $user->last_name,
            'zip_code' => $validated['zip_code'] ?? $user->zip_code,
            'email' => $validated['email'],
        ]);

        if (! empty($validated['password'])) {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        $user->syncRoles([$validated['role']]);

        return redirect()->route('cms.users.index')
            ->with('success', 'Pengguna berhasil diperbarui.');
    }

    /**
     * Soft delete the specified user.
     */
    public function destroy(User $user)
    {
        if ($user->email === 'superadmin@gmail.com' || $user->hasRole('super-admin')) {
            return redirect()->route('cms.users.index')
                ->with('error', 'Super Admin tidak dapat dihapus.');
        }

        $user->delete();

        return redirect()->route('cms.users.index')
            ->with('success', 'Pengguna berhasil dihapus.');
    }
}
