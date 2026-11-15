<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Iklan (spanduk Beranda & modal popup) pindah dari JSON `isf_settings` ke tabel sendiri,
     * dengan jadwal tanggal+jam, jam tayang harian, status, dan statistik.
     *
     * Iklan lama (key `iklan_items` & `iklan` di `isf_settings`) dihapus, tidak dimigrasikan.
     */
    public function up(): void
    {
        Schema::create('isf_ads', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('kicker', 100)->nullable();
            // spanduk = slot banner kolom kiri Beranda, popup = modal popup.
            $table->string('placement', 20);
            // banner (gambar) | video (khusus popup).
            $table->string('type', 20)->default('banner');
            $table->string('image_url', 1000)->nullable();
            $table->string('video_url', 1000)->nullable();
            $table->unsignedSmallInteger('video_duration')->nullable();
            $table->string('target_url', 1000)->nullable();
            // Rentang tayang (null = tanpa batas). Zona waktu aplikasi (Asia/Jakarta).
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            // Jam tayang harian (null = sepanjang hari); boleh melewati tengah malam (22:00–02:00).
            $table->time('active_hours_start')->nullable();
            $table->time('active_hours_end')->nullable();
            // active | paused | expired (expired diisi otomatis oleh `ads:expire`).
            $table->string('status', 20)->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            // Popup: jeda tampil pertama (detik) & tampil lagi (menit); null = default frontend.
            $table->unsignedSmallInteger('first_delay_seconds')->nullable();
            $table->unsignedSmallInteger('interval_minutes')->nullable();
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->timestamps();

            $table->index(['placement', 'status', 'sort_order']);
            $table->index(['status', 'end_at']);
        });

        DB::table('isf_settings')->whereIn('key', ['iklan_items', 'iklan'])->delete();
    }

    public function down(): void
    {
        // Data iklan lama tidak dipulihkan.
        Schema::dropIfExists('isf_ads');
    }
};
