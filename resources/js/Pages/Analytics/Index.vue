<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { BarChart3, TrendingUp, Users, MessageSquare } from 'lucide-vue-next';

const metrics = [
  { label: 'LinkedIn Total Impressions', val: '248,300', rate: '5.2% Eng. Rate' },
  { label: 'X Total Impressions', val: '180,650', rate: '4.4% Eng. Rate' },
  { label: 'Total Reposts & Shares', val: '4,120', rate: '+12% WoW' },
  { label: 'Inbound Comments Answered', val: '186', rate: '100% Resolved' },
];
</script>

<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between pb-4 border-b border-white/10">
        <div>
          <h1 class="text-xl font-bold text-white flex items-center gap-2">
            <BarChart3 class="w-5 h-5 text-indigo-400" />
            Performance & Growth Analytics
          </h1>
          <p class="text-xs text-gray-400 mt-0.5">Continuous learning feedback loop tracking reach, engagement, and topic virality across channels.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div v-for="m in metrics" :key="m.label" class="bg-surface-card border border-white/10 rounded-xl p-4 space-y-1">
          <span class="text-xs text-gray-400 font-medium">{{ m.label }}</span>
          <div class="text-2xl font-bold text-white font-display">{{ m.val }}</div>
          <div class="text-[11px] text-cyan-400 font-mono">{{ m.rate }}</div>
        </div>
      </div>

      <div class="bg-surface-card border border-white/10 rounded-xl p-6 space-y-4">
        <h3 class="text-xs font-bold text-gray-300 uppercase tracking-wider flex items-center gap-2">
          <TrendingUp class="w-4 h-4 text-emerald-400" />
          Top Resonant Topic Niches (Feedback Loop into AI Generator)
        </h3>

        <div class="space-y-3">
          <div class="p-3 rounded-lg bg-surface-elevated border border-white/5 flex items-center justify-between text-xs">
            <span class="font-semibold text-gray-200">1. Autonomous SDLC Engines & Verification Gates</span>
            <span class="font-mono text-emerald-400 font-bold">6.8% Avg. Engagement Rate</span>
          </div>
          <div class="p-3 rounded-lg bg-surface-elevated border border-white/5 flex items-center justify-between text-xs">
            <span class="font-semibold text-gray-200">2. Deterministic State Machines vs Brittle LLM Loops</span>
            <span class="font-mono text-emerald-400 font-bold">5.4% Avg. Engagement Rate</span>
          </div>
          <div class="p-3 rounded-lg bg-surface-elevated border border-white/5 flex items-center justify-between text-xs">
            <span class="font-semibold text-gray-200">3. Metered Cloud Cost Controls & Token Caching</span>
            <span class="font-mono text-emerald-400 font-bold">4.9% Avg. Engagement Rate</span>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
