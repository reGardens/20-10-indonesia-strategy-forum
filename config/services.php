<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more.
    |
    */

    /*
    | WhatsApp (Issue #42 — verifikasi Nomor WhatsApp Aktif).
    | driver "fonnte": kirim lewat Fonnte (nomor WhatsApp biasa di-scan QR di dashboard Fonnte).
    | driver "log"   : tidak mengirim, pesan ditulis ke log (default bila FONNTE_TOKEN kosong).
    | verification   : alur verifikasi OTP. Default MATI → Nomor WhatsApp hanya input biasa,
    |                  Fonnte tidak dipanggil sama sekali. Aktifkan dengan WHATSAPP_VERIFICATION=true.
    */
    'whatsapp' => [
        'verification' => (bool) env('WHATSAPP_VERIFICATION', false),
        'driver' => env('WHATSAPP_DRIVER') ?: (env('FONNTE_TOKEN') ? 'fonnte' : 'log'),
    ],

    'fonnte' => [
        'token' => env('FONNTE_TOKEN'),
        'endpoint' => env('FONNTE_ENDPOINT', 'https://api.fonnte.com/send'),
    ],

];
