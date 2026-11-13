<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AI\TextGeneratorManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiApiKeyEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Alex Rivera',
            'email' => 'alex@synthex.internal',
            'password' => bcrypt('password123'),
            'role' => 'owner',
        ]);
    }

    public function test_ai_endpoints_are_strictly_disabled_with_422_when_user_has_no_ai_api_key_in_settings(): void
    {
        // Ensure settings has empty api keys
        TextGeneratorManager::saveConfig([
            'active_provider' => 'z_ai',
            'providers' => [
                'z_ai' => ['api_key' => '', 'model' => 'z-ai-large'],
                'gemini' => ['api_key' => '', 'model' => 'gemini-3.1-flash-lite'],
                'openai' => ['api_key' => '', 'model' => 'gpt-4o'],
                'claude' => ['api_key' => '', 'model' => 'claude-3-5-sonnet'],
                'custom' => ['api_key' => '', 'base_url' => '', 'model' => 'llama3.2'],
            ],
        ]);

        $this->assertFalse(TextGeneratorManager::hasConfiguredApiKey());

        // 1. Draft AI generation is blocked with 422
        $draftRes = $this->actingAs($this->user)
            ->postJson('/api/v1/drafts/ai/generate', [
                'topic' => 'Quantum computing in distributed consensus',
                'platforms' => ['linkedin'],
            ]);

        $draftRes->assertStatus(422)
            ->assertJsonFragment(['success' => false]);
        $this->assertStringContainsString('No AI API key', $draftRes->json('message'));

        // 2. Media AI generation is blocked with 422
        $mediaRes = $this->actingAs($this->user)
            ->postJson('/api/v1/media/ai/generate', [
                'prompt' => 'A hyper-minimalist server cluster layout',
                'provider' => 'z_ai',
            ]);

        $mediaRes->assertStatus(422)
            ->assertJsonFragment(['success' => false]);
        $this->assertStringContainsString('API key', $mediaRes->json('message'));

        // 3. Analytics AI insights is blocked with 422
        $analyticsRes = $this->actingAs($this->user)
            ->postJson('/api/v1/analytics/ai-insights');

        $analyticsRes->assertStatus(422)
            ->assertJsonFragment(['success' => false]);
        $this->assertStringContainsString('No AI API key', $analyticsRes->json('message'));
    }

    public function test_inertia_pages_pass_has_ai_key_false_when_no_key_is_configured(): void
    {
        TextGeneratorManager::saveConfig([
            'active_provider' => 'gemini',
            'providers' => [
                'gemini' => ['api_key' => '', 'model' => 'gemini-3.1-flash-lite'],
                'z_ai' => ['api_key' => '', 'model' => 'z-ai-large'],
                'openai' => ['api_key' => '', 'model' => 'gpt-4o'],
                'claude' => ['api_key' => '', 'model' => 'claude-3-5-sonnet'],
                'custom' => ['api_key' => '', 'base_url' => '', 'model' => 'llama3.2'],
            ],
        ]);

        $routes = ['/dashboard', '/inbox', '/calendar', '/trends', '/media', '/analytics'];

        foreach ($routes as $route) {
            $res = $this->actingAs($this->user)->get($route);
            $res->assertStatus(200);
            $res->assertInertia(fn ($page) => $page->where('hasAiKey', false));
        }
    }

    public function test_saving_api_key_in_settings_enables_ai_functions_and_returns_has_ai_key_true(): void
    {
        // Save a valid key in Settings
        TextGeneratorManager::saveConfig([
            'active_provider' => 'gemini',
            'providers' => [
                'gemini' => ['api_key' => 'mock_gemini_api_key_valid', 'model' => 'gemini-3.1-flash-lite'],
            ],
        ]);

        $this->assertTrue(TextGeneratorManager::hasConfiguredApiKey());

        // 1. All routes report hasAiKey true
        $routes = ['/dashboard', '/inbox', '/calendar', '/trends', '/media', '/analytics'];

        foreach ($routes as $route) {
            $res = $this->actingAs($this->user)->get($route);
            $res->assertStatus(200);
            $res->assertInertia(fn ($page) => $page->where('hasAiKey', true));
        }

        // 2. Draft AI generation succeeds
        $draftRes = $this->actingAs($this->user)
            ->postJson('/api/v1/drafts/ai/generate', [
                'topic' => 'Database indexing trade-offs at 1M QPS',
                'platforms' => ['linkedin', 'x_twitter'],
            ]);

        $draftRes->assertSuccessful()
            ->assertJsonFragment(['success' => true]);

        // 3. Analytics AI insights succeeds
        $analyticsRes = $this->actingAs($this->user)
            ->postJson('/api/v1/analytics/ai-insights');

        $analyticsRes->assertSuccessful()
            ->assertJsonFragment(['success' => true]);

        // 4. Media generation succeeds
        $mediaRes = $this->actingAs($this->user)
            ->postJson('/api/v1/media/ai/generate', [
                'prompt' => 'Database indexing B-tree visual infographic',
                'provider' => 'gemini',
            ]);

        $mediaRes->assertSuccessful()
            ->assertJsonFragment(['success' => true]);
    }
}
