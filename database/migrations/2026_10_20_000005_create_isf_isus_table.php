<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Isu Mingguan: "Anatomi Isu Mingguan" page (/isu/{slug}), 7 frameworks.
     */
    public function up(): void
    {
        Schema::create('isf_isus', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('category', 20);
            $table->date('published_at');
            $table->text('summary');
            $table->string('author');
            $table->text('teaser')->nullable();
            $table->boolean('is_weekly')->default(false);
            // 01 Ringkasan
            $table->text('ringkasan');
            // 02 Visual Kunci
            $table->foreignId('visual_chart_id')->nullable()->constrained('isf_charts')->nullOnDelete();
            $table->string('visual_image_alt')->nullable();
            $table->string('visual_image_url')->nullable();
            // 03 Fakta Kunci & Linimasa
            $table->json('fakta_kunci');
            $table->json('linimasa');
            // 04 Aktor & Posisi
            $table->json('aktor');
            // 05 Lensa GRC
            $table->text('grc_governance');
            $table->string('grc_risk_level');
            $table->text('grc_risk_description');
            $table->json('grc_compliance');
            // 06 Dampak & Reaksi
            $table->json('dampak_sektoral');
            $table->json('dampak_reaksi');
            // 07 Apa Selanjutnya
            $table->json('selanjutnya_tahapan');
            $table->json('selanjutnya_pantau');
            // Sidebar + bottom sections
            $table->text('metodologi_text');
            $table->json('metodologi_signals');
            $table->json('faq');
            $table->json('related');
            $table->string('status', 10)->default('draf')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isf_isus');
    }
};
