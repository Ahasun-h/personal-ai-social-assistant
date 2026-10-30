<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Flame, Bookmark, ArrowUpRight, Sparkles } from 'lucide-vue-next';

const trends = ref([
  {
    id: 'tr_1',
    title: 'Autonomous Multi-Agent SDLC & Deterministic TDD Loops',
    source: 'Hacker News',
    score: 98,
    summary: 'Discussion on replacing brittle single-prompt code generators with dual-agent maker-checker architectures.',
    bookmarked: true,
  },
  {
    id: 'tr_2',
    title: 'Tailwind CSS v4 Oxide Engine Benchmarks',
    source: 'Tech Radar',
    score: 84,
    summary: 'New Rust-based compilation engine delivers 10x faster HMR updates on large Next.js and Vue apps.',
    bookmarked: false,
  },
  {
    id: 'tr_3',
    title: 'PostgreSQL 17 Logical Replication & Index Improvements',
    source: 'Database Trends',
    score: 79,
    summary: 'High-throughput 3NF data pipelines and faster VACUUM operations for append-heavy analytical workloads.',
    bookmarked: false,
  },
]);

const toggleBookmark = (item: any) => {
  item.bookmarked = !item.bookmarked;
};
</script>

<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between pb-4 border-b border-white/10">
        <div>
          <h1 class="text-xl font-bold text-white flex items-center gap-2">
            <Flame class="w-5 h-5 text-amber-500" />
            Trend Intelligence Radar
          </h1>
          <p class="text-xs text-gray-400 mt-0.5">Scored topic discovery from RSS, Hacker News, and Tech Feeds. Inspiration only — zero plagiarism.</p>
        </div>
      </div>

      <div class="space-y-4">
        <div
          v-for="t in trends"
          :key="t.id"
          class="bg-surface-card border border-white/10 rounded-xl p-4 flex items-start justify-between gap-4 hover:border-indigo-500/40 transition"
        >
          <div class="space-y-1.5 flex-1">
            <div class="flex items-center gap-2">
              <span class="text-xs font-semibold px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 font-mono">
                {{ t.score }}% Match
              </span>
              <span class="text-[11px] text-gray-400 font-medium">Source: {{ t.source }}</span>
            </div>
            <h3 class="text-sm font-bold text-white">{{ t.title }}</h3>
            <p class="text-xs text-gray-400 leading-relaxed">{{ t.summary }}</p>
          </div>

          <div class="flex items-center gap-2">
            <button
              @click="toggleBookmark(t)"
              class="p-2 rounded-lg border border-white/10 hover:bg-white/5 transition"
              :class="t.bookmarked ? 'text-amber-400 border-amber-500/30' : 'text-gray-400'"
            >
              <Bookmark class="w-4 h-4" />
            </button>

            <a
              href="/inbox"
              class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-lg shadow-indigo-600/30 transition"
            >
              <Sparkles class="w-3.5 h-3.5" />
              Generate Draft
            </a>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
