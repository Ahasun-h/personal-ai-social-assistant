<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  Calendar as CalendarIcon,
  ChevronLeft,
  ChevronRight,
  Clock,
  Plus,
  Send,
  Layers,
  CheckCircle2,
  AlertCircle,
  X as CloseIcon,
  Sparkles,
} from 'lucide-vue-next';

interface ScheduleItem {
  id: string;
  scheduled_for?: string;
  scheduled_at?: string;
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

// Calendar State
const today = new Date();
const currentMonth = ref(today.getMonth());
const currentYear = ref(today.getFullYear());
const selectedDate = ref<Date>(today);
const platformFilter = ref<'all' | 'linkedin' | 'x_twitter'>('all');

const monthNames = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December'
];

const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const currentMonthName = computed(() => monthNames[currentMonth.value]);

// Navigation
const prevMonth = () => {
  if (currentMonth.value === 0) {
    currentMonth.value = 11;
    currentYear.value--;
  } else {
    currentMonth.value--;
  }
};

const nextMonth = () => {
  if (currentMonth.value === 11) {
    currentMonth.value = 0;
    currentYear.value++;
  } else {
    currentMonth.value++;
  }
};

const goToToday = () => {
  currentMonth.value = today.getMonth();
  currentYear.value = today.getFullYear();
  selectedDate.value = today;
};

