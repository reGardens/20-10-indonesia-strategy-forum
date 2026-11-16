<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('isf_charts', 'sector')) {
            Schema::table('isf_charts', function (Blueprint $table) {
                $table->string('sector', 50)->nullable()->after('is_beranda')->index();
            });
        }

        // Seed grafik data & statistik untuk rubrik kebijakan publik
        $now = now();
        $charts = [
            [
                'title' => 'Alokasi Belanja Prioritas APBN 2026 per Sektor',
                'description' => 'Proporsi alokasi belanja kementerian dan lembaga strategis dalam APBN Tahun Anggaran 2026.',
                'sector' => 'ekonomi',
                'unit' => ' T',
                'data' => json_encode([
                    ['label' => 'Pendidikan (20%)', 'value' => 722],
                    ['label' => 'Perlindungan Sosial', 'value' => 504],
                    ['label' => 'Infrastruktur & Konektivitas', 'value' => 422],
                    ['label' => 'Pertahanan & Keamanan', 'value' => 380],
                    ['label' => 'Kesehatan (BPJS & Faskes)', 'value' => 218],
                    ['label' => 'Ketahanan Pangan & Energi', 'value' => 195],
                ]),
                'source_title' => 'Nota Keuangan dan Buku II APBN 2026, Kementerian Keuangan RI',
                'source_url' => 'https://kemenkeu.go.id/nota-keuangan-2026',
                'is_beranda' => false,
                'order' => 10,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Progres Bauran Energi Terbarukan Nasional (2020-2026)',
                'description' => 'Persentase capaian pemanfaatan pembangkit EBT (Surya, Air, Geotermal) dalam sistem kelistrikan PLN.',
                'sector' => 'lingkungan',
                'unit' => '%',
                'data' => json_encode([
                    ['label' => 'Tahun 2020', 'value' => 11.2],
                    ['label' => 'Tahun 2022', 'value' => 12.3],
                    ['label' => 'Tahun 2024', 'value' => 13.8],
                    ['label' => 'Tahun 2025', 'value' => 14.5],
                    ['label' => 'Target 2026', 'value' => 17.5],
                    ['label' => 'Target JETP 2030', 'value' => 23.0],
                ]),
                'source_title' => 'Laporan Kinerja Kementerian ESDM & Statistik Ketenagalistrikan PLN',
                'source_url' => 'https://esdm.go.id/bauran-ebt',
                'is_beranda' => false,
                'order' => 11,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('isf_charts')->insert($charts);
    }

    public function down(): void
    {
        if (Schema::hasColumn('isf_charts', 'sector')) {
            Schema::table('isf_charts', function (Blueprint $table) {
                $table->dropColumn('sector');
            });
        }
    }
};

