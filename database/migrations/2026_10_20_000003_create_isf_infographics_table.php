<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Infografik: Beranda "Infografik Terbaru".
     */
    public function up(): void
    {
        Schema::create('isf_infographics', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('category', 20);
            $table->date('published_at');
            $table->string('image_alt');
            $table->string('image_url')->nullable();
            $table->string('status', 10)->default('draf')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isf_infographics');
    }
};
