<?php

namespace App\Http\Controllers;

use App\Models\Draft;
use App\Models\MetricsSnapshot;
use App\Models\SocialAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        return $this->dashboard($request);
    }

    public function dashboard(Request $request): Response|JsonResponse
    {
        $snapshots = MetricsSnapshot::query()
            ->with(['socialAccount'])
            ->latest('captured_at')
            ->take(30)
            ->get();

        $accounts = SocialAccount::where('is_active', true)->get();

        $totals = [
            'impressions' => (int) MetricsSnapshot::sum('impressions'),
            'likes' => (int) MetricsSnapshot::sum('likes'),
            'reposts' => (int) MetricsSnapshot::sum('retweets_reposts'),
            'comments' => (int) MetricsSnapshot::sum('comments_count'),
            'clicks' => (int) MetricsSnapshot::sum('clicks'),
            'avg_engagement_rate' => round((float) (MetricsSnapshot::avg('engagement_rate') ?: 0.054), 4),
            'published_posts_count' => Draft::where('status', Draft::STATUS_PUBLISHED)->count(),
        ];

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'totals' => $totals,
                    'snapshots' => $snapshots,
                    'accounts' => $accounts,
                ],
            ]);
        }

        return Inertia::render('Analytics/Index', [
            'snapshots' => $snapshots,
            'accounts' => $accounts,
            'totals' => $totals,
        ]);
    }
}
