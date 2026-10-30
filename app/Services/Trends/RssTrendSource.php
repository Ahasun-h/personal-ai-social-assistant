<?php

namespace App\Services\Trends;

use App\Contracts\Trends\TrendSourceInterface;
use App\Models\TrendSource;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RssTrendSource implements TrendSourceInterface
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
            $response = $this->client->get($source->url);
            $xmlString = (string) $response->getBody();
            $xml = simplexml_load_string($xmlString);

            if ($xml && isset($xml->channel->item)) {
                foreach ($xml->channel->item as $entry) {
                    $items->push([
                        'external_id' => (string) ($entry->guid ?: $entry->link),
                        'title' => (string) $entry->title,
                        'summary' => strip_tags((string) ($entry->description ?: '')),
                        'content' => (string) ($entry->description ?: ''),
                        'url' => (string) $entry->link,
                        'author' => (string) ($entry->author ?? $source->name),
                        'published_at' => date('Y-m-d H:i:s', strtotime((string) $entry->pubDate)),
                        'engagement_score' => rand(40, 95),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("RSS fetch failed for source {$source->name}: " . $e->getMessage());
        }

        return $items;
    }
}
