<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('isf_tokohs', function (Blueprint $table) {
            $table->boolean('show_in_politik')->default(false)->after('is_featured')->index();
        });

        // Set budi-santoso sebagai tokoh politik awal
        DB::table('isf_tokohs')->where('slug', 'budi-santoso')->update(['show_in_politik' => true]);
    }

    public function down(): void
    {
        Schema::table('isf_tokohs', function (Blueprint $table) {
            $table->dropIndex(['show_in_politik']);
            $table->dropColumn('show_in_politik');
        });
    }
};

