<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\WhatsApp\FonnteSender;
use App\Services\WhatsApp\WhatsAppSender;
use App\Services\WhatsApp\WhatsAppSendException;
use App\Support\WhatsAppNumber;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Issue #42: Nomor WhatsApp Aktif + verifikasi OTP, role member/admin.
 */
class WhatsAppVerificationTest extends TestCase
{
    use DatabaseTransactions;

    /** Pengirim palsu: menyimpan pesan terakhir supaya kode OTP bisa dibaca di test. */
    private object $sender;

    protected function setUp(): void
    {
        parent::setUp();
        // Tanpa Cache::flush(): key OTP per-user & setiap test memakai user baru (cache lokal tidak ikut terhapus).
        // Test alur OTP: saklar verifikasi dinyalakan (default di aplikasi MATI; lihat test *_when_verification_disabled).
        config(['services.whatsapp.verification' => true]);

        $this->sender = new class implements WhatsAppSender {
            public array $sent = [];

            public bool $fail = false;

            public function send(string $to, string $message): void
            {
                if ($this->fail) {
                    throw new WhatsAppSendException('gagal');
                }
                $this->sent[] = ['to' => $to, 'message' => $message];
            }

            public function lastCode(): ?string
            {
                $last = end($this->sent);

                return $last && preg_match('/\*(\d{6})\*/', $last['message'], $m) ? $m[1] : null;
            }
        };
        $this->app->instance(WhatsAppSender::class, $this->sender);
    }

    private function makeUser(?string $whatsapp = null): User
    {
        return User::create([
            'name' => 'Rina Kartika',
            'email' => 'uji-wa-' . uniqid() . '@example.test',
            'password' => Hash::make('rahasia-123'),
            'whatsapp' => $whatsapp,
        ]);
    }

    public function test_number_normalization_and_validation(): void
    {
        $this->assertSame('6281234567890', WhatsAppNumber::normalize('0812-3456-7890'));
        $this->assertSame('6281234567890', WhatsAppNumber::normalize('+62 812 3456 7890'));
        $this->assertSame('6281234567890', WhatsAppNumber::normalize('6281234567890'));
        $this->assertTrue(WhatsAppNumber::isValid('081234567890'));
        $this->assertFalse(WhatsAppNumber::isValid('021555123'));   // telepon rumah
        $this->assertFalse(WhatsAppNumber::isValid('0812'));        // terlalu pendek
        $this->assertFalse(WhatsAppNumber::isValid('abc'));
    }

    public function test_verification_is_disabled_by_default(): void
    {
        // Nilai bawaan dari config/services.php (tanpa WHATSAPP_VERIFICATION di .env) harus MATI.
        $services = require base_path('config/services.php');
        $this->assertFalse($services['whatsapp']['verification']);
    }

    public function test_save_number_without_otp_when_verification_disabled(): void
    {
        config(['services.whatsapp.verification' => false]);
        $user = $this->makeUser();

        $this->actingAs($user)->putJson('/api/isf/me/whatsapp', ['whatsapp' => '0812-3456-7890'])
            ->assertOk()
            ->assertJson(['message' => 'Nomor WhatsApp tersimpan.', 'whatsapp' => '6281234567890', 'whatsappVerification' => false])
            ->assertJsonMissing(['codeSent' => true]);

        $this->assertSame('6281234567890', $user->fresh()->whatsapp);
        $this->assertCount(0, $this->sender->sent, 'Fonnte/pengirim WhatsApp tidak boleh dipanggil saat verifikasi mati');

        $this->actingAs($user)->postJson('/api/isf/me/whatsapp/kirim-kode')->assertNotFound();
        $this->actingAs($user)->postJson('/api/isf/me/whatsapp/verifikasi', ['code' => '123456'])->assertNotFound();
        $this->assertCount(0, $this->sender->sent);

        $this->actingAs($user)->getJson('/api/isf/me')->assertJson(['whatsapp' => '6281234567890', 'whatsappVerification' => false]);
    }

    public function test_guest_cannot_use_whatsapp_endpoints(): void
    {
        $this->putJson('/api/isf/me/whatsapp', ['whatsapp' => '081234567890'])->assertUnauthorized();
        $this->postJson('/api/isf/me/whatsapp/verifikasi', ['code' => '123456'])->assertUnauthorized();
    }

    public function test_save_number_sends_code_and_verify_marks_number_verified(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->putJson('/api/isf/me/whatsapp', ['whatsapp' => '0812-3456-7890'])
            ->assertOk()
            ->assertJson(['codeSent' => true, 'whatsapp' => '6281234567890', 'whatsappVerified' => false]);

        $this->assertSame('6281234567890', $this->sender->sent[0]['to']);
        $code = $this->sender->lastCode();
        $this->assertNotNull($code);

        $this->actingAs($user)->postJson('/api/isf/me/whatsapp/verifikasi', ['code' => $code])
            ->assertOk()->assertJson(['whatsappVerified' => true]);
        $this->assertNotNull($user->fresh()->whatsapp_verified_at);

        // /api/isf/me ikut melaporkan status terverifikasi
        $this->actingAs($user)->getJson('/api/isf/me')->assertJson(['whatsapp' => '6281234567890', 'whatsappVerified' => true]);
    }

