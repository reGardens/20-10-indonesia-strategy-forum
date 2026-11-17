<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('isf_parliament_seats') && !Schema::hasColumn('isf_parliament_seats', 'logo')) {
            Schema::table('isf_parliament_seats', function (Blueprint $table) {
                $table->string('logo', 500)->nullable()->after('warna_partai');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('isf_parliament_seats') && Schema::hasColumn('isf_parliament_seats', 'logo')) {
            Schema::table('isf_parliament_seats', function (Blueprint $table) {
                $table->dropColumn('logo');
            });
        }
    }
};

