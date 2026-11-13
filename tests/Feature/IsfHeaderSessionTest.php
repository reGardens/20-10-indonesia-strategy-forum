<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Header publik mengikuti status login sebenarnya: GET /api/isf/me + shell blade (isf-masthead).
 */
class IsfHeaderSessionTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $name = 'Rina Kartika'): User
    {
        return User::create([
            'name' => $name,
            'email' => 'uji-header-' . uniqid() . '@example.test',
            'password' => Hash::make('rahasia-123'),
        ]);
    }

    public function test_me_endpoint_for_guest(): void
    {
        $this->getJson('/api/isf/me')
            ->assertOk()
            ->assertExactJson(['authenticated' => false, 'name' => null, 'initials' => null]);
    }

    public function test_me_endpoint_for_logged_in_user_returns_only_name_and_initials(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->getJson('/api/isf/me')->assertOk()
            // Issue #42: ikut status Nomor WhatsApp milik user sendiri (tanpa email/role/id)
            ->assertExactJson(['authenticated' => true, 'name' => 'Rina Kartika', 'initials' => 'RK',
                'whatsapp' => null, 'whatsappDisplay' => null, 'whatsappVerified' => false, 'whatsappVerification' => false]);

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringNotContainsString($user->email, $response->getContent());
    }

    public function test_shell_header_shows_login_for_guest(): void
    {
        $html = $this->get('/tokoh/budi-santoso')->assertOk()->getContent();

        $this->assertStringContainsString('>Login</a>', $html);
        $this->assertStringNotContainsString('aria-label="Keluar"', $html);
    }

    public function test_shell_header_shows_avatar_menu_trigger_when_logged_in(): void
    {
        $html = $this->actingAs($this->makeUser())->get('/tokoh/budi-santoso')->assertOk()->getContent();

        $this->assertStringNotContainsString('>Login</a>', $html);
        // Shell = pemicu menu profil (ikon avatar saja), nama & Keluar ada di dalam menu (Vue).
        $this->assertMatchesRegularExpression('#<button type="button" aria-haspopup="menu" aria-expanded="false" aria-label="Menu akun Rina Kartika"[^>]*>RK</button>#', $html);
        // Status login dikirim ke Vue sejak awal (tanpa menunggu /api/isf/me).
        $this->assertStringContainsString('window.__ISF_SESSION__ = {"authenticated":true,"name":"Rina Kartika","whatsapp":null,"whatsappDisplay":null,"whatsappVerified":false,"whatsappVerification":false}', $html);
    }

    public function test_spa_seeds_guest_session_for_guest(): void
    {
        $html = $this->get('/beranda')->assertOk()->getContent();

        $this->assertStringContainsString('window.__ISF_SESSION__ = {"authenticated":false,"name":null}', $html);
    }

    public function test_logout_from_header_ends_session(): void
    {
        $this->actingAs($this->makeUser());

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->getJson('/api/isf/me')->assertJson(['authenticated' => false]);
    }
}
