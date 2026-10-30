<script setup lang="ts">
import { computed } from 'vue';
import { ShieldCheck, AlertTriangle, AlertOctagon } from 'lucide-vue-next';

interface Props {
  daysRemaining: number;
  platform: 'linkedin' | 'x_twitter';
}

const props = defineProps<Props>();

const status = computed(() => {
  if (props.daysRemaining <= 0) return 'expired';
  if (props.daysRemaining <= 7) return 'critical';
  if (props.daysRemaining <= 15) return 'warning';
  return 'healthy';
});

const badgeStyles = computed(() => {
  switch (status.value) {
    case 'healthy':
      return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
    case 'warning':
      return 'bg-amber-500/10 text-amber-400 border-amber-500/20';
    case 'critical':
    case 'expired':
      return 'bg-rose-500/10 text-rose-400 border-rose-500/20 animate-pulse';
  }
});
</script>

<template>
  <div
    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-mono font-medium border"
    :class="badgeStyles"
  >
    <ShieldCheck v-if="status === 'healthy'" class="w-3.5 h-3.5" />
    <AlertTriangle v-else-if="status === 'warning'" class="w-3.5 h-3.5" />
    <AlertOctagon v-else class="w-3.5 h-3.5" />

    <span v-if="status === 'expired'">Token Expired</span>
    <span v-else>{{ daysRemaining }}d left</span>
  </div>
</template>
