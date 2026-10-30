# Operational Runbook: Personal AI Social Media Assistant

## 1. System Overview & Service Architecture

The system operates 4 primary daemons in production:
1. **PHP-FPM (Port 9000):** Processes incoming web and REST API requests.
2. **Nginx (Port 80/443):** Web server handling TLS termination, static asset delivery, and proxy pass.
3. **Laravel Horizon:** Manages 4 supervisor worker pools over Redis with fine-grained queue isolation:
   - `supervisor-publisher`: Processes `publish` and `publish-critical` (Priority 1, high reliability, max 3 retries, exponential backoff).
   - `supervisor-ai`: Processes `generate-text` and `ai-generation` (Priority 2, for LLM calls with 180s timeout).
   - `supervisor-media`: Processes `media-image`, `media-video`, and `media-transcode` (Priority 3 & 4, image generation and chunked uploads with 300s timeout).
   - `supervisor-background`: Processes `trends`, `trend-sync`, `analytics`, `analytics-sync`, and `default` (Priority 5 & 6, RSS polling, metrics collection, and notifications).
4. **Cron Scheduler:** Dispatches `schedule:run` every minute to trigger trend discovery and post publishing.

---

## 2. Queue Operations & Horizon Monitoring

### Accessing the Horizon Dashboard
Navigate to `http://your-domain.com/horizon`. Ensure you are authenticated with your owner account.

### Useful CLI Commands
```bash
# Check Horizon status
php artisan horizon:status

# Gracefully terminate and restart Horizon workers after a code update
php artisan horizon:terminate

# Retry a specific failed job
php artisan queue:retry <uuid>

# Retry all failed jobs
php artisan queue:retry all

# Purge failed jobs older than 7 days
php artisan queue:prune-failed --hours=168
```

---

## 3. LinkedIn OAuth 60-Day Token Lifecycle Management

### Background
Personal LinkedIn profiles use tokens issued under the `w_member_social` and `openid` scopes. These tokens have a strict **60-day expiration window** and do not offer perpetual offline refresh tokens.

### Automated Warning Protocol
1. At **Day 53 (7 days before expiry)**, the system creates a high-priority in-app notification and badge in the Settings Vault.
2. If token reaches 0 days without renewal:
   - Any scheduled LinkedIn posts are automatically paused and flagged as `Account Token Expired`.
   - The user is notified with a one-click re-authorization link.

### Re-Authentication Steps
1. Navigate to **Settings > Social Accounts**.
2. Locate the LinkedIn card displaying the warning badge `Expires in X days`.
3. Click **Reconnect LinkedIn Account**.
4. Complete the LinkedIn OAuth consent dialog. The access token is refreshed for another 60 days.

---

## 4. X (Twitter) Metered Cost Control & Budget Safeguards

### Pricing Matrix
- Standard text tweet: **\$0.015**
- Tweet containing an external link: **\$0.20**
- Read / search operations: **\$0.005**

### Budget Safeguards (`CostControlService`)
- **Daily Budget Cap:** Default \$10.00 USD.
- **Monthly Budget Cap:** Default \$150.00 USD.
- **Warning Threshold (80%):** Triggers email/in-app alert when 80% of daily or monthly budget is spent.
- **Hard Circuit Breaker (100%):** Prevents new AI generation jobs or X publishing attempts from executing once budget is reached.

### Emergency Budget Override
If an urgent post must be published when budget cap is met:
```bash
# Edit .env to increase the cap temporarily
DAILY_BUDGET_CAP_USD=25.00

# Clear configuration cache
php artisan config:clear
php artisan config:cache
```

---

## 5. Backup & Disaster Recovery

### Database Backup (PostgreSQL)
```bash
# Export full database dump
docker exec -t social_assistant_postgres pg_dump -U postgres social_assistant > backup_$(date +%Y%m%d_%H%M%S).sql

# Restore from backup
cat backup_YYYYMMDD_HHMMSS.sql | docker exec -i social_assistant_postgres psql -U postgres -d social_assistant
```

### Media Assets Backup
All generated and uploaded images/videos are stored in `storage/app/public`.
Ensure the Docker volume `media_storage` or the directory is backed up to off-site S3 storage nightly.
