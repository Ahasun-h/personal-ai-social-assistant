<?php

namespace App\Services\AI;

use App\Contracts\AI\TextGeneratorInterface;
use App\DTOs\DraftPrompt;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class GeminiTextGenerator implements TextGeneratorInterface
{
    protected Client $client;
    protected string $apiKey;
    protected string $model;

    public function __construct(?string $apiKey = null, ?string $model = null, ?Client $client = null)
    {
        $this->apiKey = $apiKey ?? (string) env('GEMINI_API_KEY', '');
        $this->model = $model ?? (string) env('GEMINI_MODEL', 'gemini-1.5-flash');
        $this->client = $client ?? new Client(['timeout' => 30]);
    }

    public function generateDrafts(DraftPrompt $prompt): array
    {
        // Deterministic generation fallback for tests & offline mode
        if (empty($this->apiKey) || str_starts_with($this->apiKey, 'mock_')) {
            $topic = $prompt->topic;
            return [
                'linkedin' => "🔥 Key Perspective: {$topic}\n\nMost teams overlook that building autonomous systems requires strict deterministic state machines rather than just prompt tweaks.\n\n3 key takeaways:\n1. Maker-checker dual verification.\n2. Idempotent queue workers with exponential backoff.\n3. Continuous metrics synchronization.\n\nWhat are your thoughts on this approach? Let's discuss below.\n\n#SoftwareArchitecture #Engineering #Automation",
                'x_twitter' => "Building autonomous systems is 10% prompt engineering and 90% deterministic state machines.\n\nHere's why maker-checker pipelines outperform raw agent loops: 👇\n\n#AI #DevOps",
                'thread' => [
                    "1/3 Most AI agents fail in production not because the LLM wasn't smart enough, but because error boundaries weren't isolated.",
                    "2/3 Implementing Redis locks + exponential backoff retries prevents duplicate API dispatches and rate-limit bans.",
                    "3/3 Full post breakdown on LinkedIn. Thoughts on autonomous SDLC engines?",
                ],
            ];
        }

        try {
            $systemInstruction = "You are an elite tech ghostwriter. Write two platform-specific posts for topic: {$prompt->topic}. Banned words: " . implode(', ', $prompt->bannedWords);
            $response = $this->client->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}", [
                'json' => [
                    'contents' => [
                        ['parts' => [['text' => "Generate a long-form LinkedIn post (1000-2500 chars) and a concise X tweet (<280 chars) for: {$prompt->topic}"]]]
                    ]
                ]
            ]);

            $data = json_decode((string) $response->getBody(), true);
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            return [
                'linkedin' => $text,
                'x_twitter' => mb_substr($text, 0, 270, 'UTF-8'),
                'thread' => [],
            ];
        } catch (GuzzleException $e) {
            $msg = $this->extractErrorMessage($e);
            Log::error("Gemini API error: " . $msg);
            throw new \RuntimeException($msg);
        }
    }

    protected function extractErrorMessage(\Throwable $e): string
    {
        if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
            $body = (string) $e->getResponse()->getBody();
            $json = json_decode($body, true);
            if (!empty($json['error']['message'])) {
                return $json['error']['message'];
            }
        }
        return $e->getMessage();
    }

    public function refine(string $content, string $instruction, string $platform): string
    {
        if (str_contains(strtolower($instruction), 'shorten')) {
            $words = explode(' ', $content);
            return implode(' ', array_slice($words, 0, (int) (count($words) * 0.7)));
        }

        return $content . "\n\n[Refined with: {$instruction}]";
    }

    public function generateHooks(string $topic, string $platform): array
    {
        return [
            "Contrarian: Everything you were taught about {$topic} is now outdated.",
            "Data-driven: 84% of engineering teams make this exact mistake with {$topic}.",
            "Narrative: 6 months ago, our team made a choice with {$topic} that changed everything.",
            "Curiosity: The single highest-leverage insight on {$topic} nobody talks about.",
            "Actionable: Step-by-step framework to master {$topic} in under 10 minutes.",
        ];
    }

    public function moderate(string $text): bool
    {
        $flaggedWords = ['hate', 'violence', 'scam', 'fraud'];
        foreach ($flaggedWords as $word) {
            if (stripos($text, $word) !== false) {
                return false;
            }
        }
        return true;
    }
}
