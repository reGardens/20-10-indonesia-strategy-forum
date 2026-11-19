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
        Schema::table('isf_ads', function (Blueprint $table) {
            $table->boolean('is_skippable')->default(true)->after('first_delay_seconds');
            $table->unsignedSmallInteger('skip_delay_seconds')->default(10)->nullable()->after('is_skippable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('isf_ads', function (Blueprint $table) {
            $table->dropColumn(['is_skippable', 'skip_delay_seconds']);
        });
    }
};

