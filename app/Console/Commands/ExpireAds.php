<?php

namespace App\Console\Commands;

use App\Models\Isf\Ad;
use Illuminate\Console\Command;

/**
 * Ubah status iklan aktif yang tanggal selesainya sudah lewat menjadi "expired".
 * Dijadwalkan tiap jam (routes/console.php). Tanpa cron pun iklan tetap berhenti tayang tepat waktu
 * karena API publik memfilter tanggal selesai; perintah ini merapikan status di CMS.
 */
class ExpireAds extends Command
{
    protected $signature = 'ads:expire';

    protected $description = 'Tandai iklan yang sudah lewat tanggal selesai sebagai expired';

    public function handle(): int
    {
        $count = Ad::expireOverdue();
        $this->info("{$count} iklan ditandai expired.");

        return self::SUCCESS;
    }
}
