<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Master Istilah Hukum
        if (!Schema::hasTable('isf_legal_terms')) {
            Schema::create('isf_legal_terms', function (Blueprint $table) {
                $table->id();
                $table->string('istilah', 150);
                $table->string('slug', 150)->nullable()->index();
                $table->string('abjad', 2)->default('A')->index();
                $table->string('kategori', 100)->nullable();
                $table->text('keterangan')->nullable();
                $table->integer('urutan')->default(0);
                $table->string('status', 20)->default('terbit')->index();
                $table->timestamps();
            });

            // Seed initial common terms from existing glossary if available
            $terms = [
                ['istilah' => 'Habeas Corpus', 'slug' => 'habeas-corpus', 'abjad' => 'H', 'kategori' => 'Hukum Pidana & HAM', 'keterangan' => 'Jaminan hak pengujian keabsahan penahanan oleh peradilan.'],
                ['istilah' => 'Judicial Review', 'slug' => 'judicial-review', 'abjad' => 'J', 'kategori' => 'Hukum Tata Negara', 'keterangan' => 'Hak uji materi undang-undang terhadap konstitusi.'],
                ['istilah' => 'Pra-peradilan', 'slug' => 'pra-peradilan', 'abjad' => 'P', 'kategori' => 'Hukum Acara Pidana', 'keterangan' => 'Wewenang PN untuk memeriksa sah/tidaknya penangkapan atau penahanan.'],
                ['istilah' => 'Asas Praduga Tak Bersalah', 'slug' => 'asas-praduga-tak-bersalah', 'abjad' => 'A', 'kategori' => 'Hukum Acara Pidana', 'keterangan' => 'Presumption of innocence.'],
                ['istilah' => 'Beban Pembuktian Terbalik', 'slug' => 'beban-pembuktian-terbalik', 'abjad' => 'B', 'kategori' => 'Tindak Pidana Korupsi', 'keterangan' => 'Terdakwa membuktikan harta bukan hasil tipikor.'],
                ['istilah' => 'Dissenting Opinion', 'slug' => 'dissenting-opinion', 'abjad' => 'D', 'kategori' => 'Peradilan & Putusan', 'keterangan' => 'Pendapat berbeda hakim dari putusan mayoritas.'],
                ['istilah' => 'Inkracht', 'slug' => 'inkracht', 'abjad' => 'I', 'kategori' => 'Hukum Acara', 'keterangan' => 'Berkekuatan hukum tetap.'],
                ['istilah' => 'Ne Bis In Idem', 'slug' => 'ne-bis-in-idem', 'abjad' => 'N', 'kategori' => 'Asas Hukum Pidana', 'keterangan' => 'Seseorang tidak dapat dituntut dua kali untuk perbuatan yang sama.'],
            ];
            foreach ($terms as $t) {
                DB::table('isf_legal_terms')->insert(array_merge($t, [
                    'urutan' => 0,
                    'status' => 'terbit',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        // 2. Kategori Sektor Kebijakan Publik
        if (!Schema::hasTable('isf_policy_sectors')) {
            Schema::create('isf_policy_sectors', function (Blueprint $table) {
                $table->id();
                $table->string('nama', 150);
                $table->string('slug', 150)->unique();
                $table->text('deskripsi')->nullable();
                $table->integer('urutan')->default(0);
                $table->string('status', 20)->default('terbit')->index();
                $table->timestamps();
            });

            // Seed initial default sectors
            $sectors = [
                ['nama' => 'Ekonomi & Fiskal', 'slug' => 'ekonomi', 'deskripsi' => 'Kebijakan fiskal, moneter, APBN, dan stabilitas makroekonomi.'],
                ['nama' => 'Kesehatan Masyarakat', 'slug' => 'kesehatan', 'deskripsi' => 'Transformasi sistem kesehatan nasional, BPJS, dan obat.'],
                ['nama' => 'Pendidikan & Riset', 'slug' => 'pendidikan', 'deskripsi' => 'Reformasi kurikulum, pendidikan tinggi, dan riset inovasi.'],
                ['nama' => 'Pertahanan & Keamanan', 'slug' => 'pertahanan', 'deskripsi' => 'Modernisasi alutsista, ketahanan siber, dan diplomasi.'],
                ['nama' => 'Lingkungan & Energi', 'slug' => 'lingkungan', 'deskripsi' => 'Transisi energi terbarukan, dekarbonisasi, dan kehutanan.'],
            ];
            foreach ($sectors as $idx => $sec) {
                DB::table('isf_policy_sectors')->insert(array_merge($sec, [
                    'urutan' => $idx + 1,
                    'status' => 'terbit',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        // 3. Database Peraturan & Rekomendasi Kebijakan
        if (!Schema::hasTable('isf_database_peraturans')) {
            Schema::create('isf_database_peraturans', function (Blueprint $table) {
                $table->id();
                $table->string('judul', 255);
                $table->string('nomor', 100)->nullable();
                $table->string('tahun', 10)->nullable();
                $table->string('kategori', 100)->nullable();
                $table->text('tentang')->nullable();
                $table->string('url_dokumen', 500);
                $table->string('lembaga', 150)->nullable();
                $table->integer('urutan')->default(0);
                $table->string('status', 20)->default('terbit')->index();
                $table->timestamps();
            });

            // Seed initial reference regulations
            $regs = [
                [
                    'judul' => 'JDIH Badan Pemeriksa Keuangan RI',
                    'nomor' => 'JDIH BPK RI',
                    'tahun' => '2026',
                    'kategori' => 'Rujukan Resmi Utama',
                    'tentang' => 'Portal basis data peraturan terlengkap Republik Indonesia: UU, PP, Perpres, dan Perda.',
                    'url_dokumen' => 'https://peraturan.bpk.go.id',
                    'lembaga' => 'Badan Pemeriksa Keuangan RI',
                ],
                [
                    'judul' => 'Jaringan Dokumentasi & Informasi Hukum Nasional',
                    'nomor' => 'JDIHN BPHN',
                    'tahun' => '2026',
                    'kategori' => 'Rujukan Nasional',
                    'tentang' => 'Pusat integrasi dokumen hukum seluruh instansi kementerian/lembaga negara RI.',
                    'url_dokumen' => 'https://jdihn.go.id',
                    'lembaga' => 'BPHN Kemenkumham RI',
                ],
                [
                    'judul' => 'JDIH Sekretariat Kabinet RI',
                    'nomor' => 'JDIH Setkab',
                    'tahun' => '2026',
                    'kategori' => 'Regulasi Eksekutif',
                    'tentang' => 'Publikasi naskah autentik Peraturan Presiden dan Keputusan Presiden RI.',
                    'url_dokumen' => 'https://setkab.go.id/jdih',
                    'lembaga' => 'Sekretariat Kabinet RI',
                ],
            ];
            foreach ($regs as $idx => $reg) {
                DB::table('isf_database_peraturans')->insert(array_merge($reg, [
                    'urutan' => $idx + 1,
                    'status' => 'terbit',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('isf_database_peraturans');
        Schema::dropIfExists('isf_policy_sectors');
        Schema::dropIfExists('isf_legal_terms');
    }
};