// Format YYYY-MM-DD
const formatDateKey = (d: Date): string => {
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

// Filtered schedules map: dateKey -> ScheduleItem[]
const schedulesByDate = computed(() => {
  const map: Record<string, ScheduleItem[]> = {};

  props.schedules.forEach((s) => {
    const rawDate = s.scheduled_for || s.scheduled_at;
    if (!rawDate) return;

    if (platformFilter.value !== 'all') {
      const provider = s.social_account?.provider;
      if (provider !== platformFilter.value) return;
    }

    try {
      const d = new Date(rawDate);
      const key = formatDateKey(d);
      if (!map[key]) {
        map[key] = [];
      }
      map[key].push(s);
    } catch (e) {
      // ignore invalid dates
    }
  });

  return map;
});

// Generate 35-42 Grid Days
interface CalendarDay {
  date: Date;
  dayNumber: number;
  isCurrentMonth: boolean;
  isToday: boolean;
  isSelected: boolean;
  dateKey: string;
  schedules: ScheduleItem[];
}

const calendarGrid = computed<CalendarDay[]>(() => {
  const year = currentYear.value;
  const month = currentMonth.value;

  const firstDayOfMonth = new Date(year, month, 1);
  const startingDayOfWeek = firstDayOfMonth.getDay(); // 0 for Sun

  const daysInCurrentMonth = new Date(year, month + 1, 0).getDate();
  const daysInPrevMonth = new Date(year, month, 0).getDate();

  const days: CalendarDay[] = [];
  const selectedKey = formatDateKey(selectedDate.value);
  const todayKey = formatDateKey(today);

  // 1. Previous Month Spillover Days
  for (let i = startingDayOfWeek - 1; i >= 0; i--) {
    const dayNum = daysInPrevMonth - i;
    const date = new Date(year, month - 1, dayNum);
    const dateKey = formatDateKey(date);
    days.push({
      date,
      dayNumber: dayNum,
      isCurrentMonth: false,
      isToday: dateKey === todayKey,
      isSelected: dateKey === selectedKey,
      dateKey,
      schedules: schedulesByDate.value[dateKey] || [],
    });
  }

  // 2. Current Month Days
  for (let d = 1; d <= daysInCurrentMonth; d++) {
    const date = new Date(year, month, d);
    const dateKey = formatDateKey(date);
    days.push({
      date,
      dayNumber: d,
      isCurrentMonth: true,
      isToday: dateKey === todayKey,
      isSelected: dateKey === selectedKey,
      dateKey,
      schedules: schedulesByDate.value[dateKey] || [],
    });
  }

  // 3. Next Month Spillover Days to fill 35 or 42 grid cells
  const remainingCells = 42 - days.length >= 7 ? 35 - days.length : 42 - days.length;
  const targetTotal = days.length <= 35 ? 35 : 42;
  const overflow = targetTotal - days.length;

  for (let d = 1; d <= overflow; d++) {
    const date = new Date(year, month + 1, d);
    const dateKey = formatDateKey(date);
    days.push({
      date,
      dayNumber: d,
      isCurrentMonth: false,
      isToday: dateKey === todayKey,
      isSelected: dateKey === selectedKey,
      dateKey,
      schedules: schedulesByDate.value[dateKey] || [],
    });
  }

  return days;
});

// Selected Day Schedule Items
const selectedDaySchedules = computed(() => {
  const key = formatDateKey(selectedDate.value);
  return schedulesByDate.value[key] || [];
});

const onSelectDay = (day: CalendarDay) => {
  selectedDate.value = day.date;
  if (!day.isCurrentMonth) {
    currentMonth.value = day.date.getMonth();
    currentYear.value = day.date.getFullYear();
  }
};

const formatTime = (isoString?: string): string => {
  if (!isoString) return '';
  try {
    const d = new Date(isoString);
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  } catch {
    return '';
  }
};

const selectedDateFormatted = computed(() => {
  return selectedDate.value.toLocaleDateString(undefined, {
    weekday: 'long',
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
});

// Quick Schedule Modal State
const showScheduleModal = ref(false);
const scheduleForm = ref({
  draft_id: '',
  social_account_id: props.accounts[0]?.id || '',
  date: formatDateKey(today),
  time: '09:00',
});
const isSubmitting = ref(false);

const openScheduleModal = (dateKey?: string) => {
  if (dateKey) {
    scheduleForm.value.date = dateKey;
  }
  showScheduleModal.value = true;
};

const submitSchedule = async () => {
  if (!scheduleForm.value.date || !scheduleForm.value.time) {
    alert('Please choose date and time.');
    return;
  }
  isSubmitting.value = true;
  const scheduledFor = `${scheduleForm.value.date}T${scheduleForm.value.time}:00`;

  try {
    const res = await fetch('/api/v1/calendar/schedules', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        draft_id: scheduleForm.value.draft_id || 'new_slot',
        social_account_id: scheduleForm.value.social_account_id || props.accounts[0]?.id,
        scheduled_for: scheduledFor,
      }),
    });
    if (res.ok) {
      showScheduleModal.value = false;
      router.reload();
    }
  } catch (err) {
    console.error(err);
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<template>
  <AppLayout title="Content Calendar">
    <Head title="Content Calendar & Schedule Grid" />

    <div class="max-w-7xl mx-auto space-y-6">
      <!-- Calendar Controls & Navigation Bar -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-white/10">
        <!-- Month Navigation -->
        <div class="flex items-center gap-3">
          <div class="flex items-center bg-surface-elevated/80 border border-white/10 rounded-2xl p-1 shadow-sm">
            <button
              @click="prevMonth"
              class="p-2 rounded-xl hover:bg-white/5 text-gray-400 hover:text-white transition"
              title="Previous Month"
            >
              <ChevronLeft class="w-4 h-4" />
            </button>
            <button
              @click="nextMonth"
              class="p-2 rounded-xl hover:bg-white/5 text-gray-400 hover:text-white transition"
              title="Next Month"
            >
              <ChevronRight class="w-4 h-4" />
            </button>
          </div>

          <button
            @click="goToToday"
            class="px-3.5 py-1.5 rounded-xl bg-surface-elevated/80 border border-white/10 text-xs font-semibold text-gray-300 hover:text-white hover:bg-white/5 transition"
          >
            Today
          </button>

          <h1 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
            <span>{{ currentMonthName }}</span>
            <span class="text-indigo-400 font-mono">{{ currentYear }}</span>
          </h1>
        </div>

        <!-- Filter & Quick Action -->
        <div class="flex items-center gap-3">
          <!-- Platform Filter Pills -->
          <div class="bg-surface-elevated/80 border border-white/10 rounded-2xl p-1 flex items-center gap-1 text-xs">
            <button
              @click="platformFilter = 'all'"
              :class="[
                'px-3 py-1 rounded-xl font-medium transition',
                platformFilter === 'all'
                  ? 'bg-indigo-600/30 text-indigo-300 border border-indigo-500/40 shadow-sm'
                  : 'text-gray-400 hover:text-white'
              ]"
            >
              All Channels
            </button>
            <button
              @click="platformFilter = 'linkedin'"
              :class="[
                'px-3 py-1 rounded-xl font-medium transition flex items-center gap-1.5',
                platformFilter === 'linkedin'
                  ? 'bg-blue-600/30 text-blue-300 border border-blue-500/40 shadow-sm'
                  : 'text-gray-400 hover:text-white'
              ]"
            >
              <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
              LinkedIn
            </button>
            <button
              @click="platformFilter = 'x_twitter'"
              :class="[
                'px-3 py-1 rounded-xl font-medium transition flex items-center gap-1.5',
                platformFilter === 'x_twitter'
                  ? 'bg-slate-700 text-slate-200 border border-slate-600 shadow-sm'
                  : 'text-gray-400 hover:text-white'
              ]"
            >
              <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
              X (Twitter)
            </button>
          </div>

          <button
            @click="openScheduleModal()"
            class="flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs shadow-lg shadow-indigo-600/30 transition"
          >
            <Plus class="w-4 h-4" />
            <span>Schedule Post</span>
          </button>
        </div>
      </div>

      <!-- Main Layout: Calendar Grid + Day Details Drawer -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Calendar Grid (8 Cols on Desktop) -->
        <div class="lg:col-span-8 bg-surface-elevated/70 border border-white/10 rounded-3xl overflow-hidden backdrop-blur-sm shadow-xl">
          <!-- 7-Day Header -->
          <div class="grid grid-cols-7 border-b border-white/10 bg-surface-elevated/90 text-center py-3 text-[11px] font-mono uppercase font-semibold text-gray-400">
            <div v-for="d in dayNames" :key="d" :class="{ 'text-indigo-400': d === 'Sun' || d === 'Sat' }">
              {{ d }}
            </div>
          </div>

          <!-- Days Grid (Cells) -->
          <div class="grid grid-cols-7 divide-x divide-y divide-white/5 bg-[#0B0F19]/60">
            <div
              v-for="(day, idx) in calendarGrid"
              :key="idx"
              @click="onSelectDay(day)"
              :class="[
                'min-h-[105px] p-2 flex flex-col justify-between transition relative cursor-pointer group',
                day.isCurrentMonth ? 'text-gray-200' : 'text-gray-600 bg-white/[0.01]',
                day.isSelected ? 'bg-indigo-600/10 ring-1 ring-inset ring-indigo-500/50' : 'hover:bg-white/[0.03]',
              ]"
            >
              <!-- Cell Top: Day Number & Indicators -->
              <div class="flex items-center justify-between">
                <span
                  :class="[
                    'text-xs font-mono font-bold w-6 h-6 flex items-center justify-center rounded-lg transition',
                    day.isToday
                      ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/50'
                      : day.isSelected
                      ? 'text-indigo-300'
                      : 'text-gray-400 group-hover:text-white',
                  ]"
                >
                  {{ day.dayNumber }}
                </span>

                <!-- Count badge if has events -->
                <span
                  v-if="day.schedules.length > 0"
                  class="text-[10px] font-mono font-semibold px-1.5 py-0.2 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30"
                >
                  {{ day.schedules.length }}
                </span>
              </div>

              <!-- Cell Content: Scheduled Event Chips -->
              <div class="space-y-1 my-1">
                <div
                  v-for="s in day.schedules.slice(0, 2)"
                  :key="s.id"
                  class="text-[10px] p-1.5 rounded-lg border flex items-center gap-1.5 transition truncate shadow-sm"
                  :class="[
                    s.social_account?.provider === 'linkedin'
                      ? 'bg-blue-950/40 border-blue-500/30 text-blue-200 hover:bg-blue-900/50'
                      : 'bg-slate-900/60 border-white/10 text-slate-200 hover:bg-slate-800'
                  ]"
                >
                  <span
                    :class="[
                      'w-1.5 h-1.5 rounded-full flex-shrink-0',
                      s.social_account?.provider === 'linkedin' ? 'bg-blue-400' : 'bg-slate-300'
                    ]"
                  ></span>
                  <span class="font-mono text-[9px] text-gray-400">{{ formatTime(s.scheduled_for || s.scheduled_at) }}</span>
                  <span class="truncate font-medium">{{ s.draft?.title || 'Post' }}</span>
                </div>

                <div
                  v-if="day.schedules.length > 2"
                  class="text-[9px] font-mono text-gray-400 pl-1"
                >
                  +{{ day.schedules.length - 2 }} more
                </div>
              </div>

              <!-- Quick add hover trigger -->
              <button
                @click.stop="openScheduleModal(day.dateKey)"
                class="opacity-0 group-hover:opacity-100 transition text-[10px] text-indigo-400 hover:text-indigo-300 font-mono flex items-center gap-0.5 mt-auto"
              >
                <Plus class="w-3 h-3" />
                <span>Slot</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Selected Day Schedule Inspector (4 Cols on Desktop) -->
        <div class="lg:col-span-4 bg-surface-elevated/70 border border-white/10 rounded-3xl p-6 backdrop-blur-sm space-y-5 shadow-xl">
          <div class="flex items-start justify-between pb-4 border-b border-white/10">
            <div>
              <span class="text-[11px] font-mono uppercase tracking-wider text-indigo-400 font-bold">Selected Day</span>
              <h2 class="text-base font-bold text-white mt-0.5">{{ selectedDateFormatted }}</h2>
            </div>

            <button
              @click="openScheduleModal(formatDateKey(selectedDate))"
              class="p-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-indigo-300 transition"
              title="Add Dispatch for this Day"
            >
              <Plus class="w-4 h-4" />
            </button>
          </div>

          <!-- List of events on this day -->
          <div v-if="selectedDaySchedules.length > 0" class="space-y-3">
            <div
              v-for="s in selectedDaySchedules"
              :key="s.id"
              class="p-4 rounded-2xl bg-surface-card/70 border border-white/10 space-y-2 hover:border-indigo-500/40 transition relative group"
            >
              <div class="flex items-center justify-between">
                <span
                  :class="[
                    'text-[10px] font-mono uppercase px-2 py-0.5 rounded-full font-bold',
                    s.social_account?.provider === 'linkedin'
                      ? 'bg-blue-500/20 text-blue-400 border border-blue-500/30'
                      : 'bg-slate-700 text-slate-300 border border-slate-600'
                  ]"
                >
                  {{ s.social_account?.provider === 'linkedin' ? 'LinkedIn' : 'X (Twitter)' }}
                </span>

                <span class="flex items-center gap-1 font-mono text-xs text-cyan-300 bg-cyan-500/10 px-2 py-0.5 rounded-md border border-cyan-500/20">
                  <Clock class="w-3 h-3" />
                  {{ formatTime(s.scheduled_for || s.scheduled_at) }}
                </span>
              </div>

              <h4 class="font-bold text-white text-sm leading-snug">
                {{ s.draft?.title || 'Scheduled Post' }}
              </h4>

              <p
                v-if="s.draft?.versions?.[0]?.content"
                class="text-xs text-gray-300 line-clamp-3 leading-relaxed font-sans"
              >
                {{ s.draft.versions[0].content }}
              </p>

              <div class="pt-2 border-t border-white/5 flex items-center justify-between text-xs">
                <span class="text-gray-400 text-[11px] font-mono">
                  Channel: {{ s.social_account?.name || 'Personal Profile' }}
                </span>

                <Link
                  href="/inbox"
                  class="text-indigo-400 hover:text-indigo-300 font-mono text-[11px] font-semibold transition"
                >
                  Edit in Inbox →
                </Link>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else class="text-center py-10 space-y-3 border border-dashed border-white/10 rounded-2xl p-6">
            <div class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center mx-auto text-gray-400">
              <CalendarIcon class="w-5 h-5 text-gray-500" />
            </div>
            <p class="text-xs text-gray-400 leading-relaxed">
              No dispatches scheduled for <br />
              <strong class="text-gray-200">{{ selectedDateFormatted }}</strong>
            </p>
            <button
              @click="openScheduleModal(formatDateKey(selectedDate))"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 text-xs font-semibold font-mono transition"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>Schedule a Slot</span>
            </button>
          </div>

          <!-- Optimal Time Slot Suggestions Heatmap Box -->
          <div class="border border-white/5 rounded-2xl p-4 bg-gradient-to-tr from-indigo-950/20 to-purple-950/10 space-y-2">
            <div class="flex items-center gap-2 text-xs font-bold text-indigo-300">
              <Sparkles class="w-4 h-4 text-indigo-400" />
              <span>Smart AI Slot Recommendation</span>
            </div>
            <p class="text-[11px] text-gray-400 leading-relaxed">
              Based on historical LinkedIn engagement, optimal post reach for engineers is between <strong class="text-gray-200">08:30 AM – 10:15 AM EST</strong>.
            </p>
          </div>
        </div>
      </div>

      <!-- Quick Schedule Modal -->
      <div
        v-if="showScheduleModal"
        class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4"
      >
        <div class="bg-surface-elevated border border-white/10 rounded-2xl p-6 max-w-md w-full space-y-4 shadow-2xl">
          <div class="flex items-center justify-between pb-3 border-b border-white/10">
            <h3 class="font-bold text-white text-base flex items-center gap-2">
              <Clock class="w-5 h-5 text-indigo-400" />
              Schedule Post Dispatch
            </h3>
            <button @click="showScheduleModal = false" class="text-gray-400 hover:text-white">
              <CloseIcon class="w-5 h-5" />
            </button>
          </div>

          <div class="space-y-3 text-xs">
            <div>
              <label class="block text-gray-400 mb-1 font-mono uppercase">Target Channel</label>
              <select
                v-model="scheduleForm.social_account_id"
                class="w-full bg-[#0B0F19] border border-white/10 rounded-xl px-3 py-2 text-sm text-white"
              >
                <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                  {{ acc.name }} ({{ acc.provider === 'linkedin' ? 'LinkedIn' : 'X' }})
                </option>
              </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-gray-400 mb-1 font-mono uppercase">Dispatch Date</label>
                <input
                  v-model="scheduleForm.date"
                  type="date"
                  class="w-full bg-[#0B0F19] border border-white/10 rounded-xl px-3 py-2 text-sm text-white font-mono"
                />
              </div>

              <div>
                <label class="block text-gray-400 mb-1 font-mono uppercase">Dispatch Time</label>
                <input
                  v-model="scheduleForm.time"
                  type="time"
                  class="w-full bg-[#0B0F19] border border-white/10 rounded-xl px-3 py-2 text-sm text-white font-mono"
                />
              </div>
            </div>

            <p class="text-[11px] text-gray-400 pt-1">
              Select or create drafts in the <Link href="/inbox" class="text-indigo-400 underline">Review Inbox</Link> to associate full copy and media assets.
            </p>
          </div>

          <div class="flex justify-end gap-3 pt-3 border-t border-white/10">
            <button
              @click="showScheduleModal = false"
              class="px-4 py-2 rounded-xl text-sm text-gray-400 hover:text-white"
            >
              Cancel
            </button>
            <button
              @click="submitSchedule"
              :disabled="isSubmitting"
              class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-semibold text-sm text-white shadow-lg shadow-indigo-600/30 transition disabled:opacity-50"
            >
              {{ isSubmitting ? 'Scheduling...' : 'Confirm Slot' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
