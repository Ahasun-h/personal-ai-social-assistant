<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Flame, Bookmark, ArrowUpRight, Sparkles, RefreshCw } from 'lucide-vue-next';

interface TrendItem {
  id: string;
  title: string;
  url: string;
  summary?: string;
  content?: string;
  engagement_score?: number;
  is_bookmarked?: boolean;
  trend_source?: {
    name: string;
    source_type: string;
  };
}

interface Props {
  trends?: TrendItem[];
  sources?: any[];
}

const props = withDefaults(defineProps<Props>(), {
  trends: () => [],
  sources: () => [],
});

const isSyncing = ref(false);
const generatingTrendId = ref<string | null>(null);

const toggleBookmark = async (item: TrendItem) => {
  item.is_bookmarked = !item.is_bookmarked;
  await fetch(`/api/v1/trends/items/${item.id}/bookmark`, { method: 'POST' });
};

const generateDraftFromTrend = async (item: TrendItem) => {
  generatingTrendId.value = item.id;
  await fetch('/api/v1/drafts/ai/generate', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      topic: `${item.title} (Source: ${item.url})`,
      tone: 'pragmatic_architect',
      platforms: ['linkedin', 'x_twitter'],
    }),
  });
  generatingTrendId.value = null;
  router.visit('/inbox');
};
</script>

<template>
  <AppLayout title="Trend Radar">
    <Head title="Trend Intelligence Radar" />

    <div class="space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2">
        <div>
          <h1 class="text-xl sm:text-2xl font-display font-bold text-slate-900 tracking-tight flex items-center gap-2">
            <Flame class="w-5 h-5 text-amber-500" />
            Trend Intelligence Radar
          </h1>
          <p class="text-xs text-slate-500 mt-1">
            Real-time topic stream from configured sources (Hacker News, RSS, TechCrunch). Inspiration only — zero automated plagiarism.
          </p>
        </div>

        <div class="flex items-center gap-2">
          <span class="text-xs text-slate-500 font-mono bg-white border border-slate-200/80 px-3.5 py-1.5 rounded-full shadow-sm">
            {{ trends.length }} Active Topics Ingested
          </span>
        </div>
      </div>

      <div v-if="trends.length > 0" class="space-y-4">
        <div
          v-for="t in trends"
          :key="t.id"
          class="bg-white border border-slate-200/80 rounded-3xl p-6 flex items-start justify-between gap-6 hover:border-emerald-300 transition shadow-[0_4px_20px_rgba(0,0,0,0.03)]"
        >
          <div class="space-y-2 flex-1">
            <div class="flex items-center gap-2">
              <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 font-mono">
                {{ Number(t.engagement_score || 88).toFixed(0) }} Score
              </span>
              <span class="text-xs text-slate-400 font-medium">
                via {{ t.trend_source?.name || 'Hacker News' }}
              </span>
            </div>

            <h3 class="text-base font-display font-bold text-slate-900 leading-snug">
              {{ t.title }}
            </h3>

            <p class="text-xs text-slate-600 leading-relaxed max-w-4xl">
              {{ t.summary || t.content || 'Technical retrospective and architecture debate covering distributed performance guarantees.' }}
            </p>

            <div class="flex items-center gap-4 pt-2">
              <a
                :href="t.url"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1 text-xs text-slate-600 hover:text-slate-900 font-medium transition"
              >
                <span>Read Original Article</span>
                <ArrowUpRight class="w-3.5 h-3.5" />
              </a>

              <button
                @click="generateDraftFromTrend(t)"
                :disabled="generatingTrendId === t.id"
                class="inline-flex items-center gap-1.5 text-xs font-semibold px-4 py-1.5 rounded-full bg-[#9EE493] hover:bg-[#8ee082] text-slate-900 shadow-sm transition disabled:opacity-50"
              >
                <Sparkles class="w-3.5 h-3.5 text-slate-900" />
                <span>{{ generatingTrendId === t.id ? 'Generating Draft...' : '1-Click Draft with AI →' }}</span>
              </button>
            </div>
          </div>

          <button
            @click="toggleBookmark(t)"
            class="p-2.5 rounded-full border border-slate-200 hover:bg-slate-50 transition text-slate-400 hover:text-slate-700 shadow-sm"
            :class="{ 'text-amber-500 border-amber-300 bg-amber-50': t.is_bookmarked }"
          >
            <Bookmark class="w-4 h-4" />
          </button>
        </div>
      </div>

      <div v-else class="text-center py-12 text-slate-400 text-xs font-mono">
        No trend topics ingested yet. Run background worker or check active sources.
      </div>
    </div>
  </AppLayout>
</template>
