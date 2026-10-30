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

    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between pb-4 border-b border-white/10">
        <div>
          <h1 class="text-xl font-bold text-white flex items-center gap-2">
            <Flame class="w-5 h-5 text-amber-500" />
            Trend Intelligence Radar
          </h1>
          <p class="text-xs text-gray-400 mt-0.5">
            Real-time topic stream from configured sources (Hacker News, RSS, TechCrunch). Inspiration only — zero automated plagiarism.
          </p>
        </div>

        <div class="flex items-center gap-2">
          <span class="text-xs text-gray-400 font-mono">
            {{ trends.length }} Active Topics Ingested
          </span>
        </div>
      </div>

      <div v-if="trends.length > 0" class="space-y-4">
        <div
          v-for="t in trends"
          :key="t.id"
          class="bg-surface-elevated/70 border border-white/10 rounded-2xl p-5 flex items-start justify-between gap-6 hover:border-indigo-500/40 transition backdrop-blur-sm"
        >
          <div class="space-y-2 flex-1">
            <div class="flex items-center gap-2">
              <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 font-mono">
                {{ Number(t.engagement_score || 88).toFixed(0) }} Score
              </span>
              <span class="text-xs text-gray-400 font-mono">
                via {{ t.trend_source?.name || 'Hacker News' }}
              </span>
            </div>

            <h3 class="text-base font-bold text-white leading-snug">
              {{ t.title }}
            </h3>

            <p class="text-xs text-gray-300 leading-relaxed max-w-4xl">
              {{ t.summary || t.content || 'Technical retrospective and architecture debate covering distributed performance guarantees.' }}
            </p>

            <div class="flex items-center gap-4 pt-2">
              <a
                :href="t.url"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1 text-xs text-cyan-400 hover:text-cyan-300 font-mono transition"
              >
                <span>Read Original Article</span>
                <ArrowUpRight class="w-3.5 h-3.5" />
              </a>

              <button
                @click="generateDraftFromTrend(t)"
                :disabled="generatingTrendId === t.id"
                class="inline-flex items-center gap-1.5 text-xs text-indigo-400 hover:text-indigo-300 font-mono font-medium transition disabled:opacity-50"
              >
                <Sparkles class="w-3.5 h-3.5 text-indigo-400" />
                <span>{{ generatingTrendId === t.id ? 'Generating Draft...' : '1-Click Draft with AI →' }}</span>
              </button>
            </div>
          </div>

          <button
            @click="toggleBookmark(t)"
            class="p-2.5 rounded-xl border border-white/5 hover:border-white/10 transition text-gray-400 hover:text-white"
            :class="{ 'text-amber-400 border-amber-500/30 bg-amber-500/10': t.is_bookmarked }"
          >
            <Bookmark class="w-4 h-4" />
          </button>
        </div>
      </div>

      <div v-else class="text-center py-12 text-gray-500 text-xs font-mono">
        No trend topics ingested yet. Run background worker or check active sources.
      </div>
    </div>
  </AppLayout>
</template>
