<?php

namespace App\Contracts\Trends;

use App\Models\TrendSource;
use Illuminate\Support\Collection;

interface TrendSourceInterface
{
    /**
     * Polls the external source and returns a collection of raw items.
     * Each item is an associative array: ['external_id', 'title', 'summary', 'url', 'published_at', 'engagement_score']
     */
    public function fetch(TrendSource $source): Collection;
}
