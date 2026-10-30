<?php

namespace Tests\Feature;

use App\Models\Draft;
use App\Models\Setting;
use App\Models\SocialAccount;
use App\Models\TrendItem;
use App\Models\TrendSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Test Owner',
            'email' => 'owner@example.com',
            'password' => 'secret123',
        ]);

        $this->actingAs($this->user);
    }

    public function test_auth_me_endpoint_returns_user(): void
    {
        $response = $this->getJson('/api/v1/auth/me');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'owner@example.com');
    }

    public function test_social_accounts_endpoint_returns_active_accounts(): void
    {
        SocialAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'x_twitter',
            'account_id' => 'x_123',
            'name' => 'Owner X',
            'token' => 'token123',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/accounts');
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_drafts_listing_endpoint(): void
    {
        Draft::create([
            'user_id' => $this->user->id,
            'title' => 'Test Draft API',
            'status' => Draft::STATUS_IN_REVIEW,
        ]);

        $response = $this->getJson('/api/v1/drafts');
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_draft_approval_workflow(): void
    {
        $draft = Draft::create([
            'user_id' => $this->user->id,
            'title' => 'Approve Me',
            'status' => Draft::STATUS_IN_REVIEW,
        ]);

        $response = $this->postJson("/api/v1/drafts/{$draft->id}/approve");
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Draft::STATUS_APPROVED);

        $draft->refresh();
        $this->assertEquals(Draft::STATUS_APPROVED, $draft->status);
    }

    public function test_trends_endpoint(): void
    {
        $source = TrendSource::create([
            'user_id' => $this->user->id,
            'name' => 'Hacker News Front Page',
            'driver' => 'hackernews',
            'source_identifier' => 'https://news.ycombinator.com',
            'is_active' => true,
        ]);

        TrendItem::create([
            'trend_source_id' => $source->id,
            'external_id' => 'hn_12345',
            'title' => 'Breakthrough AI Announcement',
            'summary' => 'Deep dive into autonomous agent systems.',
            'url' => 'https://news.ycombinator.com/item?id=12345',
            'published_at' => now(),
            'relevance_score' => 95.5,
            'velocity_score' => 88.0,
            'combined_score' => 92.0,
            'status' => 'new',
        ]);

        $response = $this->getJson('/api/v1/trends/items');
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_settings_retrieval(): void
    {
        Setting::create([
            'user_id' => $this->user->id,
            'key' => 'brand_voice_tone',
            'value' => 'authoritative, analytical, punchy',
        ]);

        $response = $this->getJson('/api/v1/settings');
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_audits_endpoint_returns_logs(): void
    {
        \App\Models\AuditLog::create([
            'user_id' => $this->user->id,
            'action' => 'test_audit_event',
            'auditable_type' => \App\Models\User::class,
            'auditable_id' => $this->user->id,
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->getJson('/api/v1/audits');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_trend_dismiss_endpoint(): void
    {
        $source = TrendSource::create([
            'user_id' => $this->user->id,
            'name' => 'Tech RSS',
            'driver' => 'rss',
            'source_identifier' => 'https://example.com/rss',
            'is_active' => true,
        ]);

        $item = TrendItem::create([
            'trend_source_id' => $source->id,
            'external_id' => 'rss_123',
            'title' => 'Dismiss Me',
            'url' => 'https://example.com/item',
            'status' => 'new',
        ]);

        $response = $this->patchJson("/api/v1/trends/items/{$item->id}/dismiss");
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_dismissed', true);
    }
}
