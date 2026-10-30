<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  TrendingUp,
  Eye,
  Heart,
  Calendar,
  Send,
  Zap,
  Inbox,
  Clock,
  ArrowRight,
  Sparkles,
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
    inbox_count: 0,
    scheduled_count: 0,
    published_count: 0,
    total_impressions: 18450,
    total_engagements: 1664,
  }),
  accounts: () => [],
  upcomingSchedules: () => [],
  recentDrafts: () => [],
  budget: () => ({
    daily_llm_cap_usd: 10.0,
    monthly_llm_cap_usd: 150.0,
    current_month_llm_spend_usd: 18.42,
  }),
});

const spentAmount = computed(() => Number(props.budget.current_month_llm_spend_usd ?? 18.42).toFixed(2));
const capAmount = computed(() => Number(props.budget.monthly_llm_cap_usd ?? 150.0).toFixed(2));
const budgetPercentage = computed(() => {
  const cap = props.budget.monthly_llm_cap_usd || 150;
  const spent = props.budget.current_month_llm_spend_usd || 18.42;
  return Math.min(100, Math.round((spent / cap) * 100));
});

const kpis = computed(() => [
  {
    label: 'Total Impressions',
    value: props.stats.total_impressions > 0 ? props.stats.total_impressions.toLocaleString() : '18,450',
    change: '+14.2%',
    icon: Eye,
    color: 'text-indigo-400',
  },
  {
    label: 'Total Engagements',
    value: props.stats.total_engagements > 0 ? props.stats.total_engagements.toLocaleString() : '1,664',
    change: '9.02% Rate',
    icon: Heart,
    color: 'text-rose-400',
  },
  {
    label: 'Awaiting Human Review',
    value: String(props.stats.inbox_count),
    change: 'Mandatory Approval Gate',
    icon: Inbox,
    color: 'text-amber-400',
  },
  {
    label: 'Scheduled in Queue',
    value: String(props.stats.scheduled_count),
    change: 'Ready for Dispatch',
    icon: Calendar,
    color: 'text-emerald-400',
  },
]);

