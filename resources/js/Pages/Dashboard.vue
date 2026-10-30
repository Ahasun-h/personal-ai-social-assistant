<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
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
  upcomingSchedules?: Array<{
    id: string;
    scheduled_for: string;
    status: string;
    social_account?: {
      provider: string;
      name: string;
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
  upcomingSchedules: () => [],
  recentDrafts: () => [],
  budget: () => ({
    daily_llm_cap_usd: 10.0,
    monthly_llm_cap_usd: 50.0,
    current_month_llm_spend_usd: 24.50,
  }),
});

const spentAmount = computed(() => Number(props.budget.current_month_llm_spend_usd ?? 24.50).toFixed(2));
const capAmount = computed(() => Number(props.budget.monthly_llm_cap_usd ?? 50.0).toFixed(2));
const remainingAmount = computed(() => (Number(capAmount.value) - Number(spentAmount.value)).toFixed(2));
const budgetPercentage = computed(() => {
  const cap = props.budget.monthly_llm_cap_usd || 50;
  const spent = props.budget.current_month_llm_spend_usd || 24.50;
  return Math.min(100, Math.round((spent / cap) * 100));
});

// Format time
const formatScheduleTime = (isoString?: string) => {
  if (!isoString) return 'Pending';
  try {
    const d = new Date(isoString);
    const now = new Date();
    const isToday = d.toDateString() === now.toDateString();
    const timeStr = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    if (isToday) return `Today ${timeStr}`;
    return d.toLocaleDateString([], { month: 'short', day: 'numeric' }) + ` ${timeStr}`;
  } catch {
    return isoString;
  }
};

// Activity Feed items with status matching Image 1
const dispatchFeed = computed(() => {
  if (props.upcomingSchedules && props.upcomingSchedules.length > 0) {
    return props.upcomingSchedules.map((s, idx) => ({
      id: s.id,
      title: s.draft?.title || 'Autonomous Multi-Agent SDLC in 2026',
      platform: s.social_account?.provider || (idx % 2 === 0 ? 'linkedin' : 'x_twitter'),
      time: formatScheduleTime(s.scheduled_for),
      status: s.status === 'published' ? 'Done' : (idx === 0 ? 'Waiting' : 'Done'),
      statusType: s.status === 'published' ? 'done' : (idx === 0 ? 'waiting' : 'done'),
      metric: s.status === 'published' ? '$2.540.00' : '$2.540.00',
    }));
  }

  return [
    { id: '1', title: 'Autonomous Multi-Agent SDLC in 2026', platform: 'linkedin', time: 'Today 16:30', status: 'Waiting', statusType: 'waiting', metric: '$2.540.00' },
    { id: '2', title: 'Speculative Decoding: Zero-Loss LLM Accelerator', platform: 'x_twitter', time: 'Tomorrow 09:00', status: 'Done', statusType: 'done', metric: '$2.540.00' },
    { id: '3', title: 'Lessons from 10 Billion Redis Commands', platform: 'linkedin', time: 'Nov 02 14:00', status: 'Done', statusType: 'done', metric: '$2.540.00' },
    { id: '4', title: 'Why SQLite + Litestream Replaces Postgres', platform: 'x_twitter', time: 'Yesterday', status: 'Done', statusType: 'done', metric: '$2.540.00' },
    { id: '5', title: 'Production Multi-Tenant RLS on Supabase', platform: 'linkedin', time: 'Oct 28', status: 'Failed', statusType: 'failed', metric: '$2.540.00' },
  ];
});

// Dot matrix data for 46.5 hours/reach card
const dotMatrix = [
  [false, false, false, false, false, false, true, true, false, false],
  [false, false, true, true, false, true, true, true, false, false],
  [false, true, true, true, true, true, true, true, true, false],
  [true, true, true, true, true, true, true, true, true, true],
];
</script>

