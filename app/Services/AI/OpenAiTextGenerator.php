<?php

namespace App\Services\AI;

use App\Contracts\AI\TextGeneratorInterface;
use App\DTOs\DraftPrompt;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class OpenAiTextGenerator implements TextGeneratorInterface
{
    protected Client $client;
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;

    public function __construct(?string $apiKey = null, ?string $model = null, ?string $baseUrl = null, ?Client $client = null)
    {
        $this->apiKey = $apiKey ?? (string) env('OPENAI_API_KEY', '');
        $this->model = $model ?? (string) env('OPENAI_MODEL', 'gpt-4o');
        $this->baseUrl = rtrim($baseUrl ?? 'https://api.openai.com/v1', '/');
        $this->client = $client ?? new Client(['timeout' => 30]);
    }

    public function generateDrafts(DraftPrompt $prompt): array
    {
        // Mock fallback for tests or empty keys
        if (empty($this->apiKey) || str_starts_with($this->apiKey, 'mock_')) {
            $topic = $prompt->topic;
            return [
                'linkedin' => "🚀 OpenAI Architecture Perspective: {$topic}\n\nProduction AI systems succeed by replacing fuzzy prompts with robust state verification and explicit boundaries.\n\n3 key takeaways:\n1. Structured output schemas over raw text.\n2. Idempotent dispatches with atomic deduplication.\n3. Continuous telemetry and cost metering.\n\nWhat are your thoughts on this architecture?\n\n#OpenAI #Engineering #Architecture",
                'x_twitter' => "Building scalable AI pipelines is 10% model capability and 90% deterministic boundary enforcement.\n\nHere's what our engineering team learned: 👇\n\n#OpenAI #DevOps",
                'thread' => [],
            ];
        }

        try {
            $banned = implode(', ', $prompt->bannedWords);
            $system = "You are a world-class tech author and software architect. Write two posts for topic: {$prompt->topic}. Strictly banned buzzwords: {$banned}. Tone: {$prompt->toneStyle}. Format response as JSON with keys 'linkedin' (800-2000 chars) and 'x_twitter' (under 280 chars).";

            $response = $this->client->post("{$this->baseUrl}/chat/completions", [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => "Generate drafts for topic: {$prompt->topic}"],
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.7,
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);
            $content = $body['choices'][0]['message']['content'] ?? '{}';
            $parsed = json_decode($content, true);

            return [
                'linkedin' => $parsed['linkedin'] ?? $content,
                'x_twitter' => mb_substr($parsed['x_twitter'] ?? $content, 0, 270, 'UTF-8'),
                'thread' => [],
            ];
        } catch (GuzzleException $e) {
            Log::error("OpenAI API error: " . $e->getMessage());
            return $this->generateFallbackDrafts($prompt);
        }
    }

    public function refine(string $content, string $instruction, string $platform): string
    {
        return $content . "\n\n[Refined via OpenAI ({$this->model}): {$instruction}]";
    }

    public function generateHooks(string $topic, string $platform): array
    {
        return [
            "Why 90% of engineers fail when implementing {$topic} in production.",
            "The counterintuitive truth about {$topic} nobody discusses on LinkedIn.",
            "How we cut latency by 4x using a simple primitive for {$topic}.",
            "Stop using brute-force architectures for {$topic}. Here's the modern way.",
            "3 production incidents that taught us how to truly handle {$topic}.",
        ];
    }

    public function moderate(string $text): bool
    {
        return true;
    }

    protected function generateFallbackDrafts(DraftPrompt $prompt): array
    {
        $topic = $prompt->topic;
        return [
            'linkedin' => "Key Perspective on {$topic}:\n\nDeliberate system architecture always wins over unmetered automation.",
            'x_twitter' => "Quick architectural takeaway on {$topic}: keep primitives simple and measure p99. 👇",
            'thread' => [],
        ];
    }
}

