# Software Requirements Specification (SRS)
## Personal AI Social Media Assistant (LinkedIn & X)
**Document Version:** 1.0.0  
**Status:** Approved for Stage 2 Architecture & Design  
**Target Platform:** Laravel 11/12 + Inertia.js (Vue 3) + Redis/Horizon + MySQL/PostgreSQL  

---

## 1. Executive Summary & Project Objectives

### 1.1 Executive Summary
The **Personal AI Social Media Assistant** is a self-hosted, single-user social media curation, generation, review, scheduling, publishing, and analytics platform. It automates the discovery of trending industry topics across public web sources, produces high-engagement drafts tailored specifically to **LinkedIn** and **X (Twitter)**, generates accompanying visual media (images, quote cards, carousels, transcoded video clips), and enforces an explicit human-in-the-loop review workflow before automated publishing.

The system is architecturally adapted from **Mixpost Lite (MIT)**, extended with an autonomous AI curation pipeline, media generation engines (DALL-E 3, Flux, Canvas/Imagick, FFmpeg), and metered API cost-control guards.

### 1.2 Core Project Objectives
1. **Time-to-Content Optimization:** Reduce the time required to research, author, format, and schedule high-quality social posts from ~60 minutes to < 5 minutes per day.
2. **Strict Platform Policy & Terms Compliance:** Strictly comply with LinkedIn Developer Terms and X Developer Agreements by requiring explicit user human approval for every automated dispatch, completely avoiding unapproved scraping feeds or spam bots.
3. **API Cost Guardrails:** Prevent unexpected cloud API and metered platform costs through real-time balance tracking, conservative caching, and explicit cost warnings (e.g. X metered $0.005/read, $0.015/post, and $0.20/post with link).
4. **Resilient Automated Publishing:** Provide guaranteed at-least-once scheduled publishing with independent failure isolation (if X fails, LinkedIn still posts) and exponential backoff retry mechanisms.
5. **Privacy & Data Sovereignty:** Run as a self-hosted single-tenant application with local or private S3-compatible asset storage, preserving all draft history, analytics, and credential tokens under complete user control.

---

## 2. Target User Personas & Roles (RBAC)

Because this is a dedicated, single-user personal productivity system, RBAC is streamlined into two system states:

| Role | Access Level | Description |
| :--- | :--- | :--- |
| **System Owner (Admin)** | Full Read/Write/Execute | The primary user holding full credentials to configure API keys, connect social accounts, review drafts, schedule posts, trigger manual syncs, and view cost audits. |
| **System Worker (Daemon/CLI)** | Background Automated Execution | Laravel Horizon / Queue Worker processes executing cron schedules, trend ingestion, background LLM generations, media transcoding, and rate-limited API dispatches. |
| **Guest / Anonymous** | No Access (Redirect to `/login`) | Unauthenticated requests are completely blocked by authentication middleware, with rate-limited login endpoints. |

---

## 3. Functional Requirements

### Module 1: Authentication & Settings (F1 – F4)
- **F1: Single-User Authentication & Password Security:** Secure password hashing (bcrypt/Argon2id), rate-limited login attempts (max 5 per minute), session expiration and CSRF protection.
- **F2: Multi-Factor Authentication (TOTP 2FA):** Optional RFC 6238 TOTP two-factor authentication with backup recovery codes.
- **F3: System Settings & Brand Voice Vault:** Encrypted storage for API keys (AES-256-GCM), interest categories, keywords, excluded topics, and brand voice guidelines (tone, language, emoji frequency, banned words, sample posts).
- **F4: Cost Control & Budget Caps:** Daily and monthly spending caps for LLM inference and X API usage ($0.005 read, $0.015 post, $0.20 post with link), with warnings at 80% and a hard stop at 100%.

### Module 2: Account Connection & Identity (A1 – A5)
- **A1: LinkedIn OAuth 2.0 Integration:** Connect LinkedIn personal profile via self-serve product with `w_member_social`, `openid`, `profile`, `email` scopes; 60-day token persistence; Member URN storage.
- **A2: X (Twitter) OAuth 2.0 Integration:** OAuth 2.0 PKCE connection with offline refresh tokens (`tweet.read`, `tweet.write`, `users.read`, `offline.access`).
- **A3: Token Health Monitoring:** Live status dashboard displaying days-until-expiry countdowns with one-click reconnect.
- **A4: Proactive Expiry Alerts & Safe Pause:** Automated notifications 7 days before token expiry; scheduled posts for disconnected accounts are automatically paused with clear warnings.
- **A5: Account Disconnection & Token Revocation:** Safely disconnect an account, revoke and delete stored tokens from the vault.

### Module 3: Trend Collection & Content Ingestion (T1 – T7)
- **T1: Multi-Source Scheduled Ingestion:** Pull topics from Google Trends, RSS feeds, Hacker News, YouTube, and configured keyword searches.
- **T2: Configurable Ingestion Drivers:** Each source can be individually enabled/disabled with independent polling schedules.
- **T3: Deduplication & Semantic Clustering:** Group similar news stories using URL normalization and Levenshtein similarity clustering.
- **T4: Trend Scoring Engine:** Compute topic scores based on freshness velocity, keyword relevance to owner interests, and past performance.
- **T5: Trend Feed Interface:** Interactive feed to view, bookmark/pin, dismiss, or manually add topics from external URLs.
- **T6: Attribution & Source Tracking:** Retain original source metadata and URLs; enforce inspiration-only policy (never plagiarize).
- **T7: Rate Limit & Metered Protection:** Strictly avoid calling paid X search APIs to prevent unnecessary read fees; respect external source rate limits.

