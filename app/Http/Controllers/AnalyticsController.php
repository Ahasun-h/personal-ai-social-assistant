<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\MetricsSnapshot;
use App\Models\SocialAccount;
use App\Models\Draft;

class AnalyticsController extends Controller
{
    public function index(): Response
    {
        $snapshots = MetricsSnapshot::query()
            ->with(['socialAccount'])
            ->latest('captured_at')
            ->take(20)
            ->get();

        $accounts = SocialAccount::where('is_active', true)->get();

        $totals = [
            'impressions' => MetricsSnapshot::sum('impressions'),
            'likes' => MetricsSnapshot::sum('likes'),
            'reposts' => MetricsSnapshot::sum('retweets_reposts'),
            'comments' => MetricsSnapshot::sum('comments_count'),
            'clicks' => MetricsSnapshot::sum('clicks'),
            'avg_engagement_rate' => MetricsSnapshot::avg('engagement_rate') ?: 0.054,
        ];

        return Inertia::render('Analytics/Index', [
            'snapshots' => $snapshots,
            'accounts' => $accounts,
            'totals' => $totals,
        ]);
    }
}
