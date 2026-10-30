# Architectural Blueprint: Personal AI Social Media Assistant (LinkedIn & X)

**Document Version:** 1.0.0  
**Target Systems:** Laravel 11/12, Inertia.js Vue 3, Redis 7, Laravel Horizon, PostgreSQL 16 (ANSI SQL compliant)

---

## 1. System Architecture & Stack Selection

- **Backend:** Laravel 11/12 with Horizon, Sanctum authentication, Redis queue drivers, and Eloquent ORM.
- **Frontend:** Inertia.js v2 with Vue 3 (Composition API / `<script setup>`), Tailwind CSS v3.4, Lucide icons, Headless UI / Radix Vue.
- **Queue Topology:** Redis queues isolated by priority:
  - `publish-critical`: High-priority scheduled post publishing.
  - `ai-generation`: Async LLM text generation and image generation.
  - `media-transcode`: FFmpeg video transcoding and Spatie image optimizations.
  - `trend-sync`: Scheduled trend collection cron tasks.
  - `analytics-sync`: Post metrics synchronization.
- **Provider Abstraction Architecture:**
  - `PublisherInterface`: Concrete implementations for `TwitterPublisher` (Mixpost Lite adaptation) and `LinkedInPublisher`.
  - `TextGeneratorInterface`: Multi-LLM provider for Anthropic Claude, OpenAI GPT-4o, and Gemini.
  - `MediaGeneratorInterface`: DALL-E 3, Flux (Replicate/Fal.ai), and Stability.
  - `TrendSourceInterface`: RSS feeds, Hacker News, Reddit, and Web search.

---

## 2. Directory Structure Blueprint

```
personal-ai-social-assistant/
├── app/
│   ├── Contracts/
│   │   ├── AI/TextGeneratorInterface.php
│   │   ├── AI/MediaGeneratorInterface.php
│   │   ├── Social/PublisherInterface.php
│   │   └── Trends/TrendSourceInterface.php
│   ├── DTOs/
│   │   ├── PostPayload.php
│   │   ├── PublishResult.php
│   │   ├── MetricsResult.php
│   │   └── DraftPrompt.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── SocialAccountController.php
│   │   │   ├── DraftController.php
│   │   │   ├── AiGenerationController.php
│   │   │   ├── CalendarController.php
│   │   │   ├── InboxController.php
│   │   │   └── AnalyticsController.php
│   ├── Jobs/
│   │   ├── PublishPostJob.php
│   │   ├── ProcessMediaTranscodeJob.php
│   │   ├── FetchTrendItemsJob.php
│   │   └── SyncSocialMetricsJob.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── SocialAccount.php
│   │   ├── InterestCategory.php
│   │   ├── TrendSource.php
│   │   ├── TrendItem.php
│   │   ├── Draft.php
│   │   ├── DraftVersion.php
│   │   ├── MediaAsset.php
│   │   ├── PostMedia.php
│   │   ├── Schedule.php
│   │   ├── PublishAttempt.php
│   │   ├── MetricsSnapshot.php
│   │   ├── Comment.php
│   │   ├── AiUsageLog.php
│   │   ├── Notification.php
│   │   └── AuditLog.php
│   └── Services/
│       ├── AI/ClaudeTextGenerator.php
│       ├── AI/OpenAiTextGenerator.php
│       ├── AI/FluxMediaGenerator.php
│       ├── Social/Twitter/TwitterPublisher.php
│       ├── Social/LinkedIn/LinkedInPublisher.php
│       └── Trends/RssTrendSource.php
├── config/
│   ├── horizon.php
│   ├── services.php
│   └── medialibrary.php
├── database/
│   └── migrations/
│       └── 2026_10_30_000001_create_social_assistant_3nf_schema.php
├── resources/
│   ├── js/
│   │   ├── Components/
│   │   ├── Layouts/
│   │   └── Pages/
└── routes/
    ├── web.php
    └── api.php
```