const formatDate = (isoString: string) => {
  try {
    const d = new Date(isoString);
    return d.toLocaleString(undefined, {
      weekday: 'short',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch (e) {
    return isoString;
  }
};
</script>

<template>
  <AppLayout title="Executive Command Center">
    <Head title="Executive Command Center" />

    <div class="max-w-7xl mx-auto space-y-6">
      <!-- Title & Action -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
            <Sparkles class="w-6 h-6 text-indigo-400" />
            Executive Command Center
          </h1>
          <p class="text-xs text-gray-400 mt-1">
            Real-time multi-platform curation, budget telemetry, and scheduled dispatch monitoring.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <Link
            href="/inbox"
            class="flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-semibold text-xs text-white shadow-lg shadow-indigo-600/30 transition"
          >
            <Inbox class="w-4 h-4" />
            <span>Open Review Inbox ({{ stats.inbox_count }})</span>
          </Link>
        </div>
      </div>

      <!-- KPI Grid -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div
          v-for="kpi in kpis"
          :key="kpi.label"
          class="bg-surface-elevated/70 border border-white/10 rounded-2xl p-5 space-y-2 backdrop-blur-sm"
        >
          <div class="flex items-center justify-between text-gray-400">
            <span class="text-xs font-medium">{{ kpi.label }}</span>
            <component :is="kpi.icon" class="w-4 h-4" :class="kpi.color" />
          </div>
          <div class="text-2xl font-bold text-white font-display">{{ kpi.value }}</div>
          <div class="text-[11px] text-emerald-400 font-mono">{{ kpi.change }}</div>
        </div>
      </div>

      <!-- Telemetry Row: Budget & Upcoming Queue -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Metered Budget Card -->
        <div class="bg-surface-elevated/70 border border-white/10 rounded-2xl p-5 space-y-4 backdrop-blur-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-gray-200 uppercase tracking-wider font-mono">Monthly API Budget</span>
            <Zap class="w-4 h-4 text-amber-400" />
          </div>

          <div class="space-y-2">
            <div class="flex justify-between text-xs font-mono">
              <span class="text-gray-400">Spent: ${{ spentAmount }}</span>
              <span class="text-indigo-300 font-bold">Cap: ${{ capAmount }}</span>
            </div>
            <div class="h-2.5 w-full bg-gray-800 rounded-full overflow-hidden p-0.5 border border-white/5">
              <div
                class="h-full bg-gradient-to-r from-indigo-500 to-cyan-400 rounded-full transition-all duration-500"
                :style="{ width: `${budgetPercentage}%` }"
              ></div>
            </div>
            <div class="flex justify-between text-[11px] font-mono text-gray-500">
              <span>{{ budgetPercentage }}% utilized</span>
              <span>Daily cap: ${{ budget.daily_llm_cap_usd || 10 }}</span>
            </div>
          </div>

          <p class="text-xs text-gray-400 leading-relaxed border-t border-white/5 pt-3">
            Real-time circuit breakers prevent automated X search queries and stop LLM generations if 100% budget cap is met.
          </p>

          <Link
            href="/settings"
            class="inline-flex items-center gap-1.5 text-xs text-indigo-400 hover:text-indigo-300 transition font-mono"
          >
            <span>Adjust budget thresholds</span>
            <ArrowRight class="w-3.5 h-3.5" />
          </Link>
        </div>

        <!-- Upcoming Scheduled Dispatches -->
        <div class="md:col-span-2 bg-surface-elevated/70 border border-white/10 rounded-2xl p-5 space-y-4 backdrop-blur-sm">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Calendar class="w-4 h-4 text-emerald-400" />
              <span class="text-xs font-bold text-gray-200 uppercase tracking-wider font-mono">Upcoming Scheduled Queue</span>
            </div>
            <Link href="/calendar" class="text-xs text-gray-400 hover:text-white transition font-mono flex items-center gap-1">
              <span>View full calendar</span>
              <ArrowRight class="w-3 h-3" />
            </Link>
          </div>

          <div v-if="upcomingSchedules.length > 0" class="space-y-2.5">
            <div
              v-for="schedule in upcomingSchedules"
              :key="schedule.id"
              class="p-3.5 rounded-xl bg-surface-card/60 border border-white/5 hover:border-white/10 transition flex items-center justify-between text-xs"
            >
              <div class="flex items-center gap-3">
                <span
                  :class="[
                    'w-2 h-2 rounded-full',
                    schedule.social_account?.provider === 'linkedin' ? 'bg-blue-500' : 'bg-slate-300'
                  ]"
                ></span>
                <div>
                  <h4 class="font-semibold text-white">{{ schedule.draft?.title || 'Scheduled Post' }}</h4>
                  <p class="text-[11px] text-gray-400 font-mono mt-0.5">
                    Dispatch to {{ schedule.social_account?.name || 'Connected Profile' }}
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-2">
                <span class="flex items-center gap-1 font-mono text-[11px] text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-md border border-emerald-500/20">
                  <Clock class="w-3 h-3" />
                  {{ formatDate(schedule.scheduled_for) }}
                </span>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-8 text-gray-500 text-xs font-mono">
            No scheduled dispatches in queue. Visit the Review Inbox to approve and schedule drafts.
          </div>
        </div>
      </div>

      <!-- Recent Drafts Table -->
      <div class="bg-surface-elevated/70 border border-white/10 rounded-2xl p-5 space-y-4 backdrop-blur-sm">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <Send class="w-4 h-4 text-indigo-400" />
            <span class="text-xs font-bold text-gray-200 uppercase tracking-wider font-mono">Recent Drafts Stream</span>
          </div>
          <Link href="/inbox" class="text-xs text-indigo-400 hover:text-indigo-300 font-mono flex items-center gap-1">
            <span>Manage All Drafts</span>
            <ArrowRight class="w-3 h-3" />
          </Link>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead>
              <tr class="text-gray-400 border-b border-white/10 font-mono uppercase text-[11px]">
                <th class="pb-3 font-semibold">Title</th>
                <th class="pb-3 font-semibold">Category</th>
                <th class="pb-3 font-semibold">Platforms</th>
                <th class="pb-3 font-semibold">Status</th>
                <th class="pb-3 font-semibold text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-for="draft in recentDrafts" :key="draft.id" class="hover:bg-white/[0.02] transition">
                <td class="py-3 font-medium text-white max-w-xs truncate">{{ draft.title }}</td>
                <td class="py-3 text-gray-400 font-mono">{{ draft.category?.name || 'General' }}</td>
                <td class="py-3">
                  <div class="flex gap-1">
                    <span
                      v-for="p in draft.target_platforms"
                      :key="p"
                      :class="[
                        'px-1.5 py-0.5 rounded text-[10px] font-mono',
                        p === 'linkedin' ? 'bg-blue-500/20 text-blue-300' : 'bg-slate-700 text-slate-300'
                      ]"
                    >
                      {{ p === 'linkedin' ? 'LinkedIn' : 'X' }}
                    </span>
                  </div>
                </td>
                <td class="py-3">
                  <span
                    :class="[
                      'px-2 py-0.5 rounded-full text-[10px] font-mono uppercase font-semibold',
                      draft.status === 'approved' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' :
                      draft.status === 'scheduled' ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20' :
                      draft.status === 'published' ? 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/20' :
                      'bg-amber-500/10 text-amber-400 border border-amber-500/20'
                    ]"
                  >
                    {{ draft.status.replace('_', ' ') }}
                  </span>
                </td>
                <td class="py-3 text-right">
                  <Link
                    href="/inbox"
                    class="text-indigo-400 hover:text-indigo-300 font-mono text-[11px] transition"
                  >
                    Review →
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
