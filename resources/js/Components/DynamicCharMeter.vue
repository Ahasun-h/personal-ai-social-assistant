<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  platform: 'linkedin' | 'x_twitter';
  currentLength: number;
}

const props = defineProps<Props>();

const MAX_CHARS = computed(() => (props.platform === 'linkedin' ? 3000 : 280));
const remaining = computed(() => MAX_CHARS.value - props.currentLength);
const percentage = computed(() => Math.min(100, Math.round((props.currentLength / MAX_CHARS.value) * 100)));

const strokeColor = computed(() => {
  if (remaining.value < 0) return '#F43F5E'; // Danger Rose
  if (remaining.value <= 20) return '#F59E0B'; // Warning Amber
  return props.platform === 'linkedin' ? '#0A66C2' : '#6366F1';
});

const radius = 13;
const circumference = 2 * Math.PI * radius;
const strokeDashoffset = computed(() => {
  return circumference - (percentage.value / 100) * circumference;
});
</script>

<template>
  <div class="flex items-center gap-2 font-mono">
    <!-- X Circular Progress Gauge -->
    <div v-if="platform === 'x_twitter'" class="relative inline-flex items-center justify-center">
      <svg class="w-8 h-8 -rotate-90 transform" viewBox="0 0 32 32">
        <circle
          cx="16"
          cy="16"
          :r="radius"
          class="text-slate-200"
          stroke="currentColor"
          stroke-width="2.5"
          fill="none"
        />
        <circle
          cx="16"
          cy="16"
          :r="radius"
          :stroke="strokeColor"
          stroke-width="2.5"
          stroke-linecap="round"
          fill="none"
          :stroke-dasharray="circumference"
          :stroke-dashoffset="strokeDashoffset"
          class="transition-all duration-300 ease-out"
        />
      </svg>
      <span
        v-if="remaining <= 20"
        class="absolute text-[10px] font-bold"
        :class="remaining < 0 ? 'text-rose-500' : 'text-amber-400'"
      >
        {{ remaining }}
      </span>
    </div>

    <!-- LinkedIn Linear Progress -->
    <div v-else class="flex flex-col gap-1 w-32">
      <div class="flex justify-between text-[11px] text-gray-400">
        <span>{{ currentLength }}</span>
        <span>/ {{ MAX_CHARS }}</span>
      </div>
      <div class="h-1.5 w-full bg-gray-800 rounded-full overflow-hidden">
        <div
          class="h-full rounded-full transition-all duration-300 ease-out"
          :style="{ width: `${percentage}%`, backgroundColor: strokeColor }"
        ></div>
      </div>
    </div>
  </div>
</template>
