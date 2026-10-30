<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Calendar as CalendarIcon, Clock } from 'lucide-vue-next';

const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const slots = [
  { time: '08:30 AM', title: 'Why Multi-Agent SDLCs Scale', platform: 'linkedin', status: 'Published' },
  { time: '11:00 AM', title: 'Deterministic State Machines in AI', platform: 'x_twitter', status: 'Scheduled' },
  { time: '02:30 PM', title: 'Prompting vs Architecture Breakdown', platform: 'both', status: 'In Review' },
];
</script>

<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between pb-4 border-b border-white/10">
        <div>
          <h1 class="text-xl font-bold text-white flex items-center gap-2">
            <CalendarIcon class="w-5 h-5 text-indigo-400" />
            Interactive Content Calendar
          </h1>
          <p class="text-xs text-gray-400 mt-0.5">Drag-and-drop rescheduling across LinkedIn and X with optimal AI time slot heatmaps.</p>
        </div>
      </div>

      <!-- Schedule Table / Grid -->
      <div class="bg-surface-card border border-white/10 rounded-xl overflow-hidden">
        <div class="grid grid-cols-7 border-b border-white/10 text-xs font-semibold text-gray-400 text-center py-2.5 bg-surface-elevated">
          <div v-for="d in days" :key="d">{{ d }}</div>
        </div>

        <div class="p-6 space-y-4">
          <h3 class="text-xs font-bold text-gray-300 uppercase tracking-wider flex items-center gap-2">
            <Clock class="w-4 h-4 text-cyan-400" />
            Today's Time Slots & Scheduled Queue
          </h3>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div
              v-for="s in slots"
              :key="s.time"
              class="p-4 rounded-xl bg-surface-elevated border border-white/10 space-y-2 hover:border-indigo-500/50 transition cursor-grab"
            >
              <div class="flex items-center justify-between text-xs">
                <span class="font-mono text-cyan-400 font-semibold">{{ s.time }}</span>
                <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold"
                  :class="s.status === 'Published' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-indigo-500/20 text-indigo-300'"
                >
                  {{ s.status }}
                </span>
              </div>
              <p class="text-xs text-gray-200 font-medium">{{ s.title }}</p>
              <div class="text-[11px] text-gray-400 capitalize">Platform: {{ s.platform }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
