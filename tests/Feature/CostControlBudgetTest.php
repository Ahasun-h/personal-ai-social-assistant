<?php

namespace Tests\Feature;

use App\Models\AiUsageLog;
use App\Models\Notification;
use App\Models\PublishAttempt;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\CostControlService;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostControlBudgetTest extends TestCase
{
    use RefreshDatabase;

    private CostControlService $costService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->costService = new CostControlService();

        $this->user = User::create([
            'name' => 'Author User',
            'email' => 'author@example.com',
            'password' => bcrypt('password123'),
        ]);

        config([
            'social.budget.daily_cap_usd' => 10.00,
            'social.budget.monthly_cap_usd' => 150.00,
            'social.budget.warning_threshold' => 0.80,
            'social.platforms.x_twitter.costs.post_text_only' => 0.015,
            'social.platforms.x_twitter.costs.post_with_link' => 0.20,
        ]);
    }

    public function test_post_cost_calculation_differentiates_links(): void
    {
        $textOnlyCost = $this->costService->calculatePostEstimatedCost('x_twitter', 'Just a normal text update without link');
        $this->assertEquals(0.015, $textOnlyCost);

        $linkCost = $this->costService->calculatePostEstimatedCost('x_twitter', 'Check out our new release https://example.com/v2');
        $this->assertEquals(0.20, $linkCost);

        $linkedinCost = $this->costService->calculatePostEstimatedCost('linkedin', 'LinkedIn post https://example.com');
        $this->assertEquals(0.0, $linkedinCost);
    }

    public function test_can_spend_succeeds_when_under_budget(): void
    {
        // 0 spent, requesting $0.50
        $this->costService->assertCanSpend($this->user, 0.50);
        $this->assertTrue(true);
    }

    public function test_hard_cap_blocks_execution_when_daily_budget_exceeded(): void
    {
        // Simulate $9.80 already spent today
        AiUsageLog::create([
            'user_id' => $this->user->id,
            'provider' => 'gemini',
            'model' => 'gemini-1.5-pro',
            'operation_type' => 'text_generation',
            'prompt_tokens' => 1000,
            'completion_tokens' => 500,
            'total_tokens' => 1500,
            'estimated_cost_usd' => 9.80,
            'latency_ms' => 450,
            'status' => 'success',
            'created_at' => Carbon::now('UTC'),
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Daily budget limit reached/');

        // Requesting $0.30 brings total to $10.10, exceeding $10.00 daily limit
        $this->costService->assertCanSpend($this->user, 0.30);
    }

    public function test_warning_notification_dispatched_at_eighty_percent_threshold(): void
    {
        // Spend $8.10 of $10.00 daily budget (81%)
        AiUsageLog::create([
            'user_id' => $this->user->id,
            'provider' => 'openai',
            'model' => 'gpt-4o',
            'operation_type' => 'text_generation',
            'prompt_tokens' => 2000,
            'completion_tokens' => 1000,
            'total_tokens' => 3000,
            'estimated_cost_usd' => 8.10,
            'latency_ms' => 500,
            'status' => 'success',
            'created_at' => Carbon::now('UTC'),
        ]);

        $this->costService->assertCanSpend($this->user, 0.10);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'budget_warning',
        ]);
    }
}