### Module 4: AI Draft Generation (G1 – G8)
- **G1: Automated Multi-Platform AI Drafting:** Generate drafts from selected topics either on schedule or on demand.
- **G2: Platform Adaptation:** Produce short-form, thread-capable drafts for X (<= 280 chars) and long-form structured drafts for LinkedIn (<= 3,000 chars).
- **G3: Brand Voice & Guardrails:** Enforce brand voice tone parameters and strip banned buzzwords.
- **G4: Multi-Option Ideation:** Provide multiple draft variations/angles (contrarian, data-driven, storytelling, question) per topic.
- **G5: In-Place Refinement:** One-click actions to regenerate, shorten, expand, adjust tone, or translate drafts.
- **G6: Hashtags & Visual Prompts:** Generate contextual hashtags and detailed image generation prompts for each draft.
- **G7: Draft Version History & Diffs:** Record every text revision with diff inspection and one-click rollback.
- **G8: Content Moderation Guard:** Automated moderation check on generated text before entering review inbox.

### Module 5: Media Generation & Self-Service Asset Management (M1 – M14)
- **M1: Independent Media Regeneration:** Regenerate image or video assets independently of post copy.
- **M2: Prompt Customization:** Edit image prompts with a "reset to auto-prompt" fallback.
- **M3: Version History & Cost Attribution:** Track every media iteration with provider, prompt, cost, and timestamp.
- **M4: Version Switching:** Easily select and switch back to any earlier generated asset before publishing.
- **M5: Multi-Model Provider Selection:** Support DALL-E 3, Flux (Replicate/Fal.ai), and Stability.
- **M6: Aspect-Ratio Presets:** 1:1 Square, 16:9 Landscape, and 4:5 Portrait formats.
- **M7: Image Variations:** Create visual variations from an existing approved asset.
- **M8: Custom File Uploads:** Upload or replace with personal files (images, MP4 videos, PDF slide carousels).
- **M9: Text-Only Fallback:** Remove media and publish as pure text.
- **M10: Cost Estimator:** Real-time generation cost estimates and running monthly totals.
- **M11: Generation Limits:** Configurable limits per post and per day to prevent runaway spending.
- **M12: Safe Generation Failures:** Failed generations never overwrite the currently selected version.
- **M13: AI Disclosure Tagging:** Flag generated media as AI-generated for platform compliance.
- **M14: Safety & Privacy Moderation:** Prevent generation of identifiable real people or unsafe imagery.

### Module 6: Review Inbox (R1 – R7)
- **R1: Workflow Inbox:** All new drafts enter inbox with status `in_review`.
- **R2: Review Actions:** Approve, edit, regenerate, reject, or snooze.
- **R3: Native Side-by-Side Previews:** Live previews mimicking LinkedIn desktop cards (with 3-line fold) and X tweet cards.
- **R4: Dynamic Validation Meters:** Real-time character counters (LinkedIn 3,000 / X 280) and URL length accounting (23 chars).
- **R5: Re-Review on Edit:** Modifying post text resets approval state until re-confirmed.
- **R6: Mandatory Human Approval (Hard Rule):** No post can ever publish without explicit owner approval.
- **R7: Media Generation Lock:** Approval blocked while media is generating, with option to publish text-only.

### Module 7: Calendar & Scheduling (S1 – S7)
- **S1: Interactive Multi-View Calendar:** Month, week, and day views with drag-and-drop rescheduling.
- **S2: Quick Slot Adjustment:** Edit time directly from post cards.
- **S3: Smart Slot Suggestions:** Heatmap suggestions based on historical engagement patterns and preferred windows.
- **S4: Queue Overview:** Comprehensive queue view with real-time status.
- **S5: Timezone-Aware Storage:** Localized display with UTC database normalization.
- **S6: Instant Pause & Cancellation:** Pause entire queue or unschedule individual posts before dispatch.
- **S7: Pre-Flight Rule Validation:** Validate character limits, media types, aspect ratios, and file sizes at scheduling time.

### Module 8: Publishing Engine (P1 – P7)
- **P1: Publisher Abstraction:** Clean `PublisherInterface` with `XPublisher` and `LinkedInPublisher`.
- **P2: Resilient Queues:** Laravel Horizon queue workers with exponential backoff retries.
- **P3: Idempotency Keys:** Atomic locks and UUID idempotency keys prevent duplicate dispatches.
- **P4: Approved Media Locking:** Publish exactly the media version selected at approval time.
- **P5: Diagnostic Failure Notifications:** Detailed failure alerts with one-click retry or reschedule.
- **P6: Remote Post Linkage:** Store external post IDs and direct canonical URLs after publishing.
- **P7: LinkedIn Posts API Compliance:** REST Posts API with version header and author Member URN (`urn:li:person:...`).

### Module 9: Analytics & Performance Tracking (N1 – N6)
- **N1: Automated Metric Synchronization:** Metrics collection at 1h, 24h, 72h, and 7d post-publish.
- **N2: X Analytics:** Impressions, likes, replies, reposts, and clicks.
- **N3: LinkedIn Analytics:** Fetch API social metadata with manual entry fallback.
- **N4: Executive Dashboard:** Best topics, formats, posting times, and platform comparison.
- **N5: Continuous Learning Loop:** High performers feed back into topic scoring and prompt generation.
- **N6: Engagement Inbox & Comment Replies:** Collect post comments with sentiment analysis and AI suggested replies.

### Module 10: Notifications & Audit (X1 – X2)
- **X1: Multi-Channel Alerts:** Real-time browser notifications and webhooks (Discord/Telegram) for draft readiness, dispatches, token expirations, and budget limits.
- **X2: Immutable Audit Log:** Complete event log of AI generations, user edits, approvals, dispatches, and API costs.
