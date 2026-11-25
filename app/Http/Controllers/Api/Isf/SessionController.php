<?php

namespace App\Http\Controllers\Api\Isf;

use App\Http\Controllers\Controller;
use App\Support\WhatsAppNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/isf/me — status login untuk header publik ISF (tampil avatar + Keluar, bukan "Login").
 *
 * Sengaja hanya mengirim nama, inisial, dan status Nomor WhatsApp milik user sendiri
 * (tanpa email/role/id) dan tidak boleh di-cache,
 * karena isinya berbeda per sesi.
 */
class SessionController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $payload = $user
            ? ['authenticated' => true, 'name' => (string) $user->name, 'initials' => self::initials((string) $user->name)] + self::whatsapp($user)
            : ['authenticated' => false, 'name' => null, 'initials' => null];

        return response()->json($payload)->header('Cache-Control', 'no-store, private');
    }

    /** Status Nomor WhatsApp (Issue #42) — dipakai header publik untuk alert "perbarui nomor". */
    public static function whatsapp($user): array
    {
        return [
            'whatsapp' => $user->whatsapp,
            'whatsappDisplay' => WhatsAppNumber::display($user->whatsapp),
            'whatsappVerified' => (bool) $user->whatsapp_verified_at,
            // Saklar verifikasi OTP (default mati): frontend hanya meminta "tambahkan nomor" bila kosong.
            'whatsappVerification' => WhatsAppVerificationController::enabled(),
        ];
    }

    /** Inisial maks. 2 huruf dari nama (sama dengan memberInitials di IsfHeader.vue). */
    public static function initials(string $name): string
    {
        return collect(preg_split('/\s+/', trim($name)))->filter()->take(2)
            ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') ?: 'U';
    }

    /**
     * GET /api/isf/profile — Data profil lengkap untuk halaman Detail Profile publik.
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $roles = method_exists($user, 'getRoleNames') ? $user->getRoleNames()->all() : [];
        $roleLabel = !empty($roles) ? ucfirst(str_replace('-', ' ', $roles[0])) : 'Anggota';

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => (string) $user->name,
                'first_name' => (string) ($user->first_name ?? ''),
                'last_name' => (string) ($user->last_name ?? ''),
                'email' => (string) $user->email,
                'email_verified' => (bool) $user->email_verified_at,
                'whatsapp' => (string) ($user->whatsapp ?? ''),
                'whatsappDisplay' => WhatsAppNumber::display($user->whatsapp),
                'whatsappVerified' => (bool) $user->whatsapp_verified_at,
                'initials' => self::initials((string) $user->name),
                'roles' => $roles,
                'role_label' => $roleLabel,
                'created_at' => $user->created_at ? $user->created_at->format('d M Y') : date('d M Y'),
            ],
        ])->header('Cache-Control', 'no-store, private');
    }

    /**
     * PUT /api/isf/profile — Perbarui nama dan kontak pengguna dari landing page.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'whatsapp' => 'nullable|string|max:25',
        ]);

        $firstName = trim((string) ($validated['first_name'] ?? ''));
        $lastName = trim((string) ($validated['last_name'] ?? ''));
        $name = trim((string) ($validated['name'] ?? ''));

        if ($firstName !== '' || $lastName !== '') {
            $user->first_name = $firstName;
            $user->last_name = $lastName;
            $user->name = trim($firstName . ' ' . $lastName) ?: $user->name;
        } elseif ($name !== '') {
            $user->name = $name;
        }

        if (array_key_exists('whatsapp', $validated)) {
            $rawWa = trim((string) $validated['whatsapp']);
            $normalizedWa = $rawWa !== '' ? WhatsAppNumber::normalize($rawWa) : null;
            if ($user->whatsapp !== $normalizedWa) {
                $user->whatsapp = $normalizedWa;
                $user->whatsapp_verified_at = null;
            }
        }

        $user->save();

        $roles = method_exists($user, 'getRoleNames') ? $user->getRoleNames()->all() : [];
        $roleLabel = !empty($roles) ? ucfirst(str_replace('-', ' ', $roles[0])) : 'Anggota';

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'data' => [
                'id' => $user->id,
                'name' => (string) $user->name,
                'first_name' => (string) ($user->first_name ?? ''),
                'last_name' => (string) ($user->last_name ?? ''),
                'email' => (string) $user->email,
                'whatsapp' => (string) ($user->whatsapp ?? ''),
                'whatsappDisplay' => WhatsAppNumber::display($user->whatsapp),
                'whatsappVerified' => (bool) $user->whatsapp_verified_at,
                'initials' => self::initials((string) $user->name),
                'roles' => $roles,
                'role_label' => $roleLabel,
            ],
        ]);
    }
}
