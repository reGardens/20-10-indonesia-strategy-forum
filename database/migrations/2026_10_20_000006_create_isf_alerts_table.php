<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alert Peringatan Dini: member dashboard feed (/anggota).
     */
    public function up(): void
    {
        Schema::create('isf_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('summary');
            $table->string('priority', 10);
            $table->date('date');
            $table->string('sektor');
            $table->string('link_url')->nullable();
            $table->string('status', 10)->default('draf')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isf_alerts');
    }
};
