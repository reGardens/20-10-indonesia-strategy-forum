<?php

namespace Tests\Feature;

use App\Models\Isf\Setting;
use App\Models\User;
use App\Services\Isf\IsfCacheService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IsfFooterSettingTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        IsfCacheService::clearAll();
    }

    protected function tearDown(): void
    {
        IsfCacheService::clearAll();
        parent::tearDown();
    }

    private function superAdmin(): User
    {
        $user = User::create([
            'name' => 'Admin Footer',
            'email' => 'admin-footer-' . uniqid() . '@example.com',
            'password' => Hash::make('secret123'),
        ]);
        $user->assignRole('super-admin');

        return $user;
    }

    public function test_public_footer_api_returns_structured_default_data(): void
    {
        $response = $this->getJson(route('api.isf.footer'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'about_us',
                    'contact_email',
                    'contact_phone',
                    'contact_address',
                    'terms_conditions',
                    'privacy_policy',
                    'nav_links',
                    'legal_links',
                    'social_links',
                    'copyright',
                    'disclaimer',
                ],
            ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data['about_us']);
        $this->assertNotEmpty($data['nav_links']);
        $this->assertNotEmpty($data['legal_links']);
        $this->assertNotEmpty($data['social_links']);
    }

    public function test_guest_cannot_access_cms_footer_api(): void
    {
        $response = $this->getJson(route('api.cms.footer.show'));
        $response->assertUnauthorized();
    }

    public function test_admin_can_view_cms_footer_settings(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->getJson(route('api.cms.footer.show'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'about_us',
                    'nav_links',
                    'legal_links',
                    'social_links',
                    'copyright',
                    'disclaimer',
                ],
            ]);
    }

    public function test_admin_can_update_footer_settings_and_public_api_reflects_changes(): void
    {
        $admin = $this->superAdmin();

        $payload = [
            'about_us' => 'Indonesia Strategy Forum (ISF) versi kustom pembaruan footer.',
            'contact_email' => 'redaksi@indonesiastrategyforum.com',
            'contact_phone' => '+62 811-999-000',
            'contact_address' => 'Gedung ISF Lantai 10, Jakarta Pusat',
            'terms_conditions' => '<h2>Syarat Uji</h2><p>Ketentuan yang diperbarui.</p>',
            'privacy_policy' => '<h2>Privasi Uji</h2><p>Kebijakan privasi yang diperbarui.</p>',
            'nav_links' => [
                ['label' => 'Tentang ISF', 'url' => '/tentang-isf', 'is_external' => false],
                ['label' => 'Pedoman Riset', 'url' => '/pedoman-riset', 'is_external' => false],
            ],
            'legal_links' => [
                ['label' => 'Syarat & Ketentuan', 'url' => '/syarat-ketentuan', 'is_external' => false],
                ['label' => 'Pedoman Media Siber', 'url' => '/pedoman-media-siber', 'is_external' => false],
            ],
            'social_links' => [
                ['platform' => 'twitter', 'label' => 'X', 'url' => 'https://x.com/isf_official'],
                ['platform' => 'linkedin', 'label' => 'LinkedIn ISF', 'url' => 'https://linkedin.com/company/isf'],
            ],
            'copyright' => '© 2026 ISF Media Nusantara. All rights reserved.',
            'disclaimer' => 'Klausul penafian hukum yang telah disesuaikan melalui panel CMS.',
        ];

        $updateResponse = $this->actingAs($admin)->putJson(route('api.cms.footer.update'), $payload);

        $updateResponse->assertOk()
            ->assertJsonPath('data.about_us', $payload['about_us'])
            ->assertJsonPath('data.contact_email', $payload['contact_email'])
            ->assertJsonPath('data.terms_conditions', $payload['terms_conditions'])
            ->assertJsonPath('data.copyright', $payload['copyright']);

        // Pastikan API publik langsung menampilkan data yang telah diupdate
        $publicResponse = $this->getJson(route('api.isf.footer'));
        $publicResponse->assertOk()
            ->assertJsonPath('data.about_us', $payload['about_us'])
            ->assertJsonPath('data.contact_phone', $payload['contact_phone'])
            ->assertJsonPath('data.terms_conditions', $payload['terms_conditions'])
            ->assertJsonPath('data.copyright', $payload['copyright']);
    }

    public function test_cms_footer_inertia_page_renders_for_admin(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->get(route('cms.isf.footer.index'));

        $response->assertOk();
    }
}
