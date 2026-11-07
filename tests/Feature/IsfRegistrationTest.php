<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IsfRegistrationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure 'user' role exists
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // Bypass rate limiting during test executions
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    /**
     * Test captcha generation endpoint.
     */
    public function test_captcha_endpoint_generates_valid_svg_challenge(): void
    {
        $response = $this->getJson('/api/isf/captcha');

        $response->assertStatus(200)
            ->assertJsonStructure(['key', 'image']);

        $key = $response->json('key');
        $this->assertNotEmpty($key);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $response->json('image'));
        $this->assertTrue(Cache::has('captcha:' . $key));
    }

    /**
     * Test registration validation fails when required fields are missing.
     */
    public function test_registration_validation_fails_on_empty_request(): void
    {
        $response = $this->postJson('/api/isf/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'first_name',
                'last_name',
                'zip_code',
                'age_requirement',
                'email',
                'password',
                'password_confirmation',
                'captcha_key',
                'captcha_answer',
            ]);
    }

    /**
     * Test registration validation fails when captcha is incorrect.
     */
    public function test_registration_fails_when_captcha_is_wrong(): void
    {
        $response = $this->postJson('/api/isf/register', [
            'first_name' => 'Budi',
            'last_name' => 'Santoso',
            'zip_code' => '12345',
            'age_requirement' => true,
            'email' => 'budi_' . uniqid() . '@example.com',
            'password' => 'Rahasia123',
            'password_confirmation' => 'Rahasia123',
            'captcha_key' => 'invalid-key',
            'captcha_answer' => 'WRONG',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['captcha_answer']);
    }

    /**
     * Test registration validation fails when age requirement is false.
     */
    public function test_registration_fails_when_age_not_accepted(): void
    {
        $response = $this->postJson('/api/isf/register', [
            'first_name' => 'Budi',
            'last_name' => 'Santoso',
            'zip_code' => '12345',
            'age_requirement' => false,
            'email' => 'budi_' . uniqid() . '@example.com',
            'password' => 'Rahasia123',
            'password_confirmation' => 'Rahasia123',
            'captcha_key' => 'test-key',
            'captcha_answer' => 'TEST_CAPTCHA',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['age_requirement']);
    }

    /**
     * Test successful registration creates user in DB, validates captcha, and assigns role 'user'.
     */
    public function test_user_can_register_with_valid_captcha_and_gets_user_role(): void
    {
        // 1. Fetch real captcha
        $captchaRes = $this->getJson('/api/isf/captcha');
        $captchaKey = $captchaRes->json('key');
        $expectedCode = Cache::get('captcha:' . $captchaKey);

        $email = 'member_' . uniqid() . '@example.com';

        // 2. Submit with valid captcha code
        $response = $this->postJson('/api/isf/register', [
            'first_name' => 'Ahmad',
            'last_name' => 'Hidayat',
            'zip_code' => '10110',
            'age_requirement' => true,
            'email' => $email,
            'password' => 'Katasandi123',
            'password_confirmation' => 'Katasandi123',
            'captcha_key' => $captchaKey,
            'captcha_answer' => $expectedCode,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        // Captcha should be invalidated
        $this->assertFalse(Cache::has('captcha:' . $captchaKey));

        // User should be created
        $user = User::where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertSame('Ahmad Hidayat', $user->name);
        $this->assertSame('Ahmad', $user->first_name);
        $this->assertSame('Hidayat', $user->last_name);
        $this->assertSame('10110', $user->zip_code);
        $this->assertTrue((bool) $user->age_verified);

        // Verify Spatie role 'user' is automatically assigned
        $this->assertTrue($user->hasRole('user'));
    }
}
