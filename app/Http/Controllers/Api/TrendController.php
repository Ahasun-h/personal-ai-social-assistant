<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\TrendItem;

class TrendController extends Controller
{
    public function items(Request $request): JsonResponse
    {
        $items = TrendItem::with('trendSource')
            ->orderBy('engagement_score', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $items->items(),
            'meta' => [
                'total' => $items->total(),
                'page' => $items->currentPage(),
            ],
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
}
