<?php

namespace Tests\Feature;

use App\DTOs\PublishResult;
use App\Jobs\PublishPostJob;
use App\Models\Draft;
use App\Models\Schedule;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Social\LinkedIn\LinkedInPublisher;
use App\Services\Social\Twitter\TwitterPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class PublishingIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private SocialAccount $xAccount;
    private SocialAccount $linkedInAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Test Owner',
            'email' => 'owner@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $this->xAccount = SocialAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'x_twitter',
            'account_id' => 'x_12345',
            'name' => 'Owner X Profile',
            'token' => 'mock_token',
            'is_active' => true,
        ]);

        $this->linkedInAccount = SocialAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'linkedin',
            'account_id' => 'urn:li:person:xyz789',
            'name' => 'Owner LinkedIn Profile',
            'token' => 'mock_linkedin_token',
            'is_active' => true,
        ]);
    }

    public function test_unapproved_draft_is_blocked_from_publishing(): void
    {
        // Status is 'in_review', NOT approved
        $draft = Draft::create([
            'user_id' => $this->user->id,
            'title' => 'Unapproved draft',
            'status' => Draft::STATUS_IN_REVIEW,
        ]);

        $schedule = Schedule::create([
            'draft_id' => $draft->id,
            'social_account_id' => $this->xAccount->id,
            'scheduled_at' => now(),
            'status' => Schedule::STATUS_QUEUED,
        ]);

        $twitterMock = Mockery::mock(TwitterPublisher::class);
        $twitterMock->shouldNotReceive('publish');

        $linkedInMock = Mockery::mock(LinkedInPublisher::class);
        $linkedInMock->shouldNotReceive('publish');

        $job = new PublishPostJob($schedule->id, $draft->id, $this->xAccount->id);
        $job->handle($twitterMock, $linkedInMock);

        $schedule->refresh();
        $this->assertEquals(Schedule::STATUS_FAILED, $schedule->status);

        // Verify draft was never marked published
        $draft->refresh();
        $this->assertNotEquals(Draft::STATUS_PUBLISHED, $draft->status);
    }

    public function test_approved_draft_publishes_successfully_and_records_idempotency(): void
    {
        $draft = Draft::create([
            'user_id' => $this->user->id,
            'title' => 'Approved draft ready to publish',
            'status' => Draft::STATUS_APPROVED,
        ]);

        $schedule = Schedule::create([
            'draft_id' => $draft->id,
            'social_account_id' => $this->xAccount->id,
            'scheduled_at' => now(),
            'status' => Schedule::STATUS_QUEUED,
        ]);

        $twitterMock = Mockery::mock(TwitterPublisher::class);
        $twitterMock->shouldReceive('publish')
            ->once()
            ->andReturn(PublishResult::success('tweet_987654321', 'https://x.com/owner/status/987654321', ['id' => '987654321']));

        $linkedInMock = Mockery::mock(LinkedInPublisher::class);

        $job = new PublishPostJob($schedule->id, $draft->id, $this->xAccount->id);
        $job->handle($twitterMock, $linkedInMock);

        $schedule->refresh();
        $this->assertEquals(Schedule::STATUS_COMPLETED, $schedule->status);

        $draft->refresh();
        $this->assertEquals(Draft::STATUS_PUBLISHED, $draft->status);

        $this->assertDatabaseHas('publish_attempts', [
            'schedule_id' => $schedule->id,
            'status' => 'succeeded',
            'external_post_id' => 'tweet_987654321',
        ]);
    }

    public function test_concurrent_lock_prevents_duplicate_dispatches(): void
    {
        $draft = Draft::create([
            'user_id' => $this->user->id,
            'title' => 'Draft with lock',
            'status' => Draft::STATUS_APPROVED,
        ]);

        $schedule = Schedule::create([
            'draft_id' => $draft->id,
            'social_account_id' => $this->xAccount->id,
            'scheduled_at' => now(),
            'status' => Schedule::STATUS_QUEUED,
        ]);

        // Manually acquire lock for this schedule
        $lockKey = "publish:schedule:{$schedule->id}";
        $lock = Cache::lock($lockKey, 120);
        $lock->get();

        $twitterMock = Mockery::mock(TwitterPublisher::class);
        $twitterMock->shouldNotReceive('publish');

        $linkedInMock = Mockery::mock(LinkedInPublisher::class);
        $linkedInMock->shouldNotReceive('publish');

        // Job attempts to run while locked
        $job = new PublishPostJob($schedule->id, $draft->id, $this->xAccount->id);
        $job->handle($twitterMock, $linkedInMock);

        // Schedule should still be queued, not completed or failed by duplicate job
        $schedule->refresh();
        $this->assertEquals(Schedule::STATUS_QUEUED, $schedule->status);

        $lock->release();
    }
}
