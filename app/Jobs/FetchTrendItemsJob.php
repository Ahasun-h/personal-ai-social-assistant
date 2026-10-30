<?php

namespace App\Jobs;

use App\Models\TrendItem;
use App\Models\TrendSource;
use App\Services\Trends\HackerNewsTrendSource;
use App\Services\Trends\RssTrendSource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchTrendItemsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly ?string $sourceId = null)
    {
        $this->onQueue('trends');
    }

    public function handle(RssTrendSource $rss, HackerNewsTrendSource $hn): void
    {
        $sourcesQuery = TrendSource::where('status', 'active');
        if ($this->sourceId) {
            $sourcesQuery->where('id', $this->sourceId);
        }

        $sources = $sourcesQuery->get();

        foreach ($sources as $source) {
            try {
                $driver = $source->source_type === 'hackernews' ? $hn : $rss;
                $items = $driver->fetch($source);

                foreach ($items as $raw) {
                    TrendItem::firstOrCreate(
                        [
                            'trend_source_id' => $source->id,
                            'external_id' => $raw['external_id'],
                        ],
                        [
                            'title' => $raw['title'],
                            'summary' => $raw['summary'],
                            'content' => $raw['content'],
                            'url' => $raw['url'],
                            'author' => $raw['author'],
                            'published_at' => $raw['published_at'],
                            'engagement_score' => $raw['engagement_score'],
                        ]
                    );
                }

                $source->update(['last_fetched_at' => now()]);
            } catch (\Throwable $e) {
                Log::error("Failed fetching trends for source {$source->id}: " . $e->getMessage());
            }
        }
    }
}
