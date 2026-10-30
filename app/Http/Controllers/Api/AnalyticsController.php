<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\MetricsSnapshot;
use App\Models\AuditLog;

class AnalyticsController extends Controller
{
    public function dashboard(): JsonResponse
    {
        $totals = [
            'impressions' => MetricsSnapshot::sum('impressions'),
            'reach' => MetricsSnapshot::sum('reach'),
            'likes' => MetricsSnapshot::sum('likes'),
            'reposts' => MetricsSnapshot::sum('retweets_reposts'),
            'comments' => MetricsSnapshot::sum('comments_count'),
            'clicks' => MetricsSnapshot::sum('clicks'),
            'avg_engagement_rate' => MetricsSnapshot::avg('engagement_rate') ?: 0.054,
        ];

        $snapshots = MetricsSnapshot::with('socialAccount')->latest()->take(10)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'totals' => $totals,
                'recent_snapshots' => $snapshots,
            ],
        ]);
    }

    public function comments(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [],
        ]);
    }

    public function audits(): JsonResponse
    {
        $audits = AuditLog::latest()->take(30)->get();
        return response()->json([
            'success' => true,
            'data' => $audits,
        ]);
    }
}

