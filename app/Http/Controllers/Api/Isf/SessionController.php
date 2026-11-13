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
}
