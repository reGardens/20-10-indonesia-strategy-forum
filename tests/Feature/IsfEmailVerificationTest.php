<?php

namespace Tests\Feature;

use App\Mail\MemberEmailVerificationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IsfEmailVerificationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    /**
     * Test registration dispatches email verification mail with signed URL.
     */
    public function test_registration_dispatches_email_verification_mail(): void
    {
        Mail::fake();

        $email = 'verify_test_' . uniqid() . '@example.com';

        $response = $this->postJson('/api/isf/register', [
            'first_name' => 'Raden',
            'last_name' => 'Ajeng',
            'whatsapp' => '081234567890',
            'age_requirement' => true,
            'email' => $email,
            'password' => 'Katasandi123',
            'password_confirmation' => 'Katasandi123',
            'captcha_key' => 'test-key',
            'captcha_answer' => 'TEST_CAPTCHA',
        ]);

        $response->assertStatus(201);

        Mail::assertSent(MemberEmailVerificationMail::class, function ($mail) use ($email) {
            return $mail->hasTo($email)
                && ! empty($mail->verificationUrl)
                && str_contains($mail->verificationUrl, '/verifikasi-email/');
        });
    }

    /**
     * Test clicking valid signed URL verifies email and logs user in to member portal.
     */
    public function test_user_can_verify_email_via_signed_url(): void
    {
        $user = User::create([
            'name' => 'Kartini Test',
            'first_name' => 'Kartini',
            'last_name' => 'Test',
            'whatsapp' => '6281234567890',
            'age_verified' => true,
            'email' => 'kartini_' . uniqid() . '@example.com',
            'password' => Hash::make('Katasandi123'),
            'email_verified_at' => null,
        ]);
        $user->assignRole('member');

        $this->assertNull($user->email_verified_at);

        $signedUrl = URL::temporarySignedRoute(
            'isf.verification.verify',
            now()->addHours(24),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->get($signedUrl);

        $response->assertRedirect('/anggota?verified=success');

        // Refresh user from DB
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);

        // User should be authenticated
        $this->assertTrue(Auth::check());
        $this->assertSame($user->id, Auth::id());
    }

    /**
     * Test verification URL with invalid signature is rejected.
     */
    public function test_email_verification_rejects_invalid_signature(): void
    {
        $user = User::create([
            'name' => 'Invalid Sig Test',
            'first_name' => 'Invalid',
            'last_name' => 'Sig',
            'whatsapp' => '6281234567891',
            'age_verified' => true,
            'email' => 'invalid_' . uniqid() . '@example.com',
            'password' => Hash::make('Katasandi123'),
            'email_verified_at' => null,
        ]);

        $invalidUrl = "/verifikasi-email/{$user->id}/" . sha1($user->email) . "?signature=tampered_signature";

        $response = $this->get($invalidUrl);

        $response->assertRedirect('/anggota?verified=invalid');
        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    /**
     * Test already verified user redirected with already status.
     */
    public function test_already_verified_user_is_redirected_with_already_status(): void
    {
        $user = User::create([
            'name' => 'Already Verified',
            'first_name' => 'Already',
            'last_name' => 'Verified',
            'whatsapp' => '6281234567892',
            'age_verified' => true,
            'email' => 'already_' . uniqid() . '@example.com',
            'password' => Hash::make('Katasandi123'),
            'email_verified_at' => now(),
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'isf.verification.verify',
            now()->addHours(24),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->get($signedUrl);

        $response->assertRedirect('/anggota?verified=already');
    }
}

