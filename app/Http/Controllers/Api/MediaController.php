<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\MediaAsset;
use App\Models\User;

class MediaController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $user = User::first();

        $asset = MediaAsset::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'filename' => 'uploaded_asset_' . time() . '.png',
            'disk' => 'local',
            'path' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200&auto=format&fit=crop&q=80',
            'mime_type' => 'image/png',
            'file_size_bytes' => 312000,
            'width' => 1200,
            'height' => 675,
            'alt_text' => 'Uploaded asset',
            'is_ai_generated' => false,
        ]);

        return response()->json([
            'success' => true,
            'data' => $asset,
        ], 201);
    }
}

