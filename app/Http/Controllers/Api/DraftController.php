<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Draft;
use App\Models\DraftVersion;
use App\Models\User;

class DraftController extends Controller
{
    public function index(): JsonResponse
    {
        $drafts = Draft::with(['versions', 'postMedia.mediaAsset'])->latest()->get();
        return response()->json([
            'success' => true,
            'data' => $drafts,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'category_id' => 'nullable|uuid',
            'target_platforms' => 'required|array',
            'content_linkedin' => 'nullable|string',
            'content_x' => 'nullable|string',
        ]);

        $user = User::first();

        $draft = Draft::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'category_id' => $validated['category_id'] ?? null,
            'title' => $validated['title'],
            'status' => Draft::STATUS_IN_REVIEW,
            'target_platforms' => $validated['target_platforms'],
        ]);

        if (!empty($validated['content_linkedin'])) {
            DraftVersion::create([
                'id' => (string) Str::uuid(),
                'draft_id' => $draft->id,
                'version_number' => 1,
                'platform' => 'linkedin',
                'content' => $validated['content_linkedin'],
                'created_by_ai' => false,
            ]);
        }

        if (!empty($validated['content_x'])) {
            DraftVersion::create([
                'id' => (string) Str::uuid(),
                'draft_id' => $draft->id,
                'version_number' => 1,
                'platform' => 'x_twitter',
                'content' => $validated['content_x'],
                'created_by_ai' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $draft->load('versions'),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $draft = Draft::with(['versions', 'postMedia.mediaAsset', 'category', 'trendItem'])->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $draft,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $draft = Draft::findOrFail($id);

        if ($request->has('title')) {
            $draft->update(['title' => $request->input('title')]);
        }

        if ($request->has('status')) {
            $draft->update(['status' => $request->input('status')]);
        }

        if ($request->has('content') && $request->has('platform')) {
            $versionCount = $draft->versions()->where('platform', $request->input('platform'))->count();
            DraftVersion::create([
                'id' => (string) Str::uuid(),
                'draft_id' => $draft->id,
                'version_number' => $versionCount + 1,
                'platform' => $request->input('platform'),
                'content' => $request->input('content'),
                'created_by_ai' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $draft->load('versions'),
        ]);
    }

    public function approve(string $id): JsonResponse
    {
        $draft = Draft::findOrFail($id);
        $draft->update(['status' => Draft::STATUS_APPROVED]);

        return response()->json([
            'success' => true,
            'message' => 'Draft explicitly approved by owner.',
            'data' => $draft,
        ]);
    }

    public function publishNow(string $id): JsonResponse
    {
        $draft = Draft::findOrFail($id);
        $draft->update([
            'status' => Draft::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Draft dispatched and published successfully.',
            'data' => $draft,
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $draft = Draft::findOrFail($id);
        $draft->delete();

        return response()->json([
            'success' => true,
            'message' => 'Draft deleted successfully.',
        ]);
    }
}

