<?php

use App\Models\Isf\Letter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('isf_letters', function (Blueprint $table) {
            $table->id();
            $table->string('sender_name', 150);
            $table->string('sender_email', 150);
            $table->string('sender_city', 100)->nullable();
            $table->string('title', 300);
            $table->string('slug', 255)->unique();
            $table->text('body');
            $table->enum('moderation_status', ['pending', 'disetujui', 'ditolak'])->default('pending')->index();
            $table->timestamp('moderated_at')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('moderation_notes')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->enum('status', ['terbit', 'draft'])->default('terbit')->index();
            $table->timestamps();
        });

        // Seed sample approved letters
        Letter::create([
            'sender_name' => 'Dr. Hendra Gunawan, M.T.',
            'sender_email' => 'hendra.gunawan@alumni.itb.ac.id',
            'sender_city' => 'Bandung, Jawa Barat',
            'title' => 'Tinjauan Teknis Standarisasi PLTS Atap dan Kesiapan Jaringan PLN',
            'slug' => 'tinjauan-teknis-standarisasi-plts-atap-pln',
            'body' => 'Mencermati polemik pembatasan kuota pasang PLTS atap, kami memandang perlu adanya modernisasi pada sisi proteksi gardu distribusi. Selama keandalan grid terus diperkuat melalui otomasi smart grid, penetrasi energi surya tidak akan mengganggu stabilitas frekuensi listrik nasional.',
            'moderation_status' => 'disetujui',
            'moderated_at' => now()->subDays(2),
            'published_at' => now()->subDays(2),
            'order' => 1,
            'status' => 'terbit',
        ]);

        Letter::create([
            'sender_name' => 'Ratna Widyastuti',
            'sender_email' => 'ratna.w@pendidik.org',
            'sender_city' => 'Yogyakarta, D.I. Yogyakarta',
            'title' => 'Harapan Guru Honorer 3T terhadap Pembenahan Tata Kelola PPPK',
            'slug' => 'harapan-guru-honorer-3t-tata-kelola-pppk',
            'body' => 'Kami para pendidik di daerah tertinggal, terdepan, dan terluar (3T) berharap proses seleksi dan penempatan PPPK guru lebih memperhatikan masa pengabdian dan kondisi riil sekolah dasar pedesaan yang kerap kekurangan tenaga pengajar tetap.',
            'moderation_status' => 'disetujui',
            'moderated_at' => now()->subDays(4),
            'published_at' => now()->subDays(4),
            'order' => 2,
            'status' => 'terbit',
        ]);

        Letter::create([
            'sender_name' => 'Fajar Nugroho',
            'sender_email' => 'fajar.nugroho@startup.id',
            'sender_city' => 'Surabaya, Jawa Timur',
            'title' => 'Pentingnya Kejelasan Regulasi Perlindungan Data Pribadi bagi Ekosistem Digital Lokal',
            'slug' => 'kejelasan-regulasi-pdp-ekosistem-digital-lokal',
            'body' => 'Pembentukan lembaga pengawas PDP yang independen sesuai mandat UU PDP sangat dinanti oleh pelaku industri teknologi nasional agar tercipta kepastian standar kepatuhan siber.',
            'moderation_status' => 'pending',
            'order' => 3,
            'status' => 'terbit',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('isf_letters');
    }
};

