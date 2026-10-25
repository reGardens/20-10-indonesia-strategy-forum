<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tokoh: "Profil & Tokoh Pekan Ini" page (/tokoh/{slug}).
     */
    public function up(): void
    {
        Schema::create('isf_tokohs', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('position');
            $table->string('photo_alt')->nullable();
            $table->string('photo_url')->nullable();
            $table->json('stats');
            $table->unsignedTinyInteger('skor_value');
            $table->string('skor_period');
            $table->text('skor_explanation');
            $table->json('matrix_columns');
            $table->json('matrix_rows');
            $table->json('related_articles');
            $table->unsignedSmallInteger('calendar_year');
            $table->unsignedTinyInteger('calendar_month');
            $table->json('calendar_events');
            $table->text('teaser')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('status', 10)->default('draf')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isf_tokohs');
    }
};
