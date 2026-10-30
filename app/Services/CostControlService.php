<?php

namespace App\Services;

use App\Models\AiUsageLog;
use App\Models\Notification;
use App\Models\PublishAttempt;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class CostControlService
{
    /**
     * Check if user is within spending limits for an upcoming operation
     * Throws Exception if hard cap is exceeded.
     */
    public function assertCanSpend(User $user, float $estimatedCost): void
    {
        $dailySpend = $this->getDailySpend($user);
        $monthlySpend = $this->getMonthlySpend($user);

        $dailyCap = (float) config('social.budget.daily_cap_usd', 10.00);
        $monthlyCap = (float) config('social.budget.monthly_cap_usd', 150.00);

        // Check Hard Cap (100%)
        if (($dailySpend + $estimatedCost) > $dailyCap) {
            throw new Exception("Daily budget limit reached (\${$dailyCap}). Current spend: \${$dailySpend}. Cannot proceed.");
        }

        if (($monthlySpend + $estimatedCost) > $monthlyCap) {
            throw new Exception("Monthly budget limit reached (\${$monthlyCap}). Current spend: \${$monthlySpend}. Cannot proceed.");
        }

        // Check Warning Threshold (80%)
        $warningThreshold = (float) config('social.budget.warning_threshold', 0.80);
        if (($dailySpend + $estimatedCost) >= ($dailyCap * $warningThreshold)) {
            $this->dispatchWarningNotification($user, 'daily', $dailySpend + $estimatedCost, $dailyCap);
        }

        if (($monthlySpend + $estimatedCost) >= ($monthlyCap * $warningThreshold)) {
            $this->dispatchWarningNotification($user, 'monthly', $monthlySpend + $estimatedCost, $monthlyCap);
        }
    }

    public function getDailySpend(User $user): float
    {
        $today = Carbon::today('UTC');

        $aiCost = (float) AiUsageLog::where('user_id', $user->id)
            ->where('created_at', '>=', $today)
            ->sum('estimated_cost_usd');

        $xApiCost = $this->calculateXApiSpend($user, $today);

        return round($aiCost + $xApiCost, 4);
    }

    public function getMonthlySpend(User $user): float
    {
        $startOfMonth = Carbon::now('UTC')->startOfMonth();

        $aiCost = (float) AiUsageLog::where('user_id', $user->id)
            ->where('created_at', '>=', $startOfMonth)
            ->sum('estimated_cost_usd');

        $xApiCost = $this->calculateXApiSpend($user, $startOfMonth);

        return round($aiCost + $xApiCost, 4);
    }

    /**
     * Calculate metered X API spending based on dispatches
     * ($0.015 post text-only, $0.20 post with external URL link)
     */
    public function calculateXApiSpend(User $user, Carbon $since): float
    {
        $attempts = PublishAttempt::whereHas('socialAccount', function ($q) use ($user) {
            $q->where('user_id', $user->id)->where('provider', 'x_twitter');
        })
            ->where('status', PublishAttempt::STATUS_SUCCEEDED)
            ->where('attempted_at', '>=', $since)
            ->get();

        $totalCost = 0.0;
        $costText = (float) config('social.platforms.x_twitter.costs.post_text_only', 0.015);
        $costLink = (float) config('social.platforms.x_twitter.costs.post_with_link', 0.20);

        foreach ($attempts as $attempt) {
            $payload = $attempt->payload_sent ?? [];
            $text = $payload['text'] ?? '';
            $hasLink = preg_match('/https?:\/\//i', $text);

            $totalCost += $hasLink ? $costLink : $costText;
        }

        return round($totalCost, 4);
    }

    public function calculatePostEstimatedCost(string $platform, string $text): float
    {
        if ($platform !== 'x_twitter') {
            return 0.0;
        }

        $hasLink = preg_match('/https?:\/\//i', $text);

        return $hasLink
            ? (float) config('social.platforms.x_twitter.costs.post_with_link', 0.20)
            : (float) config('social.platforms.x_twitter.costs.post_text_only', 0.015);
    }

    protected function dispatchWarningNotification(User $user, string $period, float $current, float $cap): void
    {
        // Avoid duplicate spam notifications within the last 6 hours
        $recent = Notification::where('user_id', $user->id)
            ->where('type', Notification::TYPE_BUDGET_WARNING)
            ->where('created_at', '>=', Carbon::now()->subHours(6))
            ->exists();

        if (! $recent) {
            Notification::create([
                'user_id' => $user->id,
                'type' => Notification::TYPE_BUDGET_WARNING,
                'title' => ucfirst($period) . " Budget Alert (80% reached)",
                'message' => "You have spent \${$current} of your \${$cap} {$period} spending limit.",
                'data' => [
                    'period' => $period,
                    'current_spend' => $current,
                    'cap' => $cap,
                ],
            ]);
        }
    }
}
