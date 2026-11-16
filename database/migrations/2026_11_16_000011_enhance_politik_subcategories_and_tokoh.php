<?php

use App\Models\Isf\Article;
use App\Models\Isf\Tokoh;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Assign subcategories to existing politik articles
        Article::where('slug', 'komisi-ii-buka-konsultasi-publik-revisi-uu-pemilu')
            ->update(['subcategory' => 'pemilu']);

        Article::where('slug', 'peta-jalan-legislasi-prioritas-dpr')
            ->update(['subcategory' => 'parlemen']);

        Article::where('slug', 'fraksi-dpr-sepakati-jadwal-pembahasan-rapbn-2027')
            ->update(['subcategory' => 'parlemen']);

        Article::where('slug', 'koalisi-partai-bahas-peta-jalan-legislasi-akhir-tahun')
            ->update(['subcategory' => 'partai']);

        Article::where('slug', 'survei-kepercayaan-publik-terhadap-parlemen-naik')
            ->update(['subcategory' => 'pemilu']);

        // Pastikan ada artikel politik untuk subkategori pemerintahan
        Article::updateOrCreate(
            ['slug' => 'konsolidasi-kebijakan-kementerian-kabinet-pemerintahan-baru'],
            [
                'title' => 'Konsolidasi Kebijakan Kementerian di Bawah Kabinet Pemerintahan Baru',
                'category' => 'politik',
                'subcategory' => 'pemerintahan',
                'published_at' => '2026-11-15',
                'summary' => 'Sinkronisasi program prioritas lintas kementerian dan lembaga eksekutif untuk mempercepat implementasi agenda reformasi birokrasi nasional.',
                'body' => 'Pemerintah mempercepat konsolidasi kebijakan antar kementerian guna menyelaraskan target pembangunan nasional dan efisiensi regulasi sektoral.',
                'author_name' => 'Tim Riset Kebijakan ISF',
                'reading_minutes' => 4,
                'image_url' => 'https://images.unsplash.com/photo-1541872703-74c5e44368f9?q=80&w=800&auto=format&fit=crop',
                'image_alt' => 'Gedung Kementerian dan Lembaga Eksekutif',
                'status' => 'terbit',
            ]
        );

        // 2. Set show_in_politik = true for key political figures in CMS
        Tokoh::whereIn('slug', [
            'budi-santoso',
            'sri-hartati',
            'mahardika-wijaya',
            'hendra-gunawan',
        ])->update(['show_in_politik' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Article::where('slug', 'konsolidasi-kebijakan-kementerian-kabinet-pemerintahan-baru')->delete();
        Tokoh::whereIn('slug', ['sri-hartati', 'mahardika-wijaya', 'hendra-gunawan'])
            ->update(['show_in_politik' => false]);
    }
};