    public function test_wrong_code_is_rejected_and_locked_after_max_attempts(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)->putJson('/api/isf/me/whatsapp', ['whatsapp' => '081234567890'])->assertOk();
        $wrong = $this->sender->lastCode() === '000000' ? '111111' : '000000';

        for ($i = 1; $i <= 4; $i++) {
            $this->actingAs($user)->postJson('/api/isf/me/whatsapp/verifikasi', ['code' => $wrong])
                ->assertUnprocessable()->assertJsonPath('errors.code.0', 'Kode verifikasi salah.');
        }
        $this->actingAs($user)->postJson('/api/isf/me/whatsapp/verifikasi', ['code' => $wrong])
            ->assertUnprocessable()->assertJsonPath('errors.code.0', 'Terlalu banyak percobaan. Minta kode baru.');

        // kode benar pun tidak berlaku lagi setelah terkunci
        $this->actingAs($user)->postJson('/api/isf/me/whatsapp/verifikasi', ['code' => $this->sender->lastCode()])
            ->assertUnprocessable();
        $this->assertNull($user->fresh()->whatsapp_verified_at);
    }

    public function test_resend_respects_cooldown(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)->putJson('/api/isf/me/whatsapp', ['whatsapp' => '081234567890'])->assertOk();

        $this->actingAs($user)->postJson('/api/isf/me/whatsapp/kirim-kode')->assertStatus(429);
        $this->assertCount(1, $this->sender->sent);
    }

    public function test_changing_number_resets_verification_and_invalidates_old_code(): void
    {
        $user = $this->makeUser('6281234567890');
        $user->forceFill(['whatsapp_verified_at' => now()])->save();

        $this->actingAs($user)->putJson('/api/isf/me/whatsapp', ['whatsapp' => '081298765432'])
            ->assertOk()->assertJson(['whatsapp' => '6281298765432', 'whatsappVerified' => false]);
        $this->assertNull($user->fresh()->whatsapp_verified_at);
    }

    public function test_invalid_number_is_rejected(): void
    {
        $this->actingAs($this->makeUser())->putJson('/api/isf/me/whatsapp', ['whatsapp' => '021555123'])
            ->assertUnprocessable()->assertJsonValidationErrors('whatsapp');
        $this->assertCount(0, $this->sender->sent);
    }

    public function test_gateway_failure_returns_502_without_storing_code(): void
    {
        $this->sender->fail = true;
        $user = $this->makeUser();

        $this->actingAs($user)->putJson('/api/isf/me/whatsapp', ['whatsapp' => '081234567890'])->assertStatus(502);
        // nomor tetap tersimpan (belum terverifikasi) sehingga bisa dicoba kirim ulang
        $this->assertSame('6281234567890', $user->fresh()->whatsapp);
        $this->assertNull($user->fresh()->whatsapp_verified_at);
    }

    public function test_fonnte_sender_posts_expected_request(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true, 'detail' => 'success'], 200)]);

        (new FonnteSender('TOKEN-UJI'))->send('6281234567890', 'Halo');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.fonnte.com/send'
            && $request->hasHeader('Authorization', 'TOKEN-UJI')
            && $request['target'] === '6281234567890'
            && $request['message'] === 'Halo'
            && $request['countryCode'] === '0');
    }

    public function test_fonnte_sender_throws_when_status_false(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => false, 'reason' => 'invalid token'], 200)]);

        $this->expectException(WhatsAppSendException::class);
        (new FonnteSender('SALAH'))->send('6281234567890', 'Halo');
    }

    public function test_roles_member_and_admin_exist_and_acl_lists_all_non_super_admin(): void
    {
        $this->assertTrue(Role::where('name', 'member')->where('guard_name', 'web')->exists());
        $this->assertTrue(Role::where('name', 'admin')->where('guard_name', 'web')->exists());
        $this->assertFalse(Role::where('name', 'user')->where('guard_name', 'web')->exists());

        $super = User::create(['name' => 'Super Uji', 'email' => 'uji-super-' . uniqid() . '@example.test', 'password' => Hash::make('rahasia-123')]);
        $super->assignRole('super-admin');
        $admin = $this->makeUser();
        $admin->assignRole('admin');
        $member = $this->makeUser();
        $member->assignRole('member');

        $this->actingAs($super)->get('/cms/acl')->assertOk()
            ->assertInertia(fn ($page) => $page->component('Cms/Acl/Index', false)
                ->where('users', fn ($users) => collect($users)->pluck('id')->contains($admin->id)
                    && collect($users)->pluck('id')->contains($member->id)
                    && ! collect($users)->pluck('id')->contains($super->id)));
    }
}
