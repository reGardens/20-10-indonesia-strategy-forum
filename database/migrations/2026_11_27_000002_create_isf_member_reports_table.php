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
        if (!Schema::hasTable('isf_member_reports')) {
            Schema::create('isf_member_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('category', 50)->default('lainnya')->index();
                $table->string('subject', 255);
                $table->text('message');
                $table->string('attachment_path', 500)->nullable();
                $table->string('status', 20)->default('pending')->index(); // pending | in_review | resolved | rejected
                $table->text('admin_notes')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('isf_member_reports');
    }
};
