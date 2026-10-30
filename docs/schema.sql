-- =============================================================================
-- SYSTEM DATABASE DDL: PERSONAL AI SOCIAL MEDIA ASSISTANT
-- Target Engine: PostgreSQL 14+ / MySQL 8.0+ / SQLite 3 (ANSI-SQL Compatible)
-- Schema Normalization: Third Normal Form (3NF)
-- =============================================================================

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- -----------------------------------------------------------------------------
-- 1. USERS
-- -----------------------------------------------------------------------------
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    timezone VARCHAR(64) NOT NULL DEFAULT 'UTC',
    avatar_url VARCHAR(1024) NULL,
    role VARCHAR(32) NOT NULL DEFAULT 'owner',
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_users_email UNIQUE (email)
);

CREATE INDEX idx_users_email_role ON users(email, role);

-- -----------------------------------------------------------------------------
-- 2. SOCIAL ACCOUNTS
-- -----------------------------------------------------------------------------
CREATE TABLE social_accounts (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL,
    provider VARCHAR(32) NOT NULL, -- 'x_twitter', 'linkedin'
    provider_account_id VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    avatar_url VARCHAR(1024) NULL,
    token_type VARCHAR(32) NOT NULL DEFAULT 'Bearer',
    access_token TEXT NOT NULL,
    refresh_token TEXT NULL,
    token_expires_at TIMESTAMP WITH TIME ZONE NULL,
    scopes JSONB NOT NULL DEFAULT '[]'::jsonb,
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_social_accounts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT uq_social_accounts_provider_acc UNIQUE (provider, provider_account_id)
);

CREATE INDEX idx_social_accounts_user_provider_active ON social_accounts(user_id, provider, is_active);

-- -----------------------------------------------------------------------------
-- 3. INTEREST CATEGORIES
-- -----------------------------------------------------------------------------
CREATE TABLE interest_categories (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL,
    name VARCHAR(128) NOT NULL,
    slug VARCHAR(128) NOT NULL,
    description TEXT NULL,
    keywords JSONB NOT NULL DEFAULT '[]'::jsonb,
    tone_guidelines TEXT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_interest_categories_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT uq_interest_categories_user_slug UNIQUE (user_id, slug)
);

CREATE INDEX idx_interest_categories_user_active ON interest_categories(user_id, is_active);

-- -----------------------------------------------------------------------------
-- 4. SETTINGS
-- -----------------------------------------------------------------------------
CREATE TABLE settings (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL,
    key VARCHAR(128) NOT NULL,
    value JSONB NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_settings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT uq_settings_user_key UNIQUE (user_id, key)
);

CREATE INDEX idx_settings_user_key ON settings(user_id, key);

-- -----------------------------------------------------------------------------
-- 5. TREND SOURCES
-- -----------------------------------------------------------------------------
CREATE TABLE trend_sources (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL,
    category_id UUID NULL,
    name VARCHAR(255) NOT NULL,
    source_type VARCHAR(32) NOT NULL, -- 'rss', 'reddit', 'hackernews', 'youtube', 'search'
    url VARCHAR(2048) NOT NULL,
    fetch_frequency_minutes INTEGER NOT NULL DEFAULT 60,
    last_fetched_at TIMESTAMP WITH TIME ZONE NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'active', -- 'active', 'paused', 'failing'
    config JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_trend_sources_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_trend_sources_category FOREIGN KEY (category_id) REFERENCES interest_categories(id) ON DELETE SET NULL
);

CREATE INDEX idx_trend_sources_user_status ON trend_sources(user_id, status);
CREATE INDEX idx_trend_sources_fetch ON trend_sources(status, last_fetched_at);

-- -----------------------------------------------------------------------------
-- 6. TREND ITEMS
-- -----------------------------------------------------------------------------
CREATE TABLE trend_items (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    trend_source_id UUID NOT NULL,
    external_id VARCHAR(512) NOT NULL,
    title VARCHAR(512) NOT NULL,
    summary TEXT NULL,
    content TEXT NULL,
    url VARCHAR(2048) NOT NULL,
    author VARCHAR(255) NULL,
    published_at TIMESTAMP WITH TIME ZONE NOT NULL,
    engagement_score NUMERIC(8, 2) NOT NULL DEFAULT 0.00,
    virality_indicators JSONB NOT NULL DEFAULT '{}'::jsonb,
    is_bookmarked BOOLEAN NOT NULL DEFAULT FALSE,
    is_dismissed BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_trend_items_source FOREIGN KEY (trend_source_id) REFERENCES trend_sources(id) ON DELETE CASCADE,
    CONSTRAINT uq_trend_items_source_external UNIQUE (trend_source_id, external_id)
);

