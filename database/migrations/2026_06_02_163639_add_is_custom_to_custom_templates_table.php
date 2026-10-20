<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('custom_templates', function (Blueprint $table) {
            $table->boolean('is_custom')->default(true)->after('thumbnail_url');
        });

        // Insert code-based templates (developed templates)
        DB::table('custom_templates')->insert([
            [
                'slug' => 'bugis-makassar',
                'name' => 'Bugis Makassar',
                'description' => 'Nuansa biru laut dan emas Sulawesi',
                'thumbnail_url' => '/templates/bugis-makassar/thumbnail.webp',
                'is_custom' => false,
                'config' => json_encode([
                    'primary_color' => '#1B3A5C',
                    'secondary_color' => '#2E6B9E',
                    'accent_color' => '#C9A84C',
                    'font_family' => 'Cormorant Garamond',
                    'animation_style' => 'fade-up',
                    'bg_pattern_url' => '',
                    'custom_css' => ''
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'dayak-borneo',
                'name' => 'Dayak Borneo',
                'description' => 'Motif etnik Dayak dengan merah dan hitam',
                'thumbnail_url' => '/templates/dayak-borneo/thumbnail.webp',
                'is_custom' => false,
                'config' => json_encode([
                    'primary_color' => '#8B0000',
                    'secondary_color' => '#2C1810',
                    'accent_color' => '#DAA520',
                    'font_family' => 'Montserrat',
                    'animation_style' => 'fade-right',
                    'bg_pattern_url' => '',
                    'custom_css' => ''
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'toraja-sulawesi',
                'name' => 'Toraja Sulawesi',
                'description' => 'Motif ukiran Toraja merah dan hitam',
                'thumbnail_url' => '/templates/toraja-sulawesi/thumbnail.webp',
                'is_custom' => false,
                'config' => json_encode([
                    'primary_color' => '#4A1C1C',
                    'secondary_color' => '#8B4513',
                    'accent_color' => '#CD853F',
                    'font_family' => 'Playfair Display',
                    'animation_style' => 'fade-up',
                    'bg_pattern_url' => '',
                    'custom_css' => ''
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'melayu-riau',
                'name' => 'Melayu Riau',
                'description' => 'Nuansa kuning kerajaan Melayu',
                'thumbnail_url' => '/templates/melayu-riau/thumbnail.webp',
                'is_custom' => false,
                'config' => json_encode([
                    'primary_color' => '#B8860B',
                    'secondary_color' => '#8B6914',
                    'accent_color' => '#FFFACD',
                    'font_family' => 'Lora',
                    'animation_style' => 'fade-up',
                    'bg_pattern_url' => '',
                    'custom_css' => ''
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'papua-cendrawasih',
                'name' => 'Papua Cendrawasih',
                'description' => 'Motif tifa dan cendrawasih, warna tanah dan merah',
                'thumbnail_url' => '/templates/papua-cendrawasih/thumbnail.webp',
                'is_custom' => false,
                'config' => json_encode([
                    'primary_color' => '#5C3317',
                    'secondary_color' => '#8B4513',
                    'accent_color' => '#CD5C5C',
                    'font_family' => 'Montserrat',
                    'animation_style' => 'zoom-in',
                    'bg_pattern_url' => '',
                    'custom_css' => ''
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('custom_templates')->whereIn('slug', [
            'bugis-makassar',
            'dayak-borneo',
            'toraja-sulawesi',
            'melayu-riau',
            'papua-cendrawasih'
        ])->delete();

        Schema::table('custom_templates', function (Blueprint $table) {
            $table->dropColumn('is_custom');
        });
    }
};
