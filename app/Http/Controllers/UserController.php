<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\WhatsAppNumber;
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
        $roles = Role::whereIn('name', User::ASSIGNABLE_ROLES)
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
            'whatsapp' => ['nullable', 'string', 'max:25', fn (string $attr, mixed $value, \Closure $fail) => WhatsAppNumber::isValid($value) || $fail('Gunakan nomor seluler Indonesia aktif, contoh 0812xxxxxxxx.')],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-zA-Z])(?=.*\d)/',
            ],
            'role' => ['required', 'string', Rule::in(User::ASSIGNABLE_ROLES)],
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
            'whatsapp' => WhatsAppNumber::normalize($validated['whatsapp'] ?? null),
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
        $roles = Role::whereIn('name', User::ASSIGNABLE_ROLES)
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
            'whatsapp' => ['nullable', 'string', 'max:25', fn (string $attr, mixed $value, \Closure $fail) => WhatsAppNumber::isValid($value) || $fail('Gunakan nomor seluler Indonesia aktif, contoh 0812xxxxxxxx.')],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'regex:/^(?=.*[a-zA-Z])(?=.*\d)/',
            ],
            'role' => ['required', 'string', Rule::in(User::ASSIGNABLE_ROLES)],
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
            'email' => $validated['email'],
        ]);

        // Nomor diubah admin → status verifikasi direset (member memverifikasi ulang via OTP).
        $newWhatsapp = WhatsAppNumber::normalize($validated['whatsapp'] ?? null);
        if ($newWhatsapp !== $user->whatsapp) {
            $user->forceFill(['whatsapp' => $newWhatsapp, 'whatsapp_verified_at' => null])->save();
        }

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
