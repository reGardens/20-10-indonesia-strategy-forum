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
        if (Schema::hasTable('isf_articles')) {
            Schema::table('isf_articles', function (Blueprint $table) {
                if (! Schema::hasColumn('isf_articles', 'show_in_running_text')) {
                    $table->boolean('show_in_running_text')->default(false)->after('show_in_terkini')->index();
                }
                if (! Schema::hasColumn('isf_articles', 'running_text_order')) {
                    $table->unsignedTinyInteger('running_text_order')->nullable()->after('show_in_running_text');
                }
                if (! Schema::hasColumn('isf_articles', 'show_in_top_rank')) {
                    $table->boolean('show_in_top_rank')->default(false)->after('running_text_order')->index();
                }
                if (! Schema::hasColumn('isf_articles', 'top_rank_order')) {
                    $table->unsignedTinyInteger('top_rank_order')->nullable()->after('show_in_top_rank');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('isf_articles')) {
            Schema::table('isf_articles', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('isf_articles', 'show_in_running_text')) {
                    $columns[] = 'show_in_running_text';
                }
                if (Schema::hasColumn('isf_articles', 'running_text_order')) {
                    $columns[] = 'running_text_order';
                }
                if (Schema::hasColumn('isf_articles', 'show_in_top_rank')) {
                    $columns[] = 'show_in_top_rank';
                }
                if (Schema::hasColumn('isf_articles', 'top_rank_order')) {
                    $columns[] = 'top_rank_order';
                }
                if (! empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
