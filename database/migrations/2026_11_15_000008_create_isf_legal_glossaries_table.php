<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('isf_legal_glossaries', function (Blueprint $table) {
            $table->id();
            $table->string('istilah', 150);
            $table->string('slug', 180)->unique();
            $table->char('abjad', 1)->index();
            $table->text('definisi');
            $table->text('dasar_hukum')->nullable();
            $table->json('istilah_terkait')->nullable();
            $table->integer('urutan')->default(0);
            $table->string('status', 20)->default('terbit');
            $table->timestamps();

            $table->index(['status', 'abjad', 'urutan']);
        });

        // Seed kamus istilah hukum awal terbit
        $now = now();
        $terms = [
            [
                'istilah' => 'Actus Reus',
                'slug' => 'actus-reus',
                'abjad' => 'A',
                'definisi' => 'Unsur perbuatan lahiriah atau wujud perbuatan fisik terlarang yang dilakukan seseorang yang dapat dipidana menurut hukum.',
                'dasar_hukum' => 'Doktrin Hukum Pidana & Asas Legalitas Pasal 1 ayat (1) KUHP.',
                'istilah_terkait' => json_encode(['Mens Rea', 'Asas Legalitas']),
                'urutan' => 1,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'istilah' => 'Banding',
                'slug' => 'banding',
                'abjad' => 'B',
                'definisi' => 'Upaya hukum biasa yang diajukan oleh salah satu pihak yang berperkara kepada Pengadilan Tinggi untuk memeriksa ulang putusan Pengadilan Negeri.',
                'dasar_hukum' => 'Pasal 67 KUHAP (Perkara Pidana) & UU No. 20 Tahun 1947 (Perkara Perdata).',
                'istilah_terkait' => json_encode(['Kasasi', 'Inkracht', 'Pengadilan Tinggi']),
                'urutan' => 2,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'istilah' => 'Daluwarsa',
                'slug' => 'daluwarsa',
                'abjad' => 'D',
                'definisi' => 'Lewatnya tenggang waktu tertentu yang ditentukan undang-undang, yang mengakibatkan gugurnya hak menuntut pidana atau gugurnya kewajiban hukum perdata.',
                'dasar_hukum' => 'Pasal 78 KUHP (Daluwarsa Penuntutan Pidana) & Pasal 1967 KUHPerdata.',
                'istilah_terkait' => json_encode(['Gugur Penuntutan', 'Kedaluwarsa Hak']),
                'urutan' => 3,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'istilah' => 'Gratifikasi',
                'slug' => 'gratifikasi',
                'abjad' => 'G',
                'definisi' => 'Pemberian dalam arti luas, meliputi pemberian uang, barang, rabat, komisi, pinjaman tanpa bunga, tiket perjalanan, fasilitas penginapan, dan fasilitas lainnya yang diterima oleh pegawai negeri atau penyelenggara negara.',
                'dasar_hukum' => 'Pasal 12B UU No. 20 Tahun 2001 jo. UU No. 31 Tahun 1999 tentang Pemberantasan Tindak Pidana Korupsi.',
                'istilah_terkait' => json_encode(['Suap', 'Tipikor', 'Laporan KPK']),
                'urutan' => 4,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'istilah' => 'Inkracht (Berkekuatan Hukum Tetap)',
                'slug' => 'inkracht',
                'abjad' => 'I',
                'definisi' => 'Kondisi putusan pengadilan yang tidak dapat diubah lagi melalui upaya hukum biasa (banding atau kasasi), baik karena tenggang waktu telah lewat atau para pihak telah menerimanya.',
                'dasar_hukum' => 'Pasal 1 angka 32 KUHAP jo. Pasal 195 HIR.',
                'istilah_terkait' => json_encode(['Eksekusi Putusan', 'Peninjauan Kembali']),
                'urutan' => 5,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'istilah' => 'Judicial Review',
                'slug' => 'judicial-review',
                'abjad' => 'J',
                'definisi' => 'Hak uji materiil atau formil yang dimiliki lembaga peradilan terhadap peraturan perundang-undangan; di Indonesia kewenangan menguji UU terhadap UUD berada di Mahkamah Konstitusi, sedangkan peraturan di bawah UU terhadap UU berada di Mahkamah Agung.',
                'dasar_hukum' => 'Pasal 24C ayat (1) & Pasal 24A ayat (1) UUD NRI 1945.',
                'istilah_terkait' => json_encode(['Mahkamah Konstitusi', 'Mahkamah Agung', 'Uji Materiil']),
                'urutan' => 6,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'istilah' => 'Kasasi',
                'slug' => 'kasasi',
                'abjad' => 'K',
                'definisi' => 'Upaya hukum biasa ke Mahkamah Agung terhadap putusan pengadilan tingkat banding atau tingkat terakhir dari semua lingkungan peradilan guna membatalkan putusan yang dinilai tidak menerapkan hukum atau melanggar hukum.',
                'dasar_hukum' => 'Pasal 244-258 KUHAP & UU No. 14 Tahun 1985 jo. UU No. 3 Tahun 2009 tentang Mahkamah Agung.',
                'istilah_terkait' => json_encode(['Banding', 'Mahkamah Agung', 'Inkracht']),
                'urutan' => 7,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'istilah' => 'Mens Rea',
                'slug' => 'mens-rea',
                'abjad' => 'M',
                'definisi' => 'Sikap batin jahat, niat jahat, atau kesalahan (kesengajaan atau kealpaan) dari pelaku tindak pidana saat melakukan perbuatan melanggar hukum.',
                'dasar_hukum' => 'Doktrin Hukum Pidana: Actus non facit reum nisi mens sit rea (tiada pidana tanpa kesalahan).',
                'istilah_terkait' => json_encode(['Actus Reus', 'Pertanggungjawaban Pidana']),
                'urutan' => 8,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'istilah' => 'Ne Bis In Idem',
                'slug' => 'ne-bis-in-idem',
                'abjad' => 'N',
                'definisi' => 'Asas hukum fundamental yang menyatakan seseorang tidak boleh dituntut atau diadili dua kali atas perbuatan atau peristiwa yang sama apabila telah ada putusan hakim yang berkekuatan hukum tetap (inkracht).',
                'dasar_hukum' => 'Pasal 76 KUHP (Kitab Undang-Undang Hukum Pidana).',
                'istilah_terkait' => json_encode(['Inkracht', 'Kepastian Hukum']),
                'urutan' => 9,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'istilah' => 'Praperadilan',
                'slug' => 'praperadilan',
                'abjad' => 'P',
                'definisi' => 'Wewenang hakim Pengadilan Negeri untuk memeriksa dan memutus sah atau tidaknya penangkapan, penahanan, penghentian penyidikan atau penuntutan, serta penetapan tersangka.',
                'dasar_hukum' => 'Pasal 77-83 KUHAP jo. Putusan Mahkamah Konstitusi No. 21/PUU-XII/2014.',
                'istilah_terkait' => json_encode(['Tersangka', 'Penyidikan', 'Hakim Tunggal']),
                'urutan' => 10,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'istilah' => 'Ultra Petita',
                'slug' => 'ultra-petita',
                'abjad' => 'U',
                'definisi' => 'Penjatuhan putusan oleh hakim atas perkara yang tidak dituntut atau menjatuhkan putusan melebihi dari apa yang dituntut dalam permohonan atau gugatan.',
                'dasar_hukum' => 'Pasal 178 ayat (3) HIR jo. Pasal 189 ayat (3) RBg.',
                'istilah_terkait' => json_encode(['Gugatan', 'Petitum', 'Putusan Hakim']),
                'urutan' => 11,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'istilah' => 'Vonis',
                'slug' => 'vonis',
                'abjad' => 'V',
                'definisi' => 'Putusan akhir majelis hakim dalam persidangan terbuka untuk umum yang menyelesaikan sengketa perdata atau menentukan pidana bagi terdakwa dalam perkara pidana.',
                'dasar_hukum' => 'Pasal 182-195 KUHAP.',
                'istilah_terkait' => json_encode(['Amar Putusan', 'Inkracht', 'Banding']),
                'urutan' => 12,
                'status' => 'terbit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('isf_legal_glossaries')->insert($terms);
    }

    public function down(): void
    {
        Schema::dropIfExists('isf_legal_glossaries');
    }
};

