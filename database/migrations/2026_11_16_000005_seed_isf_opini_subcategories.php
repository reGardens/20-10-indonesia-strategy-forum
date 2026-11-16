<?php

use App\Models\Isf\Article;
use App\Models\Isf\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Kategori Induk: Opini Publik (slug: opini)
        $parent = Category::firstOrCreate(
            ['slug' => 'opini'],
            [
                'name' => 'Opini Publik',
                'description' => 'Wadah pemikiran kritis, pandangan resmi redaksi, dan suara kontribusi publik.',
                'order' => 4,
                'status' => 'terbit',
                'color' => '#8B5CF6',
            ]
        );

        // 2. Tiga Sub-Kategori Sektoral
        $subcategories = [
            [
                'name' => 'Op-Ed & Analisis Pakar',
                'slug' => 'op-ed',
                'description' => 'Tulisan gagasan dan analisis mendalam dari akademisi, intelektual, dan pakar independen.',
                'order' => 1,
            ],
            [
                'name' => 'Editorial ISF',
                'slug' => 'editorial',
                'description' => 'Sikap, refleksi, dan pandangan resmi Dewan Redaksi Indonesia Strategy Forum terhadap isu strategis bangsa.',
                'order' => 2,
            ],
            [
                'name' => 'Surat Pembaca',
                'slug' => 'surat-pembaca',
                'description' => 'Kontribusi pemikiran, catatan kritis, tanggapan, dan aspirasi masyarakat dari berbagai penjuru tanah air.',
                'order' => 3,
            ],
        ];

        foreach ($subcategories as $sub) {
            Category::firstOrCreate(
                ['slug' => $sub['slug']],
                [
                    'name' => $sub['name'],
                    'description' => $sub['description'],
                    'parent_id' => $parent->id,
                    'order' => $sub['order'],
                    'status' => 'terbit',
                    'color' => '#8B5CF6',
                ]
            );
        }

        // 3. Seed contoh artikel Op-Ed dan Editorial ISF
        $articles = [
            [
                'title' => 'Menjaga Marwah Etika Publik dalam Transisi Demokrasi Elektoral 2029',
                'slug' => 'menjaga-marwah-etika-publik-transisi-demokrasi-2029',
                'category' => 'opini',
                'subcategory' => 'editorial',
                'published_at' => now()->subHours(5),
                'summary' => 'Demokrasi substantif tidak sekadar angka perolehan suara di bilik suara, melainkan kesepakatan etis menjaga keadilan institusi hukum dan kepercayaan masyarakat sipil.',
                'body' => '<p>Indonesia berada di simpang jalan strategis. Kematangan demokrasi tidak hanya diukur dari suksesi kekuasaan yang damai, melainkan dari sejauh mana lembaga-lembaga pengawas, peradilan konstitusi, dan birokrasi profesional dapat beroperasi secara independen tanpa kooptasi kepentingan partisan.</p><p>Dewan Redaksi Indonesia Strategy Forum menekankan pentingnya komitmen nyata seluruh pemangku kepentingan untuk menempatkan etika bernegara di atas kompromi politik jangka pendek.</p>',
                'author_name' => 'Dewan Redaksi ISF',
                'author_role' => 'Editorial Resmi ISF',
                'reading_minutes' => 5,
                'image_url' => 'https://images.unsplash.com/photo-1541872703-74c5e44368f9?auto=format&fit=crop&w=1200&q=80',
                'image_alt' => 'Pilar gedung institusi publik representasi demokrasi',
                'status' => 'terbit',
            ],
            [
                'title' => 'Mengurai Jebakan Pendapatan Menengah: Rekonfigurasi Modal Manusia & Industrialisasi',
                'slug' => 'mengurai-jebakan-pendapatan-menengah-modal-manusia',
                'category' => 'opini',
                'subcategory' => 'op-ed',
                'published_at' => now()->subDay(),
                'summary' => 'Kunci meloloskan diri dari Middle Income Trap bukan sekadar pembangunan infrastruktur fisik, melainkan lonjakan drastis pada kualitas riset, inovasi vokasi, dan reformasi pasar tenaga kerja.',
                'body' => '<p>Banyak negara berkembang terperangkap dalam jebakan pendapatan menengah selama berdekade-dekade. Tanpa perbaikan mendasar pada ekosistem sains dan teknologi nasional, bonus demografi Indonesia berisiko berubah menjadi beban sosial.</p><p>Pemerintah perlu menyelaraskan kurikulum pendidikan tinggi dengan tuntutan rantai pasok manufaktur berteknologi tinggi.</p>',
                'author_name' => 'Prof. Emil Salim & Dr. Faisal Basri',
                'author_role' => 'Guru Besar Ekonomi & Peneliti Senior',
                'reading_minutes' => 6,
                'image_url' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=80',
                'image_alt' => 'Gedung modern pusat riset dan inovasi ekonomi',
                'status' => 'terbit',
            ],
            [
                'title' => 'Catatan Kritis Warga: Aksesibilitas Transportasi Publik Antarmoda di Kawasan Aglomerasi',
                'slug' => 'catatan-kritis-warga-aksesibilitas-transportasi-publik-aglomerasi',
                'category' => 'opini',
                'subcategory' => 'surat-pembaca',
                'published_at' => now()->subDays(2),
                'summary' => 'Integrasi tiket tunggal dan feeder transportasi di kawasan suburban mendesak diperluas demi mengurangi polusi udara dan kemacetan harian.',
                'body' => '<p>Sebagai komuter harian dari wilayah penyangga ibu kota, kami mengapresiasi modernisasi KRL dan MRT. Namun celah terbesar saat ini ada pada layanan feeder first-mile dan last-mile dari permukiman warga menuju stasiun utama.</p><p>Kami berharap pemerintah daerah dan pusat memperkuat subsidi integrasi tarif agar masyarakat beralih sepenuhnya ke transportasi massal.</p>',
                'author_name' => 'Bambang Sudibyo',
                'author_role' => 'Pengguna Transportasi Publik & Warga Tangerang',
                'reading_minutes' => 4,
                'image_url' => 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=1200&q=80',
                'image_alt' => 'Suasana integrasi transportasi publik kereta perkotaan',
                'status' => 'terbit',
            ],
        ];

        foreach ($articles as $art) {
            Article::firstOrCreate(
                ['slug' => $art['slug']],
                $art
            );
        }
    }

    public function down(): void
    {
        Article::whereIn('slug', [
            'menjaga-marwah-etika-publik-transisi-demokrasi-2029',
            'mengurai-jebakan-pendapatan-menengah-modal-manusia',
            'catatan-kritis-warga-aksesibilitas-transportasi-publik-aglomerasi',
        ])->delete();

        Category::whereIn('slug', ['op-ed', 'editorial', 'surat-pembaca'])->delete();
        Category::where('slug', 'opini')->delete();
    }
};

