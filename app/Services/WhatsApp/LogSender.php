<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Log;

/**
 * Driver "log": tidak mengirim WhatsApp, pesan (termasuk kode OTP) ditulis ke storage/logs.
 * Hanya untuk lokal/testing — di server isi FONNTE_TOKEN dan WHATSAPP_DRIVER=fonnte.
 */
class LogSender implements WhatsAppSender
{
    public function send(string $to, string $message): void
    {
        Log::info('[WhatsApp:log] pesan tidak dikirim (driver log)', ['to' => $to, 'message' => $message]);
    }
}
