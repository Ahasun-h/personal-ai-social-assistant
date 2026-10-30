<?php

namespace App\Http\Controllers;

use App\Contracts\AI\TextGeneratorInterface;
use App\DTOs\DraftPrompt;
use App\Jobs\PublishPostJob;
use App\Models\AiUsageLog;
use App\Models\AuditLog;
use App\Models\Draft;
use App\Models\DraftVersion;
use App\Models\Schedule;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\CostControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DraftController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Draft::with(['versions', 'postMedia.asset'])
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $drafts = $query->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $drafts->items(),
            'meta' => [
                'current_page' => $drafts->currentPage(),
                'per_page' => $drafts->perPage(),
                'total' => $drafts->total(),
                'last_page' => $drafts->lastPage(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $draft = Draft::with(['versions', 'postMedia.asset'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $draft,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'nullable|uuid',
            'trend_item_id' => 'nullable|uuid',
            'target_platforms' => 'required|array',
            'scheduled_at' => 'nullable|date',
            'versions' => 'required|array|min:1',
        ]);

        $user = User::first();

        $draft = Draft::create([
            'user_id' => $user->id,
            'category_id' => $validated['category_id'] ?? null,
            'trend_item_id' => $validated['trend_item_id'] ?? null,
            'title' => $validated['title'],
            'status' => Draft::STATUS_IN_REVIEW,
            'target_platforms' => $validated['target_platforms'],
            'scheduled_at' => $validated['scheduled_at'] ?? null,
        ]);

        foreach ($validated['versions'] as $idx => $v) {
            DraftVersion::create([
                'draft_id' => $draft->id,
                'version_number' => 1,
                'platform' => $v['platform'] ?? 'universal',
                'content' => $v['content'],
                'thread_items' => $v['thread_items'] ?? [],
                'tone' => $v['tone'] ?? 'balanced',
            ]);
        }

        AuditLog::record('create_draft', $draft);

        return response()->json([
            'success' => true,
            'data' => $draft->load('versions'),
        ], 201);
    }

    public function approve(string $id): JsonResponse
    {
        $draft = Draft::findOrFail($id);
        $draft->update(['status' => Draft::STATUS_APPROVED]);

        AuditLog::record('approve_draft', $draft);

        return response()->json([
            'success' => true,
            'data' => $draft,
        ]);
    }

    public function publishNow(string $id): JsonResponse
    {
        $draft = Draft::findOrFail($id);

        if (!$draft->isApproved()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'title' => 'Explicit Human Approval Required',
                    'detail' => 'Draft must be approved by the owner before publishing.',
                    'status' => 422,
                ],
            ], 422);
        }

        $accounts = SocialAccount::where('is_active', true)
            ->whereIn('provider', $draft->target_platforms ?? ['linkedin', 'x_twitter'])
            ->get();

        foreach ($accounts as $account) {
            $schedule = Schedule::create([
                'user_id' => $draft->user_id,
                'social_account_id' => $account->id,
                'draft_id' => $draft->id,
                'scheduled_for' => now(),
                'status' => Schedule::STATUS_PENDING,
            ]);

            PublishPostJob::dispatch($schedule->id, $draft->id, $account->id);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'Publishing jobs queued',
                'draft_id' => $draft->id,
            ],
        ], 202);
    }

    public function generateAi(Request $request, TextGeneratorInterface $ai, CostControlService $costService): JsonResponse
    {
        $validated = $request->validate([
            'topic_or_idea' => 'required|string',
            'platforms' => 'nullable|array',
            'tone_style' => 'nullable|string',
        ]);

        $user = $request->user() ?? User::first();
        $estimatedCost = 0.03; // Estimated Gemini/Claude tokens cost

        // Enforce hard spend caps & budget warnings
        if ($user) {
            $costService->assertCanSpend($user, $estimatedCost);
        }

        $prompt = new DraftPrompt(
            topic: $validated['topic_or_idea'],
            toneStyle: $validated['tone_style'] ?? 'thought_leader',
            targetPlatforms: $validated['platforms'] ?? ['linkedin', 'x_twitter']
        );

        $results = $ai->generateDrafts($prompt);
        if (!$user) {
            $user = User::first();
        }

        $draft = Draft::create([
            'user_id' => $user->id,
            'title' => substr($validated['topic_or_idea'], 0, 80),
            'status' => Draft::STATUS_IN_REVIEW,
            'target_platforms' => $validated['platforms'] ?? ['linkedin', 'x_twitter'],
            'ai_prompt_context' => $validated['topic_or_idea'],
        ]);

        DraftVersion::create([
            'draft_id' => $draft->id,
            'version_number' => 1,
            'platform' => 'linkedin',
            'content' => $results['linkedin'],
            'created_by_ai' => true,
        ]);

        DraftVersion::create([
            'draft_id' => $draft->id,
            'version_number' => 1,
            'platform' => 'x_twitter',
            'content' => $results['x_twitter'],
            'thread_items' => $results['thread'] ?? [],
            'created_by_ai' => true,
        ]);

        AiUsageLog::create([
            'user_id' => $user->id,
            'provider' => 'gemini',
            'model' => 'gemini-1.5-pro',
            'operation_type' => 'generate_draft',
            'prompt_tokens' => 350,
            'completion_tokens' => 450,
            'total_tokens' => 800,
            'estimated_cost_usd' => $estimatedCost,
            'latency_ms' => 420,
            'status' => 'success',
        ]);

        AuditLog::record('ai_generate_draft', $draft, $user->id, null, [
            'topic' => $validated['topic_or_idea'],
            'cost_usd' => $estimatedCost,
        ]);

        return response()->json([
            'success' => true,
            'data' => $draft->load('versions'),
        ], 201);
    }
}
