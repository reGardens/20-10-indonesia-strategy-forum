<?php

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
        // 1. Add author_avatar (1:1 photo) to isf_articles
        if (Schema::hasTable('isf_articles') && ! Schema::hasColumn('isf_articles', 'author_avatar')) {
            Schema::table('isf_articles', function (Blueprint $table) {
                $table->string('author_avatar', 500)->nullable()->after('author_role');
            });
        }

        // 2. Add full article fields to isf_letters (Artikel by Member)
        if (Schema::hasTable('isf_letters')) {
            Schema::table('isf_letters', function (Blueprint $table) {
                if (! Schema::hasColumn('isf_letters', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('isf_letters', 'category')) {
                    $table->string('category', 50)->nullable()->after('title');
                }
                if (! Schema::hasColumn('isf_letters', 'subcategory')) {
                    $table->string('subcategory', 100)->nullable()->after('category');
                }
                if (! Schema::hasColumn('isf_letters', 'summary')) {
                    $table->text('summary')->nullable()->after('subcategory');
                }
                if (! Schema::hasColumn('isf_letters', 'author_role')) {
                    $table->string('author_role', 150)->nullable()->after('sender_city');
                }
                if (! Schema::hasColumn('isf_letters', 'author_avatar')) {
                    $table->string('author_avatar', 500)->nullable()->after('author_role');
                }
                if (! Schema::hasColumn('isf_letters', 'reading_minutes')) {
                    $table->unsignedSmallInteger('reading_minutes')->nullable()->default(5)->after('author_avatar');
                }
                if (! Schema::hasColumn('isf_letters', 'image_url')) {
                    $table->string('image_url', 500)->nullable()->after('reading_minutes');
                }
                if (! Schema::hasColumn('isf_letters', 'image_alt')) {
                    $table->string('image_alt', 255)->nullable()->after('image_url');
                }
                if (! Schema::hasColumn('isf_letters', 'link_url')) {
                    $table->string('link_url', 500)->nullable()->after('image_alt');
                }
                if (! Schema::hasColumn('isf_letters', 'fact_blocks')) {
                    $table->json('fact_blocks')->nullable()->after('link_url');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('isf_articles') && Schema::hasColumn('isf_articles', 'author_avatar')) {
            Schema::table('isf_articles', function (Blueprint $table) {
                $table->dropColumn('author_avatar');
            });
        }

        if (Schema::hasTable('isf_letters')) {
            Schema::table('isf_letters', function (Blueprint $table) {
                $columns = [
                    'user_id', 'category', 'subcategory', 'summary',
                    'author_role', 'author_avatar', 'reading_minutes',
                    'image_url', 'image_alt', 'link_url', 'fact_blocks',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('isf_letters', $column)) {
                        if ($column === 'user_id') {
                            $table->dropForeign(['user_id']);
                        }
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};

