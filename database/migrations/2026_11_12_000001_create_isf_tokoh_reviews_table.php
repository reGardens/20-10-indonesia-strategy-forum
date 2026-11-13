<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indeks Tokoh: rating (1–5) & ulasan publik per tokoh (/tokoh/{slug}).
     * Satu ulasan per akun per tokoh; kirim ulang = perbarui ulasan lama.
     */
    public function up(): void
    {
        Schema::create('isf_tokoh_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tokoh_id')->constrained('isf_tokohs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('body');
            $table->boolean('is_anonymous')->default(false);
            $table->timestamps();

            $table->unique(['tokoh_id', 'user_id']);
            $table->index(['tokoh_id', 'rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isf_tokoh_reviews');
    }
};
