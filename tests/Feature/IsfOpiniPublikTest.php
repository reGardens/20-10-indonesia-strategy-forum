<?php

namespace Tests\Feature;

use App\Models\Isf\Article;
use App\Models\Isf\Letter;
use App\Models\Isf\PublicQuote;
use App\Models\Isf\ReaderPoll;
use App\Models\Isf\ReaderPollVote;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IsfOpiniPublikTest extends TestCase
{
    use DatabaseTransactions;

    private function createAdminUser(array $features = ['reader_poll', 'kutipan_publik', 'surat_pembaca'], string $role = 'admin'): User
    {
        if ($role) {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $user = User::create([
            'name' => 'Editor Opini',
            'email' => 'editor-opini-' . uniqid() . '@example.test',
            'password' => Hash::make('secret123'),
            'allowed_features' => $features,
        ]);

        if ($role) {
            $user->assignRole($role);
        }

        return $user;
    }

    public function test_opini_publik_web_and_api_return_expected_structure(): void
    {
        // 1. Web route
        $web = $this->get('/opini-publik');
        $web->assertOk();

        // 2. Subcategory web route
        $webSub = $this->get('/opini-publik/op-ed');
        $webSub->assertOk();

        // 3. Public API
        $res = $this->getJson('/api/isf/opini-publik');
        $res->assertOk();
        $res->assertJsonStructure([
            'rubrik' => [
                'key',
                'title',
                'label',
                'description',
                'subkategori',
                'activeSubkategori',
            ],
            'headline',
            'articles',
            'readerPoll',
            'kutipanPublik',
            'suratPembaca',
            'meta' => [
                'currentPage',
                'lastPage',
                'total',
                'perPage',
            ],
        ]);

        $this->assertEquals('opini', $res->json('rubrik.key'));
    }

    public function test_opini_publik_filter_by_subkategori(): void
    {
        $now = now();
        $artOpEd = Article::create([
            'title' => 'Kritik Kebijakan Moneter ' . uniqid(),
            'slug' => 'kritik-kebijakan-moneter-' . uniqid(),
            'category' => 'opini',
            'subcategory' => 'op-ed',
            'published_at' => $now,
            'summary' => 'Ringkasan op-ed',
            'body' => 'Isi lengkap op-ed',
            'status' => 'terbit',
        ]);

        $artEditorial = Article::create([
            'title' => 'Editorial ISF: Menjaga Demokrasi ' . uniqid(),
            'slug' => 'editorial-isf-menjaga-demokrasi-' . uniqid(),
            'category' => 'opini',
            'subcategory' => 'editorial',
            'published_at' => $now,
            'summary' => 'Ringkasan editorial',
            'body' => 'Isi editorial resmi',
            'status' => 'terbit',
        ]);

        // Filter op-ed
        $resOpEd = $this->getJson('/api/isf/opini-publik?subkategori=op-ed');
        $resOpEd->assertOk();
        $resOpEd->assertJsonFragment(['slug' => $artOpEd->slug]);
        $resOpEd->assertJsonMissing(['slug' => $artEditorial->slug]);

        // Filter editorial
        $resEd = $this->getJson('/api/isf/opini-publik?subkategori=editorial');
        $resEd->assertOk();
        $resEd->assertJsonFragment(['slug' => $artEditorial->slug]);
        $resEd->assertJsonMissing(['slug' => $artOpEd->slug]);
    }

    public function test_reader_poll_voting_and_duplicate_prevention(): void
    {
        $poll = ReaderPoll::create([
            'question' => 'Apakah Anda mendukung program subsidi tepat sasaran? ' . uniqid(),
            'slug' => 'poll-subsidi-' . uniqid(),
            'description' => 'Jajak pendapat non-ilmiah pembaca ISF',
            'options' => [
                ['key' => 'opt_ya', 'label' => 'Sangat Setuju', 'votes' => 0],
                ['key' => 'opt_ragu', 'label' => 'Ragu-ragu', 'votes' => 0],
                ['key' => 'opt_tidak', 'label' => 'Tidak Setuju', 'votes' => 0],
            ],
            'total_votes' => 0,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(7),
            'is_featured' => true,
            'status' => 'aktif',
        ]);

        // First vote succeeds
        $voteRes = $this->withServerVariables(['REMOTE_ADDR' => '192.168.10.50'])
            ->postJson("/api/isf/reader-poll/{$poll->id}/vote", [
                'option_key' => 'opt_ya',
            ]);

        $voteRes->assertOk();
        $voteRes->assertJson([
            'message' => 'Suara Anda berhasil dicatat. Terima kasih telah berpartisipasi!',
            'data' => [
                'id' => $poll->id,
                'total_votes' => 1,
            ],
        ]);

        $this->assertEquals(1, $poll->fresh()->total_votes);

        // Second vote with same IP / device hash is rejected (duplicate prevention)
        $dupRes = $this->withServerVariables(['REMOTE_ADDR' => '192.168.10.50'])
            ->postJson("/api/isf/reader-poll/{$poll->id}/vote", [
                'option_key' => 'opt_tidak',
            ]);

        $dupRes->assertStatus(422);
        $dupRes->assertJsonFragment(['message' => 'Anda sudah memberikan suara pada jajak pendapat ini.']);
    }

    public function test_surat_pembaca_submission_and_moderation_flow(): void
    {
        // 1. Setup mock captcha
        $captchaKey = 'test_captcha_' . uniqid();
        Cache::put("captcha:{$captchaKey}", 'AB123', now()->addMinutes(10));

        // 2. Submit surat pembaca via public API
        $payload = [
            'nama_pengirim' => 'Dewi Rahmawati',
            'email_pengirim' => 'dewi@example.test',
            'kota_domisili' => 'Yogyakarta',
            'judul_surat' => 'Harapan untuk Perbaikan Transportasi Publik Daerah',
            'isi_surat' => 'Transportasi antarmoda di daerah pinggiran memerlukan integrasi tiket yang memadai agar masyarakat lebih terdorong menggunakan angkutan umum ketimbang kendaraan pribadi.',
            'captcha_key' => $captchaKey,
            'captcha_answer' => 'ab123', // case insensitive check
        ];

        $subRes = $this->postJson('/api/isf/surat-pembaca', $payload);
        $subRes->assertStatus(201);
        $letterId = $subRes->json('data.id');

        $letter = Letter::findOrFail($letterId);
        $this->assertEquals('pending', $letter->moderation_status);

        // 3. Pending letter does NOT appear on public endpoint
        $pubRes1 = $this->getJson('/api/isf/surat-pembaca');
        $pubRes1->assertOk();
        $pubRes1->assertJsonMissing(['id' => $letterId]);

        // 3b. Non-admin user cannot approve (must be admin or super-admin)
        $memberUser = $this->createAdminUser(['surat_pembaca'], 'member');
        $forbiddenRes = $this->actingAs($memberUser)
            ->postJson("/api/cms/surat-pembaca/{$letterId}/approve", [
                'notes' => 'Coba approve',
            ]);
        $forbiddenRes->assertStatus(403);

        // 4. Admin approves letter
        $admin = $this->createAdminUser(['surat_pembaca'], 'admin');
        $approveRes = $this->actingAs($admin)
            ->postJson("/api/cms/surat-pembaca/{$letterId}/approve", [
                'notes' => 'Layak tayang dengan judul orisinal',
            ]);
        $approveRes->assertOk();

        $this->assertEquals('disetujui', $letter->fresh()->moderation_status);

        // 5. Approved letter NOW appears on public endpoint
        $pubRes2 = $this->getJson('/api/isf/surat-pembaca');
        $pubRes2->assertOk();
        $pubRes2->assertJsonFragment(['id' => $letterId]);

        // 6. Assert article is also copied to isf_articles table with is_by_member = true
        $createdArticle = Article::where('title', $letter->title)->first();
        $this->assertNotNull($createdArticle);
        $this->assertTrue((bool) $createdArticle->is_by_member);
        $this->assertEquals('terbit', $createdArticle->status);

        // 7. Rejecting the letter updates the article in isf_articles with ditolak status
        $rejectRes = $this->actingAs($admin)
            ->postJson("/api/cms/surat-pembaca/{$letterId}/reject", [
                'notes' => 'Topik tidak relevan dengan kebijakan',
            ]);
        $rejectRes->assertOk();

        $this->assertEquals('ditolak', $letter->fresh()->moderation_status);
        $rejectedArticle = Article::where('title', $letter->title)->where('is_by_member', true)->first();
        $this->assertNotNull($rejectedArticle);
        $this->assertEquals('ditolak', $rejectedArticle->status);

        // 8. Rejected letter cannot be approved again
        $reApproveRes = $this->actingAs($admin)
            ->postJson("/api/cms/surat-pembaca/{$letterId}/approve", [
                'notes' => 'Coba approve lagi',
            ]);
        $reApproveRes->assertStatus(422);
    }

    public function test_public_penulis_profile_endpoint(): void
    {
        $uniqueName = 'Penulis Uji ' . uniqid();
        $slug = \Illuminate\Support\Str::slug($uniqueName);

        $letter = Letter::create([
            'sender_name' => $uniqueName,
            'sender_email' => 'penulis@example.test',
            'sender_city' => 'Surabaya',
            'title' => 'Analisis Kebijakan ' . uniqid(),
            'slug' => 'analisis-kebijakan-' . uniqid(),
            'body' => 'Naskah ulasan kebijakan publik',
            'moderation_status' => 'disetujui',
            'status' => 'terbit',
            'published_at' => now(),
        ]);

        // API Endpoint test
        $res = $this->getJson("/api/isf/penulis/{$slug}");
        $res->assertOk();
        $res->assertJsonPath('data.name', $uniqueName);
        $this->assertNotEmpty($res->json('data.articles'));

        // Web Route test
        $web = $this->get("/penulis/{$slug}");
        $web->assertOk();

        // Non-existent penulis
        $res404 = $this->getJson('/api/isf/penulis/penulis-fiktif-' . uniqid());
        $res404->assertStatus(404);
    }

    public function test_cms_acl_enforcement_for_opini_modules(): void
    {
        // User without features
        $unauthorized = $this->createAdminUser([]);

        $this->actingAs($unauthorized)->getJson('/api/cms/reader-poll')->assertStatus(403);
        $this->actingAs($unauthorized)->getJson('/api/cms/kutipan-publik')->assertStatus(403);
        $this->actingAs($unauthorized)->getJson('/api/cms/surat-pembaca')->assertStatus(403);

        // Authorized user
        $authorized = $this->createAdminUser(['reader_poll', 'kutipan_publik', 'surat_pembaca']);

        $this->actingAs($authorized)->getJson('/api/cms/reader-poll')->assertOk();
        $this->actingAs($authorized)->getJson('/api/cms/kutipan-publik')->assertOk();
        $this->actingAs($authorized)->getJson('/api/cms/surat-pembaca')->assertOk();
    }
}
