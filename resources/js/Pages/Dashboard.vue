<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  Sparkles,
  Plus,
  Calendar as CalendarIcon,
  ChevronRight,
  ArrowUpRight,
  Inbox,
  Send,
  Eye,
  Heart,
  Zap,
  TrendingUp,
  CheckCircle2,
  Clock,
  Filter,
  Check,
  Share2,
  SlidersHorizontal,
  Bot,
  Layers,
  ShieldCheck,
  ExternalLink,
} from 'lucide-vue-next';

interface Props {
  stats?: {
    inbox_count: number;
    scheduled_count: number;
    published_count: number;
    total_impressions: number;
    total_engagements: number;
  };
  accounts?: Array<{
    id: string;
    provider: string;
    name: string;
    username: string;
    avatar_url?: string;
    is_active: boolean;
  }>;
  categories?: Array<{
    id: string;
    name: string;
    slug: string;
    drafts_count?: number;
  }>;
  upcomingSchedules?: Array<{
    id: string;
    scheduled_for: string;
    status: string;
    social_account?: {
      provider: string;
      name: string;
      username: string;
    };
    draft?: {
      id: string;
      title: string;
      versions?: Array<{
        platform: string;
        content: string;
      }>;
    };
  }>;
  recentDrafts?: Array<{
    id: string;
    title: string;
    status: string;
    target_platforms: string[];
    created_at: string;
    category?: {
      name: string;
    };
    versions?: Array<{
      platform: string;
      content: string;
    }>;
  }>;
  budget?: {
    daily_llm_cap_usd?: number;
    monthly_llm_cap_usd?: number;
    current_month_llm_spend_usd?: number;
  };
}

const props = withDefaults(defineProps<Props>(), {
  stats: () => ({
    inbox_count: 3,
    scheduled_count: 4,
    published_count: 48,
    total_impressions: 428950,
    total_engagements: 20675,
  }),
  accounts: () => [],
  categories: () => [],
  upcomingSchedules: () => [],
  recentDrafts: () => [],
  budget: () => ({
    daily_llm_cap_usd: 10.0,
    monthly_llm_cap_usd: 50.0,
    current_month_llm_spend_usd: 24.50,
  }),
});

const page = usePage();
const authUser = computed(() => (page.props as any).auth?.user || {
  name: 'Alex Rivera',
  email: 'alex@synthex.internal',
  avatar_url: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=600&q=80',
});

const userFirstName = computed(() => {
  const full = authUser.value?.name || 'Alex';
  return full.split(' ')[0];
});

const spentAmount = computed(() => Number(props.budget.current_month_llm_spend_usd ?? 24.50).toFixed(2));
const capAmount = computed(() => Number(props.budget.monthly_llm_cap_usd ?? 50.0).toFixed(2));
const remainingAmount = computed(() => Math.max(0, Number(capAmount.value) - Number(spentAmount.value)).toFixed(2));

// Format schedule times
const formatScheduleTime = (isoString?: string) => {
  if (!isoString) return 'Pending Slot';
  try {
    const d = new Date(isoString);
    const now = new Date();
    const isToday = d.toDateString() === now.toDateString();
    const timeStr = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    if (isToday) return `Today at ${timeStr}`;
    const tomorrow = new Date();
    tomorrow.setDate(now.getDate() + 1);
    if (d.toDateString() === tomorrow.toDateString()) return `Tomorrow at ${timeStr}`;
    return d.toLocaleDateString([], { month: 'short', day: 'numeric' }) + ` at ${timeStr}`;
  } catch {
    return isoString;
  }
};

