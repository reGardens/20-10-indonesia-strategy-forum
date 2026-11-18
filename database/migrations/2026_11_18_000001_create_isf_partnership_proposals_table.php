<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('isf_partnership_proposals')) {
            Schema::create('isf_partnership_proposals', function (Blueprint $table) {
                $table->id();
                $table->string('institution_name', 255)->comment('Nama Lembaga / Perusahaan / Instansi');
                $table->string('pic_name', 150)->comment('Nama PIC / Kontak');
                $table->string('email', 150)->index()->comment('Email PIC');
                $table->string('phone', 50)->comment('Nomor WhatsApp / Telepon');
                $table->string('partnership_type', 50)->index()->comment('Jenis Kemitraan: banner, advertorial, event, riset, lainnya');
                $table->text('message')->comment('Deskripsi kebutuhan / pesan kemitraan');
                $table->string('status', 20)->default('baru')->index()->comment('Status: baru, dihubungi, selesai, ditolak');
                $table->text('internal_notes')->nullable()->comment('Catatan internal tim ISF');
                $table->timestamp('contacted_at')->nullable()->comment('Waktu PIC dihubungi');
                $table->foreignId('contacted_by')->nullable()->constrained('users')->nullOnDelete()->comment('User admin yang menghubungi');
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
            });

            // Seed initial sample proposals for testing & verification
            $now = now();
            $samples = [
                [
                    'institution_name' => 'PT Mandiri Sekuritas Persero',
                    'pic_name' => 'Dimas Prasetyo, S.E.',
                    'email' => 'dimas.prasetyo@mandirisek.co.id',
                    'phone' => '081289123456',
                    'partnership_type' => 'advertorial',
                    'message' => 'Kami berencana mempublikasikan kajian komprehensif mengenai prospek penerbitan Green Bond korporasi dan transisi ESG perbankan di Indonesia pada kuartal mendatang. Mohon informasi tindak lanjut kemitraan advertorial dan audiensi pembaca.',
                    'status' => 'baru',
                    'internal_notes' => 'Proposal masuk via web. Sektor keuangan/pasar modal, prioritas tinggi.',
                    'contacted_at' => null,
                    'contacted_by' => null,
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    'created_at' => $now->copy()->subHours(2),
                    'updated_at' => $now->copy()->subHours(2),
                ],
                [
                    'institution_name' => 'Asosiasi Pengusaha Energi Hijau Indonesia (APEHI)',
                    'pic_name' => 'Sarah Melinda, M.Sc.',
                    'email' => 'sarah.melinda@apehi.org',
                    'phone' => '081398765432',
                    'partnership_type' => 'event',
                    'message' => 'APEHI bermaksud mengundang Dewan Pakar ISF dan menyelenggarakan Roundtable Discussion bertajuk "Harmonisasi Regulasi Tarif Listrik EBT 2026" secara hybrid bersama kementerian terkait. Ingin mendiskusikan peluang kolaborasi kepanitiaan dan media partner.',
                    'status' => 'dihubungi',
                    'internal_notes' => 'Sudah dikontak via WhatsApp oleh tim redaksi, jadwal meeting disepakati lusa.',
                    'contacted_at' => $now->copy()->subDay(),
                    'contacted_by' => null,
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    'created_at' => $now->copy()->subDays(2),
                    'updated_at' => $now->copy()->subDay(),
                ],
                [
                    'institution_name' => 'Bappenas RI - Direktorat Hukum dan Regulasi',
                    'pic_name' => 'Bambang Irawan, S.H., M.H.',
                    'email' => 'bambang.irawan@bappenas.go.id',
                    'phone' => '081122334455',
                    'partnership_type' => 'riset',
                    'message' => 'Kami memerlukan publikasi ringkasan eksekutif (Policy Brief) dan visualisasi infografik data terkait evaluasi sistem regulasi nasional untuk disebarluaskan ke pemangku kebijakan lintas lembaga.',
                    'status' => 'selesai',
                    'internal_notes' => 'Kerjasama publikasi policy brief telah terbit di rubrik Kebijakan Publik.',
                    'contacted_at' => $now->copy()->subDays(5),
                    'contacted_by' => null,
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    'created_at' => $now->copy()->subDays(7),
                    'updated_at' => $now->copy()->subDays(3),
                ],
            ];

            DB::table('isf_partnership_proposals')->insert($samples);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('isf_partnership_proposals');
    }
};

