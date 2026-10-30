<?php

namespace App\Services\Trends;

use App\Contracts\Trends\TrendSourceInterface;
use App\Models\TrendSource;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class HackerNewsTrendSource implements TrendSourceInterface
{
    protected Client $client;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client(['timeout' => 15]);
    }

    public function fetch(TrendSource $source): Collection
    {
        $items = collect();

        try {
            // Fetch top 10 stories via Firebase JSON API
            $response = $this->client->get('https://hacker-news.firebaseio.com/v0/topstories.json');
            $topIds = array_slice(json_decode((string) $response->getBody(), true) ?? [], 0, 10);

            foreach ($topIds as $id) {
                $itemRes = $this->client->get("https://hacker-news.firebaseio.com/v0/item/{$id}.json");
                $story = json_decode((string) $itemRes->getBody(), true);

                if ($story && isset($story['title'])) {
                    $score = $story['score'] ?? 0;
                    $items->push([
                        'external_id' => (string) $story['id'],
                        'title' => (string) $story['title'],
                        'summary' => "Hacker News top story with {$score} points and " . ($story['descendants'] ?? 0) . " comments.",
                        'content' => $story['text'] ?? '',
                        'url' => $story['url'] ?? "https://news.ycombinator.com/item?id={$story['id']}",
                        'author' => (string) ($story['by'] ?? 'hn_user'),
                        'published_at' => date('Y-m-d H:i:s', $story['time'] ?? time()),
                        'engagement_score' => min(100.0, max(10.0, (float) $score)),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("HN fetch failed: " . $e->getMessage());
        }

        return $items;
    }
}
