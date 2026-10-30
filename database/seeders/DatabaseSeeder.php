<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\SocialAccount;
use App\Models\InterestCategory;
use App\Models\TrendSource;
use App\Models\TrendItem;
use App\Models\Draft;
use App\Models\DraftVersion;
use App\Models\MediaAsset;
use App\Models\PostMedia;
use App\Models\Schedule;
use App\Models\MetricsSnapshot;
use App\Models\Setting;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Default Owner User
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Alex Rivera',
            'email' => 'alex@synthex.internal',
            'password_hash' => Hash::make('password123'),
            'timezone' => 'America/New_York',
            'avatar_url' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=250&auto=format&fit=crop&q=80',
            'role' => 'owner',
        ]);

        // 2. Connect Social Accounts
        $xAccount = SocialAccount::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'provider' => 'x_twitter',
            'provider_account_id' => '14920392019482',
            'name' => 'Alex Rivera',
            'username' => 'alexrivera_tech',
            'avatar_url' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=250&auto=format&fit=crop&q=80',
            'token_type' => 'Bearer',
            'access_token' => 'x_mock_access_token_secure_vault',
            'refresh_token' => 'x_mock_refresh_token_secure_vault',
            'token_expires_at' => now()->addDays(28),
            'scopes' => ['tweet.read', 'tweet.write', 'users.read', 'offline.access'],
            'metadata' => ['followers_count' => 12450, 'tier' => 'Basic Developer'],
            'is_active' => true,
        ]);

        $liAccount = SocialAccount::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'provider' => 'linkedin',
            'provider_account_id' => 'urn:li:person:AQXe492Jkl9',
            'name' => 'Alex Rivera, PE',
            'username' => 'alex-rivera-architect',
            'avatar_url' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=250&auto=format&fit=crop&q=80',
            'token_type' => 'Bearer',
            'access_token' => 'li_mock_access_token_secure_vault',
            'refresh_token' => 'li_mock_refresh_token_secure_vault',
            'token_expires_at' => now()->addDays(52),
            'scopes' => ['w_member_social', 'openid', 'profile', 'email'],
            'metadata' => ['headline' => 'Staff Distributed Systems Architect', 'connections' => 4820],
            'is_active' => true,
        ]);

        // 3. Interest Categories
        $catSystems = InterestCategory::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'name' => 'Distributed Systems & Cloud',
            'slug' => 'distributed-systems',
            'description' => 'Architectural trade-offs, consensus algorithms, microservices vs monoliths, and low-latency systems.',
            'keywords' => ['raft', 'kafka', 'event-driven', 'monolith', 'grpc', 'kubernetes', 'scaling'],
            'tone_guidelines' => 'Analytical, pragmatic, engineering-grounded, zero-hype, data-backed.',
            'is_active' => true,
        ]);

        $catAI = InterestCategory::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'name' => 'AI Engineering & LLM Infra',
            'slug' => 'ai-infra',
            'description' => 'LLM serving, speculative decoding, agentic workflows, memory graphs, and production inference.',
            'keywords' => ['vllm', 'speculative decoding', 'inference', 'rag', 'context window', 'quantization'],
            'tone_guidelines' => 'Visionary yet technical, code snippets, benchmarks over platitudes.',
            'is_active' => true,
        ]);

        // 4. Trend Sources & Items
        $hnSource = TrendSource::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'category_id' => $catSystems->id,
            'name' => 'Hacker News Frontpage',
            'source_type' => 'hackernews',
            'url' => 'https://hacker-news.firebaseio.com/v0/topstories.json',
            'status' => 'active',
            'fetch_frequency_minutes' => 60,
            'last_fetched_at' => now()->subMinutes(12),
        ]);

        $trend1 = TrendItem::create([
            'id' => (string) Str::uuid(),
            'trend_source_id' => $hnSource->id,
            'external_id' => 'hn_41982301',
            'title' => 'Why Modular Monoliths Outperform Microservices for 95% of Scale',
            'url' => 'https://architect-digest.io/modular-monolith-2026',
            'summary' => 'Comprehensive retrospective analyzing latency, operational overhead, and distributed transaction pitfalls across 40 mid-market engineering teams.',
            'content' => 'Full article detailing why in-process boundaries provide lower latency and superior developer velocity compared to premature network microservices.',
            'published_at' => now()->subHours(3),
            'engagement_score' => 96.50,
            'is_bookmarked' => false,
            'is_dismissed' => false,
        ]);

        $trend2 = TrendItem::create([
            'id' => (string) Str::uuid(),
            'trend_source_id' => $hnSource->id,
            'external_id' => 'hn_41985412',
            'title' => 'Speculative Decoding in Production: Cutting 400B Token Serving Latency by 65%',
            'url' => 'https://ai-infra-labs.org/speculative-decoding-benchmarks',
            'summary' => 'Empirical evaluation of speculative draft models paired with deep models under dynamic batch workloads in multi-GPU clusters.',
            'content' => 'In-depth benchmarks of GPU memory-bandwidth saturation and speculative draft acceptance rates.',
            'published_at' => now()->subHours(6),
            'engagement_score' => 91.20,
            'is_bookmarked' => true,
            'is_dismissed' => false,
        ]);

        // 5. Media Assets
        $media1 = MediaAsset::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'filename' => 'monolith-vs-microservices.png',
            'disk' => 'local',
            'path' => 'media/monolith-vs-microservices-arch.png',
            'mime_type' => 'image/png',
            'file_size_bytes' => 482910,
            'width' => 1200,
            'height' => 675,
            'alt_text' => 'Architecture diagram contrasting distributed microservices with modular monolith',
            'is_ai_generated' => true,
            'metadata' => ['provider' => 'flux', 'prompt' => 'Clean modern architectural diagram, dark neon aesthetic.'],
        ]);

        // 6. Draft 1: In Review (Multi-Platform)
        $draftReview = Draft::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'category_id' => $catSystems->id,
            'trend_item_id' => $trend1->id,
            'title' => 'The Microservice Tax: Why We Consolidated Back to a Modular Monolith',
            'status' => Draft::STATUS_IN_REVIEW,
            'target_platforms' => ['linkedin', 'x_twitter'],
            'ai_prompt_context' => 'Contrast distributed debugging complexity with single-binary deployment simplicity.',
            'is_evergreen' => true,
        ]);

        DraftVersion::create([
            'id' => (string) Str::uuid(),
            'draft_id' => $draftReview->id,
            'version_number' => 1,
            'platform' => 'linkedin',
            'content' => "The most expensive architectural mistake teams make in 2026 isn't choosing the wrong language.\n\nIt is adopting microservices before they have the organizational scale to justify the distributed tax.\n\nHere is what 3 years of debugging distributed systems taught our engineering team:\n\n1. Network latency is not free. When one customer click cascades through 14 microservice hops, your p99 latency degrades by an order of magnitude.\n2. Distributed transactions fail silently. Eventual consistency works until customer balances or inventory syncs require immediate reconciliation.\n3. Observability overhead explodes. You spend 40% of your cloud bill on APM traces, mesh sidecars, and cluster logging just to understand which service dropped the connection.\n\nMoving to a strictly bounded Modular Monolith gave us:\n→ 4x faster CI/CD pipelines\n→ Sub-15ms p99 response times\n→ Single-command local developer environments\n\nMicroservices solve organization scaling problems, not code architecture problems. Don't pay the distributed tax prematurely.\n\n#SoftwareEngineering #SystemDesign #CloudArchitecture #Backend #DistributedSystems",
            'tone' => 'contrarian_architect',
            'created_by_ai' => true,
        ]);

        DraftVersion::create([
            'id' => (string) Str::uuid(),
            'draft_id' => $draftReview->id,
            'version_number' => 1,
            'platform' => 'x_twitter',
            'content' => "Microservices in 2026 are mostly an organizational tax.\n\nUnless you have 200+ engineers, an in-process Modular Monolith beats 20 microservices every time:\n\n• Sub-15ms p99\n• No distributed tracing nightmares\n• 4x faster CI/CD\n\nDon't build distributed systems until you have to. 🧵👇",
            'thread_items' => [
                "1/ When a single user request cascades through 12 HTTP hops, network overhead eats your SLA alive. In-process function calls take nanoseconds.",
                "2/ The secret to maintainable scale: Strict module boundaries at the package/folder level with zero circular dependencies. Keep it modular, keep it together.",
            ],
            'tone' => 'punchy_thread',
            'created_by_ai' => true,
        ]);

        PostMedia::create([
            'id' => (string) Str::uuid(),
            'draft_id' => $draftReview->id,
            'media_asset_id' => $media1->id,
            'platform' => 'universal',
            'sort_order' => 1,
        ]);

        // 7. Draft 2: Scheduled Post
        $draftScheduled = Draft::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'category_id' => $catAI->id,
            'title' => 'Speculative Decoding: The Zero-Quality-Loss LLM Accelerator',
            'status' => Draft::STATUS_SCHEDULED,
            'scheduled_at' => now()->addDay()->setHour(9)->setMinute(0),
            'target_platforms' => ['linkedin', 'x_twitter'],
            'is_evergreen' => false,
        ]);

        DraftVersion::create([
            'id' => (string) Str::uuid(),
            'draft_id' => $draftScheduled->id,
            'version_number' => 1,
            'platform' => 'linkedin',
            'content' => "Speculative decoding is the closest thing to a free lunch in LLM inference engineering.\n\nBy pairing a small, fast 1B 'draft model' with your 70B target model, you can achieve 2.5x - 3.2x faster token generation without changing output distribution by even 0.01%.\n\nWhy? Because modern GPUs are memory-bandwidth bound during autoregressive generation. Verifying 5 speculative tokens in parallel takes nearly the same compute time as generating 1 token sequentially.\n\nIf you run production LLM inference clusters and haven't enabled speculative verification yet, you are leaving 60% of your compute budget on the table.\n\n#ArtificialIntelligence #MachineLearning #LLM #Inference #CloudCost",
            'tone' => 'technical_educator',
            'created_by_ai' => true,
        ]);

        Schedule::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'draft_id' => $draftScheduled->id,
            'social_account_id' => $liAccount->id,
            'scheduled_for' => now()->addDay()->setHour(9)->setMinute(0),
            'status' => 'pending',
        ]);

        // 8. Draft 3: Published with Metrics
        $draftPublished = Draft::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'category_id' => $catSystems->id,
            'title' => 'Lessons from 10 Billion Redis Commands Under Black Friday Load',
            'status' => Draft::STATUS_PUBLISHED,
            'published_at' => now()->subDays(2),
            'target_platforms' => ['linkedin'],
            'is_evergreen' => true,
        ]);

        DraftVersion::create([
            'id' => (string) Str::uuid(),
            'draft_id' => $draftPublished->id,
            'version_number' => 1,
            'platform' => 'linkedin',
            'content' => "Redis single-threaded event loop is an absolute masterclass in software engineering.\n\nLast week our cluster handled 10.4B cache operations with 0 downtime. Here are the 3 architectural rules we enforced:\n\n1. O(N) commands like KEYS or HGETALL are strictly banned via ACLs.\n2. Key eviction policies: volatile-lru with strict TTLs on every single write.\n3. Connection pooling with local TCP socket reuse to eliminate handshake latency.\n\nSimple primitives scaled well outperform overengineered architectures every time.",
            'tone' => 'empirical_case_study',
            'created_by_ai' => false,
        ]);

        MetricsSnapshot::create([
            'id' => (string) Str::uuid(),
            'social_account_id' => $liAccount->id,
            'external_post_id' => 'li_share_9281048291',
            'impressions' => 18450,
            'reach' => 14200,
            'engagements' => 1664,
            'likes' => 932,
            'retweets_reposts' => 64,
            'comments_count' => 148,
            'clicks' => 520,
            'engagement_rate' => 0.0902,
            'captured_at' => now(),
        ]);

        // 9. Brand & Cost Settings
        Setting::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'key' => 'brand_voice',
            'value' => [
                'tone' => 'Architectural, pragmatic, data-grounded, zero-hype',
                'banned_buzzwords' => ['revolutionary', 'game-changer', 'supercharge', 'paradigm-shift', 'delve'],
                'target_audiences' => ['Staff Engineers', 'Founding Engineers', 'CTOs', 'Tech Leads'],
                'preferred_length_linkedin' => '1200-1800 characters',
                'preferred_length_x' => '240-280 characters + thread option',
            ],
            'description' => 'Brand voice constraints and banned AI buzzwords',
        ]);

        Setting::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'key' => 'budget_guardrails',
            'value' => [
                'daily_llm_cap_usd' => 10.00,
                'monthly_llm_cap_usd' => 150.00,
                'current_month_llm_spend_usd' => 18.42,
                'daily_media_cap_usd' => 15.00,
                'monthly_media_cap_usd' => 200.00,
                'current_month_media_spend_usd' => 24.15,
                'daily_x_post_limit' => 50,
                'current_day_x_posts' => 4,
            ],
            'description' => 'Real-time metered spend caps and safety breakers',
        ]);
    }
}
