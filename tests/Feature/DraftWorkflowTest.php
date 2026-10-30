<?php

namespace Tests\Feature;

use App\Models\Draft;
use App\Models\DraftVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DraftWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Reviewer User',
            'email' => 'reviewer@example.com',
            'password' => bcrypt('password123'),
        ]);
    }

    public function test_draft_initializes_with_in_review_status(): void
    {
        $draft = Draft::create([
            'user_id' => $this->user->id,
            'title' => 'AI Breakthroughs in 2026',
            'status' => Draft::STATUS_IN_REVIEW,
            'target_platforms' => ['linkedin', 'x_twitter'],
        ]);

        $this->assertEquals(Draft::STATUS_IN_REVIEW, $draft->status);
        $this->assertFalse($draft->isApproved());
    }

    public function test_draft_versioning_and_automatic_character_count(): void
    {
        $draft = Draft::create([
            'user_id' => $this->user->id,
            'title' => 'Productivity Tips',
            'status' => Draft::STATUS_IN_REVIEW,
        ]);

        $v1 = DraftVersion::create([
            'draft_id' => $draft->id,
            'version_number' => 1,
            'platform' => 'x_twitter',
            'content' => 'Here are 3 tips to boost your focus today: 1. Deep work block. 2. Turn off notifications. 3. Daily review.',
            'created_by_ai' => true,
        ]);

        $this->assertEquals(106, $v1->character_count);
        $this->assertTrue($v1->created_by_ai);

        // Edit generates version 2
        $v2 = DraftVersion::create([
            'draft_id' => $draft->id,
            'version_number' => 2,
            'platform' => 'x_twitter',
            'content' => 'Top 3 focus habits: 1. Deep blocks 2. Mute alerts 3. Evening review.',
            'created_by_ai' => false,
        ]);

        $draft->refresh();
        $this->assertCount(2, $draft->versions);
        $this->assertEquals($v2->id, $draft->latestVersion('x_twitter')->id);
        $this->assertEquals(68, $v2->character_count);
    }

    public function test_draft_approval_lifecycle(): void
    {
        $draft = Draft::create([
            'user_id' => $this->user->id,
            'title' => 'Weekly Insights',
            'status' => Draft::STATUS_IN_REVIEW,
        ]);

        $this->assertFalse($draft->isApproved());

        // Approve
        $draft->update(['status' => Draft::STATUS_APPROVED]);
        $this->assertTrue($draft->isApproved());

        // Schedule
        $draft->update([
            'status' => Draft::STATUS_SCHEDULED,
            'scheduled_at' => now()->addDay(),
        ]);
        $this->assertTrue($draft->isApproved());

        // Editing sets status back to in_review
        $draft->update(['status' => Draft::STATUS_IN_REVIEW]);
        $this->assertFalse($draft->isApproved());
    }
}
