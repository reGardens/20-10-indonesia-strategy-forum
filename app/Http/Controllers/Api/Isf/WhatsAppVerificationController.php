<?php

namespace App\Http\Controllers\Api\Isf;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppSendException;
use App\Services\WhatsApp\WhatsAppVerification;
use App\Support\WhatsAppNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Nomor WhatsApp Aktif milik user yang login (Issue #42). Dipakai dari landing page & CMS.
 *
 * - PUT  /api/isf/me/whatsapp            simpan/ganti nomor → status jadi "belum terverifikasi", kirim kode OTP
 * - POST /api/isf/me/whatsapp/kirim-kode kirim ulang kode (jeda 60 detik)
 * - POST /api/isf/me/whatsapp/verifikasi cocokkan kode 6 digit → whatsapp_verified_at diisi
 *
 * Saklar config('services.whatsapp.verification') (default MATI): PUT hanya menyimpan nomor,
 * kirim-kode & verifikasi menjawab 404, dan pengirim WhatsApp (Fonnte) tidak pernah dipanggil.
 */
class WhatsAppVerificationController extends Controller
{
    public function __construct(private readonly WhatsAppVerification $verification)
    {
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate(
            ['whatsapp' => ['required', 'string', 'max:25']],
            ['whatsapp.required' => 'Nomor WhatsApp wajib diisi.'],
        );

        if (! WhatsAppNumber::isValid($request->input('whatsapp'))) {
            return response()->json([
                'message' => 'Nomor WhatsApp tidak valid.',
                'errors' => ['whatsapp' => ['Gunakan nomor seluler Indonesia aktif, contoh 0812xxxxxxxx.']],
            ], 422);
        }

        /** @var User $user */
        $user = $request->user();
        $number = WhatsAppNumber::normalize($request->input('whatsapp'));

        if ($number !== $user->whatsapp) {
            $user->forceFill(['whatsapp' => $number, 'whatsapp_verified_at' => null])->save();
            $this->verification->forget($user);
        }

        if (! self::enabled()) {
            return response()->json(['message' => 'Nomor WhatsApp tersimpan.'] + $this->status($user));
        }

        if ($user->whatsapp_verified_at) {
            return response()->json(['message' => 'Nomor WhatsApp sudah terverifikasi.'] + $this->status($user));
        }

        return $this->sendCode($user, 'Nomor tersimpan. Kode verifikasi dikirim ke WhatsApp Anda.');
    }

    public function resend(Request $request): JsonResponse
    {
        if (! self::enabled()) {
            return $this->disabled();
        }

        /** @var User $user */
        $user = $request->user();

        if (! $user->whatsapp) {
            return response()->json(['message' => 'Isi Nomor WhatsApp terlebih dahulu.'] + $this->status($user), 422);
        }
        if ($user->whatsapp_verified_at) {
            return response()->json(['message' => 'Nomor WhatsApp sudah terverifikasi.'] + $this->status($user));
        }

        return $this->sendCode($user, 'Kode verifikasi baru dikirim ke WhatsApp Anda.');
    }

    public function verify(Request $request): JsonResponse
    {
        if (! self::enabled()) {
            return $this->disabled();
        }

        $request->validate(
            ['code' => ['required', 'digits:6']],
            ['code.required' => 'Masukkan kode verifikasi.', 'code.digits' => 'Kode verifikasi terdiri dari 6 angka.'],
        );

        /** @var User $user */
        $user = $request->user();

        if ($user->whatsapp_verified_at) {
            return response()->json(['message' => 'Nomor WhatsApp sudah terverifikasi.'] + $this->status($user));
        }

        $result = $this->verification->verify($user, (string) $request->input('code'));

        return match ($result) {
            'verified' => response()->json(['message' => 'Nomor WhatsApp berhasil diverifikasi.'] + $this->status($user->refresh())),
            'invalid' => response()->json(['message' => 'Kode verifikasi salah.', 'errors' => ['code' => ['Kode verifikasi salah.']]] + $this->status($user), 422),
            'too_many_attempts' => response()->json(['message' => 'Terlalu banyak percobaan. Minta kode baru.', 'errors' => ['code' => ['Terlalu banyak percobaan. Minta kode baru.']]] + $this->status($user), 422),
            default => response()->json(['message' => 'Kode sudah kedaluwarsa. Minta kode baru.', 'errors' => ['code' => ['Kode sudah kedaluwarsa. Minta kode baru.']]] + $this->status($user), 422),
        };
    }

    private function sendCode(User $user, string $okMessage): JsonResponse
    {
        if (($wait = $this->verification->cooldownRemaining($user)) > 0) {
            return response()->json(['message' => "Tunggu {$wait} detik sebelum meminta kode baru."] + $this->status($user), 429);
        }

        try {
            $this->verification->sendCode($user);
        } catch (WhatsAppSendException) {
            return response()->json(['message' => 'Kode gagal dikirim ke WhatsApp. Pastikan nomor aktif lalu coba lagi.'] + $this->status($user), 502);
        }

        return response()->json(['message' => $okMessage, 'codeSent' => true] + $this->status($user));
    }

    public static function enabled(): bool
    {
        return (bool) config('services.whatsapp.verification');
    }

    private function disabled(): JsonResponse
    {
        return response()->json(['message' => 'Verifikasi WhatsApp belum diaktifkan.'], 404);
    }

    /** Status nomor milik user sendiri (tanpa data user lain). */
    private function status(User $user): array
    {
        return [
            'whatsapp' => $user->whatsapp,
            'whatsappDisplay' => WhatsAppNumber::display($user->whatsapp),
            'whatsappVerified' => (bool) $user->whatsapp_verified_at,
            'whatsappVerification' => self::enabled(),
            'resendIn' => self::enabled() ? $this->verification->cooldownRemaining($user) : 0,
        ];
    }
}
