<?php

namespace Tests\Feature;

use App\Mail\MemberReportNotificationMail;
use App\Models\Isf\Letter;
use App\Models\Isf\MemberReport;
use App\Models\Isf\Tokoh;
use App\Models\Isf\TokohReview;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberReportAndActivityTest extends TestCase
{
    use DatabaseTransactions;

    protected User $memberUser;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'member']);
        Role::firstOrCreate(['name' => 'admin']);

        $this->memberUser = User::create([
            'name' => 'Member Test User',
            'email' => 'member_activity_' . time() . '@isf.test',
            'password' => Hash::make('password123'),
        ]);
        $this->memberUser->assignRole('member');

        $this->adminUser = User::create([
            'name' => 'Admin Test User',
            'email' => 'admin_report_' . time() . '@isf.test',
            'password' => Hash::make('password123'),
        ]);
        $this->adminUser->assignRole('admin');
    }

    public function test_member_can_submit_report_and_triggers_email_notification(): void
    {
        Mail::fake();
        Storage::fake('public');

        $file = UploadedFile::fake()->create('screenshot.png', 100, 'image/png');

        $response = $this->actingAs($this->memberUser)
            ->postJson('/api/cms/laporan-member', [
                'category' => 'kendala_teknis',
                'subject' => 'Kendala Pengisian Survei di HP',
                'message' => 'Saya mengalami kendala saat submit survei opini publik.',
                'attachment' => $file,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('isf_member_reports', [
            'user_id' => $this->memberUser->id,
            'category' => 'kendala_teknis',
            'subject' => 'Kendala Pengisian Survei di HP',
            'status' => 'pending',
        ]);

        Mail::assertSent(MemberReportNotificationMail::class);
    }

    public function test_admin_can_view_and_update_report_status(): void
    {
        $report = MemberReport::create([
            'user_id' => $this->memberUser->id,
            'category' => 'survei',
            'subject' => 'Pertanyaan nomor 2 tidak muncul',
            'message' => 'Detail masalah kuisioner...',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/cms/laporan-member');

        $response->assertOk()
            ->assertJsonFragment([
                'subject' => 'Pertanyaan nomor 2 tidak muncul',
            ]);

        $updateRes = $this->actingAs($this->adminUser)
            ->putJson('/api/cms/laporan-member/' . $report->id, [
                'status' => 'resolved',
                'admin_notes' => 'Masalah telah diselesaikan oleh tim IT.',
            ]);

        $updateRes->assertOk();

        $this->assertDatabaseHas('isf_member_reports', [
            'id' => $report->id,
            'status' => 'resolved',
            'admin_notes' => 'Masalah telah diselesaikan oleh tim IT.',
        ]);
    }

    public function test_member_activities_endpoint_returns_comprehensive_history(): void
    {
        // 1. Create a letter
        Letter::create([
            'user_id' => $this->memberUser->id,
            'sender_name' => $this->memberUser->name,
            'sender_email' => $this->memberUser->email,
            'title' => 'Catatan Kritis Kebijakan Hilirisasi',
            'slug' => 'catatan-kritis-kebijakan-hilirisasi-' . time(),
            'category' => 'Opini',
            'body' => 'Konten naskah opini...',
            'moderation_status' => 'disetujui',
        ]);

        // 2. Create a report
        MemberReport::create([
            'user_id' => $this->memberUser->id,
            'category' => 'lainnya',
            'subject' => 'Usulan Rubrik Baru',
            'message' => 'Saran penambahan analisis maritim.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->memberUser)
            ->getJson('/api/isf/member/activities');

        $response->assertOk()
            ->assertJsonStructure([
                'letters',
                'reviews',
                'surveys',
                'reports',
            ])
            ->assertJsonFragment([
                'title' => 'Catatan Kritis Kebijakan Hilirisasi',
            ])
            ->assertJsonFragment([
                'subject' => 'Usulan Rubrik Baru',
            ]);
    }
}
