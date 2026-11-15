<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('isf_articles', function (Blueprint $table) {
            $table->string('subcategory', 50)->nullable()->after('category')->index();
        });
    }

    public function down(): void
    {
        Schema::table('isf_articles', function (Blueprint $table) {
            $table->dropIndex(['subcategory']);
            $table->dropColumn('subcategory');
        });
    }
};

