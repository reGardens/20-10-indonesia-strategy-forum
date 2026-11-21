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
        if (Schema::hasTable('isf_articles') && ! Schema::hasColumn('isf_articles', 'is_by_member')) {
            Schema::table('isf_articles', function (Blueprint $table) {
                $table->boolean('is_by_member')->default(false)->after('status')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('isf_articles') && Schema::hasColumn('isf_articles', 'is_by_member')) {
            Schema::table('isf_articles', function (Blueprint $table) {
                $table->dropColumn('is_by_member');
            });
        }
    }
};

