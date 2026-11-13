<?php

namespace Tests\Feature;

use App\Models\Isf\Tokoh;
use App\Models\Isf\TokohReview;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Indeks Tokoh (#41): rating & ulasan publik, wajib login untuk mengirim, anonim menyembunyikan identitas.
 */
class IsfTokohReviewTest extends TestCase
{
    use DatabaseTransactions;

    private Tokoh $tokoh;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokoh = $this->makeTokoh('uji-indeks-' . uniqid(), 'terbit');
    }

    private function makeTokoh(string $slug, string $status): Tokoh
    {
        return Tokoh::create([
            'slug' => $slug,
            'name' => 'Tokoh Uji',
            'position' => 'Anggota DPR',
            'stats' => [],
            'skor_value' => 50,
            'skor_period' => '30 hari',
            'skor_explanation' => 'Uji',
            'matrix_columns' => [],
            'matrix_rows' => [],
            'related_articles' => [],
            'calendar_year' => 2026,
            'calendar_month' => 11,
            'calendar_events' => [],
            'status' => $status,
        ]);
    }

    private function makeUser(string $name = 'Rina Kartika'): User
    {
        return User::create([
            'name' => $name,
            'email' => 'uji-' . uniqid() . '@example.test',
            'password' => Hash::make('rahasia-123'),
        ]);
    }

    private function url(?Tokoh $tokoh = null): string
    {
        return '/api/isf/tokoh/' . ($tokoh ?? $this->tokoh)->slug . '/ulasan';
    }

    public function test_guest_can_read_empty_summary_and_is_not_authenticated(): void
    {
        $json = $this->getJson($this->url())->assertOk()->json();

        $this->assertSame(0, $json['summary']['total']);
        $this->assertEquals(0, $json['summary']['average']);
        $this->assertSame(['5' => 0, '4' => 0, '3' => 0, '2' => 0, '1' => 0], $json['summary']['distribution']);
        $this->assertFalse($json['viewer']['authenticated']);
        $this->assertNull($json['viewer']['myReview']);
        $this->assertSame([], $json['reviews']['data']);
    }

    public function test_guest_cannot_submit_review(): void
    {
        $this->postJson($this->url(), ['rating' => 5, 'body' => 'Bagus sekali'])->assertUnauthorized();
        $this->assertSame(0, TokohReview::where('tokoh_id', $this->tokoh->id)->count());
    }

    public function test_submit_updates_aggregate_score_immediately(): void
    {
        TokohReview::create(['tokoh_id' => $this->tokoh->id, 'user_id' => $this->makeUser('A B')->id, 'rating' => 2, 'body' => 'Kurang']);

        $json = $this->actingAs($this->makeUser())
            ->postJson($this->url(), ['rating' => 5, 'body' => 'Kinerja sangat baik', 'is_anonymous' => false])
            ->assertCreated()->json();

        $this->assertSame(2, $json['summary']['total']);
        $this->assertEquals(3.5, $json['summary']['average']);
        $this->assertSame(1, $json['summary']['suka']);
        $this->assertSame(1, $json['summary']['tidakSuka']);
        $this->assertSame(5, $json['viewer']['myReview']['rating']);
        $this->assertSame('Rina Kartika', $json['reviews']['data'][0]['author']['name']);
        $this->assertSame('RK', $json['reviews']['data'][0]['author']['initials']);
        $this->assertTrue($json['reviews']['data'][0]['isMine']);
    }

    public function test_resubmit_updates_existing_review_instead_of_duplicating(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)->postJson($this->url(), ['rating' => 5, 'body' => 'Awalnya bagus'])->assertCreated();
        $json = $this->actingAs($user)->postJson($this->url(), ['rating' => 1, 'body' => 'Ternyata mengecewakan'])
            ->assertOk()->json();

        $this->assertSame(1, TokohReview::where('tokoh_id', $this->tokoh->id)->count());
        $this->assertSame(1, $json['summary']['total']);
        $this->assertSame(0, $json['summary']['suka']);
        $this->assertSame(1, $json['summary']['tidakSuka']);
    }

    public function test_anonymous_review_hides_identity_in_public_list(): void
    {
        $this->actingAs($this->makeUser())
            ->postJson($this->url(), ['rating' => 4, 'body' => 'Rahasia tapi jujur', 'is_anonymous' => true])
            ->assertCreated();

        // Dibaca sebagai tamu: tidak boleh ada nama / inisial / id user.
        $this->app['auth']->forgetGuards();
        $response = $this->getJson($this->url() . '?tab=suka')->assertOk();
        $item = $response->json('reviews.data.0');

        $this->assertSame('Anonim', $item['author']['name']);
        $this->assertNull($item['author']['initials']);
        $this->assertArrayNotHasKey('user_id', $item);
        $this->assertFalse($item['isMine']);
        $this->assertStringNotContainsString('Rina', $response->getContent());
    }

    public function test_tabs_split_by_rating_and_three_stars_only_counted_in_summary(): void
    {
        foreach ([5, 3, 1] as $rating) {
            TokohReview::create(['tokoh_id' => $this->tokoh->id, 'user_id' => $this->makeUser()->id, 'rating' => $rating, 'body' => "Bintang {$rating}"]);
        }

        $suka = $this->getJson($this->url() . '?tab=suka')->assertOk()->json();
        $tidak = $this->getJson($this->url() . '?tab=tidak-suka')->assertOk()->json();

        $this->assertSame(3, $suka['summary']['total']);
        $this->assertEquals(3.0, $suka['summary']['average']);
        $this->assertSame([5], array_column($suka['reviews']['data'], 'rating'));
        $this->assertSame([1], array_column($tidak['reviews']['data'], 'rating'));
    }

    public function test_validation_rejects_invalid_rating_and_empty_body(): void
    {
        $this->actingAs($this->makeUser())
            ->postJson($this->url(), ['rating' => 6, 'body' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rating', 'body']);
    }

    public function test_unpublished_tokoh_returns_404(): void
    {
        $draf = $this->makeTokoh('uji-draf-' . uniqid(), 'draf');

        $this->getJson($this->url($draf))->assertNotFound();
        $this->actingAs($this->makeUser())->postJson($this->url($draf), ['rating' => 5, 'body' => 'Halo'])->assertNotFound();
    }

    public function test_login_redirects_back_only_to_safe_internal_path(): void
    {
        $user = $this->makeUser();
        $credentials = ['email' => $user->email, 'password' => 'rahasia-123'];

        $this->post('/login', $credentials + ['redirect' => '/tokoh/budi-santoso'])->assertRedirect('/tokoh/budi-santoso');
        $this->post('/logout');

        foreach (['//evil.example', 'https://evil.example', '/\\evil.example', '/login'] as $unsafe) {
            $this->post('/login', $credentials + ['redirect' => $unsafe])->assertRedirect('/cms/dashboard');
            $this->post('/logout');
        }

        // Form login Inertia: tujuan SPA blade → full page visit (409 + X-Inertia-Location).
        $this->withHeaders(['X-Inertia' => 'true'])
            ->post('/login', $credentials + ['redirect' => '/tokoh/budi-santoso'])
            ->assertStatus(409)->assertHeader('X-Inertia-Location', '/tokoh/budi-santoso');
        $this->flushHeaders();
        $this->post('/logout');

        // Tanpa redirect: perilaku lama tidak berubah.
        $this->post('/login', $credentials)->assertRedirect('/cms/dashboard');
        // Member biasa (tanpa role admin) bisa membuka dashboard CMS, bukan 403.
        $this->get('/cms/dashboard')->assertOk();
    }

    public function test_failed_login_keeps_redirect_for_next_attempt(): void
    {
        $user = $this->makeUser();
        $loginUrl = '/login?redirect=' . urlencode('/tokoh/budi-santoso');

        // Password salah: kembali ke halaman login yang sama (query redirect tetap ada).
        $this->from($loginUrl)
            ->post('/login', ['email' => $user->email, 'password' => 'salah', 'redirect' => '/tokoh/budi-santoso'])
            ->assertRedirect($loginUrl)
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => $user->email, 'password' => 'rahasia-123', 'redirect' => '/tokoh/budi-santoso'])
            ->assertRedirect('/tokoh/budi-santoso');
    }

    public function test_already_logged_in_user_opening_login_with_redirect_goes_back(): void
    {
        $this->actingAs($this->makeUser());

        $this->get('/login?redirect=' . urlencode('/tokoh/budi-santoso'))->assertRedirect('/tokoh/budi-santoso');
        $this->get('/login?redirect=' . urlencode('//evil.example'))->assertRedirect('/beranda');
        // Tanpa redirect: perilaku lama.
        $this->get('/login')->assertRedirect('/beranda');
        $this->get('/register?redirect=' . urlencode('/tokoh/budi-santoso'))->assertOk();
    }
}
