<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_access_landing_page_at_root(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Landing'));
    }

    public function test_guest_can_access_showcase_route(): void
    {
        $response = $this->get('/landing');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Landing'));
    }

    public function test_authenticated_user_visiting_root_redirects_to_dashboard(): void
    {
        $user = User::create([
            'name' => 'Alex Rivera',
            'email' => 'alex@synthex.internal',
            'password' => 'password123',
            'role' => 'owner',
        ]);

        $response = $this->actingAs($user)->get('/');
        $response->assertRedirect(route('dashboard'));
    }
}
