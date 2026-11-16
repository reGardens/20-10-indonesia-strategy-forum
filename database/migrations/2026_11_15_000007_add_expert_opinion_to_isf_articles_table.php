<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('isf_articles', function (Blueprint $table) {
            $table->boolean('is_expert_opinion')->default(false)->after('is_hero')->index();
            $table->string('expert_name', 255)->nullable()->after('is_expert_opinion');
            $table->string('expert_title', 255)->nullable()->after('expert_name');
            $table->string('expert_avatar', 1000)->nullable()->after('expert_title');
        });
    }

    public function down(): void
    {
        Schema::table('isf_articles', function (Blueprint $table) {
            $table->dropIndex(['is_expert_opinion']);
            $table->dropColumn([
                'is_expert_opinion',
                'expert_name',
                'expert_title',
                'expert_avatar',
            ]);
        });
    }
};

