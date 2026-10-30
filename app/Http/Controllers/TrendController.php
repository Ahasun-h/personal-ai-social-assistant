<?php

namespace App\Http\Controllers;

use App\Jobs\FetchTrendItemsJob;
use App\Models\TrendItem;
use App\Models\TrendSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrendController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $trends = TrendItem::query()
            ->with('trendSource')
            ->where('is_dismissed', false)
            ->orderBy('engagement_score', 'desc')
            ->get();

        $sources = TrendSource::all();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'trends' => $trends,
                    'sources' => $sources,
                ],
            ]);
        }

        return Inertia::render('Trends/Index', [
            'trends' => $trends,
            'sources' => $sources,
        ]);
    }

    public function bookmark(string $id): JsonResponse
    {
        $item = TrendItem::findOrFail($id);
        $item->update(['is_bookmarked' => !$item->is_bookmarked]);

        return response()->json([
            'success' => true,
            'data' => $item,
        ]);
    }

    public function dismiss(string $id): JsonResponse
    {
        $item = TrendItem::findOrFail($id);
        $item->update(['is_dismissed' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Trend item dismissed.',
        ]);
    }

    public function triggerSync(Request $request): JsonResponse
    {
        $sources = TrendSource::where('status', 'active')->get();
        foreach ($sources as $source) {
            FetchTrendItemsJob::dispatch($source->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Trend sources sync dispatched to Horizon queue.',
        ]);
    }
}
