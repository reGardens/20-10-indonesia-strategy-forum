<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('isf_policy_briefs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('sector')->index(); // ekonomi, kesehatan, pendidikan, pertahanan, lingkungan
            $table->string('author')->default('Tim Riset Kebijakan ISF');
            $table->string('institution')->nullable();
            $table->text('executive_summary');
            $table->json('key_recommendations')->nullable();
            $table->string('pdf_path');
            $table->string('pdf_size')->nullable(); // e.g. "2.4 MB"
            $table->date('published_at');
            $table->integer('order')->default(0);
            $table->string('status', 20)->default('terbit');
            $table->timestamps();
        });

        // Seed data Policy Briefs
        $now = now();
        $briefs = [
            [
                'title' => 'Evaluasi Fiskal dan Optimalisasi Pajak Digital Nasional 2026',
                'slug' => 'evaluasi-fiskal-dan-pajak-digital-2026',
                'sector' => 'ekonomi',
                'author' => 'Dr. Hendra Gunawan, S.E., M.Ec.',
                'institution' => 'Pusat Kajian Ekonomi Fiskal ISF',
                'executive_summary' => 'Analisis komprehensif struktur penerimaan negara dan potensi rasio perpajakan sektor ekonomi digital lintas batas. Diperlukan penyesuaian regulasi PPh dan PPN transaksi digital guna memperluas basis pajak tanpa menekan daya beli masyarakat.',
                'key_recommendations' => json_encode([
                    'Penyelarasan ketentuan Bentuk Usaha Tetap (BUT) digital lintas negara.',
                    'Pemberian insentif super tax deduction bagi sektor industri padat karya.',
                    'Penguatan interoperabilitas data perpajakan dengan sistem perbankan nasional.'
                ]),
                'pdf_path' => '/storage/policy-briefs/evaluasi-fiskal-2026.pdf',
                'pdf_size' => '2.4 MB',
                'published_at' => '2026-02-15',
                'order' => 1,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Akselerasi Penguatan Ketahanan Rantai Pasok Bahan Baku Obat Nasional',
                'slug' => 'ketahanan-rantai-pasok-obat-nasional',
                'sector' => 'kesehatan',
                'author' => 'Prof. Dr. apt. Siti Rahmawati, M.Si.',
                'institution' => 'Kelompok Kerja Kebijakan Farmasi Publik',
                'executive_summary' => 'Ketergantungan impor bahan baku obat (BBO) yang mencapai lebih dari 85% menimbulkan kerentanan sistematis terhadap stabilitas pasokan jaminan kesehatan nasional saat terjadi disrupsi global. Riset ini merumuskan peta jalan subtitusi impor 5 tahunan.',
                'key_recommendations' => json_encode([
                    'Pemberian subsidi riset sintesis kimia farmasi dasar di perguruan tinggi negeri.',
                    'Kewajiban penyerapan produk BBO dalam negeri pada tender obat e-Katalog pemerintah.',
                    'Penyederhanaan birokrasi uji klinis dan registrasi izin edar obat produksi domestik.'
                ]),
                'pdf_path' => '/storage/policy-briefs/ketahanan-farmasi-nasional.pdf',
                'pdf_size' => '1.8 MB',
                'published_at' => '2026-02-10',
                'order' => 2,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Reframing Efisiensi Anggaran Pendidikan 20% Menuju Revitalisasi Vokasi',
                'slug' => 'reframing-anggaran-pendidikan-dan-vokasi',
                'sector' => 'pendidikan',
                'author' => 'Tim Peneliti Kebijakan Pendidikan ISF',
                'institution' => 'Institut Tata Kelola Pendidikan',
                'executive_summary' => 'Evaluasi alokasi mandatori 20% anggaran pendidikan nasional menunjukkan perlunya redistribusi belanja transfer ke daerah ke arah peremajaan sarana vokasi industri dan pelatihan kompetensi adaptif berbasis kecerdasan buatan.',
                'key_recommendations' => json_encode([
                    'Link-and-match kurikulum SMK dan politeknik langsung dengan konsorsium industri strategis.',
                    'Peningkatan porsi tunjangan sertifikasi berbasis kinerja capaian lulusan terserap kerja.',
                    'Alokasi afirmasi laboratorium digital interaktif bagi sekolah di wilayah 3T.'
                ]),
                'pdf_path' => '/storage/policy-briefs/alokasi-pendidikan-vokasi.pdf',
                'pdf_size' => '3.1 MB',
                'published_at' => '2026-02-05',
                'order' => 3,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Penguatan Ketahanan Siber dan Pengawasan Zona Maritim Strategis',
                'slug' => 'ketahanan-siber-dan-zona-maritim',
                'sector' => 'pertahanan',
                'author' => 'Marsda TNI (Purn.) Ir. Bambang Soedjarwo',
                'institution' => 'Pusat Studi Ketahanan & Diplomasi Pertahanan',
                'executive_summary' => 'Konvergensi ancaman hybrid di ruang siber dan jalur perdagangan laut Nusantara menuntut penguatan arsitektur National Cyber Security Operations Center serta integrasi sensor radar pantai lintas matra.',
                'key_recommendations' => json_encode([
                    'Integrasi jalur komando informasi antara Bakamla, TNI AL, dan BSSN.',
                    'Modernisasi armada drone maritim pengintai jarak jauh berdaya tahan tinggi.',
                    'Penyusunan protokol pertahanan siber wajib bagi pengelola infrastruktur energi dan logistik vital.'
                ]),
                'pdf_path' => '/storage/policy-briefs/ketahanan-siber-maritim.pdf',
                'pdf_size' => '2.8 MB',
                'published_at' => '2026-01-28',
                'order' => 4,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Transisi Berkeadilan: Pensiun Dini PLTU Batubara dan Integrasi EBT',
                'slug' => 'transisi-energi-berkeadilan-pltu-ebt',
                'sector' => 'lingkungan',
                'author' => 'Dr. Maria Kusuma, M.Eng.',
                'institution' => 'Gugus Tugas Kebijakan Energi Bersih ISF',
                'executive_summary' => 'Skema pembiayaan transisi energi (Energy Transition Mechanism) memerlukan kepastian hukum tarif pasokan dan jaminan perlindungan sosial bagi komunitas lokal terdampak penghentian operasional tambang.',
                'key_recommendations' => json_encode([
                    'Penerapan insentif pajak karbon terukur bagi industri yang beralih ke energi surya atap.',
                    'Program pendampingan alih profesi bagi tenaga kerja di kawasan PLTU yang dipensiunkan.',
                    'Penyederhanaan perizinan transmisi cerdas (smart grid) untuk menghubungkan pulau-pulau terisolasi.'
                ]),
                'pdf_path' => '/storage/policy-briefs/transisi-ebt-pltu.pdf',
                'pdf_size' => '4.2 MB',
                'published_at' => '2026-01-20',
                'order' => 5,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('isf_policy_briefs')->insert($briefs);
    }

    public function down(): void
    {
        Schema::dropIfExists('isf_policy_briefs');
    }
};

