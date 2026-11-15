<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('isf_categories', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('isf_categories')->nullOnDelete();
        });

        // Pastikan kategori induk Politik ada
        $politik = DB::table('isf_categories')->where('slug', 'politik')->first();
        $politikId = $politik?->id;

        if ($politikId) {
            $now = now();
            $subcategories = [
                [
                    'parent_id' => $politikId,
                    'name' => 'Pemilu & Pilkada',
                    'slug' => 'pemilu',
                    'description' => 'Isu dinamika Pemilu, Pilpres, Pilkada serentak, dan kepemiluan nasional.',
                    'color' => '#E10600',
                    'order' => 1,
                    'status' => 'terbit',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'parent_id' => $politikId,
                    'name' => 'DPR/DPD/MPR',
                    'slug' => 'parlemen',
                    'description' => 'Kinerja legislasi, rapat komisi, dan dinamika fraksi parlemen DPR, DPD, dan MPR RI.',
                    'color' => '#D97706',
                    'order' => 2,
                    'status' => 'terbit',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'parent_id' => $politikId,
                    'name' => 'Eksekutif',
                    'slug' => 'pemerintahan',
                    'description' => 'Arah kebijakan kementerian, kabinet pemerintahan, dan lembaga eksekutif nasional.',
                    'color' => '#2563EB',
                    'order' => 3,
                    'status' => 'terbit',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'parent_id' => $politikId,
                    'name' => 'Dinamika Partai',
                    'slug' => 'partai',
                    'description' => 'Perkembangan internal, suksesi kepemimpinan, dan peta koalisi partai politik.',
                    'color' => '#7C3AED',
                    'order' => 4,
                    'status' => 'terbit',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ];

            foreach ($subcategories as $sub) {
                if (!DB::table('isf_categories')->where('slug', $sub['slug'])->exists()) {
                    DB::table('isf_categories')->insert($sub);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('isf_categories') && Schema::hasColumn('isf_categories', 'parent_id')) {
            DB::table('isf_categories')->whereIn('slug', ['pemilu', 'parlemen', 'pemerintahan', 'partai'])->delete();
            Schema::table('isf_categories', function (Blueprint $table) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            });
        }
    }
};

