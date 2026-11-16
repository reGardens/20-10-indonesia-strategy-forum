<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // 1. Pastikan kategori induk 'Hukum' ada di isf_categories
        $hukum = DB::table('isf_categories')->where('slug', 'hukum')->first();
        if (!$hukum) {
            $hukumId = DB::table('isf_categories')->insertGetId([
                'parent_id' => null,
                'name' => 'Hukum',
                'slug' => 'hukum',
                'description' => 'Analisis yuridis, RUU, putusan pengadilan, dan kepatuhan regulasi.',
                'color' => '#E10600',
                'order' => 2,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $hukumId = $hukum->id;
        }

        // 2. Sub-kategori rubrik Hukum sesuai Issue #58:
        //    - /hukum/regulasi/ : UU, Perpres, Perda baru
        //    - /hukum/putusan/  : Analisis Putusan MK/MA
        //    - /hukum/kasus/    : Kasus Hukum Besar/Tipikor
        $subcategories = [
            [
                'parent_id' => $hukumId,
                'name' => 'Regulasi & Perundang-undangan',
                'slug' => 'regulasi',
                'description' => 'UU, Perpres, Perda baru, dan analisis kepatuhan regulasi.',
                'color' => '#2563EB',
                'order' => 1,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'parent_id' => $hukumId,
                'name' => 'Putusan Peradilan',
                'slug' => 'putusan',
                'description' => 'Analisis putusan Mahkamah Konstitusi, Mahkamah Agung, dan peradilan umum.',
                'color' => '#7C3AED',
                'order' => 2,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'parent_id' => $hukumId,
                'name' => 'Kasus Hukum',
                'slug' => 'kasus',
                'description' => 'Kasus hukum besar, tindak pidana korupsi, dan proses penegakan hukum.',
                'color' => '#DC2626',
                'order' => 3,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($subcategories as $sub) {
            $exists = DB::table('isf_categories')
                ->where('parent_id', $hukumId)
                ->where('slug', $sub['slug'])
                ->exists();

            if (!$exists) {
                DB::table('isf_categories')->insert($sub);
            }
        }
    }

    public function down(): void
    {
        $hukum = DB::table('isf_categories')->where('slug', 'hukum')->first();
        if ($hukum) {
            DB::table('isf_categories')
                ->where('parent_id', $hukum->id)
                ->whereIn('slug', ['regulasi', 'putusan', 'kasus'])
                ->delete();
        }
    }
};

