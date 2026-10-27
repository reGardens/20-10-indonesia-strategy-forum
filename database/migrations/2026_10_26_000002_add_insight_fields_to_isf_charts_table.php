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
        Schema::table('isf_charts', function (Blueprint $table) {
            if (!Schema::hasColumn('isf_charts', 'author_name')) {
                $table->string('author_name')->nullable()->after('description');
            }
            if (!Schema::hasColumn('isf_charts', 'author_role')) {
                $table->string('author_role')->nullable()->after('author_name');
            }
            if (!Schema::hasColumn('isf_charts', 'author_avatar')) {
                $table->string('author_avatar', 1000)->nullable()->after('author_role');
            }
            if (!Schema::hasColumn('isf_charts', 'read_more_url')) {
                $table->string('read_more_url')->nullable()->after('author_avatar');
            }
            if (!Schema::hasColumn('isf_charts', 'order')) {
                $table->integer('order')->default(0)->after('is_beranda');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('isf_charts', function (Blueprint $table) {
            $table->dropColumn([
                'author_name',
                'author_role',
                'author_avatar',
                'read_more_url',
                'order',
            ]);
        });
    }
};
