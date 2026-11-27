<?php

use App\Models\Isf\Survey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('isf_surveys', function (Blueprint $table) {
            if (!Schema::hasColumn('isf_surveys', 'editorial_intro')) {
                $table->text('editorial_intro')->nullable()->after('headline_stat_comparison');
            }
            if (!Schema::hasColumn('isf_surveys', 'estimated_minutes')) {
                $table->unsignedSmallInteger('estimated_minutes')->default(5)->after('editorial_intro');
            }
            if (!Schema::hasColumn('isf_surveys', 'questions')) {
                $table->json('questions')->nullable()->after('estimated_minutes');
            }
            if (!Schema::hasColumn('isf_surveys', 'is_member_active')) {
                $table->boolean('is_member_active')->default(true)->index()->after('is_featured');
            }
        });

        if (!Schema::hasTable('isf_survey_responses')) {
            Schema::create('isf_survey_responses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('survey_id')->constrained('isf_surveys')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('status', 20)->default('draft')->index(); // draft | completed
                $table->json('answers')->nullable();
                $table->json('demographics')->nullable();
                $table->unsignedSmallInteger('current_step')->default(1);
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                // Satu akun hanya dapat memberikan satu suara (Pencegahan Double-Voting)
                $table->unique(['survey_id', 'user_id'], 'survey_user_unique');
            });
        }

        // Seed default sample questions and intro for active ISF member survey
        $featuredSurvey = Survey::where('is_isf', true)->first();
        if ($featuredSurvey) {
            $questions = [
                [
                    'id' => 'q1',
                    'type' => 'scale',
                    'question' => 'Seberapa setuju Anda bahwa kesinambungan kepemimpinan nasional (termasuk proyeksi kelanjutan peran Gibran Rakabuming Raka menjelang 2029) krusial bagi kepastian stabilitas agenda pembangunan strategis?',
                    'context' => 'Berdasarkan rilis survei opini publik nasional ISF sebelumnya, lebih dari 62% kalangan dunia usaha dan analis menekankan pentingnya kepastian kebijakan jangka panjang. Namun isu estafet kepemimpinan alternatif tetap menjadi perhatian publik. Bagaimana pandangan kritis Anda?',
                    'required' => true,
                    'scale_min' => 1,
                    'scale_max' => 10,
                    'scale_min_label' => 'Sangat Tidak Setuju',
                    'scale_max_label' => 'Sangat Setuju',
                ],
                [
                    'id' => 'q2',
                    'type' => 'radio',
                    'question' => 'Pilar kebijakan prioritas manakah yang menurut Anda paling mendesak diakselerasi dalam 2 tahun ke depan?',
                    'context' => 'Pilihlah salah satu sektor yang paling mendasar untuk ketahanan ekonomi politik nasional.',
                    'required' => true,
                    'options' => [
                        'Ketahanan Pangan, Air, dan Kemandirian Energi Terbarukan',
                        'Hilirisasi Komoditas & Reindustrialisasi Bernilai Tambah Tinggi',
                        'Reformasi Penegakan Hukum, Pemberantasan Korupsi, & Kepastian Investasi',
                        'Pendidikan Vokasi Terintegrasi & Perluasan Lapangan Kerja Formal Berkualitas',
                        'Pemerataan Infrastruktur Regional & Efisiensi Konektivitas Logistik Nasional',
                    ],
                ],
                [
                    'id' => 'q3',
                    'type' => 'checkbox',
                    'question' => 'Tantangan struktural apa saja yang berpotensi menjadi risiko kritis bagi stabilitas kebijakan nasional? (Boleh pilih lebih dari satu)',
                    'context' => 'Identifikasi faktor-faktor yang membutuhkan intervensi mitigasi darurat dari pembuat kebijakan.',
                    'required' => true,
                    'options' => [
                        'Peningkatan beban pajak & penurunan daya beli kelas menengah perkotaan',
                        'Disrupsi digitalisasi, otomatisasi, dan adopsi AI terhadap tenaga kerja formal',
                        'Efektivitas pengawasan akuntabilitas belanja APBN dan stimulus bantuan sosial',
                        'Fragmentasi koordinasi kelembagaan di tingkat kementerian dan lembaga',
                        'Polarisasi diskursus publik di media sosial menjelang dinamika politik 2029',
                    ],
                ],
                [
                    'id' => 'q4',
                    'type' => 'text',
                    'question' => 'Catatan strategis atau rekomendasi kebijakan apa yang ingin Anda sampaikan langsung kepada Dewan Pakar ISF?',
                    'context' => 'Masukan naratif Anda akan dikurasi dan disintesis ke dalam Catatan Kebijakan (Policy Brief) independen ISF.',
                    'placeholder' => 'Tuliskan pandangan, analisis kritis, atau saran konkret Anda di sini...',
                    'required' => false,
                ],
            ];

            $featuredSurvey->update([
                'editorial_intro' => 'Berdasarkan hasil pemantauan ISF Research, dinamika kebijakan publik pasca-konsolidasi kabinet menuntut partisipasi berbasis keahlian dari para profesional dan akademisi. Melalui survei panel khusus anggota ini, suara dan telaah Anda menjadi rujukan penting perumusan rekomendasi independen ISF.',
                'estimated_minutes' => 5,
                'questions' => $questions,
                'is_member_active' => true,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('isf_survey_responses');

        Schema::table('isf_surveys', function (Blueprint $table) {
            $table->dropColumn(['editorial_intro', 'estimated_minutes', 'questions', 'is_member_active']);
        });
    }
};