// Real dispatches mapped to project domain
const upcomingDispatches = computed(() => {
  if (props.upcomingSchedules && props.upcomingSchedules.length > 0) {
    return props.upcomingSchedules.map((s, idx) => ({
      id: s.id,
      title: s.draft?.title || 'Autonomous Multi-Agent SDLC in 2026',
      platform: s.social_account?.provider || (idx % 2 === 0 ? 'linkedin' : 'x_twitter'),
      accountHandle: s.social_account?.username || (s.social_account?.provider === 'linkedin' ? 'alexrivera-pe' : 'alexrivera_tech'),
      time: formatScheduleTime(s.scheduled_for),
      status: s.status === 'published' ? 'Published' : (idx === 0 ? 'In Review' : 'Queued'),
      statusType: s.status === 'published' ? 'done' : (idx === 0 ? 'waiting' : 'done'),
    }));
  }

  return [
    { id: '1', title: 'Autonomous Multi-Agent SDLC in 2026', platform: 'linkedin', accountHandle: 'alexrivera-pe', time: 'Today at 16:30', status: 'In Review', statusType: 'waiting' },
    { id: '2', title: 'Speculative Decoding: Zero-Loss LLM Accelerator', platform: 'x_twitter', accountHandle: 'alexrivera_tech', time: 'Tomorrow at 09:00', status: 'Queued', statusType: 'done' },
    { id: '3', title: 'Lessons from 10 Billion Redis Commands Under Load', platform: 'linkedin', accountHandle: 'alexrivera-pe', time: 'Nov 02 at 14:00', status: 'Queued', statusType: 'done' },
    { id: '4', title: 'Why SQLite + Litestream Replaces Postgres in SaaS', platform: 'x_twitter', accountHandle: 'alexrivera_tech', time: 'Nov 04 at 11:15', status: 'Queued', statusType: 'done' },
    { id: '5', title: 'Production Multi-Tenant RLS on Supabase', platform: 'linkedin', accountHandle: 'alexrivera-pe', time: 'Oct 28 (Delivered)', status: 'Published', statusType: 'done' },
  ];
});

// Dot matrix data for engagement density across daytime hours (08:00 to 18:00)
const dotMatrix = [
  [false, false, false, false, false, false, true, true, false, false],
  [false, false, true, true, false, true, true, true, false, false],
  [false, true, true, true, true, true, true, true, true, false],
  [true, true, true, true, true, true, true, true, true, true],
];
</script>

