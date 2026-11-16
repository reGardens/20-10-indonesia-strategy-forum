<?php

use App\Models\Isf\ReaderPoll;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('isf_reader_poll_votes');
        Schema::dropIfExists('isf_reader_polls');

        Schema::create('isf_reader_polls', function (Blueprint $table) {
            $table->id();
            $table->string('question', 500);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->json('options'); // [{ "id": "1", "label": "Sangat Mendesak", "votes": 342 }, ...]
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['aktif', 'selesai', 'draft'])->default('aktif')->index();
            $table->unsignedInteger('total_votes')->default(0);
            $table->boolean('is_featured')->default(true);
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
        });

        Schema::create('isf_reader_poll_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained('isf_reader_polls')->cascadeOnDelete();
            $table->string('option_id', 50);
            $table->string('voter_hash', 64)->index();
            $table->string('ip_address', 45)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['poll_id', 'voter_hash']);
        });

        // Seed 2 Reader Polls
        ReaderPoll::create([
            'question' => 'Seberapa mendesak pengesahan RUU Perampasan Aset Tindak Pidana untuk memulihkan kerugian keuangan negara?',
            'slug' => 'urgensi-pengesahan-ruu-perampasan-aset-2026',
            'description' => 'Isu hangat reformasi hukum pemberantasan korupsi di DPR RI masa sidang 2026.',
            'options' => [
                ['id' => '1', 'label' => 'Sangat Mendesak (Harus Diprioritaskan Tahun Ini)', 'votes' => 428],
                ['id' => '2', 'label' => 'Mendesak (Dengan Perbaikan Ketentuan Sita Aset)', 'votes' => 195],
                ['id' => '3', 'label' => 'Cukup Mengoptimalkan UU Tipikor & TPPU yang Ada', 'votes' => 64],
                ['id' => '4', 'label' => 'Belum Tahu / Tidak Memiliki Pendapat', 'votes' => 18],
            ],
            'total_votes' => 705,
            'status' => 'aktif',
            'is_featured' => true,
            'start_date' => now()->subDays(7)->toDateString(),
            'end_date' => now()->addDays(23)->toDateString(),
            'order' => 1,
        ]);

        ReaderPoll::create([
            'question' => 'Apakah subsidi transportasi publik antarmoda di kawasan metropolitan sudah memadai dan tepat sasaran?',
            'slug' => 'evaluasi-subsidi-transportasi-publik-metropolitan',
            'description' => 'Evaluasi integrasi tarif KRL, MRT, LRT, dan feeder perkotaan.',
            'options' => [
                ['id' => '1', 'label' => 'Sudah Memadai & Sangat Membantu Masyarakat', 'votes' => 156],
                ['id' => '2', 'label' => 'Belum Merata, Terutama di Wilayah Pinggiran/Penyangga', 'votes' => 312],
                ['id' => '3', 'label' => 'Perlu Integrasi Tiket Tunggal Semua Moda', 'votes' => 245],
            ],
            'total_votes' => 713,
            'status' => 'aktif',
            'is_featured' => false,
            'start_date' => now()->subDays(14)->toDateString(),
            'end_date' => now()->addDays(16)->toDateString(),
            'order' => 2,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('isf_reader_poll_votes');
        Schema::dropIfExists('isf_reader_polls');
    }
};
