<?php

namespace App\Services\WhatsApp;

/**
 * Pengirim pesan WhatsApp. Implementasi dipilih lewat config('services.whatsapp.driver'):
 * - "fonnte": Fonnte API (nomor WhatsApp biasa yang di-scan QR di dashboard Fonnte).
 * - "log"   : tidak mengirim apa pun, pesan ditulis ke log (lokal / testing / token belum diisi).
 */
interface WhatsAppSender
{
    /**
     * @param  string  $to  nomor ter-normalisasi, mis. 6281234567890
     *
     * @throws WhatsAppSendException bila gagal terkirim
     */
    public function send(string $to, string $message): void;
}
