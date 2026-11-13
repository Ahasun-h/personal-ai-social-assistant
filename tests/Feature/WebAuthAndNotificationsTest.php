<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebAuthAndNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Alex Rivera',
            'email' => 'alex@synthex.internal',
            'password' => 'password123',
            'role' => 'owner',
        ]);
    }

    public function test_login_page_is_accessible_by_guests(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_authenticated_user_visiting_login_is_redirected_to_dashboard(): void
    {
        $response = $this->actingAs($this->user)->get('/login');
        $response->assertRedirect(route('dashboard'));
    }

    public function test_unauthenticated_user_visiting_dashboard_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_user_can_authenticate_via_web_login(): void
    {
        $response = $this->post('/login', [
            'email' => 'alex@synthex.internal',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_invalid_login_credentials_are_rejected(): void
    {
        $response = $this->post('/login', [
            'email' => 'alex@synthex.internal',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_can_log_out(): void
    {
        $response = $this->actingAs($this->user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_user_notifications_api_and_read_actions(): void
    {
        $notification = Notification::create([
            'user_id' => $this->user->id,
            'type' => Notification::TYPE_PUBLISH_SUCCESS,
            'title' => 'Post Published',
            'message' => 'Post published to LinkedIn successfully.',
            'read_at' => null,
        ]);

        // Fetch notifications
        $response = $this->actingAs($this->user)->getJson('/api/v1/notifications');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.unread_count', 1);

        // Mark single notification as read
        $readResponse = $this->actingAs($this->user)->postJson("/api/v1/notifications/{$notification->id}/read");
        $readResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.unread_count', 0);

        $this->assertNotNull($notification->fresh()->read_at);

        // Create another notification and test mark all as read
        Notification::create([
            'user_id' => $this->user->id,
            'type' => Notification::TYPE_BUDGET_WARNING,
            'title' => 'Budget Warning',
            'message' => 'Approaching limit.',
            'read_at' => null,
        ]);

        $markAllResponse = $this->actingAs($this->user)->postJson('/api/v1/notifications/read-all');
        $markAllResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.unread_count', 0);
    }
}
