<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('webpush.vapid.public_key', 'test-public-key');
        config()->set('webpush.vapid.private_key', 'test-private-key');
    }

    public function test_authenticated_user_can_enable_phone_notifications(): void
    {
        $user = User::factory()->create(['approval_status' => 'approved']);
        $endpoint = 'https://fcm.googleapis.com/fcm/send/device-token';

        $this->actingAs($user)
            ->postJson(route('notifications.phone-subscription.store'), [
                'endpoint' => $endpoint,
                'keys' => [
                    'p256dh' => 'browser-public-key',
                    'auth' => 'browser-auth-token',
                ],
                'content_encoding' => 'aes128gcm',
            ])
            ->assertOk()
            ->assertJson(['subscribed' => true]);

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_type' => User::class,
            'subscribable_id' => $user->id,
            'endpoint' => $endpoint,
            'content_encoding' => 'aes128gcm',
        ]);
    }

    public function test_notification_menu_offers_phone_alerts_when_server_is_configured(): void
    {
        $user = User::factory()->create(['approval_status' => 'approved']);

        $this->actingAs($user)
            ->blade('<x-notification-menu :user="$user" />', ['user' => $user])
            ->assertSee('data-phone-push', false)
            ->assertSee('Enable phone alerts');
    }

    public function test_user_can_disable_phone_notifications(): void
    {
        $user = User::factory()->create(['approval_status' => 'approved']);
        $endpoint = 'https://fcm.googleapis.com/fcm/send/device-token';
        $user->updatePushSubscription($endpoint, 'browser-public-key', 'browser-auth-token', 'aes128gcm');

        $this->actingAs($user)
            ->deleteJson(route('notifications.phone-subscription.destroy'), [
                'endpoint' => $endpoint,
            ])
            ->assertOk()
            ->assertJson(['subscribed' => false]);

        $this->assertDatabaseMissing('push_subscriptions', [
            'subscribable_id' => $user->id,
            'endpoint' => $endpoint,
        ]);
    }

    public function test_guest_cannot_register_a_phone_subscription(): void
    {
        $this->postJson(route('notifications.phone-subscription.store'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/device-token',
            'keys' => ['p256dh' => 'key', 'auth' => 'token'],
        ])->assertUnauthorized();
    }
}
