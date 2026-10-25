<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Artikel: Beranda hero, Terkini list, Isu Terpopuler sidebar.
     */
    public function up(): void
    {
        Schema::create('isf_articles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('category', 20);
            $table->date('published_at');
            $table->text('summary')->nullable();
            $table->longText('body')->nullable();
            $table->string('author_name')->nullable();
            $table->string('author_role')->nullable();
            $table->unsignedSmallInteger('reading_minutes')->nullable();
            $table->string('image_alt')->nullable();
            $table->string('image_url')->nullable();
            $table->string('link_url')->nullable();
            $table->json('fact_blocks')->nullable();
            $table->boolean('is_hero')->default(false);
            $table->boolean('show_in_terkini')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->unsignedSmallInteger('popular_rank')->nullable();
            $table->string('status', 10)->default('draf')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isf_articles');
    }
};
