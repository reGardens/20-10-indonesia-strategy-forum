<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kategori konten disamakan dengan section Beranda / menu navbar (3 kategori):
 *   ruu     → hukum      (Hukum & Regulasi)
 *   ekonomi → kebijakan  (Kebijakan Publik & Fiskal)
 *
 * Konten berkategori ruu/ekonomi dipindahkan, lalu kategori ruu & ekonomi DIHAPUS dari isf_categories.
 * Nama kategori disamakan dengan menu navbar: Politik, Hukum, Kebijakan Publik.
 *
 * Catatan rollback: down() membuat ulang kategori ruu & ekonomi dan mengembalikan nama lama,
 * tetapi kategori asli tiap artikel/infografik tidak dapat dikembalikan otomatis (tidak disimpan).
 */
return new class extends Migration
{
    private const MERGE = [
        'ruu' => 'hukum',
        'ekonomi' => 'kebijakan',
    ];

    /** slug => [nama baru, deskripsi baru, nama lama, deskripsi lama] */
    private const RENAME = [
        'politik' => [
            'Politik',
            'Dinamika politik nasional, parlemen, partai, pemilu, dan lembaga negara.',
            'Politik',
            'Isu dinamika politik nasional, pemilu, dan lembaga negara.',
        ],
        'hukum' => [
            'Hukum',
            'Analisis yuridis, RUU, putusan peradilan, penegakan hukum, dan kepatuhan regulasi.',
            'Hukum',
            'Analisis putusan peradilan, penegakan hukum, dan kepatuhan hukum korporasi.',
        ],
        'kebijakan' => [
            'Kebijakan Publik',
            'Kebijakan publik, ekonomi, fiskal, moneter, dan regulasi sektoral.',
            'Kebijakan',
            'Perumusan dan implementasi kebijakan publik serta regulasi sektoral.',
        ],
    ];

    /** Baris kategori lama untuk down(). */
    private const REMOVED = [
        'ekonomi' => ['Ekonomi', 'Makroekonomi, moneter, fiskal, dan perdagangan.', '#2563eb', 2],
        'ruu' => ['RUU', 'Rancangan Undang-Undang dalam pembahasan di legislatif dan pemerintah.', '#ea580c', 5],
    ];

    public function up(): void
    {
        foreach (['isf_articles', 'isf_infographics', 'isf_isus'] as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'category')) {
                continue;
            }
            foreach (self::MERGE as $from => $to) {
                DB::table($table)->where('category', $from)->update(['category' => $to]);
            }
        }

        if (!Schema::hasTable('isf_categories')) {
            return;
        }

        DB::table('isf_categories')->whereIn('slug', array_keys(self::MERGE))->delete();

        foreach (self::RENAME as $slug => [$name, $description]) {
            DB::table('isf_categories')->where('slug', $slug)->update([
                'name' => $name,
                'description' => $description,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('isf_categories')) {
            return;
        }

        foreach (self::RENAME as $slug => [, , $oldName, $oldDescription]) {
            DB::table('isf_categories')->where('slug', $slug)->update([
                'name' => $oldName,
                'description' => $oldDescription,
                'updated_at' => now(),
            ]);
        }

        foreach (self::REMOVED as $slug => [$name, $description, $color, $order]) {
            if (DB::table('isf_categories')->where('slug', $slug)->exists()) {
                continue;
            }
            DB::table('isf_categories')->insert([
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'color' => $color,
                'order' => $order,
                'status' => 'terbit',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
