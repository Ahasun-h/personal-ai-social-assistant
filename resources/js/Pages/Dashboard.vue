<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  TrendingUp,
  Eye,
  Heart,
  Calendar,
  Send,
  Zap,
} from 'lucide-vue-next';

const kpis = [
  { label: '30-Day Impressions', value: '428,950', change: '+24.8%', icon: Eye, color: 'text-indigo-400' },
  { label: 'Engagement Rate', value: '4.82%', change: '+0.8%', icon: TrendingUp, color: 'text-cyan-400' },
  { label: 'Total Engagements', value: '20,675', change: '+18.4%', icon: Heart, color: 'text-rose-400' },
  { label: 'Published Posts', value: '48', change: '100% Approved', icon: Send, color: 'text-emerald-400' },
];
</script>

<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto space-y-6">
      <div>
        <h1 class="text-xl font-bold text-white">Executive Command Center</h1>
        <p class="text-xs text-gray-400 mt-0.5">Performance overview, API budget metrics, and upcoming queue schedule.</p>
      </div>

      <!-- KPI Grid -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div
          v-for="kpi in kpis"
          :key="kpi.label"
          class="bg-surface-card border border-white/10 rounded-xl p-4 space-y-2"
        >
          <div class="flex items-center justify-between text-gray-400">
            <span class="text-xs font-medium">{{ kpi.label }}</span>
            <component :is="kpi.icon" class="w-4 h-4" :class="kpi.color" />
          </div>
          <div class="text-2xl font-bold text-white font-display">{{ kpi.value }}</div>
          <div class="text-[11px] text-emerald-400 font-mono">{{ kpi.change }} vs last month</div>
        </div>
      </div>

      <!-- Budget & Quick Schedule Row -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Budget Card -->
        <div class="bg-surface-card border border-white/10 rounded-xl p-4 space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-gray-200">X & AI Monthly Budget</span>
            <Zap class="w-4 h-4 text-amber-400" />
          </div>
          <div class="space-y-1">
            <div class="flex justify-between text-xs font-mono">
              <span class="text-gray-400">Spent: $24.50</span>
              <span class="text-gray-200 font-bold">Cap: $50.00</span>
            </div>
            <div class="h-2 w-full bg-gray-800 rounded-full overflow-hidden">
              <div class="h-full bg-indigo-500 rounded-full" style="width: 49%"></div>
            </div>
          </div>
          <p class="text-[11px] text-gray-400 leading-normal">
            Hard stop limit active. You have 51% remaining budget before automatic generation pauses.
          </p>
        </div>

        <!-- Upcoming Queue -->
        <div class="md:col-span-2 bg-surface-card border border-white/10 rounded-xl p-4 space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-gray-200">Upcoming Scheduled Dispatches</span>
            <Calendar class="w-4 h-4 text-indigo-400" />
          </div>
          <div class="space-y-2">
            <div class="p-2.5 rounded-lg bg-surface-elevated border border-white/5 flex items-center justify-between text-xs">
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                <span class="text-gray-200 font-medium">Autonomous Multi-Agent SDLC in 2026</span>
              </div>
              <span class="text-gray-400 font-mono text-[11px]">Today at 14:00 (Ready)</span>
            </div>
            <div class="p-2.5 rounded-lg bg-surface-elevated border border-white/5 flex items-center justify-between text-xs">
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-white"></span>
                <span class="text-gray-200 font-medium">Why Deterministic State Machines Beat Raw Prompting</span>
              </div>
              <span class="text-gray-400 font-mono text-[11px]">Tomorrow at 09:30</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
