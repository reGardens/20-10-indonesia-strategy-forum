<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('isf_case_timelines', function (Blueprint $table) {
            $table->id();
            $table->string('judul_kasus', 255);
            $table->string('slug', 255)->unique();
            $table->string('kategori_kasus', 100)->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('status_kasus', 100)->default('Proses Sidang');
            $table->string('gambar', 1000)->nullable();
            $table->integer('urutan')->default(0);
            $table->string('status', 20)->default('terbit');
            $table->timestamps();

            $table->index(['status', 'urutan']);
        });

        Schema::create('isf_case_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_timeline_id')->constrained('isf_case_timelines')->cascadeOnDelete();
            $table->date('tanggal_peristiwa');
            $table->string('tahap', 100);
            $table->string('judul', 255);
            $table->text('deskripsi')->nullable();
            $table->string('sumber_tautan', 500)->nullable();
            $table->integer('urutan')->default(0);
            $table->string('status', 20)->default('terbit');
            $table->timestamps();

            $table->index(['case_timeline_id', 'status', 'tanggal_peristiwa']);
        });

        // Seed data kasus hukum besar dan alur kronologi peristiwanya
        $now = now();
        $timelineId = DB::table('isf_case_timelines')->insertGetId([
            'judul_kasus' => 'Uji Materiil Ambang Batas Parlemen (Parliamentary Threshold) di Mahkamah Konstitusi',
            'slug' => 'uji-materiil-parliamentary-threshold-mk',
            'kategori_kasus' => 'Sengketa Konstitusi',
            'deskripsi' => 'Rangkaian uji materiil ketentuan ambang batas parlemen 4% dalam Pasal 414 ayat (1) UU No. 7/2017 tentang Pemilu yang berujung pada Putusan MK No. 116/PUU-XXI/2023.',
            'status_kasus' => 'Putusan Inkracht (Masa Transisi Regulasi 2029)',
            'gambar' => null,
            'urutan' => 1,
            'status' => 'terbit',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $events = [
            [
                'case_timeline_id' => $timelineId,
                'tanggal_peristiwa' => '2023-06-21',
                'tahap' => 'Pengajuan Permohonan',
                'judul' => 'Perludem Mendaftarkan Uji Materiil Pasal 414 ayat (1) UU Pemilu ke MK',
                'deskripsi' => 'Pemohon mendalilkan ketentuan ambang batas parlemen 4% menyebabkan disproporsionalitas pemilu dan hilangnya jutaan suara sah rakyat yang tidak terkonversi menjadi kursi DPR.',
                'sumber_tautan' => 'https://www.mkri.id/index.php?page=web.PerkaraExcel&id=1&no=116/PUU-XXI/2023',
                'urutan' => 1,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'case_timeline_id' => $timelineId,
                'tanggal_peristiwa' => '2023-09-12',
                'tahap' => 'Sidang Pleno',
                'judul' => 'Keterangan Pemerintah dan DPR Mempertahankan Ambang Batas 4%',
                'deskripsi' => 'Pemerintah dan Komisi III DPR RI menyampaikan bahwa ambang batas parlemen merupakan open legal policy pembentuk undang-undang demi menyederhanakan sistem kepartaian dan stabilitas presidensial.',
                'sumber_tautan' => 'https://www.mkri.id/public/content/persidangan/risalah/risalah_116_PUU-XXI_2023.pdf',
                'urutan' => 2,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'case_timeline_id' => $timelineId,
                'tanggal_peristiwa' => '2024-02-29',
                'tahap' => 'Pembacaan Putusan',
                'judul' => 'MK Mengabulkan Sebagian Permohonan: Ambang Batas 4% Harus Diubah Sebelum Pemilu 2029',
                'deskripsi' => 'Dalam Putusan No. 116/PUU-XXI/2023, MK menyatakan norma ambang batas parlemen 4% bertentangan dengan UUD 1945 dan tidak memiliki kekuatan hukum mengikat secara bersyarat sepanjang tidak diubah sebelum Pemilu DPR 2029.',
                'sumber_tautan' => 'https://www.mkri.id/public/content/persidangan/putusan/putusan_116_PUU-XXI_2023.pdf',
                'urutan' => 3,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'case_timeline_id' => $timelineId,
                'tanggal_peristiwa' => '2024-07-24',
                'tahap' => 'Tindak Lanjut Legislasi',
                'judul' => 'Badan Keahlian DPR RI Menyusun Naskah Akademik Revisi Ambang Batas Parlemen',
                'deskripsi' => 'DPR RI bersama Kementerian Dalam Negeri memulai kajian simulasi formula ambang batas baru yang rasional dan proporsional untuk dimasukkan ke dalam RUU Pemilu Kodifikasi mendatang.',
                'sumber_tautan' => null,
                'urutan' => 4,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('isf_case_events')->insert($events);

        // Kasus 2: Kasus Tata Niaga Komoditas Tambang / Tipikor
        $timeline2Id = DB::table('isf_case_timelines')->insertGetId([
            'judul_kasus' => 'Penanganan Perkara Dugaan Korupsi Tata Niaga Komoditas Timah 2015–2022',
            'slug' => 'dugaan-korupsi-tata-niaga-timah',
            'kategori_kasus' => 'Tindak Pidana Korupsi',
            'deskripsi' => 'Penegakan hukum oleh Kejaksaan Agung terkait penyimpangan tata niaga komoditas timah di wilayah IUP PT Timah Tbk yang menimbulkan kerugian keuangan dan kerusakan lingkungan masif.',
            'status_kasus' => 'Sidang Pembuktian di Pengadilan Tipikor',
            'gambar' => null,
            'urutan' => 2,
            'status' => 'terbit',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $events2 = [
            [
                'case_timeline_id' => $timeline2Id,
                'tanggal_peristiwa' => '2023-10-17',
                'tahap' => 'Penyidikan Awal',
                'judul' => 'Kejaksaan Agung Menaikkan Status Kasus Tata Niaga Timah ke Tahap Penyidikan Khusus',
                'deskripsi' => 'Tim Penyidik Jampidsus melakukan penggeledahan serentak di sejumlah kantor smelter swasta di Bangka Belitung dan menyita dokumen perizinan sewa alat peleburan timah.',
                'sumber_tautan' => null,
                'urutan' => 1,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'case_timeline_id' => $timeline2Id,
                'tanggal_peristiwa' => '2024-02-19',
                'tahap' => 'Penetapan Tersangka',
                'judul' => 'Penyidik Jampidsus Menetapkan Belasan Tersangka dari Unsur Direksi dan Swasta',
                'deskripsi' => 'Sejumlah pejabat korporasi swasta dan mantan direksi BUMN ditetapkan sebagai tersangka dan ditahan rutan atas dugaan pemufakatan jahat pengeluaran bijih timah ilegal.',
                'sumber_tautan' => null,
                'urutan' => 2,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'case_timeline_id' => $timeline2Id,
                'tanggal_peristiwa' => '2024-05-29',
                'tahap' => 'Audit Kerugian Negara',
                'judul' => 'BPKP Merilis Hasil Audit: Kerugian Negara & Lingkungan Mencapai Rp300 Triliun',
                'deskripsi' => 'Hasil perhitungan BPKP bersama ahli lingkungan forensik IPB menetapkan total kerugian sebesar Rp300,003 triliun yang mencakup kerugian ekologis, ekonomi lingkungan, dan biaya pemulihan tambang.',
                'sumber_tautan' => null,
                'urutan' => 3,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'case_timeline_id' => $timeline2Id,
                'tanggal_peristiwa' => '2024-07-31',
                'tahap' => 'Sidang Perdana',
                'judul' => 'Pembacaan Surat Dakwaan di Pengadilan Tindak Pidana Korupsi Jakarta Pusat',
                'deskripsi' => 'Jaksa Penuntut Umum membacakan dakwaan primair Pasal 2 ayat (1) jo. Pasal 18 UU Tipikor jo. Pasal 55 ayat (1) ke-1 KUHP serta dakwaan tindak pidana pencucian uang (TPPU).',
                'sumber_tautan' => null,
                'urutan' => 4,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('isf_case_events')->insert($events2);
    }

    public function down(): void
    {
        Schema::dropIfExists('isf_case_events');
        Schema::dropIfExists('isf_case_timelines');
    }
};

