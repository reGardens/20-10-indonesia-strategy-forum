<?php

namespace Tests\Feature;

use App\Models\Isf\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Iklan Beranda di tabel isf_ads: filter tayang (status, tanggal+jam, jam tayang harian),
 * rotasi spanduk acak, antrean popup, statistik tayangan/klik, perintah ads:expire, dan CRUD CMS.
 */
class IsfAdsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Data uji terisolasi dari iklan lokal (dibatalkan oleh transaksi).
        Ad::query()->delete();
        $this->travelTo(Carbon::parse('2026-11-15 10:00:00'));
    }

    private function ad(array $attributes = []): Ad
    {
        return Ad::create(array_merge([
            'title' => 'Iklan Uji ' . uniqid(),
            'placement' => 'spanduk',
            'type' => 'banner',
            'image_url' => 'https://example.test/ad.jpg',
            'target_url' => '/register',
            'status' => 'active',
        ], $attributes));
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin Iklan',
            'email' => 'uji-iklan-' . uniqid() . '@example.test',
            'password' => Hash::make('rahasia-123'),
        ]);
    }

    public function test_spanduk_endpoint_only_returns_live_ads(): void
    {
        $live = $this->ad(['title' => 'Tayang']);
        $this->ad(['status' => 'paused']);
        $this->ad(['status' => 'expired']);
        $this->ad(['start_at' => '2026-11-15 11:00']);
        $this->ad(['end_at' => '2026-11-15 09:59']);
        $this->ad(['active_hours_start' => '12:00:00', 'active_hours_end' => '17:00:00']);
        $this->ad(['placement' => 'popup']);

        for ($i = 0; $i < 10; $i++) {
            $this->getJson('/api/isf/ads?slot=spanduk')
                ->assertOk()
                ->assertHeader('Cache-Control', 'no-store, private')
                ->assertJsonPath('data.id', $live->id)
                ->assertJsonPath('data.title', 'Tayang')
                ->assertJsonPath('data.imageUrl', 'https://example.test/ad.jpg');
        }
    }

    public function test_spanduk_endpoint_returns_null_without_live_ads(): void
    {
        $this->ad(['status' => 'paused']);

        $this->getJson('/api/isf/ads?slot=spanduk')->assertOk()->assertJsonPath('data', null);
        $this->getJson('/api/isf/ads?slot=lainnya')->assertUnprocessable();
    }

    public function test_active_hours_window_including_overnight(): void
    {
        $day = $this->ad(['title' => 'Siang', 'active_hours_start' => '08:00:00', 'active_hours_end' => '17:00:00']);
        $night = $this->ad(['title' => 'Malam', 'active_hours_start' => '22:00:00', 'active_hours_end' => '02:00:00']);

        $this->assertEquals([$day->id], Ad::query()->live()->pluck('id')->all());

        $this->travelTo(Carbon::parse('2026-11-15 23:30:00'));
        $this->assertEquals([$night->id], Ad::query()->live()->pluck('id')->all());

        $this->travelTo(Carbon::parse('2026-11-16 01:59:00'));
        $this->assertEquals([$night->id], Ad::query()->live()->pluck('id')->all());

        $this->travelTo(Carbon::parse('2026-11-16 02:00:00'));
        $this->assertEquals([], Ad::query()->live()->pluck('id')->all());
    }

    public function test_spanduk_rotation_picks_randomly_between_live_ads(): void
    {
        $a = $this->ad();
        $b = $this->ad();
        $this->ad(['status' => 'paused']);

        $counts = [$a->id => 0, $b->id => 0];
        for ($i = 0; $i < 200; $i++) {
            $counts[Ad::pickRandom(Ad::query()->live()->get())->id]++;
        }

        // Peluang sama (harapan 100 : 100). Batas longgar agar tidak flaky.
        $this->assertGreaterThan(50, $counts[$a->id]);
        $this->assertGreaterThan(50, $counts[$b->id]);
        $this->assertNull(Ad::pickRandom(collect()));
    }

    public function test_popup_endpoint_returns_live_popups_in_order_with_timing(): void
    {
        $second = $this->ad(['placement' => 'popup', 'title' => 'Kedua', 'sort_order' => 2, 'interval_minutes' => 10]);
        $first = $this->ad(['placement' => 'popup', 'title' => 'Pertama', 'sort_order' => 1, 'first_delay_seconds' => 3]);
        $this->ad(['placement' => 'popup', 'title' => 'Dijeda', 'sort_order' => 0, 'status' => 'paused']);
        $video = $this->ad([
            'placement' => 'popup', 'type' => 'video', 'title' => 'Video', 'sort_order' => 3,
            'image_url' => null, 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'video_duration' => 3600,
            'is_skippable' => false, 'skip_delay_seconds' => null,
        ]);

        $data = $this->getJson('/api/isf/ads?slot=popup')->assertOk()->json('data');

        $this->assertSame([$first->id, $second->id, $video->id], array_column($data, 'id'));
        $this->assertSame(3, $data[0]['firstDelaySeconds']);
        $this->assertTrue($data[0]['isSkippable']);
        $this->assertSame(10, $data[0]['skipDelaySeconds']);
        $this->assertNull($data[0]['intervalMinutes']);
        $this->assertSame(10, $data[1]['intervalMinutes']);
        $this->assertSame('video', $data[2]['type']);
        $this->assertSame(3600, $data[2]['videoDuration']);
        $this->assertFalse($data[2]['isSkippable']);
    }

    public function test_impression_and_click_are_counted_for_live_ads_only(): void
    {
        $live = $this->ad();
        $paused = $this->ad(['status' => 'paused']);
        $updatedAt = $live->updated_at;

        $this->postJson("/api/isf/ads/{$live->id}/impression")->assertNoContent();
        $this->postJson("/api/isf/ads/{$live->id}/impression")->assertNoContent();
        $this->postJson("/api/isf/ads/{$live->id}/click")->assertNoContent();
        $this->postJson("/api/isf/ads/{$paused->id}/impression")->assertNoContent();

        $live->refresh();
        $this->assertSame(2, $live->impressions);
        $this->assertSame(1, $live->clicks);
        $this->assertEquals($updatedAt, $live->updated_at);
        $this->assertSame(0, $paused->fresh()->impressions);
    }

    public function test_expire_command_marks_overdue_active_ads(): void
    {
        $overdue = $this->ad(['end_at' => '2026-11-15 09:00']);
        $running = $this->ad(['end_at' => '2026-11-20 09:00']);
        $paused = $this->ad(['end_at' => '2026-11-15 09:00', 'status' => 'paused']);

        $this->artisan('ads:expire')->expectsOutput('1 iklan ditandai expired.')->assertSuccessful();

        $this->assertSame('expired', $overdue->fresh()->status);
        $this->assertSame('active', $running->fresh()->status);
        $this->assertSame('paused', $paused->fresh()->status);
    }

    public function test_beranda_payload_no_longer_contains_iklan(): void
    {
        \App\Services\Isf\IsfCacheService::clearBeranda();
        $this->getJson('/api/isf/beranda')->assertOk()->assertJsonMissingPath('iklan');
        \App\Services\Isf\IsfCacheService::clearBeranda();
    }

    public function test_cms_crud_toggle_and_reorder(): void
    {
        $this->getJson('/api/cms/iklan')->assertUnauthorized();
        $this->actingAs($this->admin());

        $created = $this->postJson('/api/cms/iklan', [
            'title' => 'Spanduk Baru',
            'placement' => 'spanduk',
            'type' => 'video', // diabaikan untuk spanduk
            'image_url' => 'https://example.test/s.jpg',
            'video_url' => 'https://example.test/v.mp4',
            'start_at' => '2026-11-15 08:30',
            'end_at' => '2026-11-30 23:59',
            'active_hours_start' => '22:00',
            'active_hours_end' => '02:00',
            'first_delay_seconds' => 9,
            'kicker' => '',
            'target_url' => '',
        ])->assertCreated()->json('data');

        $this->assertSame('banner', $created['type']);
        $this->assertNull($created['video_url']);
        $this->assertNull($created['first_delay_seconds']);
        $this->assertSame('SPONSOR RESMI', $created['kicker']);
        $this->assertSame('/register', $created['target_url']);
        $this->assertSame('2026-11-15 08:30', $created['start_at']);
        $this->assertSame('08:30', $created['start_time']);
        $this->assertSame('2026-11-30', $created['end_date']);
        $this->assertSame('22:00', $created['active_hours_start']);
        $this->assertSame('active', $created['status']);
        $this->assertSame('off_hours', $created['display_state']);
        $this->assertArrayNotHasKey('weight', $created);
        $this->assertSame(1, $created['order']);

        $popup = $this->postJson('/api/cms/iklan', [
            'title' => 'Popup Video', 'placement' => 'popup', 'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'video_duration' => 1800, 'is_skippable' => true, 'skip_delay_seconds' => 10, 'interval_minutes' => 15,
        ])->assertCreated()->json('data');
        $this->assertSame(2, $popup['order']);
        $this->assertSame(15, $popup['interval_minutes']);
        $this->assertTrue($popup['is_skippable']);
        $this->assertSame(10, $popup['skip_delay_seconds']);
        $this->assertSame(1800, $popup['video_duration']);
        $this->assertSame('live', $popup['display_state']);

        // Validasi jadwal & jam tayang
        $this->putJson("/api/cms/iklan/{$created['id']}", [
            'title' => 'X', 'placement' => 'spanduk', 'start_at' => '2026-11-20 10:00', 'end_at' => '2026-11-19 10:00',
            'active_hours_start' => '08:00',
        ])->assertUnprocessable()->assertJsonValidationErrors(['end_at', 'active_hours_end']);

        // Waktu selesai sudah lewat → otomatis kedaluwarsa.
        $updated = $this->putJson("/api/cms/iklan/{$created['id']}", [
            'title' => 'Spanduk Lewat', 'placement' => 'spanduk', 'end_at' => '2026-11-14 10:00', 'status' => 'active',
        ])->assertOk()->json('data');
        $this->assertSame('expired', $updated['status']);
        $this->assertNull($updated['active_hours_start']);

        // Toggle: kedaluwarsa dengan tanggal lewat tidak bisa diaktifkan; popup aktif → dijeda → aktif.
        $this->postJson("/api/cms/iklan/{$created['id']}/toggle")->assertUnprocessable();
        $this->postJson("/api/cms/iklan/{$popup['id']}/toggle")->assertOk()->assertJsonPath('data.status', 'paused');
        $this->postJson("/api/cms/iklan/{$popup['id']}/toggle")->assertOk()->assertJsonPath('data.status', 'active');

        $this->postJson('/api/cms/iklan/reorder', ['items' => [
            ['id' => $popup['id'], 'order' => 1],
            ['id' => $created['id'], 'order' => 2],
        ]])->assertOk();

        $list = $this->getJson('/api/cms/iklan')->assertOk()->json('data');
        $this->assertSame([$popup['id'], $created['id']], array_column($list, 'id'));
        $this->assertSame([1, 2], array_column($list, 'order'));

        $this->getJson("/api/cms/iklan/{$popup['id']}")->assertOk()->assertJsonPath('data.title', 'Popup Video');
        $this->deleteJson("/api/cms/iklan/{$popup['id']}")->assertOk();
        $this->getJson("/api/cms/iklan/{$popup['id']}")->assertNotFound();
    }

    public function test_cms_index_expires_overdue_ads_without_cron(): void
    {
        $this->actingAs($this->admin());
        $ad = $this->ad(['end_at' => '2026-11-15 09:00']);

        $this->getJson('/api/cms/iklan')->assertOk()->assertJsonPath('data.0.status', 'expired');
        $this->assertSame('expired', $ad->fresh()->status);
    }
}
