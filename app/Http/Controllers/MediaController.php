<?php

namespace App\Http\Controllers;

use App\Contracts\AI\MediaGeneratorInterface;
use App\Models\AuditLog;
use App\Models\MediaAsset;
use App\Models\Setting;
use App\Models\User;
use App\Services\CostControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MediaController extends Controller
{
    public function index(): Response
    {
        $assets = MediaAsset::query()
            ->latest()
            ->get();

        $budgetSetting = Setting::where('key', 'budget_guardrails')->first();

        return Inertia::render('Media/Index', [
            'assets' => $assets,
            'budget' => $budgetSetting ? $budgetSetting->value : [],
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,jpg,png,webp,mp4,mov,gif|max:102400', // 100MB max
            'alt_text' => 'nullable|string|max:500',
        ]);

        $file = $request->file('file');
        $user = User::first();

        $path = $file->store('media', 'public');
        $mime = $file->getClientMimeType();
        $mediaType = str_starts_with($mime, 'video/') ? 'video' : (str_ends_with($mime, 'gif') ? 'gif' : 'image');

        $asset = MediaAsset::create([
            'user_id' => $user->id,
            'media_type' => $mediaType,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $mime,
            'file_size_bytes' => $file->getSize(),
            'alt_text' => $request->input('alt_text'),
            'source' => 'upload',
            'is_ai_generated' => false,
        ]);

        AuditLog::record('upload_media', $asset, $user->id);

        return response()->json([
            'success' => true,
            'data' => $asset,
        ], 201);
    }

    public function generateAi(Request $request, MediaGeneratorInterface $generator, CostControlService $costService): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => 'required|string|max:1000',
            'aspect_ratio' => 'nullable|string|in:1:1,16:9,4:5,9:16',
        ]);

        $user = User::first();
        $estimatedCost = 0.04; // Standard Flux/DALL-E image generation cost

        // Cost control check
        $costService->assertCanSpend($user, $estimatedCost);

        $aspectRatio = $validated['aspect_ratio'] ?? '1:1';
        $asset = $generator->generateImage($validated['prompt'], $aspectRatio, $user);

        AuditLog::record('ai_generate_media', $asset, $user->id, null, [
            'prompt' => $validated['prompt'],
            'cost_usd' => $estimatedCost,
        ]);

        return response()->json([
            'success' => true,
            'data' => $asset,
        ], 201);
    }
}
