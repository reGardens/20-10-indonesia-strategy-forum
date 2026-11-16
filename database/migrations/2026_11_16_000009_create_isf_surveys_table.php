<?php

use App\Models\Isf\Survey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('isf_surveys', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->string('institution', 150);
            $table->boolean('is_isf')->default(false)->index();
            $table->enum('topic', ['politik', 'ekonomi', 'sosial', 'hukum'])->default('politik')->index();
            $table->string('region', 50)->default('nasional')->index(); // nasional, jawa, sumatera, kalimantan, sulawesi, bali_nusra, maluku_papua
            $table->string('survey_period', 150)->nullable();
            $table->date('release_date')->nullable();
            $table->unsignedInteger('sample_size')->default(1200);
            $table->string('margin_of_error', 50)->nullable()->default('±2.8%');
            $table->string('confidence_level', 50)->nullable()->default('95%');
            $table->string('sampling_method', 255)->nullable()->default('Multistage Random Sampling');
            $table->string('interview_method', 150)->nullable()->default('Wawancara Tatap Muka (Face-to-Face)');
            $table->string('headline_stat_value', 50)->nullable();
            $table->string('headline_stat_label', 255)->nullable();
            $table->string('headline_stat_comparison', 150)->nullable();
            $table->text('summary')->nullable();
            $table->text('expert_analysis')->nullable();
            $table->json('chart_data')->nullable();
            $table->string('pdf_path', 255)->nullable();
            $table->string('pdf_size', 50)->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->boolean('is_featured')->default(false)->index();
            $table->string('status', 20)->default('terbit')->index();
            $table->timestamps();
        });

        // Seed 1: Survei Nasional ISF (Politik & Pemerintahan) - Featured
        Survey::create([
            'title' => 'Evaluasi Kinerja 100 Hari Kabinet & Indeks Kepuasan Kebijakan Publik Nasional',
            'slug' => 'evaluasi-kinerja-100-hari-kabinet-dan-indeks-kepuasan-kebijakan-publik',
            'institution' => 'Indonesia Strategy Forum (ISF) Research',
            'is_isf' => true,
            'topic' => 'politik',
            'region' => 'nasional',
            'survey_period' => '24 Oktober – 4 November 2026',
            'release_date' => '2026-11-10',
            'sample_size' => 1600,
            'margin_of_error' => '±2.45%',
            'confidence_level' => '95%',
            'sampling_method' => 'Stratified Multistage Random Sampling di 38 Provinsi',
            'interview_method' => 'Wawancara Tatap Muka Terstruktur (CAPI System)',
            'headline_stat_value' => '74.8%',
            'headline_stat_label' => 'Tingkat Kepuasan Publik terhadap Stabilitas Pemerintahan & Kebijakan Ekonomi Makro',
            'headline_stat_comparison' => '+3.6% dibanding survei baseline pra-pelantikan',
            'summary' => 'Survei nasional ISF menunjukkan optimisme publik yang kokoh terhadap konsolidasi politik kabinet baru. Sentimen positif ditopang kepastian regulasi investasi dan keberlanjutan program perlindungan sosial, kendati sektor penciptaan lapangan kerja formal masih menjadi catatan kritis masyarakat bawah.',
            'expert_analysis' => 'Data memperlihatkan diferensiasi persepsi yang cukup tajam antara segmen generasi muda (Gen Z dan Milenial) dengan generasi senior. Generasi muda menuntut akselerasi reformasi birokrasi perizinan dan transparansi perpajakan, sementara generasi senior lebih mengapresiasi ketahanan pasokan pangan dan ketertiban umum. Kunci menjaga tren kepuasan di atas 70% berada pada mitigasi inflasi kebutuhan pokok menjelang kuartal pertama tahun mendatang.',
            'chart_data' => [
                'segments' => [
                    [
                        'id' => 'overall',
                        'name' => 'Nasional (Agregat)',
                        'type' => 'bar',
                        'items' => [
                            ['label' => 'Sangat Puas', 'value' => 26.4, 'color' => '#10B981'],
                            ['label' => 'Cukup Puas', 'value' => 48.4, 'color' => '#3B82F6'],
                            ['label' => 'Kurang Puas', 'value' => 17.8, 'color' => '#F59E0B'],
                            ['label' => 'Tidak Puas Sama Sekali', 'value' => 5.2, 'color' => '#EF4444'],
                            ['label' => 'Tidak Tahu / TT', 'value' => 2.2, 'color' => '#6B7280'],
                        ],
                    ],
                    [
                        'id' => 'usia',
                        'name' => 'Kelompok Usia Responden',
                        'type' => 'grouped_bar',
                        'groups' => [
                            ['name' => 'Gen Z (17-27)', 'items' => [['label' => 'Puas', 'value' => 69.4], ['label' => 'Kurang Puas', 'value' => 27.6], ['label' => 'TT', 'value' => 3.0]]],
                            ['name' => 'Milenial (28-43)', 'items' => [['label' => 'Puas', 'value' => 74.2], ['label' => 'Kurang Puas', 'value' => 23.6], ['label' => 'TT', 'value' => 2.2]]],
                            ['name' => 'Gen X (44-59)', 'items' => [['label' => 'Puas', 'value' => 77.8], ['label' => 'Kurang Puas', 'value' => 20.8], ['label' => 'TT', 'value' => 1.4]]],
                            ['name' => 'Boomer (60+)', 'items' => [['label' => 'Puas', 'value' => 76.0], ['label' => 'Kurang Puas', 'value' => 21.5], ['label' => 'TT', 'value' => 2.5]]],
                        ],
                    ],
                    [
                        'id' => 'gender',
                        'name' => 'Gender',
                        'type' => 'grouped_bar',
                        'groups' => [
                            ['name' => 'Laki-laki', 'items' => [['label' => 'Puas', 'value' => 73.6], ['label' => 'Kurang Puas', 'value' => 24.5], ['label' => 'TT', 'value' => 1.9]]],
                            ['name' => 'Perempuan', 'items' => [['label' => 'Puas', 'value' => 76.0], ['label' => 'Kurang Puas', 'value' => 21.5], ['label' => 'TT', 'value' => 2.5]]],
                        ],
                    ],
                    [
                        'id' => 'wilayah',
                        'name' => 'Sebaran Regional',
                        'type' => 'grouped_bar',
                        'groups' => [
                            ['name' => 'Pulau Jawa', 'items' => [['label' => 'Puas', 'value' => 76.2], ['label' => 'Kurang Puas', 'value' => 21.8], ['label' => 'TT', 'value' => 2.0]]],
                            ['name' => 'Luar Jawa', 'items' => [['label' => 'Puas', 'value' => 73.1], ['label' => 'Kurang Puas', 'value' => 24.4], ['label' => 'TT', 'value' => 2.5]]],
                        ],
                    ],
                ],
            ],
            'pdf_path' => '/storage/survei/laporan-survei-100-hari-isf-2026.pdf',
            'pdf_size' => '3.8 MB PDF',
            'order' => 1,
            'is_featured' => true,
            'status' => 'terbit',
        ]);

        // Seed 2: Survei Ekonomi (Lembaga Eksternal: Indikator Politik)
        Survey::create([
            'title' => 'Indeks Keyakinan Konsumen & Ekspektasi Inflasi Rumah Tangga Indonesia',
            'slug' => 'indeks-keyakinan-konsumen-dan-ekspektasi-inflasi-rumah-tangga',
            'institution' => 'Indikator Politik Indonesia',
            'is_isf' => false,
            'topic' => 'ekonomi',
            'region' => 'nasional',
            'survey_period' => '15 – 22 Oktober 2026',
            'release_date' => '2026-10-30',
            'sample_size' => 1220,
            'margin_of_error' => '±2.9%',
            'confidence_level' => '95%',
            'sampling_method' => 'Simple Random Sampling dari Kontak Basis Data RDD',
            'interview_method' => 'Wawancara Melalui Telepon Berbasis Komputer (CATI)',
            'headline_stat_value' => '62.1%',
            'headline_stat_label' => 'Masyarakat Yakin Kondisi Ekonomi Keluarga Membaik dalam 6 Bulan ke Depan',
            'headline_stat_comparison' => '-1.2% dari kuartal sebelumnya akibat volatilitas harga pangan',
            'summary' => 'Keyakinan konsumen nasional berada dalam zona optimis di atas ambang batas 50 poin. Kendati demikian, rumah tangga berpendapatan menengah ke bawah mencemaskan potensi kenaikan tarif PPN dan biaya pendidikan anak.',
            'expert_analysis' => 'Survei ini mengonfirmasi adanya fenomena kehati-hatian belanja (precautionary saving) pada kelas menengah perkotaan. Kebijakan stimulus bantalan sosial tunai dinilai penting untuk menahan kontraksi daya beli segmen desil 3 sampai 6.',
            'chart_data' => [
                'segments' => [
                    [
                        'id' => 'overall',
                        'name' => 'Nasional (Agregat)',
                        'type' => 'bar',
                        'items' => [
                            ['label' => 'Jauh Lebih Baik', 'value' => 18.5, 'color' => '#10B981'],
                            ['label' => 'Sedikit Lebih Baik', 'value' => 43.6, 'color' => '#3B82F6'],
                            ['label' => 'Sama Saja / Stagnan', 'value' => 24.3, 'color' => '#F59E0B'],
                            ['label' => 'Lebih Buruk', 'value' => 11.2, 'color' => '#EF4444'],
                            ['label' => 'Tidak Tahu / TT', 'value' => 2.4, 'color' => '#6B7280'],
                        ],
                    ],
                ],
            ],
            'pdf_path' => '/storage/survei/indikator-keyakinan-konsumen-okt-2026.pdf',
            'pdf_size' => '2.1 MB PDF',
            'order' => 2,
            'is_featured' => false,
            'status' => 'terbit',
        ]);

        // Seed 3: Survei Hukum & Keadilan (ISF Research)
        Survey::create([
            'title' => 'Tingkat Kepercayaan Publik terhadap Lembaga Penegak Hukum: KPK, Kejaksaan Agung, dan Polri',
            'slug' => 'kepercayaan-publik-terhadap-lembaga-penegak-hukum-kpk-kejakgung-polri',
            'institution' => 'Indonesia Strategy Forum (ISF) Research',
            'is_isf' => true,
            'topic' => 'hukum',
            'region' => 'nasional',
            'survey_period' => '5 – 14 Oktober 2026',
            'release_date' => '2026-10-25',
            'sample_size' => 1400,
            'margin_of_error' => '±2.6%',
            'confidence_level' => '95%',
            'sampling_method' => 'Multistage Random Sampling',
            'interview_method' => 'Wawancara Tatap Muka Terstruktur',
            'headline_stat_value' => '81.2%',
            'headline_stat_label' => 'Kejaksaan Agung Memperoleh Kepercayaan Tertinggi dalam Penindakan Kasus Korupsi Kakap',
            'headline_stat_comparison' => 'Mengungguli rata-rata kepercayaan institusi penegak hukum nasional (72.5%)',
            'summary' => 'Survei mendapati Kejaksaan Agung terus memimpin peringkat kepercayaan publik dalam pemberantasan mega korupsi, disusul oleh Kepolisian Republik Indonesia dan Komisi Pemberantasan Korupsi yang mulai pulih citranya pasca-revisi internal.',
            'expert_analysis' => 'Keberhasilan pengembalian kerugian keuangan negara (aset recovery) terbukti menjadi faktor paling determinan dalam memengaruhi persepsi publik dibandingkan semata-mata jumlah penersangkaan pejabat.',
            'chart_data' => [
                'segments' => [
                    [
                        'id' => 'overall',
                        'name' => 'Peringkat Kepercayaan Institusi Penegak Hukum',
                        'type' => 'bar',
                        'items' => [
                            ['label' => 'Kejaksaan Agung RI', 'value' => 81.2, 'color' => '#10B981'],
                            ['label' => 'TNI (Perbantuan Hukum)', 'value' => 88.4, 'color' => '#059669'],
                            ['label' => 'Polri', 'value' => 73.5, 'color' => '#3B82F6'],
                            ['label' => 'KPK', 'value' => 70.8, 'color' => '#F59E0B'],
                            ['label' => 'Mahkamah Agung', 'value' => 68.2, 'color' => '#6366F1'],
                        ],
                    ],
                ],
            ],
            'pdf_path' => '/storage/survei/isf-penegakan-hukum-oktober-2026.pdf',
            'pdf_size' => '4.2 MB PDF',
            'order' => 3,
            'is_featured' => false,
            'status' => 'terbit',
        ]);

        // Seed 4: Survei Regional Jawa (Lembaga Eksternal: LSI)
        Survey::create([
            'title' => 'Aspirasi Warga Pulau Jawa terhadap Integrasi Transportasi Massal & Subsidi Tiket KRL',
            'slug' => 'aspirasi-warga-pulau-jawa-terhadap-transportasi-massal-dan-subsidi-tiket-krl',
            'institution' => 'Lembaga Survei Indonesia (LSI)',
            'is_isf' => false,
            'topic' => 'sosial',
            'region' => 'jawa',
            'survey_period' => '1 – 8 Oktober 2026',
            'release_date' => '2026-10-18',
            'sample_size' => 1000,
            'margin_of_error' => '±3.1%',
            'confidence_level' => '95%',
            'sampling_method' => 'Cluster Random Sampling di Aglomerasi Jabodetabek & Bandung Raya',
            'interview_method' => 'Wawancara Tatap Muka Terstruktur',
            'headline_stat_value' => '86.7%',
            'headline_stat_label' => 'Warga Komuter Menolak Wacana Skema Tarif KRL Berbasis Nomor Induk Kependudukan (NIK)',
            'headline_stat_comparison' => 'Mayoritas responden menghendaki tarif berbasis jarak terbuka tanpa segmentasi NIK',
            'summary' => 'Sebagian besar pengguna harian KRL Commuter Line menilai wacana pembedaan tarif berbasis NIK berpotensi menimbulkan ketidakadilan dan kerumitan birokrasi di pintu gerbang stasiun.',
            'expert_analysis' => 'Transportasi massal pada hakikatnya adalah barang publik (public goods) yang ditujukan untuk mengalihkan pengguna kendaraan pribadi ke transportasi umum. Diskriminasi tarif berisiko memicu migrasi balik ke sepeda motor yang berdampak buruk pada emisi dan kemacetan jalan tol.',
            'chart_data' => [
                'segments' => [
                    [
                        'id' => 'overall',
                        'name' => 'Sikap terhadap Skema Tarif KRL Berbasis NIK',
                        'type' => 'bar',
                        'items' => [
                            ['label' => 'Sangat Tidak Setuju', 'value' => 54.2, 'color' => '#EF4444'],
                            ['label' => 'Kurang Setuju', 'value' => 32.5, 'color' => '#F59E0B'],
                            ['label' => 'Setuju', 'value' => 9.8, 'color' => '#3B82F6'],
                            ['label' => 'Sangat Setuju', 'value' => 2.1, 'color' => '#10B981'],
                            ['label' => 'Tidak Tahu / TT', 'value' => 1.4, 'color' => '#6B7280'],
                        ],
                    ],
                ],
            ],
            'pdf_path' => '/storage/survei/lsi-transportasi-jawa-2026.pdf',
            'pdf_size' => '1.8 MB PDF',
            'order' => 4,
            'is_featured' => false,
            'status' => 'terbit',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('isf_surveys');
    }
};
