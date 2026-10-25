<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data Grafik: bar chart datasets (Beranda chart, Isu "Visual Kunci").
     */
    public function up(): void
    {
        Schema::create('isf_charts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('unit', 20)->nullable();
            $table->json('data');
            $table->string('source_title')->nullable();
            $table->string('source_url')->nullable();
            $table->boolean('is_beranda')->default(false);
            $table->string('status', 10)->default('draf')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isf_charts');
    }
};
