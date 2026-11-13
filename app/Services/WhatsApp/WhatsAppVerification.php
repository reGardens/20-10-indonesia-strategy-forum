<?php

namespace App\Services\WhatsApp;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * Verifikasi Nomor WhatsApp dengan kode OTP 6 digit (Issue #42).
 *
 * - Kode disimpan sebagai hash di cache (bukan teks asli), terikat ke user + nomor yang dituju.
 * - Berlaku 5 menit, maks. 5 kali salah, kirim ulang paling cepat 60 detik.
 * - Bila nomor diganti setelah kode dikirim, kode lama tidak berlaku.
 */
class WhatsAppVerification
{
    public const CODE_TTL_SECONDS = 300;

    public const RESEND_COOLDOWN_SECONDS = 60;

    public const MAX_ATTEMPTS = 5;

    public function __construct(private readonly WhatsAppSender $sender)
    {
    }

    /** Sisa detik sebelum boleh kirim ulang (0 = boleh). */
    public function cooldownRemaining(User $user): int
    {
        $until = Cache::get($this->cooldownKey($user));

        return $until ? max(0, (int) $until - now()->getTimestamp()) : 0;
    }

    /**
     * Kirim kode OTP ke nomor WhatsApp user.
     *
     * @throws WhatsAppSendException
     */
    public function sendCode(User $user): void
    {
        $number = (string) $user->whatsapp;
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->sender->send($number, $this->message($code));

        Cache::put($this->codeKey($user), [
            'hash' => Hash::make($code),
            'number' => $number,
            'attempts' => 0,
        ], self::CODE_TTL_SECONDS);
        Cache::put($this->cooldownKey($user), now()->getTimestamp() + self::RESEND_COOLDOWN_SECONDS, self::RESEND_COOLDOWN_SECONDS);
    }

    /**
     * @return 'verified'|'invalid'|'expired'|'too_many_attempts'
     */
    public function verify(User $user, string $code): string
    {
        $entry = Cache::get($this->codeKey($user));

        if (! is_array($entry) || ($entry['number'] ?? null) !== $user->whatsapp) {
            return 'expired';
        }

        if (($entry['attempts'] ?? 0) >= self::MAX_ATTEMPTS) {
            Cache::forget($this->codeKey($user));

            return 'too_many_attempts';
        }

        if (! Hash::check($code, $entry['hash'])) {
            $entry['attempts'] = ($entry['attempts'] ?? 0) + 1;
            Cache::put($this->codeKey($user), $entry, self::CODE_TTL_SECONDS);

            return $entry['attempts'] >= self::MAX_ATTEMPTS ? 'too_many_attempts' : 'invalid';
        }

        Cache::forget($this->codeKey($user));
        $user->forceFill(['whatsapp_verified_at' => now()])->save();

        return 'verified';
    }

    /** Batalkan kode yang sedang berjalan (mis. nomor diganti). */
    public function forget(User $user): void
    {
        Cache::forget($this->codeKey($user));
    }

    private function message(string $code): string
    {
        $minutes = intdiv(self::CODE_TTL_SECONDS, 60);

        return "Kode verifikasi Indonesia Strategy Forum: *{$code}*\n\n"
            . "Berlaku {$minutes} menit. Jangan berikan kode ini kepada siapa pun, termasuk pihak yang mengaku dari ISF.";
    }

    private function codeKey(User $user): string
    {
        return 'wa_otp:' . $user->getKey();
    }

    private function cooldownKey(User $user): string
    {
        return 'wa_otp_cooldown:' . $user->getKey();
    }
}
