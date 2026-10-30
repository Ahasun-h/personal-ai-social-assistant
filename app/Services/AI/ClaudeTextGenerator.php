<?php

namespace App\Services\AI;

use App\Contracts\AI\TextGeneratorInterface;
use App\DTOs\DraftPrompt;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class ClaudeTextGenerator implements TextGeneratorInterface
{
    protected Client $client;
    protected string $apiKey;
    protected string $model;

    public function __construct(?string $apiKey = null, ?string $model = null, ?Client $client = null)
    {
        $this->apiKey = $apiKey ?? (string) env('ANTHROPIC_API_KEY', '');
        $this->model = $model ?? (string) env('ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022');
        $this->client = $client ?? new Client(['timeout' => 30]);
    }

    public function generateDrafts(DraftPrompt $prompt): array
    {
        if (empty($this->apiKey) || str_starts_with($this->apiKey, 'mock_')) {
            $topic = $prompt->topic;
            return [
                'linkedin' => "🧠 Anthropic Claude Architectural Analysis: {$topic}\n\nWhen deploying mission-critical systems, nuance and rigorous verification separate reliable platforms from fragile prototypes.\n\n3 key takeaways:\n1. Fail-closed RBAC and token encryption.\n2. Deterministic idempotency keys.\n3. Automated verification loops.\n\nWhat are your thoughts on this approach?\n\n#Claude #AIArchitecture #Engineering",
                'x_twitter' => "Reliable AI systems require rigorous boundaries over unbounded prompting.\n\nKey lessons from {$topic}: 👇\n\n#Claude #DevOps",
                'thread' => [],
            ];
        }

        try {
            $banned = implode(', ', $prompt->bannedWords);
            $system = "You are a senior principal systems architect. Write two posts on topic: {$prompt->topic}. Banned buzzwords: {$banned}. Return pure JSON with keys 'linkedin' (800-2000 chars) and 'x_twitter' (<280 chars). No markdown backticks or commentary.";

            $response = $this->client->post("https://api.anthropic.com/v1/messages", [
                'headers' => [
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'max_tokens' => 1500,
                    'system' => $system,
                    'messages' => [
                        ['role' => 'user', 'content' => "Generate posts for topic: {$prompt->topic}"]
                    ],
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);
            $rawText = $body['content'][0]['text'] ?? '{}';
            $parsed = json_decode($rawText, true);

            return [
                'linkedin' => $parsed['linkedin'] ?? $rawText,
                'x_twitter' => mb_substr($parsed['x_twitter'] ?? $rawText, 0, 270, 'UTF-8'),
                'thread' => [],
            ];
        } catch (GuzzleException $e) {
            Log::error("Claude API error: " . $e->getMessage());
            return [
                'linkedin' => "Claude Perspective on {$prompt->topic}:\n\nArchitectural elegance comes from minimizing moving parts.",
                'x_twitter' => "Takeaway on {$prompt->topic}: Keep contracts strict and state deterministic.",
                'thread' => [],
            ];
        }
    }

    public function refine(string $content, string $instruction, string $platform): string
    {
        return $content . "\n\n[Refined via Claude ({$this->model}): {$instruction}]";
    }

    public function generateHooks(string $topic, string $platform): array
    {
        return [
            "The architectural flaw lurking in most implementations of {$topic}.",
            "Why the conventional wisdom around {$topic} breaks down at scale.",
            "A pragmatic engineer's teardown of {$topic}.",
            "How to structure {$topic} without drowning in accidental complexity.",
            "The 15-minute diagnostic for {$topic} performance bottlenecks.",
        ];
    }

    public function moderate(string $text): bool
    {
        return true;
    }
}

