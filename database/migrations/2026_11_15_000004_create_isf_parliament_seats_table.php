<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('isf_parliament_seats', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 50)->default('2024-2029');
            $table->string('lembaga', 50)->default('DPR');
            $table->string('partai', 100);
            $table->string('singkatan', 50)->nullable();
            $table->unsignedInteger('jumlah_kursi')->default(0);
            $table->string('warna_partai', 50)->default('#E10600');
            $table->integer('urutan')->default(0);
            $table->string('status', 20)->default('terbit');
            $table->timestamps();

            $table->index(['lembaga', 'periode', 'status']);
        });

        // Seed default DPR RI 2024–2029 (580 kursi total)
        $now = now();
        $seats = [
            [
                'periode' => '2024-2029',
                'lembaga' => 'DPR',
                'partai' => 'Partai Demokrasi Indonesia Perjuangan',
                'singkatan' => 'PDI Perjuangan',
                'jumlah_kursi' => 110,
                'warna_partai' => '#D61C24',
                'urutan' => 1,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'periode' => '2024-2029',
                'lembaga' => 'DPR',
                'partai' => 'Partai Golongan Karya',
                'singkatan' => 'Golkar',
                'jumlah_kursi' => 102,
                'warna_partai' => '#FFD700',
                'urutan' => 2,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'periode' => '2024-2029',
                'lembaga' => 'DPR',
                'partai' => 'Partai Gerakan Indonesia Raya',
                'singkatan' => 'Gerindra',
                'jumlah_kursi' => 86,
                'warna_partai' => '#D97706',
                'urutan' => 3,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'periode' => '2024-2029',
                'lembaga' => 'DPR',
                'partai' => 'Partai NasDem',
                'singkatan' => 'NasDem',
                'jumlah_kursi' => 69,
                'warna_partai' => '#1E3A8A',
                'urutan' => 4,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'periode' => '2024-2029',
                'lembaga' => 'DPR',
                'partai' => 'Partai Kebangkitan Bangsa',
                'singkatan' => 'PKB',
                'jumlah_kursi' => 68,
                'warna_partai' => '#059669',
                'urutan' => 5,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'periode' => '2024-2029',
                'lembaga' => 'DPR',
                'partai' => 'Partai Keadilan Sejahtera',
                'singkatan' => 'PKS',
                'jumlah_kursi' => 53,
                'warna_partai' => '#EA580C',
                'urutan' => 6,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'periode' => '2024-2029',
                'lembaga' => 'DPR',
                'partai' => 'Partai Amanat Nasional',
                'singkatan' => 'PAN',
                'jumlah_kursi' => 48,
                'warna_partai' => '#2563EB',
                'urutan' => 7,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'periode' => '2024-2029',
                'lembaga' => 'DPR',
                'partai' => 'Partai Demokrat',
                'singkatan' => 'Demokrat',
                'jumlah_kursi' => 44,
                'warna_partai' => '#1D4ED8',
                'urutan' => 8,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('isf_parliament_seats')->insert($seats);
    }

    public function down(): void
    {
        Schema::dropIfExists('isf_parliament_seats');
    }
};

