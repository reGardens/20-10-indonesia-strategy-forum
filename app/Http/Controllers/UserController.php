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
            'name' => ['required', 'string', 'min:2'],
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

        $user = User::create([
            'name' => $validated['name'],
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
            'name' => ['required', 'string', 'min:2'],
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

        $user->update([
            'name' => $validated['name'],
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
