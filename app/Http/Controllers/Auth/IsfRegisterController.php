<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\IsfRegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class IsfRegisterController extends Controller
{
    /**
     * Handle an incoming registration request from the public landing page.
     *
     * Saves user to database with default role 'member' (Issue #42).
     * Nomor WhatsApp disimpan belum terverifikasi; verifikasi OTP dilakukan setelah login.
     */
    public function store(IsfRegisterRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $fullName = trim($validated['first_name'] . ' ' . $validated['last_name']);

            $user = DB::transaction(function () use ($validated, $fullName) {
                $newUser = User::create([
                    'name' => $fullName,
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'whatsapp' => \App\Support\WhatsAppNumber::normalize($validated['whatsapp']),
                    'age_verified' => true,
                    'email' => strtolower($validated['email']),
                    'password' => Hash::make($validated['password']),
                ]);

                // Assign standard 'member' role safely with cache reset
                try {
                    app(PermissionRegistrar::class)->forgetCachedPermissions();
                    $role = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
                    $newUser->assignRole($role);
                } catch (Throwable $roleErr) {
                    Log::warning('Spatie role assignment warning: ' . $roleErr->getMessage());
                }

                return $newUser;
            });

            return response()->json([
                'success' => true,
                'message' => 'Pendaftaran anggota berhasil! Selamat datang di Indonesia Strategy Forum.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ], 201);
        } catch (Throwable $e) {
            Log::error('Registration exception: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kendala saat pendaftaran: ' . $e->getMessage(),
                'error_detail' => $e->getMessage(),
                'file' => basename($e->getFile()) . ':' . $e->getLine(),
            ], 500);
        }
    }
}
