<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Banners shown in the carousel at the top of Beranda.
     */
    public function up(): void
    {
        Schema::create('isf_banners', function (Blueprint $table) {
            $table->id();
            $table->string('kicker');
            $table->string('title');
            $table->text('deck')->nullable();
            $table->string('to')->nullable();
            $table->string('image_url')->nullable();
            $table->string('image_label')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->string('status', 10)->default('draf')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isf_banners');
    }
};
