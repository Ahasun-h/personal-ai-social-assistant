<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Calendar as CalendarIcon, Clock, Send, Plus, ArrowRight } from 'lucide-vue-next';

interface ScheduleItem {
  id: string;
  scheduled_for: string;
  status: string;
  social_account?: {
    id: string;
    provider: string;
    name: string;
    username: string;
  };
  draft?: {
    id: string;
    title: string;
    status: string;
    versions?: Array<{
      platform: string;
      content: string;
    }>;
  };
}

interface Props {
  schedules?: ScheduleItem[];
  accounts?: any[];
}

const props = withDefaults(defineProps<Props>(), {
  schedules: () => [],
  accounts: () => [],
});

const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

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
  <AppLayout title="Content Calendar">
    <Head title="Content Calendar & Queue" />

    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between pb-4 border-b border-white/10">
        <div>
          <h1 class="text-xl font-bold text-white flex items-center gap-2">
            <CalendarIcon class="w-5 h-5 text-indigo-400" />
            Interactive Content Calendar & Queue
          </h1>
          <p class="text-xs text-gray-400 mt-0.5">
            Scheduled dispatches across LinkedIn and X with automated rate-limited execution.
          </p>
        </div>

        <Link
          href="/inbox"
          class="flex items-center gap-1.5 text-xs font-semibold px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/30 transition"
        >
          <Plus class="w-4 h-4" />
          <span>Schedule from Review Inbox</span>
        </Link>
      </div>

      <!-- Schedule Table / Grid -->
      <div class="bg-surface-elevated/70 border border-white/10 rounded-2xl overflow-hidden backdrop-blur-sm">
        <div class="grid grid-cols-7 border-b border-white/10 text-xs font-semibold text-gray-400 text-center py-3 bg-surface-elevated font-mono uppercase text-[11px]">
          <div v-for="d in days" :key="d">{{ d }}</div>
        </div>

        <div class="p-6 space-y-4">
          <h3 class="text-xs font-bold text-gray-300 uppercase tracking-wider flex items-center gap-2 font-mono">
            <Clock class="w-4 h-4 text-cyan-400" />
            Current Scheduled Queue ({{ schedules.length }})
          </h3>

          <div v-if="schedules.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div
              v-for="s in schedules"
              :key="s.id"
              class="p-4 rounded-xl bg-surface-card border border-white/10 space-y-3 hover:border-indigo-500/50 transition relative overflow-hidden"
            >
              <div class="flex items-center justify-between text-xs">
                <span class="font-mono text-cyan-400 font-semibold">{{ formatDate(s.scheduled_for) }}</span>
                <span
                  class="px-2 py-0.5 rounded text-[10px] uppercase font-bold font-mono"
                  :class="[
                    s.status === 'completed' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' :
                    s.status === 'failed' ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30' :
                    'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30'
                  ]"
                >
                  {{ s.status }}
                </span>
              </div>

              <div>
                <p class="text-xs text-white font-medium line-clamp-2">
                  {{ s.draft?.title || 'Scheduled Post' }}
                </p>
                <p class="text-[11px] text-gray-400 font-mono mt-1">
                  Target: {{ s.social_account?.name || 'Connected Channel' }}
                </p>
              </div>

              <div class="pt-2 border-t border-white/5 flex items-center justify-between text-[11px]">
                <span
                  :class="[
                    'font-mono text-[10px] uppercase px-1.5 py-0.5 rounded',
                    s.social_account?.provider === 'linkedin' ? 'bg-blue-500/20 text-blue-300' : 'bg-slate-700 text-slate-300'
                  ]"
                >
                  {{ s.social_account?.provider === 'linkedin' ? 'LinkedIn' : 'X (Twitter)' }}
                </span>

                <Link href="/inbox" class="text-indigo-400 hover:text-indigo-300 font-mono">
                  Inspect Draft →
                </Link>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-12 text-gray-500 text-xs font-mono">
            No active schedules in the queue. Approve posts in the Review Inbox to schedule future dispatches.
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
