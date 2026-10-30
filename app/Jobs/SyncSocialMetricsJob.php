<?php

namespace App\Jobs;

use App\Models\MetricsSnapshot;
use App\Models\PublishAttempt;
use App\Services\Social\LinkedIn\LinkedInPublisher;
use App\Services\Social\Twitter\TwitterPublisher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncSocialMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('analytics');
    }

    public function handle(TwitterPublisher $twitter, LinkedInPublisher $linkedIn): void
    {
        // Find published posts in the last 7 days
        $attempts = PublishAttempt::with('socialAccount')
            ->where('status', PublishAttempt::STATUS_SUCCEEDED)
            ->whereNotNull('external_post_id')
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        foreach ($attempts as $attempt) {
            $account = $attempt->socialAccount;
            if (!$account) continue;

            $publisher = $account->provider === 'linkedin' ? $linkedIn : $twitter;
            $metrics = $publisher->fetchMetrics($account, $attempt->external_post_id);

            MetricsSnapshot::create([
                'social_account_id' => $account->id,
                'publish_attempt_id' => $attempt->id,
                'external_post_id' => $attempt->external_post_id,
                'impressions' => $metrics->impressions,
                'reach' => $metrics->reach,
                'engagements' => $metrics->engagements,
                'likes' => $metrics->likes,
                'retweets_reposts' => $metrics->retweets,
                'comments_count' => $metrics->comments,
                'clicks' => $metrics->clicks,
                'engagement_rate' => $metrics->engagementRate,
                'raw_metrics' => $metrics->raw,
                'captured_at' => now(),
            ]);
        }
    }
}