CREATE INDEX idx_trend_items_source_published ON trend_items(trend_source_id, published_at DESC);
CREATE INDEX idx_trend_items_bookmark_dismiss ON trend_items(is_bookmarked, is_dismissed, published_at DESC);

-- -----------------------------------------------------------------------------
-- 7. DRAFTS
-- -----------------------------------------------------------------------------
CREATE TABLE drafts (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL,
    category_id UUID NULL,
    trend_item_id UUID NULL,
    title VARCHAR(255) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'draft', -- 'draft', 'in_review', 'approved', 'scheduled', 'publishing', 'published', 'failed', 'rejected'
    scheduled_at TIMESTAMP WITH TIME ZONE NULL,
    published_at TIMESTAMP WITH TIME ZONE NULL,
    target_platforms JSONB NOT NULL DEFAULT '["linkedin", "x_twitter"]'::jsonb,
    ai_prompt_context TEXT NULL,
    is_evergreen BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP WITH TIME ZONE NULL,
    CONSTRAINT fk_drafts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_drafts_category FOREIGN KEY (category_id) REFERENCES interest_categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_drafts_trend_item FOREIGN KEY (trend_item_id) REFERENCES trend_items(id) ON DELETE SET NULL
);

CREATE INDEX idx_drafts_user_status_scheduled ON drafts(user_id, status, scheduled_at) WHERE deleted_at IS NULL;
CREATE INDEX idx_drafts_category_status ON drafts(category_id, status);

-- -----------------------------------------------------------------------------
-- 8. DRAFT VERSIONS
-- -----------------------------------------------------------------------------
CREATE TABLE draft_versions (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    draft_id UUID NOT NULL,
    version_number INTEGER NOT NULL DEFAULT 1,
    platform VARCHAR(32) NOT NULL, -- 'universal', 'linkedin', 'x_twitter'
    content TEXT NOT NULL,
    character_count INTEGER NOT NULL DEFAULT 0,
    thread_items JSONB NOT NULL DEFAULT '[]'::jsonb,
    tone VARCHAR(64) NULL,
    created_by_ai BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_draft_versions_draft FOREIGN KEY (draft_id) REFERENCES drafts(id) ON DELETE CASCADE,
    CONSTRAINT uq_draft_versions_draft_ver_plat UNIQUE (draft_id, version_number, platform)
);

CREATE INDEX idx_draft_versions_draft_plat ON draft_versions(draft_id, platform);

