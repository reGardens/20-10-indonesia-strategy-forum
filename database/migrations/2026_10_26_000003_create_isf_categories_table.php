<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('isf_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('color', 50)->nullable();
            $table->integer('order')->default(0);
            $table->string('status', 20)->default('terbit');
            $table->timestamps();
        });

        // Seed default categories according to user request & specification:
        // Politik, Ekonomi, Kebijakan, Hukum, RUU
        $now = now();
        DB::table('isf_categories')->insert([
            [
                'name' => 'Politik',
                'slug' => 'politik',
                'description' => 'Isu dinamika politik nasional, pemilu, dan lembaga negara.',
                'color' => '#dc2626',
                'order' => 1,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Ekonomi',
                'slug' => 'ekonomi',
                'description' => 'Makroekonomi, moneter, fiskal, dan perdagangan.',
                'color' => '#2563eb',
                'order' => 2,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Kebijakan',
                'slug' => 'kebijakan',
                'description' => 'Perumusan dan implementasi kebijakan publik serta regulasi sektoral.',
                'color' => '#16a34a',
                'order' => 3,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Hukum',
                'slug' => 'hukum',
                'description' => 'Analisis putusan peradilan, penegakan hukum, dan kepatuhan hukum korporasi.',
                'color' => '#9333ea',
                'order' => 4,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'RUU',
                'slug' => 'ruu',
                'description' => 'Rancangan Undang-Undang dalam pembahasan di legislatif dan pemerintah.',
                'color' => '#ea580c',
                'order' => 5,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('isf_categories');
    }
};
