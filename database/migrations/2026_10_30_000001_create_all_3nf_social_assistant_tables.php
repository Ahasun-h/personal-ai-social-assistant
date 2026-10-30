<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for all 18 entities in strict 3NF.
     */
    public function up(): void
    {
        // 1. users
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->string('timezone', 64)->default('UTC');
            $table->string('avatar_url', 1024)->nullable();
            $table->string('role', 32)->default('owner');
            $table->string('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index(['email', 'role']);
        });

        // 2. social_accounts
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('provider', 32); // 'x_twitter', 'linkedin'
            $table->string('provider_account_id');
            $table->string('name');
            $table->string('username');
            $table->string('avatar_url', 1024)->nullable();
            $table->string('token_type', 32)->default('Bearer');
            $table->text('access_token'); // encrypted
            $table->text('refresh_token')->nullable(); // encrypted
            $table->timestamp('token_expires_at')->nullable();
            $table->json('scopes')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['provider', 'provider_account_id'], 'uq_provider_acc_id');
            $table->index(['user_id', 'provider', 'is_active']);
        });

        // 3. interest_categories
        Schema::create('interest_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 128);
            $table->string('slug', 128);
            $table->text('description')->nullable();
            $table->json('keywords')->nullable();
            $table->text('tone_guidelines')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'slug'], 'uq_user_category_slug');
            $table->index(['user_id', 'is_active']);
        });

        // 4. settings
        Schema::create('settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('key', 128);
            $table->json('value');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'key'], 'uq_user_setting_key');
            $table->index(['user_id', 'key']);
        });

        // 5. trend_sources
        Schema::create('trend_sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('interest_categories')->nullOnDelete();
            $table->string('name');
            $table->string('source_type', 32); // rss, reddit, hackernews, youtube, search
            $table->string('url', 2048);
            $table->integer('fetch_frequency_minutes')->default(60);
            $table->timestamp('last_fetched_at')->nullable();
            $table->string('status', 32)->default('active'); // active, paused, failing
            $table->json('config')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'last_fetched_at']);
        });

        // 6. trend_items
        Schema::create('trend_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('trend_source_id')->constrained('trend_sources')->cascadeOnDelete();
            $table->string('external_id', 512);
            $table->string('title', 512);
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->string('url', 2048);
            $table->string('author')->nullable();
            $table->timestamp('published_at');
            $table->decimal('engagement_score', 8, 2)->default(0.00);
            $table->json('virality_indicators')->nullable();
            $table->boolean('is_bookmarked')->default(false);
            $table->boolean('is_dismissed')->default(false);
            $table->timestamps();

            $table->unique(['trend_source_id', 'external_id'], 'uq_source_external_item');
            $table->index(['trend_source_id', 'published_at']);
            $table->index(['is_bookmarked', 'is_dismissed', 'published_at']);
        });

        // 7. drafts
        Schema::create('drafts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('interest_categories')->nullOnDelete();
            $table->foreignUuid('trend_item_id')->nullable()->constrained('trend_items')->nullOnDelete();
            $table->string('title');
            $table->string('status', 32)->default('draft'); // draft, in_review, approved, scheduled, publishing, published, failed, rejected
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->json('target_platforms')->nullable(); // ["linkedin", "x_twitter"]
            $table->text('ai_prompt_context')->nullable();
            $table->boolean('is_evergreen')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'status', 'scheduled_at']);
            $table->index(['category_id', 'status']);
        });

        // 8. draft_versions
        Schema::create('draft_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('draft_id')->constrained('drafts')->cascadeOnDelete();
            $table->integer('version_number')->default(1);
            $table->string('platform', 32); // universal, linkedin, x_twitter
            $table->longText('content');
            $table->integer('character_count')->default(0);
            $table->json('thread_items')->nullable();
            $table->string('tone', 64)->nullable();
            $table->boolean('created_by_ai')->default(false);
            $table->timestamps();

            $table->unique(['draft_id', 'version_number', 'platform'], 'uq_draft_ver_plat');
            $table->index(['draft_id', 'platform']);
        });

        // 9. media_assets
        Schema::create('media_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('filename');
            $table->string('disk', 64)->default('local');
            $table->string('path', 1024);
            $table->string('mime_type', 128);
            $table->unsignedBigInteger('file_size_bytes');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->string('alt_text', 512)->nullable();
            $table->boolean('is_ai_generated')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'mime_type', 'created_at']);
        });

        // 10. post_media
        Schema::create('post_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('draft_id')->constrained('drafts')->cascadeOnDelete();
            $table->foreignUuid('draft_version_id')->nullable()->constrained('draft_versions')->cascadeOnDelete();
            $table->foreignUuid('media_asset_id')->constrained('media_assets')->restrictOnDelete();
            $table->string('platform', 32)->default('universal');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['draft_id', 'platform', 'sort_order'], 'uq_post_media_order');
            $table->index(['draft_id', 'platform', 'sort_order']);
        });

        // 11. media_generation_requests
        Schema::create('media_generation_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('draft_id')->nullable()->constrained('drafts')->nullOnDelete();
            $table->string('provider', 32); // gemini, openai, flux, stability
            $table->text('prompt');
            $table->text('negative_prompt')->nullable();
            $table->string('aspect_ratio', 16)->default('1:1');
            $table->string('style', 64)->nullable();
            $table->string('status', 32)->default('pending'); // pending, generating, ready, failed
            $table->foreignUuid('result_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->text('error_message')->nullable();
            $table->decimal('cost_usd', 8, 5)->default(0.00000);
            $table->json('raw_response')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'created_at']);
        });

        // 12. schedules
        Schema::create('schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('social_account_id')->constrained('social_accounts')->cascadeOnDelete();
            $table->foreignUuid('draft_id')->constrained('drafts')->cascadeOnDelete();
            $table->timestamp('scheduled_for');
            $table->string('status', 32)->default('pending'); // pending, dispatched, completed, failed, cancelled
            $table->string('lock_key', 128)->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_for']);
            $table->index(['user_id', 'social_account_id', 'scheduled_for']);
        });

        // 13. publish_attempts
        Schema::create('publish_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('schedule_id')->nullable()->constrained('schedules')->nullOnDelete();
            $table->foreignUuid('draft_id')->constrained('drafts')->cascadeOnDelete();
            $table->foreignUuid('social_account_id')->constrained('social_accounts')->cascadeOnDelete();
            $table->integer('attempt_number')->default(1);
            $table->string('status', 32); // in_progress, succeeded, failed
            $table->string('idempotency_key', 128)->unique();
            $table->string('external_post_id')->nullable();
            $table->string('external_post_url', 1024)->nullable();
            $table->string('error_code', 128)->nullable();
            $table->text('error_message')->nullable();
            $table->json('payload_sent')->nullable();
            $table->json('response_received')->nullable();
            $table->timestamp('attempted_at')->useCurrent();
            $table->timestamps();

            $table->index(['draft_id', 'attempt_number']);
            $table->index(['social_account_id', 'status', 'attempted_at']);
        });

        // 14. metrics_snapshots
        Schema::create('metrics_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('social_account_id')->constrained('social_accounts')->cascadeOnDelete();
            $table->foreignUuid('publish_attempt_id')->nullable()->constrained('publish_attempts')->nullOnDelete();
            $table->string('external_post_id');
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('reach')->default(0);
            $table->unsignedBigInteger('engagements')->default(0);
            $table->unsignedBigInteger('likes')->default(0);
            $table->unsignedBigInteger('retweets_reposts')->default(0);
            $table->unsignedBigInteger('comments_count')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->decimal('engagement_rate', 6, 4)->default(0.0000);
            $table->json('raw_metrics')->nullable();
            $table->timestamp('captured_at')->useCurrent();
            $table->timestamps();

            $table->index(['social_account_id', 'external_post_id', 'captured_at']);
        });

        // 15. comments
        Schema::create('comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('social_account_id')->constrained('social_accounts')->cascadeOnDelete();
            $table->string('external_post_id');
            $table->string('external_comment_id');
            $table->string('author_name');
            $table->string('author_handle');
            $table->string('author_avatar_url', 1024)->nullable();
            $table->text('content');
            $table->string('sentiment', 32)->default('neutral'); // positive, neutral, negative
            $table->text('ai_suggested_reply')->nullable();
            $table->string('reply_status', 32)->default('unanswered'); // unanswered, draft, sent, ignored
            $table->string('reply_external_id')->nullable();
            $table->timestamp('published_at');
            $table->timestamps();

            $table->unique(['social_account_id', 'external_comment_id'], 'uq_acc_ext_comment');
            $table->index(['social_account_id', 'external_post_id', 'published_at']);
            $table->index(['reply_status', 'published_at']);
        });

        // 16. ai_usage_logs
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('provider', 32); // gemini, anthropic, openai
            $table->string('model', 64);
            $table->string('operation_type', 64);
            $table->integer('prompt_tokens')->default(0);
            $table->integer('completion_tokens')->default(0);
            $table->integer('total_tokens')->default(0);
            $table->decimal('estimated_cost_usd', 8, 5)->default(0.00000);
            $table->integer('latency_ms')->default(0);
            $table->string('status', 32)->default('success');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['provider', 'operation_type', 'created_at']);
        });

        // 17. notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 128); // publish_success, publish_failed, token_expiring, budget_warning
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at', 'created_at']);
        });

        // 18. audit_logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 64);
            $table->string('auditable_type', 128);
            $table->uuid('auditable_id');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'auditable_type', 'auditable_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('ai_usage_logs');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('metrics_snapshots');
        Schema::dropIfExists('publish_attempts');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('media_generation_requests');
        Schema::dropIfExists('post_media');
        Schema::dropIfExists('media_assets');
        Schema::dropIfExists('draft_versions');
        Schema::dropIfExists('drafts');
        Schema::dropIfExists('trend_items');
        Schema::dropIfExists('trend_sources');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('interest_categories');
        Schema::dropIfExists('social_accounts');
        Schema::dropIfExists('users');
    }
};
