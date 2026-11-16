<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('isf_policy_trackers', function (Blueprint $table) {
            $table->id();
            $table->string('program_name');
            $table->string('slug')->unique();
            $table->string('sector')->index(); // ekonomi, kesehatan, pendidikan, pertahanan, lingkungan
            $table->text('promise'); // Janji / target program pemerintah
            $table->text('realization'); // Realisasi & fakta capaian lapangan terkini
            $table->unsignedTinyInteger('progress_percent')->default(0); // 0-100%
            $table->string('status_label', 50)->default('dalam_proses'); // tercapai, dalam_proses, tertunda, perlu_evaluasi
            $table->string('source_title')->nullable();
            $table->string('source_url', 500)->nullable();
            $table->date('last_updated_date');
            $table->integer('order')->default(0);
            $table->string('status', 20)->default('terbit'); // draf, terbit
            $table->timestamps();
        });

        // Seed data Monitoring Kebijakan (Janji vs Realisasi)
        $now = now();
        $trackers = [
            [
                'program_name' => 'Program Pemenuhan Makan Bergizi Gratis (MBG)',
                'slug' => 'program-makan-bergizi-gratis-mbg',
                'sector' => 'kesehatan',
                'promise' => 'Penyediaan makanan bergizi harian bagi 82,9 juta anak sekolah, balita, dan ibu hamil di seluruh Indonesia secara bertahap hingga tahun 2029.',
                'realization' => 'Pelaksanaan operasional tahap awal telah menjangkau lebih dari 4,2 juta penerima manfaat di 38 provinsi dengan pembentukan 1.100 satuan pelayanan gizi terpadu.',
                'progress_percent' => 45,
                'status_label' => 'dalam_proses',
                'source_title' => 'Laporan Akuntabilitas Badan Gizi Nasional & Nota Keuangan APBN',
                'source_url' => 'https://kemenkeu.go.id/informasi-publik/anggaran-mbg',
                'last_updated_date' => '2026-02-14',
                'order' => 1,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'program_name' => 'Pembangunan Smelter Nikel & Hilirisasi Mineral Terintegrasi',
                'slug' => 'hilirisasi-smelter-nikel-bauksit',
                'sector' => 'ekonomi',
                'promise' => 'Penghentian total ekspor bijih mentah dan pengoperasian 30 pabrik pemurnian (smelter) nikel dan bauksit terintegrasi di Morowali dan Halmahera.',
                'realization' => 'Sebanyak 23 fasilitas pemurnian nikel dan 4 fasilitas bauksit telah beroperasi komersial penuh, menyumbang devisa ekspor produk turunan bernilai tambah tinggi.',
                'progress_percent' => 82,
                'status_label' => 'tercapai',
                'source_title' => 'Kementerian Investasi/BKPM & Kementerian ESDM',
                'source_url' => 'https://esdm.go.id/kinerja-hilirisasi',
                'last_updated_date' => '2026-02-08',
                'order' => 2,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'program_name' => 'Pensiun Dini PLTU Batubara & Akselerasi Bauran EBT',
                'slug' => 'pensiun-dini-pltu-bauran-ebt',
                'sector' => 'lingkungan',
                'promise' => 'Penutupan bertahap pembangkit listrik tenaga uap batubara berkapasitas 4,8 GW serta peningkatan bauran energi baru terbarukan hingga 23%.',
                'realization' => 'Dua unit PLTU percontohan (Cirebon-1 dan Pelabuhan Ratu) telah mencapai kesepakatan pra-pensiun, namun realisasi komitmen pinjaman lunak JETP masih berjalan lambat.',
                'progress_percent' => 32,
                'status_label' => 'tertunda',
                'source_title' => 'Sekretariat JETP Indonesia & Kementerian ESDM',
                'source_url' => 'https://jetp-id.org/updates',
                'last_updated_date' => '2026-01-25',
                'order' => 3,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'program_name' => 'Digitalisasi Rekam Medis Nasional Melalui Platform SatuSehat',
                'slug' => 'digitalisasi-rekam-medis-satusehat',
                'sector' => 'kesehatan',
                'promise' => 'Integrasi menyeluruh 100% data rekam medis elektronik puskesmas, klinik, dan rumah sakit ke platform SatuSehat nasional.',
                'realization' => 'Lebih dari 93% puskesmas dan 87% rumah sakit rujukan telah sukses terkoneksi sistem rekam medis elektronik terstandar nasional.',
                'progress_percent' => 90,
                'status_label' => 'tercapai',
                'source_title' => 'Digital Transformation Office (DTO) Kementerian Kesehatan',
                'source_url' => 'https://satusehat.kemkes.go.id',
                'last_updated_date' => '2026-02-10',
                'order' => 4,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'program_name' => 'Revitalisasi SMK Unggulan & Pemadanan Fasilitas Industri',
                'slug' => 'revitalisasi-smk-vokasi-industri',
                'sector' => 'pendidikan',
                'promise' => 'Modernisasi sarana prasarana 5.000 SMK pusat keunggulan dan pembentukan kurikulum berbasis kebutuhan nyata industri strategis.',
                'realization' => 'Tercatat 2.450 SMK telah memperoleh akreditasi kemitraan industri binaan BUMN dan swasta; program terus berlanjut ke wilayah pulau terluar.',
                'progress_percent' => 58,
                'status_label' => 'dalam_proses',
                'source_title' => 'Ditjen Pendidikan Vokasi Kemendikbudristek',
                'source_url' => 'https://vokasi.kemdikbud.go.id',
                'last_updated_date' => '2026-01-30',
                'order' => 5,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'program_name' => 'Pengadaan Sensor Radar Terintegrasi & Pengamanan Selat ALKI',
                'slug' => 'pengadaan-radar-maritim-alki',
                'sector' => 'pertahanan',
                'promise' => 'Pemasangan jaringan sensor radar pantai 3D dan sistem pemantauan satelit otomatis di sepanjang Alur Laut Kepulauan Indonesia (ALKI I, II, dan III).',
                'realization' => 'Telah terpasang 16 stasiun radar maritim pesisir di Selat Sunda dan Selat Lombok; pengadaan fase kedua memasuki verifikasi kesiapan sistem.',
                'progress_percent' => 65,
                'status_label' => 'dalam_proses',
                'source_title' => 'Kementerian Pertahanan RI & Mabes TNI AL',
                'source_url' => 'https://kemhan.go.id/post-radar-maritim',
                'last_updated_date' => '2026-02-02',
                'order' => 6,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('isf_policy_trackers')->insert($trackers);
    }

    public function down(): void
    {
        Schema::dropIfExists('isf_policy_trackers');
    }
};

