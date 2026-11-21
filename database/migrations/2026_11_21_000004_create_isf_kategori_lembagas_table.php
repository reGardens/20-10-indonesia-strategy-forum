<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('isf_kategori_lembagas')) {
            Schema::create('isf_kategori_lembagas', function (Blueprint $table) {
                $table->id();
                $table->string('nama', 150);
                $table->string('slug', 150)->unique();
                $table->text('deskripsi')->nullable();
                $table->integer('urutan')->default(0);
                $table->string('status', 20)->default('terbit')->index();
                $table->timestamps();
            });

            // Seed 3 Lembaga Utama sesuai landing page ISF
            $lembagas = [
                [
                    'nama' => 'Legislatif',
                    'slug' => 'legislatif',
                    'deskripsi' => 'Lembaga perwakilan rakyat dan pembentuk undang-undang (DPR RI, DPD RI, MPR RI).',
                    'urutan' => 1,
                    'status' => 'terbit',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'nama' => 'Eksekutif',
                    'slug' => 'eksekutif',
                    'deskripsi' => 'Pemerintah dan pelaksana undang-undang (Presiden, Wakil Presiden, Kabinet Kementerian, Lembaga Pemerintah).',
                    'urutan' => 2,
                    'status' => 'terbit',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'nama' => 'Pakar & Akademisi',
                    'slug' => 'pakar',
                    'deskripsi' => 'Cendekiawan, analis independen, akademisi perguruan tinggi, peneliti, dan dewan pakar strategis.',
                    'urutan' => 3,
                    'status' => 'terbit',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            DB::table('isf_kategori_lembagas')->insert($lembagas);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('isf_kategori_lembagas');
    }
};

