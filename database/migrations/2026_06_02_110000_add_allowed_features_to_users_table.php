<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('allowed_features')->nullable()->after('password');
        });

        // Default list of 12 features
        $defaultFeatures = json_encode([
            "undangan_digital",
            "rsvp_online",
            "informasi_acara",
            "love_story",
            "manajemen_tamu",
            "amplop_digital",
            "galeri",
            "countdown",
            "qr_checkin",
            "live_streaming",
            "ucapan_doa",
            "wishlist"
        ]);

        DB::table('users')->update(['allowed_features' => $defaultFeatures]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('allowed_features');
        });
    }
};
