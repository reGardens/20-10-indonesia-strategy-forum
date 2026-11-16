<?php

use App\Models\Isf\PublicQuote;
use App\Models\Isf\Tokoh;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('isf_public_quotes', function (Blueprint $table) {
            $table->id();
            $table->text('quote');
            $table->string('author_name', 255);
            $table->string('author_title', 255)->nullable();
            $table->enum('author_type', ['tokoh', 'netizen'])->default('tokoh')->index();
            $table->foreignId('tokoh_id')->nullable()->constrained('isf_tokohs')->nullOnDelete();
            $table->string('topic', 255)->nullable();
            $table->string('source_title', 255)->nullable();
            $table->string('source_url', 500)->nullable();
            $table->date('quote_date')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->enum('status', ['terbit', 'draft'])->default('terbit')->index();
            $table->timestamps();
        });

        $tokohFeatured = Tokoh::query()->first();

        // Seed sample public quotes
        PublicQuote::create([
            'quote' => 'Hukum tidak boleh dijadikan instrumen pembenaran kekuasaan, melainkan benteng perlindungan hak-hak asasi dan keadilan bagi setiap warga negara tanpa kecuali.',
            'author_name' => $tokohFeatured ? $tokohFeatured->name : 'Budi Santoso',
            'author_title' => 'Ketua Komisi Pengawas Reformasi Hukum',
            'author_type' => 'tokoh',
            'tokoh_id' => $tokohFeatured ? $tokohFeatured->id : null,
            'topic' => 'Integritas Peradilan & Reformasi Hukum',
            'source_title' => 'Pidato Kunci Konferensi Hukum Nasional 2026',
            'source_url' => 'https://hukum.nasional.go.id',
            'quote_date' => now()->subDays(3)->toDateString(),
            'order' => 1,
            'status' => 'terbit',
        ]);

        PublicQuote::create([
            'quote' => 'Transisi energi bersih akan kehilangan legitimasi moralnya jika beban biaya paling berat justru dilimpahkan kepada masyarakat ekonomi rentan.',
            'author_name' => 'Dr. Bivitri Susanti',
            'author_title' => 'Pakar Hukum Tata Negara & Kebijakan Publik',
            'author_type' => 'tokoh',
            'topic' => 'Transisi Energi & Keadilan Iklim',
            'source_title' => 'Wawancara Eksklusif ISF Dialektika',
            'source_url' => 'https://strategyforum.id',
            'quote_date' => now()->subDays(5)->toDateString(),
            'order' => 2,
            'status' => 'terbit',
        ]);

        PublicQuote::create([
            'quote' => 'Kalau tarif integrasi antarmoda transportasi diperluas sampai Bogor, Depok, dan Bekasi, jutaan motor per hari bakal berkurang drastis dari jalanan protokol.',
            'author_name' => 'Dimas Prasetyo (@dimas_komuter)',
            'author_title' => 'Komunitas Pejalan Kaki & Pengguna KRL',
            'author_type' => 'netizen',
            'topic' => 'Subsidi Transportasi Publik & Polusi Udara',
            'source_title' => 'Diskusi Publik Media Sosial X',
            'source_url' => 'https://x.com',
            'quote_date' => now()->subDays(2)->toDateString(),
            'order' => 3,
            'status' => 'terbit',
        ]);

        PublicQuote::create([
            'quote' => 'Anggaran pendidikan 20% dari APBN harus betul-betul dirasakan sampai ke guru honorer di pelosok dan laboratorium riset kampus, bukan menguap dalam seremonial birokrasi.',
            'author_name' => 'Aulia Rahma (@auliar_edu)',
            'author_title' => 'Pemerhati Pendidikan Inklusif',
            'author_type' => 'netizen',
            'topic' => 'Efektivitas Alokasi Anggaran Pendidikan',
            'source_title' => 'Komentar Terpilih Forum Kebijakan Terbuka',
            'source_url' => 'https://forum.strategyforum.id',
            'quote_date' => now()->subDays(6)->toDateString(),
            'order' => 4,
            'status' => 'terbit',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('isf_public_quotes');
    }
};

