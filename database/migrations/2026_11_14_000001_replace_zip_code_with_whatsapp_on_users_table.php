<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Issue #42: kode pos (zip_code) diganti Nomor WhatsApp Aktif + status verifikasinya.
     * whatsapp boleh kosong untuk akun lama; pendaftar baru wajib mengisi (validasi di form).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('whatsapp', 20)->nullable()->after('last_name');
            $table->timestamp('whatsapp_verified_at')->nullable()->after('whatsapp');
        });

        if (Schema::hasColumn('users', 'zip_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('zip_code');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('zip_code', 10)->nullable()->after('last_name');
            $table->dropColumn(['whatsapp', 'whatsapp_verified_at']);
        });
    }
};
