<?php

namespace App\Jobs;

use App\DTOs\PostPayload;
use App\Models\AuditLog;
use App\Models\Draft;
use App\Models\Notification;
use App\Models\PublishAttempt;
use App\Models\Schedule;
use App\Models\SocialAccount;
use App\Services\Social\LinkedIn\LinkedInPublisher;
use App\Services\Social\Twitter\TwitterPublisher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PublishPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly string $scheduleId,
        public readonly string $draftId,
        public readonly string $socialAccountId
    ) {
        $this->onQueue('publish');
    }

    public function handle(TwitterPublisher $twitterPublisher, LinkedInPublisher $linkedInPublisher): void
    {
        $lockKey = "publish:schedule:{$this->scheduleId}";
        $lock = Cache::lock($lockKey, 120);

        if (!$lock->get()) {
            Log::warning("PublishPostJob: Schedule {$this->scheduleId} is currently locked by another worker.");
            return;
        }

        try {
            $schedule = Schedule::find($this->scheduleId);
            $draft = Draft::with(['versions', 'postMedia.asset'])->find($this->draftId);
            $account = SocialAccount::find($this->socialAccountId);

            if (!$schedule || !$draft || !$account) {
                Log::error("PublishPostJob: Missing schedule, draft, or account model.");
                return;
            }

            // HARD RULE: Mandatory explicit human approval
            if (!in_array($draft->status, [Draft::STATUS_APPROVED, Draft::STATUS_SCHEDULED, Draft::STATUS_PUBLISHING])) {
                Log::error("PublishPostJob BLOCKED: Draft {$draft->id} does not have explicit human approval (Status: {$draft->status}).");
                $schedule->update(['status' => Schedule::STATUS_FAILED]);
                return;
            }

            $draft->update(['status' => Draft::STATUS_PUBLISHING]);
            $schedule->update(['status' => Schedule::STATUS_DISPATCHED]);

            // Select platform specific version or fallback to latest
            $version = $draft->versions->firstWhere('platform', $account->provider) ?? $draft->versions->first();
            $content = $version ? $version->content : $draft->title;
            $threadItems = $version ? ($version->thread_items ?? []) : [];
            $mediaCollection = $draft->postMedia->map(fn($pm) => $pm->asset)->filter();

            $idempotencyKey = "pub_" . $this->scheduleId . "_" . $account->id;
            $attemptNumber = PublishAttempt::where('schedule_id', $this->scheduleId)->count() + 1;

            $attempt = PublishAttempt::create([
                'schedule_id' => $this->scheduleId,
                'draft_id' => $draft->id,
                'social_account_id' => $account->id,
                'attempt_number' => $attemptNumber,
                'status' => PublishAttempt::STATUS_IN_PROGRESS,
                'idempotency_key' => $idempotencyKey,
                'payload_sent' => [
                    'content' => $content,
                    'media_count' => $mediaCollection->count(),
                    'thread_count' => count($threadItems),
                ],
                'attempted_at' => now(),
            ]);

            $payload = new PostPayload(
                draftId: $draft->id,
                content: $content,
                platform: $account->provider,
                media: $mediaCollection,
                threadItems: $threadItems,
            );

            // Select publisher driver
            $publisher = $account->provider === 'linkedin' ? $linkedInPublisher : $twitterPublisher;
            $result = $publisher->publish($account, $payload);

            if ($result->isSuccess) {
                $attempt->update([
                    'status' => PublishAttempt::STATUS_SUCCEEDED,
                    'external_post_id' => $result->externalPostId,
                    'external_post_url' => $result->externalPostUrl,
                    'response_received' => $result->rawResponse,
                ]);

                $schedule->update(['status' => Schedule::STATUS_COMPLETED]);
                $draft->update([
                    'status' => Draft::STATUS_PUBLISHED,
                    'published_at' => now(),
                ]);

                Notification::create([
                    'user_id' => $draft->user_id,
                    'type' => 'publish_success',
                    'title' => "Post published on {$account->name}",
                    'message' => "Your post '{$draft->title}' is live at {$result->externalPostUrl}",
                    'data' => ['url' => $result->externalPostUrl],
                ]);

                AuditLog::record('publish_success', $draft, $draft->user_id, null, [
                    'platform' => $account->provider,
                    'url' => $result->externalPostUrl,
                ]);
            } else {
                $attempt->update([
                    'status' => PublishAttempt::STATUS_FAILED,
                    'error_code' => $result->errorCode,
                    'error_message' => $result->errorMessage,
                    'response_received' => $result->rawResponse,
                ]);

                $schedule->update(['status' => Schedule::STATUS_FAILED]);
                $draft->update(['status' => Draft::STATUS_FAILED]);

                Notification::create([
                    'user_id' => $draft->user_id,
                    'type' => 'publish_failed',
                    'title' => "Failed to publish on {$account->name}",
                    'message' => $result->errorMessage ?? 'Unknown error occurred',
                    'data' => ['error' => $result->errorMessage],
                ]);

                AuditLog::record('publish_failed', $draft, $draft->user_id, null, [
                    'error' => $result->errorMessage,
                ]);
            }

        } finally {
            $lock->release();
        }
    }
}
