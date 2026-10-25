<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Email Briefing: member email template content (/anggota/briefing/preview).
     */
    public function up(): void
    {
        Schema::create('isf_briefings', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('penerima');
            $table->string('organisasi');
            $table->string('ringkasan_judul');
            $table->text('ringkasan_catatan');
            $table->json('ringkasan_data');
            $table->text('pembuka');
            $table->json('poin');
            $table->text('penutup');
            $table->string('cta_url');
            $table->json('tautan');
            $table->string('status', 10)->default('draf')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isf_briefings');
    }
};
