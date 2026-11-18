<?php

namespace Tests\Feature;

use App\Mail\PartnershipAutoReplyMail;
use App\Models\Isf\PartnershipProposal;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class IsfMediaKitTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin Kemitraan',
            'email' => 'admin-kemitraan-' . uniqid() . '@example.test',
            'password' => Hash::make('secret-123'),
        ]);
    }

    public function test_public_media_kit_endpoint_returns_sections(): void
    {
        $response = $this->getJson('/api/isf/media-kit');

        $response->assertOk()
            ->assertJsonStructure([
                'pitch' => ['badge', 'title', 'deck'],
                'audiens' => ['total_monthly_readers', 'executive_share', 'retention_rate', 'segments'],
                'channels',
                'workflow',
                'faq',
            ]);
    }

    public function test_submit_proposal_validates_required_fields(): void
    {
        $response = $this->postJson('/api/isf/media-kit/rfp', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'institution_name',
                'pic_name',
                'email',
                'phone',
                'partnership_type',
                'message',
                'captcha_key',
                'captcha_answer',
            ]);
    }

    public function test_submit_proposal_rejects_invalid_captcha(): void
    {
        $response = $this->postJson('/api/isf/media-kit/rfp', [
            'institution_name' => 'PT Solusi Strategis Indonesia',
            'pic_name' => 'Dian Pratama',
            'email' => 'dian@solusi.test',
            'phone' => '081234567890',
            'partnership_type' => 'banner',
            'message' => 'Kami tertarik memasang display banner rubrik kebijakan publik.',
            'captcha_key' => 'invalid-key',
            'captcha_answer' => 'wrong',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['captcha_answer']);
    }

    public function test_submit_proposal_succeeds_and_queues_email(): void
    {
        Mail::fake();

        // Ambil captcha valid dari endpoint internal
        $captchaRes = $this->getJson('/api/isf/captcha')->assertOk();
        $captchaKey = $captchaRes->json('key');

        // Ambil plain code dari cache session / cache store
        $cacheAnswer = cache()->get('isf_captcha_' . $captchaKey);
        $this->assertNotEmpty($cacheAnswer, 'Captcha answer must be cached.');

        $payload = [
            'institution_name' => 'Kementerian Perencanaan Kebijakan',
            'pic_name' => 'Budi Santoso',
            'email' => 'budi.santoso@kemen.test',
            'phone' => '081299998888',
            'partnership_type' => 'advertorial',
            'message' => 'Rencana sosialisasi regulasi baru sektor energi terbarukan selama Q1 2027.',
            'captcha_key' => $captchaKey,
            'captcha_answer' => (string) $cacheAnswer,
        ];

        $response = $this->postJson('/api/isf/media-kit/rfp', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.institution_name', 'Kementerian Perencanaan Kebijakan')
            ->assertJsonPath('data.status', 'baru');

        $this->assertDatabaseHas('isf_partnership_proposals', [
            'institution_name' => 'Kementerian Perencanaan Kebijakan',
            'email' => 'budi.santoso@kemen.test',
            'partnership_type' => 'advertorial',
            'status' => 'baru',
        ]);

        Mail::assertQueued(PartnershipAutoReplyMail::class, function ($mail) {
            return $mail->hasTo('budi.santoso@kemen.test');
        });
    }

    public function test_cms_endpoints_require_authentication(): void
    {
        $this->getJson('/api/cms/kemitraan')->assertStatus(401);
        $this->postJson('/api/cms/kemitraan', [])->assertStatus(401);
        $this->getJson('/api/cms/kemitraan/1')->assertStatus(401);
        $this->putJson('/api/cms/kemitraan/1', [])->assertStatus(401);
        $this->deleteJson('/api/cms/kemitraan/1')->assertStatus(401);
    }

    public function test_cms_can_crud_partnership_proposals(): void
    {
        $admin = $this->admin();

        // 1. Create proposal via CMS
        $createRes = $this->actingAs($admin)->postJson('/api/cms/kemitraan', [
            'institution_name' => 'PT Mitra Riset Indonesia',
            'pic_name' => 'Siti Rahma',
            'email' => 'siti@mitrariset.test',
            'phone' => '081377776666',
            'partnership_type' => 'riset',
            'message' => 'Sponsorship laporan riset komparasi kebijakan ekonomi 2027.',
            'status' => 'baru',
            'internal_notes' => 'Catatan awal dari admin.',
        ]);

        $createRes->assertStatus(201);
        $id = $createRes->json('data.id');
        $this->assertNotNull($id);

        // 2. List proposals
        $listRes = $this->actingAs($admin)->getJson('/api/cms/kemitraan?q=Mitra+Riset');
        $listRes->assertOk()
            ->assertJsonFragment(['institution_name' => 'PT Mitra Riset Indonesia']);

        // 3. Show proposal
        $showRes = $this->actingAs($admin)->getJson("/api/cms/kemitraan/{$id}");
        $showRes->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.pic_name', 'Siti Rahma');

        // 4. Update proposal & mark contacted
        $updateRes = $this->actingAs($admin)->putJson("/api/cms/kemitraan/{$id}", [
            'institution_name' => 'PT Mitra Riset Indonesia TBK',
            'status' => 'dihubungi',
            'internal_notes' => 'Sudah ditelepon via WA pada tanggal 18 Nov.',
        ]);

        $updateRes->assertOk()
            ->assertJsonPath('data.status', 'dihubungi')
            ->assertJsonPath('data.institution_name', 'PT Mitra Riset Indonesia TBK');

        $this->assertDatabaseHas('isf_partnership_proposals', [
            'id' => $id,
            'status' => 'dihubungi',
            'institution_name' => 'PT Mitra Riset Indonesia TBK',
        ]);

        // 5. Export CSV
        $exportRes = $this->actingAs($admin)->get('/api/cms/kemitraan/export');
        $exportRes->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // 6. Delete proposal
        $deleteRes = $this->actingAs($admin)->deleteJson("/api/cms/kemitraan/{$id}");
        $deleteRes->assertStatus(204);

        $this->assertDatabaseMissing('isf_partnership_proposals', ['id' => $id]);
    }
}

