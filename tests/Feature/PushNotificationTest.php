<?php

namespace Tests\Feature;

use App\Models\User;
use NotificationChannels\WebPush\PushSubscription;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    protected function tearDown(): void
    {
        PushSubscription::where('endpoint', 'like', '%test-endpoint-%')->delete();
        parent::tearDown();
    }

    public function test_can_retrieve_vapid_public_key(): void
    {
        $response = $this->getJson(route('api.isf.push.vapid-key'));

        $response->assertStatus(200)
            ->assertJsonStructure(['public_key', 'enabled']);
    }

    public function test_guest_can_subscribe_to_push_notifications(): void
    {
        $payload = [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-guest-' . uniqid(),
            'keys' => [
                'p256dh' => 'BNcRdreALRFXTkOOUHK1EtK2wtaz5Ry4YfYCA_0QT9P04q2Cg2v1qzZ31yP80S56n',
                'auth' => 'tBHItJI5svbpez7KI4CCXg',
            ],
            'content_encoding' => 'aes128gcm',
        ];

        $response = $this->postJson(route('api.isf.push.subscribe'), $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint' => $payload['endpoint'],
            'public_key' => $payload['keys']['p256dh'],
            'auth_token' => $payload['keys']['auth'],
            'subscribable_id' => null,
        ]);
    }

    public function test_authenticated_user_can_subscribe_and_links_subscribable(): void
    {
        $user = User::first() ?? User::create([
            'name' => 'Test User',
            'email' => 'testuser_' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $payload = [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-user-' . uniqid(),
            'keys' => [
                'p256dh' => 'BNcRdreALRFXTkOOUHK1EtK2wtaz5Ry4YfYCA_0QT9P04q2Cg2v1qzZ31yP80S56n',
                'auth' => 'tBHItJI5svbpez7KI4CCXg',
            ],
            'content_encoding' => 'aes128gcm',
        ];

        $response = $this->actingAs($user)->postJson(route('api.isf.push.subscribe'), $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint' => $payload['endpoint'],
            'subscribable_id' => $user->id,
            'subscribable_type' => User::class,
        ]);
    }

    public function test_can_unsubscribe_from_push_notifications(): void
    {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/test-endpoint-unsub-' . uniqid();
        PushSubscription::create([
            'endpoint' => $endpoint,
            'public_key' => 'dummy_key',
            'auth_token' => 'dummy_token',
        ]);

        $this->assertDatabaseHas('push_subscriptions', ['endpoint' => $endpoint]);

        $response = $this->postJson(route('api.isf.push.unsubscribe'), [
            'endpoint' => $endpoint,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => $endpoint]);
    }

    public function test_can_fetch_notifications_and_mark_as_read(): void
    {
        $response = $this->getJson(route('api.isf.push.notifications'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'notifications',
                'unread_count',
            ]);

        // Test marking single notification as read
        $markRes = $this->postJson(route('api.isf.push.notifications.read'), [
            'id' => 'test-item-123',
        ]);
        $markRes->assertStatus(200)->assertJson(['success' => true]);

        // Test marking all notifications as read
        $markAllRes = $this->postJson(route('api.isf.push.notifications.read'), [
            'all' => true,
        ]);
        $markAllRes->assertStatus(200)->assertJson(['success' => true]);
    }
}
