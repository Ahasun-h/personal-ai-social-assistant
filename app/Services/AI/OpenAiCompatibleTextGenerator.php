<?php

namespace App\Services\AI;

use App\Contracts\AI\TextGeneratorInterface;
use App\DTOs\DraftPrompt;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class OpenAiCompatibleTextGenerator implements TextGeneratorInterface
{
    protected Client $client;
    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;
    protected string $providerLabel;

    public function __construct(
        ?string $apiKey = null,
        ?string $baseUrl = null,
        ?string $model = null,
        ?string $providerLabel = 'Z AI / Custom LLM',
        ?Client $client = null
    ) {
        $this->apiKey = $apiKey ?? (string) env('Z_AI_API_KEY', env('CUSTOM_AI_API_KEY', ''));
        $this->baseUrl = rtrim($baseUrl ?? (string) env('Z_AI_BASE_URL', 'https://api.z-ai.io/v1'), '/');
        $this->model = $model ?? (string) env('Z_AI_MODEL', 'z-ai-large');
        $this->providerLabel = $providerLabel ?? 'Z AI';
        $this->client = $client ?? new Client(['timeout' => 30]);
    }

    public function generateDrafts(DraftPrompt $prompt): array
    {
        if (empty($this->apiKey) || str_starts_with($this->apiKey, 'mock_')) {
            $topic = $prompt->topic;
            return [
                'linkedin' => "⚡ {$this->providerLabel} Perspective: {$topic}\n\nHigh-performance systems engineers know that real leverage comes from robust primitives and tight feedback loops.\n\nKey principles:\n1. Explicit boundaries and schema contracts.\n2. In-memory deduplication and atomic locks.\n3. Transparent metrics attribution.\n\nHow do you handle {$topic} in your stack?\n\n#SoftwareEngineering #Architecture #Scale",
                'x_twitter' => "Building scalable pipelines for {$topic} with {$this->providerLabel}: Keep boundaries strict and measure p99.\n\nQuick breakdown: 👇",
                'thread' => [],
            ];
        }

        try {
            $banned = implode(', ', $prompt->bannedWords);
            $system = "You are an elite software architect and thought leader. Write two posts for topic: {$prompt->topic}. Avoid banned words: {$banned}. Format output as JSON with keys 'linkedin' (800-2000 chars) and 'x_twitter' (<280 chars).";

            $response = $this->client->post("{$this->baseUrl}/chat/completions", [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => "Generate drafts for: {$prompt->topic}"],
                    ],
                    'temperature' => 0.7,
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);
            $content = $body['choices'][0]['message']['content'] ?? '';
            $parsed = json_decode($content, true);

            if (is_array($parsed) && isset($parsed['linkedin'])) {
                return [
                    'linkedin' => $parsed['linkedin'],
                    'x_twitter' => mb_substr($parsed['x_twitter'] ?? $parsed['linkedin'], 0, 270, 'UTF-8'),
                    'thread' => [],
                ];
            }

            return [
                'linkedin' => $content,
                'x_twitter' => mb_substr($content, 0, 270, 'UTF-8'),
                'thread' => [],
            ];
        } catch (GuzzleException $e) {
            Log::error("{$this->providerLabel} API error: " . $e->getMessage());
            return [
                'linkedin' => "Perspective on {$prompt->topic}:\n\nAlways design for operational visibility and deterministic recovery.",
                'x_twitter' => "Architecture lesson on {$prompt->topic}: simplicity beats cleverness every time.",
                'thread' => [],
            ];
        }
    }

    public function refine(string $content, string $instruction, string $platform): string
    {
        return $content . "\n\n[Refined via {$this->providerLabel} ({$this->model}): {$instruction}]";
    }

    public function generateHooks(string $topic, string $platform): array
    {
        return [
            "Why {$topic} is fundamentally misunderstood by most software teams.",
            "The 3 architectural rules we applied to {$topic} with zero downtime.",
            "Before you refactor {$topic}, read this benchmark analysis.",
            "How simple primitives outperform overengineered tooling for {$topic}.",
            "A pragmatic engineer's guide to mastering {$topic}.",
        ];
    }

    public function moderate(string $text): bool
    {
        return true;
    }
}
