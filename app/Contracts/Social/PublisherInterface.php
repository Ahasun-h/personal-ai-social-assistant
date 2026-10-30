<?php

namespace App\Contracts\Social;

use App\DTOs\MetricsResult;
use App\DTOs\PostPayload;
use App\DTOs\PublishResult;
use App\Models\SocialAccount;

interface PublisherInterface
{
    /**
     * Publishes a post or thread to the social network.
     */
    public function publish(SocialAccount $account, PostPayload $payload): PublishResult;

    /**
     * Deletes a published post by external ID.
     */
    public function delete(SocialAccount $account, string $externalPostId): bool;

    /**
     * Fetches current performance analytics for a post.
     */
    public function fetchMetrics(SocialAccount $account, string $externalPostId): MetricsResult;

    /**
     * Posts a first comment or reply to a post.
     */
    public function postComment(SocialAccount $account, string $externalPostId, string $commentText): ?string;
}
