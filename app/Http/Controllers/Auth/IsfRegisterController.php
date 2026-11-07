<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\IsfRegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class IsfRegisterController extends Controller
{
    /**
     * Handle an incoming registration request from the public landing page.
     *
     * Saves user to database with default role 'user'.
     */
    public function store(IsfRegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $fullName = trim($validated['first_name'] . ' ' . $validated['last_name']);

        $user = DB::transaction(function () use ($validated, $fullName) {
            $newUser = User::create([
                'name' => $fullName,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'zip_code' => $validated['zip_code'],
                'age_verified' => true,
                'email' => strtolower($validated['email']),
                'password' => Hash::make($validated['password']),
            ]);

            // Assign standard 'user' role
            $newUser->assignRole('user');

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
    }
}

