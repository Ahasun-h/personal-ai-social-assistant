<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Draft;
use App\Models\SocialAccount;
use App\Models\Schedule;
use App\Models\MetricsSnapshot;
use App\Models\Setting;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $accounts = SocialAccount::query()
            ->where('is_active', true)
            ->get();

        $inboxCount = Draft::query()
            ->where('status', Draft::STATUS_IN_REVIEW)
            ->count();

        $scheduledCount = Draft::query()
            ->where('status', Draft::STATUS_SCHEDULED)
            ->count();

        $publishedCount = Draft::query()
            ->where('status', Draft::STATUS_PUBLISHED)
            ->count();

        $upcomingSchedules = Schedule::query()
            ->with(['draft.versions', 'socialAccount'])
            ->where('status', 'pending')
            ->orderBy('scheduled_for', 'asc')
            ->take(5)
            ->get();

        $recentDrafts = Draft::query()
            ->with(['versions', 'postMedia.mediaAsset', 'category'])
            ->latest()
            ->take(6)
            ->get();

        $totalImpressions = MetricsSnapshot::sum('impressions');
        $totalEngagements = MetricsSnapshot::sum('engagements');

        $budgetSetting = Setting::where('key', 'budget_guardrails')->first();
        $budget = $budgetSetting ? $budgetSetting->value : [
            'daily_llm_cap_usd' => 10.0,
            'current_month_llm_spend_usd' => 18.42,
            'monthly_llm_cap_usd' => 150.0,
        ];

        return Inertia::render('Dashboard', [
            'stats' => [
                'inbox_count' => $inboxCount,
                'scheduled_count' => $scheduledCount,
                'published_count' => $publishedCount,
                'total_impressions' => $totalImpressions,
                'total_engagements' => $totalEngagements,
            ],
            'accounts' => $accounts,
            'upcomingSchedules' => $upcomingSchedules,
            'recentDrafts' => $recentDrafts,
            'budget' => $budget,
        ]);
    }
}

