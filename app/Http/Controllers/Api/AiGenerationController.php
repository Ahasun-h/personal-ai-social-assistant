<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Draft;
use App\Models\DraftVersion;
use App\Models\MediaAsset;
use App\Models\User;
use App\Models\Setting;

class AiGenerationController extends Controller
{
    public function generateDraft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'topic' => 'required|string',
            'tone' => 'nullable|string',
            'platforms' => 'nullable|array',
        ]);

        $user = User::first();
        $topic = $validated['topic'];
        $tone = $validated['tone'] ?? 'insightful_engineer';

        $draft = Draft::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'title' => 'AI Draft: ' . Str::limit($topic, 50),
            'status' => Draft::STATUS_IN_REVIEW,
            'target_platforms' => $validated['platforms'] ?? ['linkedin', 'x_twitter'],
            'ai_prompt_context' => "Generate drafts for: {$topic} with tone: {$tone}",
        ]);

        // Generate LinkedIn version
        DraftVersion::create([
            'id' => (string) Str::uuid(),
            'draft_id' => $draft->id,
            'version_number' => 1,
            'platform' => 'linkedin',
            'content' => "Key insights on {$topic}:\n\nIn modern engineering, the difference between good teams and great teams is how deliberately they address architectural friction.\n\nHere are 3 core principles we found effective:\n1. Favor explicit contracts over implicit assumptions.\n2. Measure p95 and p99 metrics instead of averages.\n3. Keep your deployment unit as simple as possible.\n\nWhat has your experience been with {$topic}?\n\n#Architecture #SoftwareEngineering #TechLeadership",
            'tone' => $tone,
            'created_by_ai' => true,
        ]);

        // Generate X version
        DraftVersion::create([
            'id' => (string) Str::uuid(),
            'draft_id' => $draft->id,
            'version_number' => 1,
            'platform' => 'x_twitter',
            'content' => "A practical takeaway on {$topic}:\n\nDon't optimize for theoretical scale before solving practical latency.\n\nKeep boundaries modular, minimize network hops, and measure everything.\n\nAgree or disagree? 👇",
            'tone' => $tone,
            'created_by_ai' => true,
        ]);

        return response()->json([
            'success' => true,
            'data' => $draft->load('versions'),
        ], 201);
    }

    public function generateMedia(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => 'required|string',
            'aspect_ratio' => 'nullable|string',
            'provider' => 'nullable|string',
        ]);

        $user = User::first();
        $aspectRatio = $validated['aspect_ratio'] ?? '16:9';
        $dimensions = match ($aspectRatio) {
            '1:1' => ['width' => 1024, 'height' => 1024],
            '4:5' => ['width' => 1080, 'height' => 1350],
            default => ['width' => 1200, 'height' => 675],
        };

        $asset = MediaAsset::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'filename' => 'ai_generated_' . time() . '.png',
            'disk' => 'local',
            'path' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1200&auto=format&fit=crop&q=80',
            'mime_type' => 'image/png',
            'file_size_bytes' => 524288,
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'alt_text' => $validated['prompt'],
            'is_ai_generated' => true,
            'metadata' => [
                'provider' => $validated['provider'] ?? 'flux-schnell',
                'prompt' => $validated['prompt'],
                'aspect_ratio' => $aspectRatio,
                'cost_usd' => 0.003,
            ],
        ]);

        return response()->json([
            'success' => true,
            'data' => $asset,
        ], 201);
    }
}

