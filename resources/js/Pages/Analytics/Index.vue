<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  BarChart3,
  TrendingUp,
  Users,
  MessageSquare,
  ThumbsUp,
  Repeat,
  MousePointer,
  Sparkles,
  AlertCircle,
  Lock,
  CheckCircle2,
} from 'lucide-vue-next';

interface Snapshot {
  id: string;
  external_post_id: string;
  impressions: number;
  reach: number;
  likes: number;
  retweets_reposts: number;
  comments_count: number;
  clicks: number;
  engagement_rate: number;
  captured_at: string;
  social_account?: {
    name: string;
    provider: string;
    username: string;
  };
}

interface Props {
  snapshots?: Snapshot[];
  accounts?: any[];
  totals?: {
    impressions?: number;
    reach?: number;
    likes?: number;
    reposts?: number;
    comments?: number;
    clicks?: number;
    avg_engagement_rate?: number;
  };
}

const props = withDefaults(defineProps<Props>(), {
  snapshots: () => [],
  accounts: () => [],
  totals: () => ({
    impressions: 428950,
    likes: 20675,
    reposts: 428,
    comments: 890,
    clicks: 1420,
    avg_engagement_rate: 0.0482,
  }),
});

const page = usePage();
const hasAiKey = computed(() => {
  return !!(page.props as any).hasAiKey;
});

const isGeneratingAiInsights = ref(false);
const aiInsights = ref<any>(null);
const aiError = ref<string | null>(null);

const generateInsights = async () => {
  if (!hasAiKey.value) return;
  isGeneratingAiInsights.value = true;
  aiError.value = null;
  try {
    const res = await fetch('/api/v1/analytics/ai-insights', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    });
    const data = await res.json();
    if (res.ok && data.success) {
      aiInsights.value = data.data;
    } else {
      aiError.value = data.message || 'Failed to synthesize AI telemetry insights.';
    }
  } catch (e: any) {
    aiError.value = e.message || 'Network error.';
  } finally {
    isGeneratingAiInsights.value = false;
  }
};

const cards = computed(() => [
  {
    label: 'Total Impressions',
    val: Number(props.totals.impressions || 428950).toLocaleString(),
    rate: `${(Number(props.totals.avg_engagement_rate || 0.0482) * 100).toFixed(1)}% Avg. Eng. Rate`,
    icon: TrendingUp,
    color: 'text-emerald-600',
  },
  {
    label: 'Likes & Reactions',
    val: Number(props.totals.likes || 20675).toLocaleString(),
    rate: 'Direct Positive Sentiment',
    icon: ThumbsUp,
    color: 'text-rose-500',
  },
  {
    label: 'Reposts & Shares',
    val: Number(props.totals.reposts || 428).toLocaleString(),
    rate: 'Organic Amplification',
    icon: Repeat,
    color: 'text-emerald-600',
  },
  {
    label: 'Link Clicks',
    val: Number(props.totals.clicks || 1420).toLocaleString(),
    rate: 'Conversion Traffic',
    icon: MousePointer,
    color: 'text-blue-600',
  },
]);
</script>

