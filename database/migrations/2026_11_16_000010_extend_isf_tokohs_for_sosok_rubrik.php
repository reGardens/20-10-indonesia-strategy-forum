<?php

use App\Models\Isf\Tokoh;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('isf_tokohs', function (Blueprint $table) {
            $table->string('role_category', 50)->default('legislatif')->index()->after('position'); // eksekutif, legislatif, yudikatif, pakar, militer, pengusaha
            $table->json('issues')->nullable()->after('role_category'); // ['politik', 'hukum', 'ekonomi']
            $table->string('summary_analysis', 500)->nullable()->after('issues'); // Satu kalimat ringkasan analisis ISF
            $table->text('bio')->nullable()->after('summary_analysis'); // Biografi naratif
            $table->json('career_timeline')->nullable()->after('bio'); // [{ year, title, description, period }]
            $table->json('education')->nullable()->after('career_timeline'); // [{ degree, institution, year }]
            $table->json('positions_history')->nullable()->after('education'); // [{ period, position, institution }]
            $table->unsignedTinyInteger('influence_score')->nullable()->default(75)->after('positions_history'); // 0-100
            $table->json('affiliations')->nullable()->after('influence_score'); // [{ type, name, role }]
            $table->json('allies')->nullable()->after('affiliations'); // [{ name, role, relation }]
            $table->json('critics')->nullable()->after('allies'); // [{ name, role, note }]
            $table->json('quotes')->nullable()->after('critics'); // [{ text, context, date, source_url }]
            $table->json('controversies')->nullable()->after('quotes'); // [{ year, title, description, status }]
            $table->json('aliases')->nullable()->after('controversies'); // ['Budi Santoso', 'Dr. Budi Santoso']
            $table->boolean('is_influential_weekly')->default(false)->index()->after('is_featured');
        });

        // 1. Update or create budi-santoso with complete Sosok fields
        Tokoh::updateOrCreate(
            ['slug' => 'budi-santoso'],
            [
                'name' => 'Dr. Budi Santoso, S.H., M.H.',
                'position' => 'Ketua Komisi Hukum DPR RI',
                'photo_alt' => 'Foto profil Dr. Budi Santoso',
                'photo_url' => 'https://images.unsplash.com/photo-1560250097-0b93528c311a?q=80&w=800&auto=format&fit=crop',
                'stats' => [
                    ['label' => 'Kebijakan Diinisiasi', 'value' => 12],
                    ['label' => 'Rapat Paripurna', 'value' => 48],
                    ['label' => 'Keterlibatan Publik', 'value' => 85],
                    ['label' => 'Sorotan Regulasi', 'value' => 34],
                ],
                'skor_value' => 84,
                'skor_period' => 'Pekan 42 / 2026',
                'skor_explanation' => 'Tingkat kepemimpinan aktif dalam perumusan undang-undang strategis dan konsolidasi fraksi komisi.',
                'matrix_columns' => ['Inisiasi', 'Pembahasan', 'Implementasi'],
                'matrix_rows' => [
                    ['kebijakan' => 'RUU Perampasan Aset', 'tahap' => 'Pembahasan Tingkat I', 'involvement' => [true, true, false]],
                    ['kebijakan' => 'Revisi KUHAP', 'tahap' => 'Harmonisasi Baleg', 'involvement' => [true, false, false]],
                ],
                'related_articles' => [],
                'calendar_year' => 2026,
                'calendar_month' => 11,
                'calendar_events' => [
                    ['date' => '2026-11-18', 'title' => 'RDP Komisi III dengan Jaksa Agung'],
                    ['date' => '2026-11-20', 'title' => 'Rapat Konsultasi Pembahasan RUU Perampasan Aset'],
                ],
                'teaser' => 'Legislator kunci Komisi III DPR RI yang memimpin perumusan RUU Perampasan Aset dan reformasi peradilan.',
                'role_category' => 'legislatif',
                'issues' => ['hukum', 'politik'],
                'summary_analysis' => 'Figur kunci dalam perumusan RUU Perampasan Aset dan pengawasan reformasi peradilan nasional di Parlemen.',
                'bio' => 'Budi Santoso adalah politisi senior dan legislator berpengalaman yang menjabat di Komisi Hukum DPR RI. Dikenal vokal dalam mengawal harmonisasi regulasi pidana dan akuntabilitas aparat penegak hukum, ia kerap menjadi jembatan kompromi antar-fraksi koalisi maupun oposisi di Senayan.',
                'career_timeline' => [
                    ['year' => '2024 - Sekarang', 'title' => 'Anggota Komisi III DPR RI', 'description' => 'Memimpin panitia kerja pembahasan RUU Perampasan Aset Tindak Pidana dan revisi KUHAP.'],
                    ['year' => '2019 - 2024', 'title' => 'Wakil Ketua Badan Legislasi DPR RI', 'description' => 'Menyaring Prolegnas prioritas serta sinkronisasi perundang-undangan nasional.'],
                    ['year' => '2014 - 2019', 'title' => 'Anggota Badan Anggaran DPR RI', 'description' => 'Mengawal alokasi pembiayaan reformasi sistem peradilan terpadu.'],
                    ['year' => '2004 - 2014', 'title' => 'Praktisi Hukum & Dosen Tamu', 'description' => 'Konsultan hukum tata negara dan advokasi kebijakan publik.'],
                ],
                'education' => [
                    ['degree' => 'Doktor Ilmu Hukum (S3)', 'institution' => 'Universitas Indonesia', 'year' => '2012'],
                    ['degree' => 'Magister Hukum Bisnis (S2)', 'institution' => 'Universitas Gadjah Mada', 'year' => '2004'],
                    ['degree' => 'Sarjana Hukum (S1)', 'institution' => 'Universitas Diponegoro', 'year' => '1999'],
                ],
                'positions_history' => [
                    ['period' => '2024 - 2029', 'position' => 'Anggota Komisi III DPR RI', 'institution' => 'Dewan Perwakilan Rakyat RI'],
                    ['period' => '2019 - 2024', 'position' => 'Wakil Ketua Baleg', 'institution' => 'Dewan Perwakilan Rakyat RI'],
                    ['period' => '2015 - Sekarang', 'position' => 'Ketua DPP Bidang Hukum & HAM', 'institution' => 'Partai Pengusung'],
                ],
                'influence_score' => 84,
                'affiliations' => [
                    ['type' => 'Partai Politik', 'name' => 'Fraksi Partai Pengusung DPR RI', 'role' => 'Anggota Senior'],
                    ['type' => 'Organisasi Advokat', 'name' => 'Perhimpunan Advokat Indonesia', 'role' => 'Dewan Kehormatan'],
                    ['type' => 'Lembaga Kajian', 'name' => 'Pusat Studi Konstitusi & Tata Kelola', 'role' => 'Dewan Pembina'],
                ],
                'allies' => [
                    ['name' => 'Prof. Saldi Mahfud', 'role' => 'Pakar Hukum Tata Negara', 'relation' => 'Mitra diskusi akademik perumusan norma hukum'],
                    ['name' => 'Ahmad Fauzi', 'role' => 'Ketua Fraksi Parlemen', 'relation' => 'Sekutu strategis dalam lobi lintas fraksi Prolegnas'],
                ],
                'critics' => [
                    ['name' => 'Koalisi Masyarakat Sipil Anti Korupsi', 'role' => 'LSM Transparansi', 'note' => 'Mengkritisi lambatnya penuntasan pasal pembuktian terbalik pada RUU Aset'],
                ],
                'quotes' => [
                    ['text' => 'Hukum tidak boleh menjadi instrumen kekuasaan politik sesaat, melainkan pelindung keadilan warga.', 'context' => 'Sidang Paripurna DPR RI', 'date' => '2026-08-15', 'source_url' => 'https://example.test/quotes/1'],
                    ['text' => 'Tanpa instrumen perampasan aset yang tegas, perang melawan korupsi hanyalah retorika tanpa taring.', 'context' => 'Rapat Dengar Pendapat Komisi III', 'date' => '2026-10-14', 'source_url' => 'https://example.test/quotes/2'],
                ],
                'controversies' => [
                    ['year' => '2025', 'title' => 'Perdebatan Pasal Imunitas Pejabat Pengadaan', 'description' => 'Mendapat kritik publik saat mengajukan pasal perlindungan diskresi kepala daerah.', 'status' => 'Selesai Direvisi'],
                ],
                'aliases' => ['Budi Santoso', 'Dr. Budi Santoso', 'Komisi III DPR'],
                'is_influential_weekly' => true,
                'is_featured' => true,
                'show_in_politik' => true,
                'status' => 'terbit',
            ]
        );

        // 2. Seed Tokoh Eksekutif: Sri Hartati (Menteri Koordinator Perekonomian & Fiskal)
        Tokoh::create([
            'slug' => 'sri-hartati',
            'name' => 'Dr. Sri Hartati, Ph.D.',
            'position' => 'Menteri Koordinator Bidang Perekonomian & Keuangan',
            'role_category' => 'eksekutif',
            'issues' => ['ekonomi', 'kebijakan'],
            'summary_analysis' => 'Arsitek stabilitas makroekonomi dan jangkar kredibilitas fiskal Indonesia di tengah volatilitas pasar global.',
            'photo_url' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?q=80&w=800&auto=format&fit=crop',
            'photo_alt' => 'Foto profil Dr. Sri Hartati',
            'bio' => 'Dr. Sri Hartati merupakan ekonom internasional dan birokrat terkemuka yang memimpin portofolio kebijakan fiskal nasional. Berpengalaman di lembaga moneter multilateral, ia memfokuskan kepemimpinannya pada disiplin defisit APBN, efisiensi belanja subsidi, serta stimulus hilirisasi manufaktur hijau.',
            'career_timeline' => [
                ['year' => '2024 - Sekarang', 'title' => 'Menko Perekonomian & Keuangan', 'description' => 'Mengkoordinasikan sinkronisasi fiskal, perdagangan, dan investasi strategis.'],
                ['year' => '2019 - 2024', 'title' => 'Menteri Keuangan RI', 'description' => 'Menjaga rasio utang dan stabilitas penerimaan pajak nasional.'],
                ['year' => '2012 - 2018', 'title' => 'Direktur Eksekutif Bank Pembangunan Internasional', 'description' => 'Membidangi bantuan restrukturisasi ekonomi kawasan Asia-Pasifik.'],
            ],
            'education' => [
                ['degree' => 'Ph.D. in Economics', 'institution' => 'University of Illinois at Urbana-Champaign', 'year' => '2001'],
                ['degree' => 'Master of Science in Economics', 'institution' => 'University of Illinois', 'year' => '1997'],
                ['degree' => 'Sarjana Ekonomi', 'institution' => 'Universitas Indonesia', 'year' => '1992'],
            ],
            'positions_history' => [
                ['period' => '2024 - 2029', 'position' => 'Menko Perekonomian & Keuangan', 'institution' => 'Kementerian Koordinator Perekonomian'],
                ['period' => '2019 - 2024', 'position' => 'Menteri Keuangan', 'institution' => 'Kementerian Keuangan RI'],
            ],
            'influence_score' => 92,
            'affiliations' => [
                ['type' => 'Pemerintahan', 'name' => 'Kabinet Merah Putih', 'role' => 'Menteri Koordinator'],
                ['type' => 'Asosiasi Internasional', 'name' => 'G20 Finance Ministers Forum', 'role' => 'Chair Representative'],
            ],
            'allies' => [
                ['name' => 'Gubernur Bank Indonesia', 'role' => 'Otoritas Moneter', 'relation' => 'Sinergi bauran kebijakan moneter-fiskal terkoordinasi'],
            ],
            'critics' => [
                ['name' => 'Asosiasi Serikat Pekerja', 'role' => 'Buruh', 'note' => 'Menyoroti kenaikan PPN dan pembatasan subsidi energi'],
            ],
            'quotes' => [
                ['text' => 'Kredibilitas APBN adalah fondasi kedaulatan bangsa; kita tidak boleh mempertaruhkan masa depan generasi mendatang dengan defisit ugal-ugalan.', 'context' => 'Nota Keuangan DPR', 'date' => '2026-09-01', 'source_url' => 'https://example.test/quotes/sri-1'],
            ],
            'controversies' => [
                ['year' => '2025', 'title' => 'Kebijakan Rasionalisasi Anggaran Subsidi Energi', 'description' => 'Menimbulkan perdebatan di Parlemen terkait inflasi daya beli menengah.', 'status' => 'Disetujui APBN'],
            ],
            'aliases' => ['Sri Hartati', 'Menko Perekonomian', 'Menkeu Sri'],
            'stats' => [['label' => 'Pertumbuhan PDB', 'value' => '5.2%'], ['label' => 'Defisit APBN', 'value' => '2.4%']],
            'skor_value' => 92,
            'skor_period' => '30 hari terakhir',
            'skor_explanation' => 'Tingkat pengaruh kebijakan fiskal dan stabilitas nilai tukar nasional.',
            'matrix_columns' => ['Prioritas', 'Status', 'Dampak'],
            'matrix_rows' => [
                ['tahap' => 'Implementasi', 'kebijakan' => 'Harmonisasi Pajak Karbon & EBT', 'involvement' => [true, true, true]],
            ],
            'related_articles' => [],
            'calendar_year' => 2026,
            'calendar_month' => 11,
            'calendar_events' => [],
            'teaser' => 'Arsitek kebijakan fiskal nasional dan stabilitas moneter.',
            'is_featured' => false,
            'show_in_politik' => false,
            'status' => 'terbit',
        ]);

        // 3. Seed Tokoh Yudikatif: Prof. Mahardika (Ketua Mahkamah Konstitusi)
        Tokoh::create([
            'slug' => 'mahardika-wijaya',
            'name' => 'Prof. Dr. Mahardika Wijaya, S.H., M.H.',
            'position' => 'Ketua Mahkamah Konstitusi RI',
            'role_category' => 'yudikatif',
            'issues' => ['hukum', 'politik'],
            'summary_analysis' => 'Penjaga kemurnian konstitusi yang menavigasi uji materi undang-undang strategis di tengah dinamika elektoral.',
            'photo_url' => 'https://images.unsplash.com/photo-1560250097-0b93528c311a?q=80&w=800&auto=format&fit=crop',
            'photo_alt' => 'Foto profil Prof. Mahardika Wijaya',
            'bio' => 'Prof. Mahardika Wijaya adalah begawan hukum tata negara yang dipercaya memimpin Mahkamah Konstitusi Republik Indonesia. Di bawah kepemimpinannya, MK menegaskan transparansi sidang uji materiil, penguatan dissenting opinion yang akuntabel, dan independensi peradilan konstitusi dari pengaruh politik eksekutif.',
            'career_timeline' => [
                ['year' => '2023 - Sekarang', 'title' => 'Ketua Mahkamah Konstitusi RI', 'description' => 'Memimpin 9 Hakim Konstitusi dalam mengadili perselisihan norma konstitusi.'],
                ['year' => '2018 - 2023', 'title' => 'Hakim Konstitusi RI', 'description' => 'Konsisten melahirkan putusan progresif terkait hak asasi dan pengujian UU Pemilu.'],
                ['year' => '2005 - 2018', 'title' => 'Guru Besar Hukum Tata Negara', 'description' => 'Mengajar dan meneliti di Fakultas Hukum Universitas Terkemuka.'],
            ],
            'education' => [
                ['degree' => 'Doktor Ilmu Hukum', 'institution' => 'Universitas Indonesia', 'year' => '2003'],
                ['degree' => 'Magister Hukum', 'institution' => 'Universitas Padjadjaran', 'year' => '1996'],
                ['degree' => 'Sarjana Hukum', 'institution' => 'Universitas Airlangga', 'year' => '1991'],
            ],
            'positions_history' => [
                ['period' => '2023 - 2028', 'position' => 'Ketua Mahkamah Konstitusi', 'institution' => 'Mahkamah Konstitusi Republik Indonesia'],
            ],
            'influence_score' => 88,
            'affiliations' => [
                ['type' => 'Yudikatif', 'name' => 'Mahkamah Konstitusi RI', 'role' => 'Ketua'],
                ['type' => 'Akademik', 'name' => 'Asosiasi Pengajar Hukum Tata Negara', 'role' => 'Dewan Penasihat'],
            ],
            'allies' => [
                ['name' => 'Hakim Agung Kamar Tata Usaha Negara', 'role' => 'Mahkamah Agung', 'relation' => 'Sinergi pengujian norma hukum di bawah UU'],
            ],
            'critics' => [
                ['name' => 'Koalisi Pemerhati Peradilan', 'role' => 'Masyarakat Sipil', 'note' => 'Menuntut pengawasan etik hakim konstitusi yang lebih independen'],
            ],
            'quotes' => [
                ['text' => 'Keadilan konstitusional bukan sekadar teks kaku dalam pasal, melainkan denyut nurani keadilan seluruh rakyat.', 'context' => 'Sidang Pleno Putusan MK', 'date' => '2026-07-22', 'source_url' => 'https://example.test/quotes/mk-1'],
            ],
            'controversies' => [],
            'aliases' => ['Mahardika Wijaya', 'Ketua MK Mahardika', 'Prof. Mahardika'],
            'stats' => [['label' => 'Putusan Uji UU', 'value' => '84'], ['label' => 'Dissenting Opinion', 'value' => '12']],
            'skor_value' => 88,
            'skor_period' => '30 hari terakhir',
            'skor_explanation' => 'Tolak ukur integritas dan bobot pengaruh yudisial dalam tatanan kenegaraan.',
            'matrix_columns' => ['Perkara', 'Amar Putusan', 'Konsistensi'],
            'matrix_rows' => [
                ['tahap' => 'Final', 'kebijakan' => 'Uji Materiil UU Pemilu & Ambang Batas Parlemen', 'involvement' => [true, true, true]],
            ],
            'related_articles' => [],
            'calendar_year' => 2026,
            'calendar_month' => 11,
            'calendar_events' => [],
            'teaser' => 'Penjaga gawang konstitusi dan penegak independensi peradilan.',
            'is_featured' => false,
            'show_in_politik' => false,
            'status' => 'terbit',
        ]);

        // 4. Seed Tokoh Pakar: Dr. Radian Pratama (Ekonom Senior & Peneliti Kebijakan Publik)
        Tokoh::create([
            'slug' => 'radian-pratama',
            'name' => 'Dr. Radian Pratama, M.Ec.',
            'position' => 'Ekonom Senior & Direktur Eksekutif Institute for Economic Strategy',
            'role_category' => 'pakar',
            'issues' => ['ekonomi', 'kebijakan'],
            'summary_analysis' => 'Suara kritis independen yang secara konsisten menguji validitas data dan dampak sosial kebijakan pemerintah.',
            'photo_url' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=800&auto=format&fit=crop',
            'photo_alt' => 'Foto profil Dr. Radian Pratama',
            'bio' => 'Dr. Radian Pratama adalah analis ekonomi makro terkemuka yang sering menjadi narasumber rujukan media nasional dan forum global. Berbekal keahlian ekonometrika terapan, ia aktif mempublikasikan kajian independen mengenai deindustrialisasi dini, inflasi pangan, dan tata kelola utang negara.',
            'career_timeline' => [
                ['year' => '2017 - Sekarang', 'title' => 'Direktur Eksekutif Lembaga Riset Kebijakan', 'description' => 'Memimpin penerbitan Policy Brief berkala dan indeks sentimen pasar.'],
                ['year' => '2010 - 2017', 'title' => 'Lead Economist Bank Sentral Regional', 'description' => 'Penelitian transmisi moneter dan pembiayaan UMKM.'],
            ],
            'education' => [
                ['degree' => 'Ph.D. in Development Economics', 'institution' => 'Australian National University', 'year' => '2010'],
                ['degree' => 'Master of Economics', 'institution' => 'University of Melbourne', 'year' => '2005'],
                ['degree' => 'Sarjana Ekonomi', 'institution' => 'Universitas Gadjah Mada', 'year' => '2001'],
            ],
            'positions_history' => [
                ['period' => '2017 - Sekarang', 'position' => 'Direktur Eksekutif', 'institution' => 'Institute for Strategic Economics'],
            ],
            'influence_score' => 81,
            'affiliations' => [
                ['type' => 'Think Tank', 'name' => 'Asosiasi Ekonom Pembangunan Indonesia', 'role' => 'Sekretaris Dewan Riset'],
            ],
            'allies' => [
                ['name' => 'Forum Pemimpin Redaksi Bisnis', 'role' => 'Media Ekonomi', 'relation' => 'Penyedia data analitis independen'],
            ],
            'critics' => [
                ['name' => 'Juru Bicara Kementerian Investasi', 'role' => 'Pemerintah', 'note' => 'Menyanggah proyeksi perlambatan realisasi investasi luar pulau Jawa'],
            ],
            'quotes' => [
                ['text' => 'Angka kemiskinan tidak bisa ditutup dengan bansos musiman; yang kita butuhkan adalah penciptaan lapangan kerja berupah layak di sektor manufaktur.', 'context' => 'Diskusi Publik ISF', 'date' => '2026-10-02', 'source_url' => 'https://example.test/quotes/radian-1'],
            ],
            'controversies' => [],
            'aliases' => ['Radian Pratama', 'Dr. Radian', 'Ekonom Radian Pratama'],
            'stats' => [['label' => 'Riset Dipublikasi', 'value' => '42'], ['label' => 'Sitasi Media', 'value' => '318']],
            'skor_value' => 81,
            'skor_period' => '30 hari terakhir',
            'skor_explanation' => 'Intensitas kutipan analisis dan pengaruh argumen riset terhadap diskursus publik.',
            'matrix_columns' => ['Kajian', 'Rekomendasi', 'Tanggapan'],
            'matrix_rows' => [
                ['tahap' => 'Rilis Publik', 'kebijakan' => 'Analisis Rasionalitas APBN & Subsidi Pangan', 'involvement' => [true, true, true]],
            ],
            'related_articles' => [],
            'calendar_year' => 2026,
            'calendar_month' => 11,
            'calendar_events' => [],
            'teaser' => 'Pakar ekonomi kritis independen pengawal akurasi data fiskal.',
            'is_featured' => false,
            'show_in_politik' => false,
            'status' => 'terbit',
        ]);

        // 5. Seed Tokoh Militer: Letjen (Purn) Hendra Gunawan
        Tokoh::create([
            'slug' => 'hendra-gunawan',
            'name' => 'Letjen TNI (Purn.) Hendra Gunawan',
            'position' => 'Penasihat Khusus Keamanan Nasional & Pertahanan Geopolitik',
            'role_category' => 'militer',
            'issues' => ['pertahanan', 'politik'],
            'summary_analysis' => 'Ahli doktrin pertahanan maritim dan modernisasi alutsista dengan pandangan strategis di kawasan Indo-Pasifik.',
            'photo_url' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=800&auto=format&fit=crop',
            'photo_alt' => 'Foto profil Letjen Hendra Gunawan',
            'bio' => 'Letjen (Purn.) Hendra Gunawan mengabdikan lebih dari tiga dekade kariernya di institusi militer Indonesia sebelum mendedikasikan pemikirannya pada perumusan kebijakan pertahanan strategis. Dikenal berintegritas tinggi, ia vokal mendorong kemandirian industri pertahanan dalam negeri dan transparansi pengadaan teknologi tempur.',
            'career_timeline' => [
                ['year' => '2024 - Sekarang', 'title' => 'Penasihat Khusus Dewan Pertahanan Nasional', 'description' => 'Kajian geopolitik perbatasan Laut Natuna Utara dan modernisasi radar.'],
                ['year' => '2020 - 2023', 'title' => 'Panglima Komando Gabungan Wilayah', 'description' => 'Memimpin operasi kesiapsiagaan teritorial maritim.'],
                ['year' => '1990 - 2020', 'title' => 'Perwira Tempur & Staf Strategi Mabes TNI', 'description' => 'Pengabdian militer di berbagai medan tugas teritorial.'],
            ],
            'education' => [
                ['degree' => 'Master of Strategic Studies', 'institution' => 'S. Rajaratnam School of International Studies', 'year' => '2008'],
                ['degree' => 'Akademi Militer (Akmil)', 'institution' => 'Magelang', 'year' => '1989'],
            ],
            'positions_history' => [
                ['period' => '2024 - Sekarang', 'position' => 'Penasihat Keamanan Nasional', 'institution' => 'Dewan Ketahanan Nasional'],
            ],
            'influence_score' => 79,
            'affiliations' => [
                ['type' => 'Pertahanan', 'name' => 'Persatuan Purnawirawan Angkatan Darat', 'role' => 'Dewan Pakar'],
            ],
            'allies' => [
                ['name' => 'Komite Industri Pertahanan (KKIP)', 'role' => 'Regulator Industri', 'relation' => 'Mendorong transfer teknologi alutsista mandiri'],
            ],
            'critics' => [],
            'quotes' => [
                ['text' => 'Kedaulatan tidak dipertahankan dengan diplomasi kata semata, melainkan kesiapan deteren yang kredibel di perbatasan laut dan udara kita.', 'context' => 'Simposium Pertahanan Nasional', 'date' => '2026-06-18', 'source_url' => 'https://example.test/quotes/mil-1'],
            ],
            'controversies' => [],
            'aliases' => ['Hendra Gunawan', 'Letjen Hendra', 'Jenderal Hendra'],
            'stats' => [['label' => 'Operasi Teritorial', 'value' => '18'], ['label' => 'Doktrin Alutsista', 'value' => '6']],
            'skor_value' => 79,
            'skor_period' => '30 hari terakhir',
            'skor_explanation' => 'Rujukan strategis keamanan regional dan doktrin pertahanan negara.',
            'matrix_columns' => ['Sektor', 'Inisiatif', 'Urgensi'],
            'matrix_rows' => [
                ['tahap' => 'Rencana Induk', 'kebijakan' => 'Penguatan Komando Gabungan Maritim Natuna', 'involvement' => [true, true, true]],
            ],
            'related_articles' => [],
            'calendar_year' => 2026,
            'calendar_month' => 11,
            'calendar_events' => [],
            'teaser' => 'Pakar doktrin pertahanan maritim dan modernisasi alutsista nasional.',
            'is_featured' => false,
            'show_in_politik' => false,
            'status' => 'terbit',
        ]);
    }

    public function down(): void
    {
        Schema::table('isf_tokohs', function (Blueprint $table) {
            $table->dropIndex(['role_category']);
            $table->dropIndex(['is_influential_weekly']);
            $table->dropColumn([
                'role_category',
                'issues',
                'summary_analysis',
                'bio',
                'career_timeline',
                'education',
                'positions_history',
                'influence_score',
                'affiliations',
                'allies',
                'critics',
                'quotes',
                'controversies',
                'aliases',
                'is_influential_weekly',
            ]);
        });
    }
};

