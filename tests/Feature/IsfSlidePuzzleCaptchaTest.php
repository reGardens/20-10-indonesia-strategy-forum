<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IsfSlidePuzzleCaptchaTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    /**
     * Test puzzle captcha generation endpoint returns all required payload.
     */
    public function test_puzzle_captcha_endpoint_generates_valid_challenge(): void
    {
        $response = $this->getJson('/api/isf/captcha/puzzle');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'key',
                'bg_image',
                'piece_image',
                'puzzle_y',
                'width',
                'height',
                'piece_size',
                'piece_width',
                'piece_height',
                'max_offset',
            ]);

        $key = $response->json('key');
        $this->assertNotEmpty($key);
        // Gambar harus data URI yang bisa dibaca browser (PNG dari foto, atau SVG tema), tidak terbungkus dua kali.
        $this->assertDecodableImage($response->json('bg_image'));
        $this->assertDecodableImage($response->json('piece_image'));

        // Target X coordinate must be stored in cache
        $targetX = Cache::get('puzzle:' . $key);
        $this->assertNotNull($targetX);
        $this->assertGreaterThanOrEqual(85, $targetX);
        $this->assertLessThanOrEqual(235, $targetX);
    }

    /**
     * Test puzzle verify endpoint approves correct offset within tolerance.
     */
    public function test_puzzle_verify_endpoint_approves_correct_offset(): void
    {
        $res = $this->getJson('/api/isf/captcha/puzzle')->assertOk();
        $key = $res->json('key');
        $targetX = Cache::get('puzzle:' . $key);

        // Submit exact or +-3px offset (within 6px tolerance)
        $verifyRes = $this->postJson('/api/isf/captcha/puzzle/verify', [
            'key' => $key,
            'offset' => $targetX + 2,
        ]);

        $verifyRes->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertNotNull(Cache::get('puzzle_verified:' . $key));
    }

    /**
     * Test puzzle verify endpoint rejects offset outside tolerance.
     */
    public function test_puzzle_verify_endpoint_rejects_incorrect_offset(): void
    {
        $res = $this->getJson('/api/isf/captcha/puzzle')->assertOk();
        $key = $res->json('key');
        $targetX = Cache::get('puzzle:' . $key);

        $verifyRes = $this->postJson('/api/isf/captcha/puzzle/verify', [
            'key' => $key,
            'offset' => $targetX + 30, // 30px off
        ]);

        $verifyRes->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Respons gagal tidak boleh membocorkan jarak ke posisi benar.
     */
    public function test_puzzle_verify_failure_does_not_leak_distance(): void
    {
        $key = $this->getJson('/api/isf/captcha/puzzle')->assertOk()->json('key');

        $res = $this->postJson('/api/isf/captcha/puzzle/verify', ['key' => $key, 'offset' => 0])
            ->assertStatus(422);

        $this->assertSame(['success', 'message'], array_keys($res->json()));
    }

    /**
     * Key hangus setelah 3 kali salah: jawaban benar sesudahnya ditolak.
     */
    public function test_puzzle_key_expires_after_too_many_failed_attempts(): void
    {
        $key = $this->getJson('/api/isf/captcha/puzzle')->assertOk()->json('key');
        $targetX = Cache::get('puzzle:' . $key);
        $wrong = $targetX > 150 ? 0 : 300;

        $this->postJson('/api/isf/captcha/puzzle/verify', ['key' => $key, 'offset' => $wrong])
            ->assertStatus(422)->assertJsonMissingPath('expired');
        $this->postJson('/api/isf/captcha/puzzle/verify', ['key' => $key, 'offset' => $wrong])
            ->assertStatus(422)->assertJsonMissingPath('expired');
        $this->postJson('/api/isf/captcha/puzzle/verify', ['key' => $key, 'offset' => $wrong])
            ->assertStatus(422)->assertJsonPath('expired', true);

        $this->assertNull(Cache::get('puzzle:' . $key));
        $this->postJson('/api/isf/captcha/puzzle/verify', ['key' => $key, 'offset' => $targetX])
            ->assertStatus(422)->assertJsonPath('success', false);
    }

    /**
     * Jawaban salah saat submit registrasi langsung menghanguskan key (tidak bisa ditebak berulang).
     */
    public function test_wrong_puzzle_answer_on_register_consumes_key(): void
    {
        $key = $this->getJson('/api/isf/captcha/puzzle')->assertOk()->json('key');
        $targetX = Cache::get('puzzle:' . $key);
        $payload = [
            'first_name' => 'Uji',
            'last_name' => 'Puzzle',
            'whatsapp' => '081298765432',
            'age_requirement' => true,
            'email' => 'puzzle_wrong_' . uniqid() . '@example.com',
            'password' => 'Katasandi123',
            'password_confirmation' => 'Katasandi123',
            'captcha_key' => $key,
        ];

        $this->postJson('/api/isf/register', $payload + ['captcha_answer' => (string) ($targetX > 150 ? 0 : 300)])
            ->assertStatus(422)->assertJsonValidationErrors(['captcha_answer']);
        $this->postJson('/api/isf/register', $payload + ['captcha_answer' => (string) $targetX])
            ->assertStatus(422)->assertJsonValidationErrors(['captcha_answer']);
        $this->assertNull(User::where('email', $payload['email'])->first());
    }

    private function assertDecodableImage(string $uri): void
    {
        $this->assertMatchesRegularExpression('#^data:image/(png|svg\+xml);base64,#', $uri);
        [$meta, $data] = explode(',', $uri, 2);
        $bytes = base64_decode($data, true);
        $this->assertNotFalse($bytes);
        $this->assertStringNotContainsString('data:image', substr($bytes, 0, 64), 'Gambar terbungkus dua kali');

        if (str_contains($meta, 'png')) {
            $this->assertSame("\x89PNG", substr($bytes, 0, 4));
            $this->assertNotFalse(@imagecreatefromstring($bytes));
        } else {
            $this->assertStringContainsString('<svg', $bytes);
        }
    }

    /**
     * Test complete registration with puzzle captcha.
     */
    public function test_user_can_register_using_puzzle_captcha(): void
    {
        $res = $this->getJson('/api/isf/captcha/puzzle')->assertOk();
        $key = $res->json('key');
        $targetX = Cache::get('puzzle:' . $key);

        // Verify puzzle first
        $this->postJson('/api/isf/captcha/puzzle/verify', [
            'key' => $key,
            'offset' => $targetX,
        ])->assertOk();

        $email = 'puzzle_member_' . uniqid() . '@example.com';

        // Submit registration
        $regRes = $this->postJson('/api/isf/register', [
            'first_name' => 'Dewi',
            'last_name' => 'Sartika',
            'whatsapp' => '081298765432',
            'age_requirement' => true,
            'email' => $email,
            'password' => 'Katasandi123',
            'password_confirmation' => 'Katasandi123',
            'captcha_key' => $key,
            'captcha_answer' => (string) $targetX,
        ]);

        $regRes->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        // User should exist in DB with role 'member' and unverified email
        $user = User::where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertSame('Dewi Sartika', $user->name);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue($user->hasRole('member'));

        // Puzzle cache must be cleared after single use
        $this->assertFalse(Cache::has('puzzle:' . $key));
        $this->assertFalse(Cache::has('puzzle_verified:' . $key));
    }
}