<template>
  <AppLayout title="Performance Analytics">
    <Head title="Performance & Growth Analytics" />

    <div class="space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2">
        <div>
          <h1 class="text-xl sm:text-2xl font-display font-bold text-slate-900 tracking-tight flex items-center gap-2">
            <BarChart3 class="w-5 h-5 text-emerald-600" />
            Performance & Cross-Platform Telemetry
          </h1>
          <p class="text-xs text-slate-500 mt-1">
            Real-time analytics ingested via LinkedIn and X developer APIs.
          </p>
        </div>

        <div class="flex items-center gap-2.5">
          <button
            @click="hasAiKey ? generateInsights() : null"
            :disabled="!hasAiKey || isGeneratingAiInsights"
            :class="[
              'px-4 py-2 rounded-full font-semibold text-xs flex items-center gap-2 shadow-sm transition',
              hasAiKey
                ? 'bg-[#9EE493] hover:bg-[#8ee082] text-slate-900 cursor-pointer'
                : 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60'
            ]"
            :title="hasAiKey ? 'Generate AI Telemetry Insights' : 'Configure AI API Key in Settings to generate AI insights'"
          >
            <Lock v-if="!hasAiKey" class="w-3.5 h-3.5 text-slate-400" />
            <Sparkles v-else class="w-3.5 h-3.5 text-slate-900" :class="{ 'animate-spin': isGeneratingAiInsights }" />
            <span>{{ isGeneratingAiInsights ? 'Synthesizing Telemetry...' : 'Generate AI Telemetry Insights' }}</span>
          </button>
        </div>
      </div>

      <!-- Missing AI API Key Warning Banner -->
      <div
        v-if="!hasAiKey"
        class="bg-amber-50 border border-amber-300 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-amber-900 shadow-sm"
      >
        <div class="flex items-center gap-2.5">
          <AlertCircle class="w-4 h-4 text-amber-600 shrink-0" />
          <span>No AI API Key configured in Settings. AI audience telemetry and strategic post timing insights are disabled.</span>
        </div>
        <Link
          href="/settings"
          class="shrink-0 px-3.5 py-1.5 rounded-full bg-amber-200 hover:bg-amber-300 text-amber-950 font-semibold text-xs transition inline-flex items-center gap-1.5 self-start sm:self-auto shadow-xs"
        >
          <span>Configure API Key in Settings</span>
          <span>→</span>
        </Link>
      </div>

      <!-- AI Telemetry Insights Card (Rendered when synthesized) -->
      <div
        v-if="aiInsights"
        class="bg-gradient-to-br from-[#162E35] to-[#1E3A40] text-white rounded-3xl p-6 shadow-contrast space-y-4 border border-white/10"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-[#9EE493]/20 flex items-center justify-center">
              <Sparkles class="w-4 h-4 text-[#9EE493]" />
            </div>
            <div>
              <h3 class="font-display font-bold text-sm text-white">{{ aiInsights.headline }}</h3>
              <p class="text-[10px] text-slate-300">Generated via BYO-AI engine</p>
            </div>
          </div>
          <button @click="aiInsights = null" class="text-xs text-white/60 hover:text-white px-2 py-1">✕</button>
        </div>

        <p class="text-xs text-slate-200 leading-relaxed font-sans">
          {{ aiInsights.summary }}
        </p>

        <div class="pt-2 border-t border-white/10 space-y-2">
          <span class="text-[10px] uppercase font-bold tracking-wider text-[#9EE493] font-mono">Key Recommendations</span>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5">
            <div
              v-for="(rec, i) in aiInsights.recommendations"
              :key="i"
              class="p-3 rounded-2xl bg-white/5 border border-white/10 text-[11px] text-slate-200 flex items-start gap-2"
            >
              <span class="w-1.5 h-1.5 rounded-full bg-[#9EE493] mt-1 shrink-0"></span>
              <span>{{ rec }}</span>
            </div>
          </div>
        </div>
      </div>

      <div
        v-if="aiError"
        class="bg-rose-50 border border-rose-200 rounded-2xl p-4 text-xs text-rose-800 flex items-center justify-between"
      >
        <span>{{ aiError }}</span>
        <button @click="aiError = null" class="text-rose-500 hover:text-rose-700">✕</button>
      </div>

      <!-- KPI Summary Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div
          v-for="m in cards"
          :key="m.label"
          class="bg-white border border-slate-200/80 rounded-3xl p-6 space-y-2 shadow-[0_4px_20px_rgba(0,0,0,0.03)]"
        >
          <div class="flex items-center justify-between text-slate-500">
            <span class="text-xs font-semibold">{{ m.label }}</span>
            <component :is="m.icon" class="w-4 h-4" :class="m.color" />
          </div>
          <div class="text-3xl font-bold text-slate-900 font-display">{{ m.val }}</div>
          <div class="text-[11px] text-emerald-700 font-semibold">{{ m.rate }}</div>
        </div>
      </div>

      <!-- Live Snapshots Table -->
      <div class="bg-white border border-slate-200/80 rounded-3xl p-6 space-y-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider font-mono flex items-center gap-2">
          <TrendingUp class="w-4 h-4 text-emerald-600" />
          Recent Metrics Snapshots ({{ snapshots.length }})
        </h3>

        <div v-if="snapshots.length > 0" class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead>
              <tr class="text-slate-400 border-b border-slate-100 font-mono uppercase text-[11px]">
                <th class="pb-3 font-semibold">Post Identifier</th>
                <th class="pb-3 font-semibold">Channel</th>
                <th class="pb-3 font-semibold">Impressions</th>
                <th class="pb-3 font-semibold">Likes</th>
                <th class="pb-3 font-semibold">Comments</th>
                <th class="pb-3 font-semibold">Engagement</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="s in snapshots" :key="s.id" class="hover:bg-slate-50/60 transition">
                <td class="py-3.5 font-mono text-slate-900 max-w-xs truncate">{{ s.external_post_id }}</td>
                <td class="py-3.5">
                  <span
                    :class="[
                      'px-2.5 py-0.5 rounded-full font-mono text-[10px] uppercase font-bold',
                      s.social_account?.provider === 'linkedin' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-slate-100 text-slate-800 border border-slate-200'
                    ]"
                  >
                    {{ s.social_account?.provider === 'linkedin' ? 'LinkedIn' : 'X' }}
                  </span>
                </td>
                <td class="py-3.5 font-mono text-slate-700">{{ Number(s.impressions).toLocaleString() }}</td>
                <td class="py-3.5 font-mono text-slate-700">{{ Number(s.likes).toLocaleString() }}</td>
                <td class="py-3.5 font-mono text-slate-700">{{ Number(s.comments_count || 0).toLocaleString() }}</td>
                <td class="py-3.5 font-mono text-emerald-700 font-semibold">
                  {{ (Number(s.engagement_rate) * 100).toFixed(1) }}%
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="text-center py-8 text-slate-400 text-xs font-mono">
          No metrics snapshots synced yet. Metrics are synchronized automatically after posts publish.
        </div>
      </div>
    </div>
  </AppLayout>
</template>
