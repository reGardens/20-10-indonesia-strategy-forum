<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fonnte API — https://docs.fonnte.com/api-send-message/
 * POST https://api.fonnte.com/send, header "Authorization: <TOKEN>" (tanpa "Bearer"),
 * respons JSON { "status": true|false, "reason": "..." }.
 */
class FonnteSender implements WhatsAppSender
{
    public function __construct(
        private readonly string $token,
        private readonly string $endpoint = 'https://api.fonnte.com/send',
    ) {
    }

    public function send(string $to, string $message): void
    {
        try {
            $response = Http::asForm()
                ->withHeaders(['Authorization' => $this->token])
                ->timeout(15)
                ->post($this->endpoint, [
                    'target' => $to,
                    'message' => $message,
                    // nomor sudah berawalan 62 → nonaktifkan penggantian awalan 0
                    'countryCode' => '0',
                ]);
        } catch (Throwable $e) {
            Log::warning('Fonnte: gagal menghubungi API', ['error' => $e->getMessage()]);

            throw new WhatsAppSendException('Layanan WhatsApp tidak dapat dihubungi.', 0, $e);
        }

        $body = $response->json();
        if (! $response->successful() || ! is_array($body) || ($body['status'] ?? false) !== true) {
            // Jangan log isi pesan (berisi kode OTP) maupun token.
            Log::warning('Fonnte: pesan tidak terkirim', [
                'http' => $response->status(),
                'reason' => is_array($body) ? ($body['reason'] ?? null) : null,
            ]);

            throw new WhatsAppSendException('Pesan WhatsApp gagal dikirim.');
        }
    }
}