<template>
  <AppLayout title="Executive Command Center">
    <Head title="AI Executive Command Center" />

    <div class="space-y-6">

      <!-- Breadcrumbs & Greeting Action Bar (Matching Image 1) -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 text-xs font-medium text-slate-400">
            <span>Portal</span>
            <ChevronRight class="w-3 h-3 text-slate-400" />
            <span class="text-slate-600 font-semibold">Dashboard</span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-display font-bold text-slate-900 tracking-tight mt-1">
            Good morning Alex
          </h1>
        </div>

        <!-- Header Actions: Add widget, Date Range, Add report (Matching Image 1) -->
        <div class="flex items-center gap-2.5 flex-wrap">
          <button class="bg-white hover:bg-slate-50 border border-slate-200/80 px-4 py-2 rounded-full text-xs font-medium text-slate-700 shadow-sm flex items-center gap-1.5 transition">
            <Plus class="w-3.5 h-3.5 text-slate-500" />
            <span>Add widget</span>
          </button>

          <div class="bg-white border border-slate-200/80 px-4 py-2 rounded-full text-xs font-medium text-slate-700 shadow-sm flex items-center gap-2">
            <CalendarIcon class="w-3.5 h-3.5 text-slate-400" />
            <span>24 - 30 October</span>
          </div>

          <Link
            href="/inbox"
            class="bg-[#9EE493] hover:bg-[#8fe084] text-slate-900 px-5 py-2 rounded-full text-xs font-semibold shadow-sm flex items-center gap-2 transition"
          >
            <Sparkles class="w-3.5 h-3.5 text-slate-900" />
            <span>Add report</span>
          </Link>
        </div>
      </div>

      <!-- Top KPI Bar (Image 2 mathematical relationship cards style) -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1: Automations Running -->
        <div class="bg-white rounded-3xl p-5 border border-slate-200/70 shadow-[0_4px_20px_rgba(0,0,0,0.02)] space-y-2 relative overflow-hidden group">
          <div class="flex items-center justify-between">
            <span class="text-2xl sm:text-3xl font-display font-bold text-slate-900">48</span>
            <div class="w-2.5 h-2.5 rounded-full bg-[#10B981] ring-4 ring-emerald-50"></div>
          </div>
          <p class="text-xs text-slate-400 font-medium">Automations Running</p>
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
            <span class="text-2xl sm:text-3xl font-display font-bold text-slate-900">08</span>
            <span class="text-xs text-slate-400 font-mono">x</span>
          </div>
          <p class="text-xs text-slate-400 font-medium">Apps Connected</p>
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

        <!-- 3: Active Servers / Drivers -->
        <div class="bg-white rounded-3xl p-5 border border-slate-200/70 shadow-[0_4px_20px_rgba(0,0,0,0.02)] space-y-2 relative overflow-hidden group">
          <div class="flex items-center justify-between">
            <span class="text-2xl sm:text-3xl font-display font-bold text-slate-900">02</span>
            <span class="text-xs text-slate-400 font-mono">=</span>
          </div>
          <p class="text-xs text-slate-400 font-medium">Active Channels (LI & X)</p>
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

        <!-- 4: Success Rate -->
        <div class="bg-white rounded-3xl p-5 border border-slate-200/70 shadow-[0_4px_20px_rgba(0,0,0,0.02)] space-y-2 relative overflow-hidden group">
          <div class="flex items-center justify-between">
            <span class="text-2xl sm:text-3xl font-display font-bold text-slate-900">98%</span>
            <div class="w-5 h-5 rounded-full bg-slate-900 text-white flex items-center justify-center">
              <Check class="w-3 h-3 text-[#9EE493]" />
            </div>
          </div>
          <p class="text-xs text-slate-400 font-medium">Success Rate</p>
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
        
        <!-- LEFT COLUMN: Profile Card & Wave Chart (3 Cols on desktop) -->
        <div class="lg:col-span-3 space-y-5">
          <!-- Profile Portrait Card (Matching Image 1 Left Card) -->
          <div class="bg-white rounded-3xl p-3 border border-slate-200/70 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-3">
            <div class="relative rounded-2xl overflow-hidden aspect-[4/4.5] bg-gradient-to-b from-[#C4E0E5] to-[#4CA1AF] flex items-end">
              <!-- Portrait Image -->
              <img
                src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=600&q=80"
                alt="Profile"
                class="w-full h-full object-cover object-top mix-blend-multiply opacity-90"
              />

              <!-- Pill badge on top right: 4+ years experience / AI Copilot -->
              <div class="absolute top-3 left-3 bg-slate-900/80 backdrop-blur-md text-white text-[10px] font-medium px-2.5 py-1 rounded-full flex items-center gap-1 shadow-sm">
                <span>4+ years experience</span>
                <span class="text-[#9EE493]">✦</span>
              </div>

              <!-- Frosted Glass Bar on Bottom (Matching Image 1) -->
              <div class="absolute bottom-3 inset-x-3 bg-white/70 backdrop-blur-md rounded-2xl p-3 border border-white/60 shadow-sm flex items-center justify-between">
                <div>
                  <h3 class="font-display font-bold text-xs text-slate-900 leading-tight">Chris Jonathan</h3>
                  <p class="text-[10px] text-slate-500 font-medium">General manager</p>
                </div>
                <!-- Quick Contact / Social Platform Action Icons -->
                <div class="flex items-center gap-1.5">
                  <a href="/settings" class="w-7 h-7 rounded-full bg-slate-900 text-white flex items-center justify-center hover:scale-105 transition shadow-sm" title="Connected Socials">
                    <Share2 class="w-3.5 h-3.5 text-[#9EE493]" />
                  </a>
                  <a href="/inbox" class="w-7 h-7 rounded-full bg-white text-slate-900 flex items-center justify-center hover:scale-105 transition shadow-sm border border-slate-200" title="Review Inbox">
                    <Inbox class="w-3.5 h-3.5 text-slate-700" />
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Average work time / Reach Curve Card (Matching Image 1) -->
          <div class="bg-white rounded-3xl p-5 border border-slate-200/70 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-4">
            <div>
              <p class="text-xs text-slate-400 font-medium">Average work time</p>
              <div class="flex items-baseline gap-2 mt-0.5">
                <span class="text-2xl font-display font-bold text-slate-900">46 hours</span>
                <span class="text-[11px] font-semibold text-[#10B981] bg-emerald-50 px-2 py-0.5 rounded-full flex items-center gap-0.5">
                  <span>+0.5%</span>
                  <ArrowUpRight class="w-3 h-3 text-[#10B981]" />
                </span>
              </div>
            </div>

            <!-- Wave Line Chart with Highlight Data Point Tooltip (Matching Image 1) -->
            <div class="relative h-28 w-full pt-4">
              <!-- Y-Axis guidelines -->
              <div class="absolute inset-0 flex flex-col justify-between text-[10px] text-slate-300 font-mono pointer-events-none">
                <div class="border-b border-dashed border-slate-100 pb-0.5">10 H</div>
                <div class="border-b border-dashed border-slate-100 pb-0.5">8 H</div>
                <div class="border-b border-dashed border-slate-100 pb-0.5">4 H</div>
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

              <!-- Floating Tooltip Pill: 8 Hours -->
              <div class="absolute top-14 left-[36%] -translate-x-1/2 bg-slate-900 text-white text-[10px] font-semibold px-2 py-0.5 rounded-full shadow-md">
                8 Hours
              </div>
            </div>

            <p class="text-[10px] text-slate-400 flex items-center gap-1.5 pt-2 border-t border-slate-100">
              <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
              <span>Total work hours include extra hours</span>
            </p>
          </div>
        </div>

        <!-- CENTER COLUMN: Analytics & Pipeline Grid (6 Cols on desktop) -->
        <div class="lg:col-span-6 space-y-5">
          <!-- Top Row: 46,5 Dot Matrix Card + 80% Onsite/Remote Dark Teal Card -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <!-- 46,5 Dot Matrix Card (Matching Image 1) -->
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

              <p class="text-xs text-slate-400 font-medium">avg hours / weeks</p>

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
                <span>2 Hours</span>
                <div class="flex items-center gap-1">
                  <div class="w-2 h-2 rounded-full bg-slate-200"></div>
                  <div class="w-2 h-2 rounded-full bg-slate-400"></div>
                  <div class="w-2 h-2 rounded-full bg-[#1E3A40]"></div>
                </div>
                <span>10 Hours</span>
              </div>
            </div>

            <!-- Dark Teal Contrast Card: 80% Onsite / 20% Remote (Matching Image 1) -->
            <div class="bg-[#162E35] rounded-3xl p-5 text-white shadow-contrast flex flex-col justify-between space-y-4">
              <!-- Item 1: Onsite / LinkedIn -->
              <div class="flex items-center justify-between border-b border-white/10 pb-3">
                <div>
                  <div class="w-7 h-7 rounded-full bg-white/10 flex items-center justify-center text-white mb-1">
                    <Share2 class="w-3.5 h-3.5 text-[#9EE493]" />
                  </div>
                  <div class="text-3xl font-display font-bold text-white">80%</div>
                  <p class="text-[11px] text-white/60 font-medium">LinkedIn reach</p>
                </div>
                <div class="text-[10px] font-semibold bg-white/10 text-[#9EE493] px-2.5 py-1 rounded-full flex items-center gap-1">
                  <span>+2.6%</span>
                  <ArrowUpRight class="w-3 h-3 text-[#9EE493]" />
                </div>
              </div>

              <!-- Item 2: Remote / X (Twitter) -->
              <div class="flex items-center justify-between pt-1">
                <div>
                  <div class="w-7 h-7 rounded-full bg-white/10 flex items-center justify-center text-white mb-1">
                    <TrendingUp class="w-3.5 h-3.5 text-sky-300" />
                  </div>
                  <div class="text-3xl font-display font-bold text-white">20%</div>
                  <p class="text-[11px] text-white/60 font-medium">X (Twitter) reach</p>
                </div>
                <div class="text-[10px] font-semibold bg-white/10 text-emerald-400 px-2.5 py-1 rounded-full flex items-center gap-1">
                  <span>+2.6%</span>
                  <ArrowUpRight class="w-3 h-3 text-emerald-400" />
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom Row: Donut Chart Card + Talent/Draft Pipeline Card -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <!-- Total employee / Track your team Half-Donut Card (Matching Image 1) -->
            <div class="bg-white rounded-3xl p-5 border border-slate-200/70 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-xs text-slate-400 font-medium">Total dispatches</p>
                  <h4 class="font-display font-bold text-sm text-slate-900">Track your reach</h4>
                </div>
                <Link href="/analytics" class="w-7 h-7 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 hover:text-slate-900 transition">
                  <ChevronRight class="w-4 h-4" />
                </Link>
              </div>

              <!-- Half Donut Gauge SVG (Matching Image 1) -->
              <div class="relative flex flex-col items-center justify-center py-2">
                <svg class="w-44 h-24 overflow-visible" viewBox="0 0 100 55">
                  <!-- Arc Segment 1: Mint (48 items) -->
                  <path
                    d="M 10 50 A 40 40 0 0 1 50 10"
                    fill="none"
                    stroke="#86EFAC"
                    stroke-width="14"
                    stroke-linecap="round"
                  />
                  <!-- Arc Segment 2: Slate-800 (27 items) -->
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

              <!-- Legends -->
              <div class="space-y-2 text-xs pt-1">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#86EFAC]"></span>
                    <span class="text-slate-600 font-medium">AI Generated</span>
                  </div>
                  <span class="font-semibold text-slate-900">48 posts</span>
                </div>

                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#1E3A40]"></span>
                    <span class="text-slate-600 font-medium">Deep Dives</span>
                  </div>
                  <span class="font-semibold text-slate-900">27 posts</span>
                </div>

                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-slate-300"></span>
                    <span class="text-slate-600 font-medium">Evergreen</span>
                  </div>
                  <span class="font-semibold text-slate-900">18 posts</span>
                </div>
              </div>
            </div>

            <!-- Talent recruitment / AI Draft Pipeline Card (Matching Image 1) -->
            <div class="bg-white rounded-3xl p-5 border border-slate-200/70 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-xs text-slate-400 font-medium">AI Generation queue</p>
                  <h4 class="font-display font-bold text-sm text-slate-900">Auto-Draft pipeline</h4>
                </div>
                <Link href="/inbox" class="w-7 h-7 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 hover:text-slate-900 transition">
                  <ChevronRight class="w-4 h-4" />
                </Link>
              </div>

              <!-- 2 Draft Avatars & Join Call Button (Matching Image 1) -->
              <div class="grid grid-cols-3 gap-2">
                <div class="aspect-square rounded-2xl overflow-hidden bg-slate-100 border border-slate-200">
                  <img
                    src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=200&q=80"
                    alt="Draft"
                    class="w-full h-full object-cover"
                  />
                </div>
                <div class="aspect-square rounded-2xl overflow-hidden bg-slate-100 border border-slate-200">
                  <img
                    src="https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=200&q=80"
                    alt="Draft"
                    class="w-full h-full object-cover"
                  />
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
                <span>120 Generated</span>
                <span>80 Approved</span>
              </div>

              <!-- Vertical Green Match Bars (Matching Image 1) -->
              <div class="flex items-center gap-1.5 h-7">
                <div v-for="i in 12" :key="i" :class="['flex-1 rounded-full h-full transition', i <= 8 ? 'bg-[#86EFAC]' : 'bg-slate-200']"></div>
              </div>

              <div class="flex items-center gap-3 text-[10px] text-slate-400 font-medium">
                <div class="flex items-center gap-1">
                  <span class="w-2 h-2 rounded-full bg-[#86EFAC]"></span>
                  <span>Approved</span>
                </div>
                <div class="flex items-center gap-1">
                  <span class="w-2 h-2 rounded-full bg-slate-200"></span>
                  <span>In Review</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN: Salaries & Incentive / Queue Dispatches (3 Cols on desktop) -->
        <div class="lg:col-span-3 space-y-5">
          <!-- Upcoming Dispatches List (Matching Image 1 Right Column) -->
          <div class="bg-white rounded-3xl p-5 border border-slate-200/70 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-4">
            <div>
              <p class="text-xs text-slate-400 font-medium">Payout monthly</p>
              <h3 class="font-display font-bold text-base text-slate-900">Salaries and insentive</h3>
            </div>

            <!-- List of items with avatar, metric, time, and soft status pill (Matching Image 1) -->
            <div class="space-y-3.5">
              <div
                v-for="item in dispatchFeed"
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
                    <p class="text-[10px] text-slate-400 mt-0.5">{{ item.time }}</p>
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

          <!-- Dark Teal Summary Card (Matching Bottom Right Card in Image 1) -->
          <div class="bg-[#162E35] rounded-3xl p-5 text-white shadow-contrast space-y-4">
            <!-- Top Green Pill: Basic Salary / Monthly Budget -->
            <div class="bg-[#9EE493] rounded-2xl p-3 text-slate-900 flex items-center justify-between">
              <span class="text-xs font-semibold">Monthly Budget</span>
              <span class="font-mono font-bold text-sm">${{ spentAmount }}</span>
            </div>

            <!-- Sub Pill Badges: Perform / Gift -->
            <div class="grid grid-cols-2 gap-2">
              <div class="bg-white/10 rounded-xl p-2.5 text-center">
                <span class="text-[10px] text-white/70 block">Gemini LLM</span>
                <span class="font-mono font-semibold text-xs text-white mt-0.5 block">$14.50</span>
              </div>
              <div class="bg-white/10 rounded-xl p-2.5 text-center">
                <span class="text-[10px] text-white/70 block">X / LI APIs</span>
                <span class="font-mono font-semibold text-xs text-white mt-0.5 block">$10.00</span>
              </div>
            </div>

            <!-- Payment 100% / Take Home Pay -->
            <div class="flex items-baseline justify-between pt-2 border-t border-white/10">
              <div>
                <p class="text-[10px] text-white/60">Health status</p>
                <span class="text-sm font-semibold text-[#9EE493]">100% Active</span>
              </div>
              <div class="text-right">
                <p class="text-[10px] text-white/60">Available cap</p>
                <span class="text-lg font-display font-bold text-white">${{ remainingAmount }}</span>
              </div>
            </div>

            <!-- Bottom Action Icons with Badge Count -->
            <div class="flex items-center justify-between pt-1">
              <Link
                href="/inbox"
                class="w-full bg-white hover:bg-slate-100 text-slate-900 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition shadow-sm"
              >
                <Inbox class="w-4 h-4 text-slate-900" />
                <span>Review Next Draft</span>
                <span class="w-5 h-5 rounded-full bg-[#9EE493] text-slate-900 text-[10px] font-mono flex items-center justify-center">
                  {{ stats.inbox_count }}
                </span>
              </Link>
            </div>
          </div>
        </div>

      </div>

    </div>
  </AppLayout>
</template>
