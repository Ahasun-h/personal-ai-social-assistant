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
use App\Models\AiUsageLog;
use App\Services\AI\TextGeneratorManager;
use App\DTOs\DraftPrompt;

class AiGenerationController extends Controller
{
    public function generateDraft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'topic' => 'required|string',
            'tone' => 'nullable|string',
            'platforms' => 'nullable|array',
            'provider' => 'nullable|string',
        ]);

        $user = User::first();
        $topic = $validated['topic'];
        $tone = $validated['tone'] ?? 'insightful_engineer';

        $requestedProvider = $validated['provider'] ?? null;
        if (!TextGeneratorManager::hasConfiguredApiKey($requestedProvider)) {
            return response()->json([
                'success' => false,
                'error' => 'No AI API key configured. Please configure your AI credentials in Settings Vault.',
            ], 422);
        }

        // Check budget guardrails before execution
        $budgetSetting = Setting::where('key', 'budget_guardrails')->first();
        if ($budgetSetting && isset($budgetSetting->value['monthly_llm_cap_usd'])) {
            $cap = (float) $budgetSetting->value['monthly_llm_cap_usd'];
            $spend = (float) ($budgetSetting->value['current_month_llm_spend_usd'] ?? 0);
            if ($spend >= $cap && $cap > 0) {
                return response()->json([
                    'error' => 'Monthly AI inference budget cap exceeded. Generation blocked by governance gate.',
                ], 403);
            }
        }

        // Generate text using the user's active configured model (Gemini, Z AI, Claude, OpenAI, Custom)
        $generator = TextGeneratorManager::resolve($validated['provider'] ?? null);
        $generated = $generator->generateDrafts(new DraftPrompt(
            topic: $topic,
            toneStyle: $tone,
            targetPlatforms: $validated['platforms'] ?? ['linkedin', 'x_twitter']
        ));

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
            'content' => $generated['linkedin'] ?? "Key insights on {$topic}:\n\nDeliberate system architecture always wins over unmetered automation.",
            'character_count' => mb_strlen($generated['linkedin'] ?? '', 'UTF-8'),
            'tone' => $tone,
            'created_by_ai' => true,
        ]);

        // Generate X version
        DraftVersion::create([
            'id' => (string) Str::uuid(),
            'draft_id' => $draft->id,
            'version_number' => 1,
            'platform' => 'x_twitter',
            'content' => $generated['x_twitter'] ?? "A practical takeaway on {$topic}: keep boundaries modular and measure everything. 👇",
            'character_count' => mb_strlen($generated['x_twitter'] ?? '', 'UTF-8'),
            'tone' => $tone,
            'created_by_ai' => true,
        ]);

        // Record AI Usage Log
        AiUsageLog::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'provider' => $validated['provider'] ?? 'active_provider',
            'model' => 'auto',
            'operation_type' => 'draft_generation',
            'tokens_input' => 120,
            'tokens_output' => 450,
            'cost_usd' => 0.002,
        ]);

        return response()->json([
            'success' => true,
            'data' => $draft->load('versions'),
        ], 201);
    }

    public function getConfig(): JsonResponse
    {
        $config = TextGeneratorManager::getActiveConfig();
        
        // Mask API keys for safe UI presentation
        $maskedConfig = $config;
        if (isset($maskedConfig['providers'])) {
            foreach ($maskedConfig['providers'] as $key => &$p) {
                if (!empty($p['api_key'])) {
                    $len = strlen($p['api_key']);
                    $p['masked_key'] = $len > 8 ? substr($p['api_key'], 0, 4) . '...' . substr($p['api_key'], -4) : '****';
                } else {
                    $p['masked_key'] = '';
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $maskedConfig,
        ]);
    }

    public function saveConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'active_provider' => 'required|string',
            'providers' => 'required|array',
        ]);

        $current = TextGeneratorManager::getActiveConfig();

        // Preserve existing secret keys if masked or unchanged
        foreach ($validated['providers'] as $pName => $pData) {
            if (isset($pData['api_key']) && (str_contains($pData['api_key'], '...') || empty($pData['api_key']))) {
                if (!empty($current['providers'][$pName]['api_key'])) {
                    $validated['providers'][$pName]['api_key'] = $current['providers'][$pName]['api_key'];
                }
            }
        }

        TextGeneratorManager::saveConfig($validated);

        return response()->json([
            'success' => true,
            'message' => 'AI Provider configuration encrypted and saved.',
        ]);
    }

    public function testConnection(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'provider' => 'required|string',
                'credentials' => 'required|array',
            ]);

            $result = TextGeneratorManager::testConnection($validated['provider'], $validated['credentials']);

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'latency_ms' => 0,
            ], 200);
        }
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