-- -----------------------------------------------------------------------------
-- 9. MEDIA ASSETS
-- -----------------------------------------------------------------------------
CREATE TABLE media_assets (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL,
    filename VARCHAR(255) NOT NULL,
    disk VARCHAR(64) NOT NULL DEFAULT 'local',
    path VARCHAR(1024) NOT NULL,
    mime_type VARCHAR(128) NOT NULL,
    file_size_bytes BIGINT NOT NULL,
    width INTEGER NULL,
    height INTEGER NULL,
    duration_seconds INTEGER NULL,
    alt_text VARCHAR(512) NULL,
    is_ai_generated BOOLEAN NOT NULL DEFAULT FALSE,
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_media_assets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_media_assets_user_mime ON media_assets(user_id, mime_type, created_at DESC);

-- -----------------------------------------------------------------------------
-- 10. POST MEDIA (Pivot linking Drafts/Versions to Media)
-- -----------------------------------------------------------------------------
CREATE TABLE post_media (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    draft_id UUID NOT NULL,
    draft_version_id UUID NULL,
    media_asset_id UUID NOT NULL,
    platform VARCHAR(32) NOT NULL DEFAULT 'universal',
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_post_media_draft FOREIGN KEY (draft_id) REFERENCES drafts(id) ON DELETE CASCADE,
    CONSTRAINT fk_post_media_version FOREIGN KEY (draft_version_id) REFERENCES draft_versions(id) ON DELETE CASCADE,
    CONSTRAINT fk_post_media_asset FOREIGN KEY (media_asset_id) REFERENCES media_assets(id) ON DELETE RESTRICT,
    CONSTRAINT uq_post_media_order UNIQUE (draft_id, platform, sort_order)
);

CREATE INDEX idx_post_media_draft_plat ON post_media(draft_id, platform, sort_order);

-- -----------------------------------------------------------------------------
-- 11. MEDIA GENERATION REQUESTS
-- -----------------------------------------------------------------------------
CREATE TABLE media_generation_requests (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL,
    draft_id UUID NULL,
    provider VARCHAR(32) NOT NULL, -- 'gemini', 'openai', 'flux', 'stability'
    prompt TEXT NOT NULL,
    negative_prompt TEXT NULL,
    aspect_ratio VARCHAR(16) NOT NULL DEFAULT '1:1',
    style VARCHAR(64) NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pending', -- 'pending', 'generating', 'ready', 'failed'
    result_asset_id UUID NULL,
    error_message TEXT NULL,
    cost_usd NUMERIC(8, 5) NOT NULL DEFAULT 0.00000,
    raw_response JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_media_gen_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_media_gen_draft FOREIGN KEY (draft_id) REFERENCES drafts(id) ON DELETE SET NULL,
    CONSTRAINT fk_media_gen_asset FOREIGN KEY (result_asset_id) REFERENCES media_assets(id) ON DELETE SET NULL
);

CREATE INDEX idx_media_gen_user_status ON media_generation_requests(user_id, status, created_at DESC);

-- -----------------------------------------------------------------------------
-- 12. SCHEDULES
-- -----------------------------------------------------------------------------
CREATE TABLE schedules (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL,
    social_account_id UUID NOT NULL,
    draft_id UUID NOT NULL,
    scheduled_for TIMESTAMP WITH TIME ZONE NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pending', -- 'pending', 'dispatched', 'completed', 'failed', 'cancelled'
    lock_key VARCHAR(128) NULL,
    locked_at TIMESTAMP WITH TIME ZONE NULL,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_schedules_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_schedules_account FOREIGN KEY (social_account_id) REFERENCES social_accounts(id) ON DELETE CASCADE,
    CONSTRAINT fk_schedules_draft FOREIGN KEY (draft_id) REFERENCES drafts(id) ON DELETE CASCADE
);

CREATE INDEX idx_schedules_status_time ON schedules(status, scheduled_for ASC);
CREATE INDEX idx_schedules_user_account ON schedules(user_id, social_account_id, scheduled_for);

-- -----------------------------------------------------------------------------
-- 13. PUBLISH ATTEMPTS
-- -----------------------------------------------------------------------------
CREATE TABLE publish_attempts (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    schedule_id UUID NULL,
    draft_id UUID NOT NULL,
    social_account_id UUID NOT NULL,
    attempt_number INTEGER NOT NULL DEFAULT 1,
    status VARCHAR(32) NOT NULL, -- 'in_progress', 'succeeded', 'failed'
    idempotency_key VARCHAR(128) NOT NULL,
    external_post_id VARCHAR(255) NULL,
    external_post_url VARCHAR(1024) NULL,
    error_code VARCHAR(128) NULL,
    error_message TEXT NULL,
    payload_sent JSONB NOT NULL DEFAULT '{}'::jsonb,
    response_received JSONB NOT NULL DEFAULT '{}'::jsonb,
    attempted_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_publish_attempts_schedule FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE SET NULL,
    CONSTRAINT fk_publish_attempts_draft FOREIGN KEY (draft_id) REFERENCES drafts(id) ON DELETE CASCADE,
    CONSTRAINT fk_publish_attempts_account FOREIGN KEY (social_account_id) REFERENCES social_accounts(id) ON DELETE CASCADE,
    CONSTRAINT uq_publish_attempts_idempotency UNIQUE (idempotency_key)
);

CREATE INDEX idx_publish_attempts_draft_attempt ON publish_attempts(draft_id, attempt_number DESC);
CREATE INDEX idx_publish_attempts_account_status ON publish_attempts(social_account_id, status, attempted_at DESC);

-- -----------------------------------------------------------------------------
-- 14. METRICS SNAPSHOTS
-- -----------------------------------------------------------------------------
CREATE TABLE metrics_snapshots (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    social_account_id UUID NOT NULL,
    publish_attempt_id UUID NULL,
    external_post_id VARCHAR(255) NOT NULL,
    impressions BIGINT NOT NULL DEFAULT 0,
    reach BIGINT NOT NULL DEFAULT 0,
    engagements BIGINT NOT NULL DEFAULT 0,
    likes BIGINT NOT NULL DEFAULT 0,
    retweets_reposts BIGINT NOT NULL DEFAULT 0,
    comments_count BIGINT NOT NULL DEFAULT 0,
    clicks BIGINT NOT NULL DEFAULT 0,
    engagement_rate NUMERIC(6, 4) NOT NULL DEFAULT 0.0000,
    raw_metrics JSONB NOT NULL DEFAULT '{}'::jsonb,
    captured_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_metrics_snapshots_account FOREIGN KEY (social_account_id) REFERENCES social_accounts(id) ON DELETE CASCADE,
    CONSTRAINT fk_metrics_snapshots_attempt FOREIGN KEY (publish_attempt_id) REFERENCES publish_attempts(id) ON DELETE SET NULL
);

CREATE INDEX idx_metrics_snapshots_post_captured ON metrics_snapshots(social_account_id, external_post_id, captured_at DESC);

-- -----------------------------------------------------------------------------
-- 15. COMMENTS
-- -----------------------------------------------------------------------------
CREATE TABLE comments (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    social_account_id UUID NOT NULL,
    external_post_id VARCHAR(255) NOT NULL,
    external_comment_id VARCHAR(255) NOT NULL,
    author_name VARCHAR(255) NOT NULL,
    author_handle VARCHAR(255) NOT NULL,
    author_avatar_url VARCHAR(1024) NULL,
    content TEXT NOT NULL,
    sentiment VARCHAR(32) NOT NULL DEFAULT 'neutral', -- 'positive', 'neutral', 'negative'
    ai_suggested_reply TEXT NULL,
    reply_status VARCHAR(32) NOT NULL DEFAULT 'unanswered', -- 'unanswered', 'draft', 'sent', 'ignored'
    reply_external_id VARCHAR(255) NULL,
    published_at TIMESTAMP WITH TIME ZONE NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comments_account FOREIGN KEY (social_account_id) REFERENCES social_accounts(id) ON DELETE CASCADE,
    CONSTRAINT uq_comments_account_ext_comment UNIQUE (social_account_id, external_comment_id)
);

CREATE INDEX idx_comments_account_post_published ON comments(social_account_id, external_post_id, published_at DESC);
CREATE INDEX idx_comments_reply_status ON comments(reply_status, published_at DESC);

-- -----------------------------------------------------------------------------
-- 16. AI USAGE LOGS
-- -----------------------------------------------------------------------------
CREATE TABLE ai_usage_logs (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL,
    provider VARCHAR(32) NOT NULL, -- 'gemini', 'anthropic', 'openai'
    model VARCHAR(64) NOT NULL,
    operation_type VARCHAR(64) NOT NULL, -- 'idea_generation', 'draft_writing', 'refine', 'thread_expansion', 'image_generation'
    prompt_tokens INTEGER NOT NULL DEFAULT 0,
    completion_tokens INTEGER NOT NULL DEFAULT 0,
    total_tokens INTEGER NOT NULL DEFAULT 0,
    estimated_cost_usd NUMERIC(8, 5) NOT NULL DEFAULT 0.00000,
    latency_ms INTEGER NOT NULL DEFAULT 0,
    status VARCHAR(32) NOT NULL DEFAULT 'success',
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ai_usage_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_ai_usage_user_created ON ai_usage_logs(user_id, created_at DESC);
CREATE INDEX idx_ai_usage_provider_op ON ai_usage_logs(provider, operation_type, created_at DESC);

-- -----------------------------------------------------------------------------
-- 17. NOTIFICATIONS
-- -----------------------------------------------------------------------------
CREATE TABLE notifications (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL,
    type VARCHAR(128) NOT NULL, -- 'publish_success', 'publish_failed', 'token_expiring', 'budget_warning', 'trend_alert'
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSONB NOT NULL DEFAULT '{}'::jsonb,
    read_at TIMESTAMP WITH TIME ZONE NULL,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_notifications_user_unread ON notifications(user_id, read_at, created_at DESC);

-- -----------------------------------------------------------------------------
-- 18. AUDIT LOGS
-- -----------------------------------------------------------------------------
CREATE TABLE audit_logs (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NULL,
    action VARCHAR(64) NOT NULL, -- 'create', 'update', 'delete', 'approve', 'publish', 'regenerate', 'disconnect'
    auditable_type VARCHAR(128) NOT NULL,
    auditable_id UUID NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(512) NULL,
    old_values JSONB NULL,
    new_values JSONB NULL,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_audit_logs_user_auditable ON audit_logs(user_id, auditable_type, auditable_id, created_at DESC);
CREATE INDEX idx_audit_logs_action_created ON audit_logs(action, created_at DESC);
