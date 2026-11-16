<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Isf\Article;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menata subkategori artikel hukum agar terdistribusi ke:
     * - regulasi: Regulasi & Perundang-undangan
     * - putusan: Putusan Peradilan
     * - kasus: Kasus Hukum
     */
    public function up(): void
    {
        // 1. Petakan artikel hukum yang sudah ada
        DB::table('isf_articles')
            ->where('title', 'like', '%KUHP%')
            ->whereIn('category', ['hukum', 'ruu'])
            ->update(['subcategory' => 'regulasi', 'category' => 'hukum']);

        DB::table('isf_articles')
            ->where('title', 'like', '%RUU Perampasan Aset%')
            ->whereIn('category', ['hukum', 'ruu'])
            ->update(['subcategory' => 'kasus', 'category' => 'hukum']);

        DB::table('isf_articles')
            ->where('id', 14)
            ->update(['subcategory' => 'putusan', 'category' => 'hukum']);

        // 2. Tambahkan artikel bermutu untuk subkategori Putusan jika belum ada artikel putusan spesifik
        $putusanCount = DB::table('isf_articles')
            ->where('category', 'hukum')
            ->where('subcategory', 'putusan')
            ->where('title', '!=', 'test rbp')
            ->count();

        if ($putusanCount === 0) {
            Article::create([
                'title' => 'Analisis Putusan Mahkamah Konstitusi Terkait Batas Wewenang Penyelidikan Yuridis',
                'slug' => 'analisis-putusan-mahkamah-konstitusi-batas-wewenang-penyelidikan',
                'category' => 'hukum',
                'subcategory' => 'putusan',
                'summary' => 'Telaah implikasi pertimbangan hukum putusan MK terhadap kepastian hukum dan tata kelola peradilan pidana nasional.',
                'body' => '<p>Mahkamah Konstitusi menegaskan kembali batasan kewenangan lembaga penegak hukum dalam proses penyelidikan awal untuk menjaga asas perlindungan hak asasi manusia serta kepastian hukum prosedural di Indonesia.</p>',
                'author_name' => 'Dr. Hendra Wijaya, S.H., M.H.',
                'author_role' => 'Peneliti Senior Hukum Tata Negara',
                'reading_minutes' => 6,
                'status' => 'terbit',
                'published_at' => now()->subHours(8),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('isf_articles')
            ->where('slug', 'analisis-putusan-mahkamah-konstitusi-batas-wewenang-penyelidikan')
            ->delete();
    }
};