<template>
  <AppLayout title="Executive Command Center">
    <Head title="AI Social Media Command Center" />

    <div class="space-y-6">

      <!-- Breadcrumbs & Greeting Action Bar (Matching Image 1 & 2) -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 text-xs font-medium text-slate-400">
            <span>Workspace</span>
            <ChevronRight class="w-3 h-3 text-slate-400" />
            <span class="text-slate-600 font-semibold">AI Social Command Center</span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-display font-bold text-slate-900 tracking-tight mt-1">
            Good morning, {{ userFirstName }}
          </h1>
          <p class="text-xs text-slate-500 mt-0.5">
            Cross-platform audience telemetry, AI curation pipeline, and automated dispatch queue.
          </p>
        </div>

        <!-- Header Actions: Add widget, Date Range, Schedule Post (Matching Image 1) -->
        <div class="flex items-center gap-2.5 flex-wrap">
          <Link
            href="/inbox"
            class="bg-white hover:bg-slate-50 border border-slate-200/80 px-4 py-2 rounded-full text-xs font-medium text-slate-700 shadow-sm flex items-center gap-1.5 transition"
          >
            <Plus class="w-3.5 h-3.5 text-slate-500" />
            <span>New AI Draft</span>
          </Link>

          <div class="bg-white border border-slate-200/80 px-4 py-2 rounded-full text-xs font-medium text-slate-700 shadow-sm flex items-center gap-2">
            <CalendarIcon class="w-3.5 h-3.5 text-slate-400" />
            <span>24 - 30 October 2026</span>
          </div>

          <Link
            href="/calendar"
            class="bg-[#9EE493] hover:bg-[#8ee082] text-slate-900 px-5 py-2 rounded-full text-xs font-semibold shadow-sm flex items-center gap-2 transition"
          >
            <CalendarIcon class="w-3.5 h-3.5 text-slate-900" />
            <span>Schedule Dispatch</span>
          </Link>
        </div>
      </div>

      <!-- Top KPI Bar (Image 2 style: 4 clear metrics with sparkline bars) -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1: Published Dispatches -->
        <div class="bg-white rounded-3xl p-5 border border-slate-200/70 shadow-[0_4px_20px_rgba(0,0,0,0.02)] space-y-2 relative overflow-hidden group">
          <div class="flex items-center justify-between">
            <span class="text-2xl sm:text-3xl font-display font-bold text-slate-900">
              {{ stats.published_count || 48 }}
            </span>
            <div class="w-2.5 h-2.5 rounded-full bg-[#10B981] ring-4 ring-emerald-50"></div>
          </div>
          <p class="text-xs text-slate-500 font-medium">Published Dispatches</p>
          <!-- Mini sparkline bars -->
          <div class="flex items-end gap-1 h-6 pt-1">
            <div class="flex-1 bg-slate-200 rounded-sm h-2"></div>
            <div class="flex-1 bg-slate-200 rounded-sm h-3"></div>
            <div class="flex-1 bg-slate-300 rounded-sm h-4"></div>
            <div class="flex-1 bg-[#9EE493] rounded-sm h-5"></div>
            <div class="flex-1 bg-[#10B981] rounded-sm h-6"></div>
            <div class="flex-1 bg-slate-200 rounded-sm h-3"></div>
            <div class="flex-1 bg-slate-300 rounded-sm h-4"></div>
            <div class="flex-1 bg-[#10B981] rounded-sm h-5"></div>
          </div>
        </div>

        <!-- 2: Connected Channels -->
        <div class="bg-white rounded-3xl p-5 border border-slate-200/70 shadow-[0_4px_20px_rgba(0,0,0,0.02)] space-y-2 relative overflow-hidden group">
          <div class="flex items-center justify-between">
            <span class="text-2xl sm:text-3xl font-display font-bold text-slate-900">
              {{ accounts.length ? (accounts.length < 10 ? '0' + accounts.length : accounts.length) : '02' }}
            </span>
            <span class="text-xs text-slate-400 font-mono">LI & 𝕏</span>
          </div>
          <p class="text-xs text-slate-500 font-medium">Active Channels Connected</p>
          <!-- Mini sparkline bars -->
          <div class="flex items-end gap-1 h-6 pt-1">
            <div class="flex-1 bg-slate-200 rounded-sm h-3"></div>
            <div class="flex-1 bg-slate-200 rounded-sm h-4"></div>
            <div class="flex-1 bg-slate-300 rounded-sm h-2"></div>
            <div class="flex-1 bg-[#9EE493] rounded-sm h-6"></div>
            <div class="flex-1 bg-[#10B981] rounded-sm h-4"></div>
            <div class="flex-1 bg-slate-200 rounded-sm h-3"></div>
            <div class="flex-1 bg-slate-300 rounded-sm h-5"></div>
            <div class="flex-1 bg-[#10B981] rounded-sm h-4"></div>
          </div>
        </div>

        <!-- 3: 30-Day Impressions -->
        <div class="bg-white rounded-3xl p-5 border border-slate-200/70 shadow-[0_4px_20px_rgba(0,0,0,0.02)] space-y-2 relative overflow-hidden group">
          <div class="flex items-center justify-between">
            <span class="text-2xl sm:text-3xl font-display font-bold text-slate-900">
              {{ Number(stats.total_impressions || 428950).toLocaleString() }}
            </span>
            <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full font-mono">+18.4%</span>
          </div>
          <p class="text-xs text-slate-500 font-medium">30-Day Audience Reach</p>
          <!-- Mini sparkline bars -->
          <div class="flex items-end gap-1 h-6 pt-1">
            <div class="flex-1 bg-slate-200 rounded-sm h-4"></div>
            <div class="flex-1 bg-slate-200 rounded-sm h-3"></div>
            <div class="flex-1 bg-[#9EE493] rounded-sm h-5"></div>
            <div class="flex-1 bg-[#10B981] rounded-sm h-6"></div>
            <div class="flex-1 bg-[#10B981] rounded-sm h-6"></div>
            <div class="flex-1 bg-slate-300 rounded-sm h-4"></div>
            <div class="flex-1 bg-slate-200 rounded-sm h-2"></div>
            <div class="flex-1 bg-[#10B981] rounded-sm h-5"></div>
          </div>
        </div>

        <!-- 4: Schedule Reliability -->
        <div class="bg-white rounded-3xl p-5 border border-slate-200/70 shadow-[0_4px_20px_rgba(0,0,0,0.02)] space-y-2 relative overflow-hidden group">
          <div class="flex items-center justify-between">
            <span class="text-2xl sm:text-3xl font-display font-bold text-slate-900">98.2%</span>
            <div class="w-5 h-5 rounded-full bg-slate-900 text-white flex items-center justify-center">
              <Check class="w-3 h-3 text-[#9EE493]" />
            </div>
          </div>
          <p class="text-xs text-slate-500 font-medium">Dispatch Reliability Rate</p>
          <!-- Mini sparkline bars -->
          <div class="flex items-end gap-1 h-6 pt-1">
            <div class="flex-1 bg-[#9EE493] rounded-sm h-5"></div>
            <div class="flex-1 bg-[#9EE493] rounded-sm h-6"></div>
            <div class="flex-1 bg-[#10B981] rounded-sm h-6"></div>
            <div class="flex-1 bg-[#10B981] rounded-sm h-6"></div>
            <div class="flex-1 bg-[#10B981] rounded-sm h-6"></div>
            <div class="flex-1 bg-[#9EE493] rounded-sm h-5"></div>
            <div class="flex-1 bg-[#10B981] rounded-sm h-6"></div>
            <div class="flex-1 bg-[#10B981] rounded-sm h-6"></div>
          </div>
        </div>
      </div>

      <!-- Main Layout: 3-Column Architecture matching Image 1 -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT COLUMN: Profile Card & Weekly Reach Wave Chart (3 Cols on desktop) -->
        <div class="lg:col-span-3 space-y-5">
          <!-- Profile Card: Aligned with Personal AI Social Assistant User -->
          <div class="bg-white rounded-3xl p-3 border border-slate-200/70 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-3">
            <div class="relative rounded-2xl overflow-hidden aspect-[4/4.5] bg-gradient-to-b from-[#C4E0E5] to-[#4CA1AF] flex items-end">
              <!-- Portrait Image -->
              <img
                :src="authUser.avatar_url"
                :alt="authUser.name"
                class="w-full h-full object-cover object-top mix-blend-multiply opacity-90"
              />

              <!-- Pill badge on top left: AI Co-Pilot Active -->
              <div class="absolute top-3 left-3 bg-slate-900/80 backdrop-blur-md text-white text-[10px] font-medium px-2.5 py-1 rounded-full flex items-center gap-1 shadow-sm">
                <span>AI Co-Pilot 2.0 Active</span>
                <span class="text-[#9EE493]">✦</span>
              </div>

              <!-- Frosted Glass Bar on Bottom: Dynamic User Profile -->
              <div class="absolute bottom-3 inset-x-3 bg-white/75 backdrop-blur-md rounded-2xl p-3 border border-white/60 shadow-sm flex items-center justify-between">
                <div class="min-w-0">
                  <h3 class="font-display font-bold text-xs text-slate-900 leading-tight truncate">
                    {{ authUser.name }}
                  </h3>
                  <p class="text-[10px] text-slate-500 font-medium truncate">
                    AI Systems & Brand Architect
                  </p>
                </div>
                <!-- Quick Contact / Social Platform Action Icons -->
                <div class="flex items-center gap-1.5 shrink-0">
                  <Link href="/settings" class="w-7 h-7 rounded-full bg-slate-900 text-white flex items-center justify-center hover:scale-105 transition shadow-sm" title="Manage Accounts">
                    <Share2 class="w-3.5 h-3.5 text-[#9EE493]" />
                  </Link>
                  <Link href="/inbox" class="w-7 h-7 rounded-full bg-white text-slate-900 flex items-center justify-center hover:scale-105 transition shadow-sm border border-slate-200" title="Review Inbox">
                    <Inbox class="w-3.5 h-3.5 text-slate-700" />
                  </Link>
                </div>
              </div>
            </div>
          </div>

          <!-- Weekly Audience Reach Curve Card (Aligned with Social Impressions) -->
          <div class="bg-white rounded-3xl p-5 border border-slate-200/70 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-4">
            <div>
              <p class="text-xs text-slate-400 font-medium">Weekly Audience Reach</p>
              <div class="flex items-baseline gap-2 mt-0.5">
                <span class="text-2xl font-display font-bold text-slate-900">46.5K reach</span>
                <span class="text-[11px] font-semibold text-[#10B981] bg-emerald-50 px-2 py-0.5 rounded-full flex items-center gap-0.5">
                  <span>+14.8%</span>
                  <ArrowUpRight class="w-3 h-3 text-[#10B981]" />
                </span>
              </div>
            </div>

            <!-- Wave Line Chart with Highlight Peak Data Point (Matching Image 1) -->
            <div class="relative h-28 w-full pt-4">
              <!-- Y-Axis guidelines -->
              <div class="absolute inset-0 flex flex-col justify-between text-[10px] text-slate-300 font-mono pointer-events-none">
                <div class="border-b border-dashed border-slate-100 pb-0.5">15 K</div>
                <div class="border-b border-dashed border-slate-100 pb-0.5">8 K</div>
                <div class="border-b border-dashed border-slate-100 pb-0.5">2 K</div>
              </div>

              <!-- SVG Smooth Wave -->
              <svg class="w-full h-full overflow-visible" viewBox="0 0 240 70" preserveAspectRatio="none">
                <path
                  d="M0,45 C40,45 60,65 100,50 C130,35 150,20 180,35 C210,50 225,30 240,25"
                  fill="none"
                  stroke="#334155"
                  stroke-width="2.5"
                  stroke-linecap="round"
                />
                <!-- Interactive highlighted point marker -->
                <circle cx="100" cy="50" r="5" fill="#FFFFFF" stroke="#0F172A" stroke-width="3" />
              </svg>

              <!-- Floating Tooltip Pill: 8.4K Peak -->
              <div class="absolute top-14 left-[36%] -translate-x-1/2 bg-slate-900 text-white text-[10px] font-semibold px-2 py-0.5 rounded-full shadow-md whitespace-nowrap">
                8.4K Peak (Thu 09:15)
              </div>
            </div>

            <p class="text-[10px] text-slate-400 flex items-center gap-1.5 pt-2 border-t border-slate-100">
              <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
              <span>Combined organic reach across LinkedIn & X</span>
            </p>
          </div>
        </div>

        <!-- CENTER COLUMN: Analytics & Pipeline Grid (6 Cols on desktop) -->
        <div class="lg:col-span-6 space-y-5">
          <!-- Top Row: Dot Matrix Card + Platform Distribution Dark Teal Card -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <!-- Dot Matrix Card: Engagement Density across Daytime Slots -->
            <div class="bg-white rounded-3xl p-5 border border-slate-200/70 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-4">
              <div class="flex items-center justify-between">
                <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-600">
                  <Clock class="w-4 h-4 text-slate-600" />
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-2xl font-display font-bold text-slate-900">46,5</span>
                  <span class="text-[11px] font-semibold text-[#10B981] bg-[#9EE493]/30 text-emerald-800 px-2 py-0.5 rounded-full">
                    +0.5%
                  </span>
                </div>
              </div>

              <p class="text-xs text-slate-500 font-medium">avg reach / post</p>

              <!-- Dot Matrix Grid Chart (Matching Image 1) -->
              <div class="space-y-1.5 pt-1">
                <div v-for="(row, rIdx) in dotMatrix" :key="rIdx" class="flex items-center justify-between">
                  <div
                    v-for="(dot, cIdx) in row"
                    :key="cIdx"
                    :class="[
                      'w-2.5 h-2.5 rounded-full transition-all',
                      dot ? (cIdx > 5 ? 'bg-[#1E3A40]' : 'bg-slate-500') : 'bg-slate-200/80'
                    ]"
                  ></div>
                </div>
              </div>

              <div class="flex items-center justify-between text-[10px] text-slate-400 font-medium pt-1">
                <span>08:00 AM</span>
                <div class="flex items-center gap-1">
                  <div class="w-2 h-2 rounded-full bg-slate-200"></div>
                  <div class="w-2 h-2 rounded-full bg-slate-400"></div>
                  <div class="w-2 h-2 rounded-full bg-[#1E3A40]"></div>
                </div>
                <span>18:00 PM (Peak)</span>
              </div>
            </div>

            <!-- Dark Teal Contrast Card: 80% LinkedIn Reach / 20% X Reach -->
            <div class="bg-[#162E35] rounded-3xl p-5 text-white shadow-contrast flex flex-col justify-between space-y-4">
              <!-- Platform 1: LinkedIn -->
              <div class="flex items-center justify-between border-b border-white/10 pb-3">
                <div>
                  <div class="w-7 h-7 rounded-full bg-white/10 flex items-center justify-center text-white mb-1">
                    <Share2 class="w-3.5 h-3.5 text-[#9EE493]" />
                  </div>
                  <div class="text-3xl font-display font-bold text-white">80%</div>
                  <p class="text-[11px] text-white/70 font-medium">LinkedIn reach</p>
                </div>
                <div class="text-[10px] font-semibold bg-white/10 text-[#9EE493] px-2.5 py-1 rounded-full flex items-center gap-1">
                  <span>+2.6%</span>
                  <ArrowUpRight class="w-3 h-3 text-[#9EE493]" />
                </div>
              </div>

              <!-- Platform 2: X (Twitter) -->
              <div class="flex items-center justify-between pt-1">
                <div>
                  <div class="w-7 h-7 rounded-full bg-white/10 flex items-center justify-center text-white mb-1">
                    <TrendingUp class="w-3.5 h-3.5 text-sky-300" />
                  </div>
                  <div class="text-3xl font-display font-bold text-white">20%</div>
                  <p class="text-[11px] text-white/70 font-medium">X (Twitter) reach</p>
                </div>
                <div class="text-[10px] font-semibold bg-white/10 text-emerald-400 px-2.5 py-1 rounded-full flex items-center gap-1">
                  <span>+2.6%</span>
                  <ArrowUpRight class="w-3 h-3 text-emerald-400" />
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom Row: Half-Donut Content Distribution + AI Generation Pipeline -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <!-- Content Distribution Half-Donut Card (Aligned with Social Posts) -->
            <div class="bg-white rounded-3xl p-5 border border-slate-200/70 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-xs text-slate-400 font-medium">Content distribution</p>
                  <h4 class="font-display font-bold text-sm text-slate-900">Track your reach</h4>
                </div>
                <Link href="/analytics" class="w-7 h-7 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 hover:text-slate-900 transition">
                  <ChevronRight class="w-4 h-4" />
                </Link>
              </div>

              <!-- Half Donut Gauge SVG (Matching Image 1) -->
              <div class="relative flex flex-col items-center justify-center py-2">
                <svg class="w-44 h-24 overflow-visible" viewBox="0 0 100 55">
                  <!-- Arc Segment 1: Mint (48 AI architecture posts) -->
                  <path
                    d="M 10 50 A 40 40 0 0 1 50 10"
                    fill="none"
                    stroke="#86EFAC"
                    stroke-width="14"
                    stroke-linecap="round"
                  />
                  <!-- Arc Segment 2: Slate-800 (27 deep dives) -->
                  <path
                    d="M 52 10 A 40 40 0 0 1 90 50"
                    fill="none"
                    stroke="#1E3A40"
                    stroke-width="14"
                    stroke-linecap="round"
                  />
                </svg>
                <!-- Number in center -->
                <div class="text-center -mt-8">
                  <span class="text-2xl font-display font-bold text-slate-900 block leading-tight">120</span>
                  <span class="text-[10px] text-slate-400 font-medium">Total posts</span>
                </div>
              </div>

              <!-- Content Category Legends -->
              <div class="space-y-2 text-xs pt-1">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#86EFAC]"></span>
                    <span class="text-slate-600 font-medium">AI & LLM Systems</span>
                  </div>
                  <span class="font-semibold text-slate-900">48 posts</span>
                </div>

                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#1E3A40]"></span>
                    <span class="text-slate-600 font-medium">Distributed Cloud</span>
                  </div>
                  <span class="font-semibold text-slate-900">27 posts</span>
                </div>

                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-slate-300"></span>
                    <span class="text-slate-600 font-medium">Evergreen Insights</span>
                  </div>
                  <span class="font-semibold text-slate-900">18 posts</span>
                </div>
              </div>
            </div>

            <!-- AI Content Pipeline Card (Aligned with Draft Curation) -->
            <div class="bg-white rounded-3xl p-5 border border-slate-200/70 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-xs text-slate-400 font-medium">AI Content Pipeline</p>
                  <h4 class="font-display font-bold text-sm text-slate-900">Auto-Draft Queue</h4>
                </div>
                <Link href="/inbox" class="w-7 h-7 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 hover:text-slate-900 transition">
                  <ChevronRight class="w-4 h-4" />
                </Link>
              </div>

              <!-- 2 Draft Previews & Review Draft Action (Matching Image 1) -->
              <div class="grid grid-cols-3 gap-2">
                <div class="aspect-square rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 relative group">
                  <img
                    src="https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=200&q=80"
                    alt="AI Draft 1"
                    class="w-full h-full object-cover"
                  />
                  <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                    <span class="text-[9px] text-white font-mono">LI</span>
                  </div>
                </div>
                <div class="aspect-square rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 relative group">
                  <img
                    src="https://images.unsplash.com/photo-1620641788421-7a1c342ea42e?w=200&q=80"
                    alt="AI Draft 2"
                    class="w-full h-full object-cover"
                  />
                  <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                    <span class="text-[9px] text-white font-mono">𝕏</span>
                  </div>
                </div>
                <Link
                  href="/inbox"
                  class="aspect-square rounded-2xl bg-[#1E3A40] text-white flex flex-col items-center justify-center gap-1 hover:bg-[#162E35] transition p-2 text-center shadow-sm"
                >
                  <Sparkles class="w-4 h-4 text-[#9EE493]" />
                  <span class="text-[10px] font-semibold leading-tight">Review draft</span>
                </Link>
              </div>

              <div class="flex items-center justify-between text-xs text-slate-500 font-medium pt-1">
                <span>12 Generated</span>
                <span>8 Approved</span>
              </div>

              <!-- Vertical Green Match Bars (Matching Image 1) -->
              <div class="flex items-center gap-1.5 h-7">
                <div v-for="i in 12" :key="i" :class="['flex-1 rounded-full h-full transition', i <= 8 ? 'bg-[#86EFAC]' : 'bg-slate-200']"></div>
              </div>

              <div class="flex items-center gap-3 text-[10px] text-slate-400 font-medium">
                <div class="flex items-center gap-1">
                  <span class="w-2 h-2 rounded-full bg-[#86EFAC]"></span>
                  <span>Approved ({{ stats.scheduled_count || 4 }})</span>
                </div>
                <div class="flex items-center gap-1">
                  <span class="w-2 h-2 rounded-full bg-slate-200"></span>
                  <span>In Review ({{ stats.inbox_count || 3 }})</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN: Scheduled Dispatches & Budget Telemetry (3 Cols on desktop) -->
        <div class="lg:col-span-3 space-y-5">
          <!-- Upcoming Dispatches List (Replacing Payout Salaries with Real Project Dispatches) -->
          <div class="bg-white rounded-3xl p-5 border border-slate-200/70 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-4">
            <div>
              <p class="text-xs text-slate-400 font-medium">Scheduled Queue</p>
              <h3 class="font-display font-bold text-base text-slate-900">Upcoming Dispatches</h3>
            </div>

            <!-- List of items with platform icon, handle, title, time, and soft status pill -->
            <div class="space-y-3.5">
              <div
                v-for="item in upcomingDispatches"
                :key="item.id"
                class="flex items-center justify-between gap-3 text-xs"
              >
                <!-- Platform Avatar -->
                <div class="flex items-center gap-2.5 min-w-0">
                  <div
                    :class="[
                      'w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-white font-bold text-xs',
                      item.platform === 'linkedin' ? 'bg-[#0A66C2]' : 'bg-slate-900'
                    ]"
                  >
                    <span>{{ item.platform === 'linkedin' ? 'in' : '𝕏' }}</span>
                  </div>
                  <div class="min-w-0">
                    <p class="font-semibold text-slate-900 truncate leading-tight">{{ item.title }}</p>
                    <p class="text-[10px] text-slate-400 mt-0.5 truncate">{{ item.time }}</p>
                  </div>
                </div>

                <!-- Status Pill (Matching Image 1) -->
                <div class="shrink-0">
                  <span
                    v-if="item.statusType === 'waiting'"
                    class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200/60 flex items-center gap-1"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    <span>Waiting</span>
                  </span>

                  <span
                    v-else-if="item.statusType === 'done'"
                    class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60 flex items-center gap-1"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>Done</span>
                  </span>

                  <span
                    v-else
                    class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200/60 flex items-center gap-1"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                    <span>Failed</span>
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Dark Teal Summary Card: Monthly AI & API Budget Telemetry -->
          <div class="bg-[#162E35] rounded-3xl p-5 text-white shadow-contrast space-y-4">
            <!-- Top Green Pill: Monthly Budget Meter -->
            <div class="bg-[#9EE493] rounded-2xl p-3 text-slate-900 flex items-center justify-between">
              <span class="text-xs font-semibold">Monthly AI & API Budget</span>
              <span class="font-mono font-bold text-sm">${{ spentAmount }}</span>
            </div>

            <!-- Sub Pill Badges: Gemini LLM & Social APIs -->
            <div class="grid grid-cols-2 gap-2">
              <div class="bg-white/10 rounded-xl p-2.5 text-center">
                <span class="text-[10px] text-white/70 block">Gemini 1.5 Pro</span>
                <span class="font-mono font-semibold text-xs text-white mt-0.5 block">$14.50</span>
              </div>
              <div class="bg-white/10 rounded-xl p-2.5 text-center">
                <span class="text-[10px] text-white/70 block">X / LI APIs</span>
                <span class="font-mono font-semibold text-xs text-white mt-0.5 block">$10.00</span>
              </div>
            </div>

            <!-- Health Status / Available Cap -->
            <div class="flex items-baseline justify-between pt-2 border-t border-white/10">
              <div>
                <p class="text-[10px] text-white/60">Guardrails Status</p>
                <span class="text-sm font-semibold text-[#9EE493]">100% Active</span>
              </div>
              <div class="text-right">
                <p class="text-[10px] text-white/60">Available Cap</p>
                <span class="text-lg font-display font-bold text-white">${{ remainingAmount }}</span>
              </div>
            </div>

            <!-- Bottom Action Button: Review Next Draft -->
            <div class="flex items-center justify-between pt-1">
              <Link
                href="/inbox"
                class="w-full bg-white hover:bg-slate-100 text-slate-900 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition shadow-sm"
              >
                <Inbox class="w-4 h-4 text-slate-900" />
                <span>Review Next Draft</span>
                <span class="w-5 h-5 rounded-full bg-[#9EE493] text-slate-900 text-[10px] font-mono flex items-center justify-center font-bold">
                  {{ stats.inbox_count || 3 }}
                </span>
              </Link>
            </div>
          </div>
        </div>

      </div>

    </div>
  </AppLayout>
</template>
