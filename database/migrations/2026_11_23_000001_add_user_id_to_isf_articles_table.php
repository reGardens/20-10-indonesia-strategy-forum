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
        if (Schema::hasTable('isf_articles') && ! Schema::hasColumn('isf_articles', 'user_id')) {
            Schema::table('isf_articles', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('isf_articles') && Schema::hasColumn('isf_articles', 'user_id')) {
            Schema::table('isf_articles', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }
    }
};

