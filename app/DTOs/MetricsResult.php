<?php

namespace App\DTOs;

class MetricsResult
{
    public function __construct(
        public readonly int $impressions = 0,
        public readonly int $reach = 0,
        public readonly int $engagements = 0,
        public readonly int $likes = 0,
        public readonly int $retweets = 0,
        public readonly int $comments = 0,
        public readonly int $clicks = 0,
        public readonly float $engagementRate = 0.0,
        public readonly array $raw = []
    ) {}
}
