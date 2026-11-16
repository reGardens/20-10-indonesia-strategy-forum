<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // 1. Pastikan kategori induk 'Kebijakan Publik' ada di isf_categories
        $kebijakan = DB::table('isf_categories')->where('slug', 'kebijakan')->first();
        if (!$kebijakan) {
            $kebijakanId = DB::table('isf_categories')->insertGetId([
                'parent_id' => null,
                'name' => 'Kebijakan Publik',
                'slug' => 'kebijakan',
                'description' => 'Kebijakan pemerintah, ekonomi, fiskal, dan dampaknya bagi dunia usaha.',
                'color' => '#E10600',
                'order' => 3,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $kebijakanId = $kebijakan->id;
        }

        // 2. Sub-kategori sektoral sesuai Issue #59:
        //    - /kebijakan-publik/ekonomi/
        //    - /kebijakan-publik/kesehatan/
        //    - /kebijakan-publik/pendidikan/
        //    - /kebijakan-publik/pertahanan/
        //    - /kebijakan-publik/lingkungan/
        $subcategories = [
            [
                'parent_id' => $kebijakanId,
                'name' => 'Ekonomi & Fiskal',
                'slug' => 'ekonomi',
                'description' => 'Kebijakan fiskal, APBN, moneter, inflasi, dan stabilitas makroekonomi.',
                'color' => '#2563EB',
                'order' => 1,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'parent_id' => $kebijakanId,
                'name' => 'Kesehatan Masyarakat',
                'slug' => 'kesehatan',
                'description' => 'Sistem kesehatan nasional, BPJS, ketahanan farmasi, dan layanan medis.',
                'color' => '#059669',
                'order' => 2,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'parent_id' => $kebijakanId,
                'name' => 'Pendidikan & Riset',
                'slug' => 'pendidikan',
                'description' => 'Kurikulum, alokasi anggaran pendidikan, riset teknologi, dan SDM.',
                'color' => '#D97706',
                'order' => 3,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'parent_id' => $kebijakanId,
                'name' => 'Pertahanan & Keamanan',
                'slug' => 'pertahanan',
                'description' => 'Modernisasi alutsista, ketahanan siber, diplomasi pertahanan, dan perbatasan.',
                'color' => '#475569',
                'order' => 4,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'parent_id' => $kebijakanId,
                'name' => 'Lingkungan & Energi',
                'slug' => 'lingkungan',
                'description' => 'Transisi energi terbarukan, dekarbonisasi industri, kehutanan, dan tata kelola iklim.',
                'color' => '#10B981',
                'order' => 5,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($subcategories as $sub) {
            $existing = DB::table('isf_categories')->where('slug', $sub['slug'])->first();
            if ($existing) {
                DB::table('isf_categories')->where('id', $existing->id)->update([
                    'parent_id' => $kebijakanId,
                    'name' => $sub['name'],
                    'description' => $sub['description'],
                    'order' => $sub['order'],
                    'status' => 'terbit',
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('isf_categories')->insert($sub);
            }
        }
    }

    public function down(): void
    {
        DB::table('isf_categories')->whereIn('slug', ['ekonomi', 'kesehatan', 'pendidikan', 'pertahanan', 'lingkungan'])->delete();
    }
};

