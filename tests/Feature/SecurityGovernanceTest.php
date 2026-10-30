<?php

namespace Tests\Feature;

use App\Contracts\AI\TextGeneratorInterface;
use App\Models\Draft;
use App\Models\Setting;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\CostControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityGovernanceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Security Test Owner',
            'email' => 'owner@security-test.local',
            'password' => 'supersecretpass123',
        ]);
    }

    public function test_unauthenticated_requests_are_rejected_with_401(): void
    {
        // Protected endpoints must block unauthenticated calls
        $this->getJson('/api/v1/accounts')->assertStatus(401);
        $this->getJson('/api/v1/drafts')->assertStatus(401);
        $this->getJson('/api/v1/settings')->assertStatus(401);
        $this->postJson('/api/v1/drafts/ai/generate', ['topic_or_idea' => 'AI Trend'])->assertStatus(401);
        $this->getJson('/api/v1/calendar/schedules')->assertStatus(401);
        $this->getJson('/api/v1/analytics/dashboard')->assertStatus(401);
    }

    public function test_login_issues_real_sanctum_token(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'owner@security-test.local',
            'password' => 'supersecretpass123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user']]);

        $token = $response->json('data.token');
        $this->assertNotEmpty($token);

        // Protected endpoint works with issued token
        $meResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $meResponse->assertStatus(200)
            ->assertJsonPath('data.email', 'owner@security-test.local');
    }

    public function test_social_account_tokens_are_encrypted_at_rest_fail_closed(): void
    {
        $account = SocialAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'x_twitter',
            'provider_account_id' => '12345678',
            'name' => 'Owner Account',
            'access_token' => 'plain_secret_access_token_123',
            'refresh_token' => 'plain_secret_refresh_token_456',
            'is_active' => true,
        ]);

        // Decrypted via accessor
        $this->assertEquals('plain_secret_access_token_123', $account->access_token);
        $this->assertEquals('plain_secret_refresh_token_456', $account->refresh_token);

        // Verify stored in database as encrypted payload, NOT plaintext
        $rawRow = \Illuminate\Support\Facades\DB::table('social_accounts')->where('id', $account->id)->first();
        $this->assertNotEquals('plain_secret_access_token_123', $rawRow->access_token);
        $this->assertStringNotContainsString('plain_secret_access_token_123', $rawRow->access_token);
    }

    public function test_draft_ai_generation_enforces_budget_cap(): void
    {
        config(['social.budget.daily_cap_usd' => 0.01]);

        // Record usage that exhausts the daily cap
        \App\Models\AiUsageLog::create([
            'user_id' => $this->user->id,
            'provider' => 'gemini',
            'model' => 'gemini-1.5-pro',
            'operation_type' => 'generate_draft',
            'prompt_tokens' => 100,
            'completion_tokens' => 100,
            'total_tokens' => 200,
            'estimated_cost_usd' => 0.02, // exceeds $0.01
            'latency_ms' => 200,
            'status' => 'success',
        ]);

        // Attempting AI draft generation should be blocked by cost control
        $this->actingAs($this->user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Daily budget limit reached');

        $costService = app(CostControlService::class);
        $costService->assertCanSpend($this->user, 0.03);
    }

    public function test_unapproved_draft_cannot_be_published_via_api(): void
    {
        $this->actingAs($this->user);

        $draft = Draft::create([
            'user_id' => $this->user->id,
            'title' => 'Unapproved Post',
            'status' => Draft::STATUS_IN_REVIEW, // Human approval not given yet
        ]);

        $response = $this->postJson("/api/v1/drafts/{$draft->id}/publish-now");

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.title', 'Explicit Human Approval Required');
    }
}
