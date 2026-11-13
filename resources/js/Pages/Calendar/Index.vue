<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
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
  Lock,
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

const page = usePage();
const hasAiKey = computed(() => {
  return !!(page.props as any).hasAiKey;
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
    } catch {
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

  // 3. Next Month Spillover Days
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
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  });
});

// Scheduling Modal State
const showScheduleModal = ref(false);
const scheduleForm = ref({
  draft_id: '',
  social_account_id: '',
  date: '',
  time: '10:00',
});
const isSubmitting = ref(false);

const openScheduleModal = (prefillDate?: string) => {
  scheduleForm.value.date = prefillDate || formatDateKey(selectedDate.value);
  scheduleForm.value.social_account_id = props.accounts[0]?.id || '';
  showScheduleModal.value = true;
};

const submitSchedule = async () => {
  if (!scheduleForm.value.date || !scheduleForm.value.time) return;
  isSubmitting.value = true;

  try {
    const scheduledFor = `${scheduleForm.value.date} ${scheduleForm.value.time}:00`;
    const res = await fetch('/api/v1/schedules', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        draft_id: scheduleForm.value.draft_id || 'manual-slot',
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

    <div class="space-y-6">
      <!-- Calendar Controls & Navigation Bar -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-2">
        <!-- Month Navigation -->
        <div class="flex items-center gap-3">
          <div class="flex items-center bg-white border border-slate-200/80 rounded-full p-1 shadow-sm">
            <button
              @click="prevMonth"
              class="p-2 rounded-full hover:bg-slate-100 text-slate-500 hover:text-slate-900 transition"
              title="Previous Month"
            >
              <ChevronLeft class="w-4 h-4" />
            </button>
            <button
              @click="nextMonth"
              class="p-2 rounded-full hover:bg-slate-100 text-slate-500 hover:text-slate-900 transition"
              title="Next Month"
            >
              <ChevronRight class="w-4 h-4" />
            </button>
          </div>

          <button
            @click="goToToday"
            class="px-4 py-2 rounded-full bg-white hover:bg-slate-50 border border-slate-200/80 text-xs font-semibold text-slate-700 transition shadow-sm"
          >
            Today
          </button>

          <h1 class="text-xl sm:text-2xl font-display font-bold text-slate-900 tracking-tight flex items-center gap-2">
            <span>{{ currentMonthName }}</span>
            <span class="text-slate-400 font-mono text-lg">{{ currentYear }}</span>
          </h1>
        </div>

        <!-- Filter & Quick Action -->
        <div class="flex items-center gap-3 flex-wrap">
          <!-- Platform Filter Pills -->
          <div class="bg-white border border-slate-200/80 rounded-full p-1 flex items-center gap-1 text-xs shadow-sm">
            <button
              @click="platformFilter = 'all'"
              :class="[
                'px-3.5 py-1.5 rounded-full font-medium transition',
                platformFilter === 'all'
                  ? 'bg-slate-900 text-white shadow-sm'
                  : 'text-slate-600 hover:text-slate-900'
              ]"
            >
              All Channels
            </button>
            <button
              @click="platformFilter = 'linkedin'"
              :class="[
                'px-3.5 py-1.5 rounded-full font-medium transition flex items-center gap-1.5',
                platformFilter === 'linkedin'
                  ? 'bg-[#0A66C2] text-white shadow-sm'
                  : 'text-slate-600 hover:text-slate-900'
              ]"
            >
              <span class="w-1.5 h-1.5 rounded-full" :class="platformFilter === 'linkedin' ? 'bg-white' : 'bg-[#0A66C2]'"></span>
              LinkedIn
            </button>
            <button
              @click="platformFilter = 'x_twitter'"
              :class="[
                'px-3.5 py-1.5 rounded-full font-medium transition flex items-center gap-1.5',
                platformFilter === 'x_twitter'
                  ? 'bg-slate-900 text-white shadow-sm'
                  : 'text-slate-600 hover:text-slate-900'
              ]"
            >
              <span class="w-1.5 h-1.5 rounded-full" :class="platformFilter === 'x_twitter' ? 'bg-white' : 'bg-slate-900'"></span>
              X (Twitter)
            </button>
          </div>

          <button
            @click="openScheduleModal()"
            class="flex items-center gap-2 px-5 py-2 rounded-full bg-[#9EE493] hover:bg-[#8ee082] text-slate-900 font-semibold text-xs shadow-sm transition"
          >
            <Plus class="w-4 h-4 text-slate-900" />
            <span>Schedule Post</span>
          </button>
        </div>
      </div>

      <!-- Missing AI API Key Warning Banner -->
      <div
        v-if="!hasAiKey"
        class="bg-amber-50 border border-amber-300 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-amber-900 shadow-sm"
      >
        <div class="flex items-center gap-2.5">
          <AlertCircle class="w-4 h-4 text-amber-600 shrink-0" />
          <span>No AI API Key configured in Settings. Smart AI slot optimization and AI reach telemetry are offline.</span>
        </div>
        <Link
          href="/settings"
          class="shrink-0 px-3.5 py-1.5 rounded-full bg-amber-200 hover:bg-amber-300 text-amber-950 font-semibold text-xs transition inline-flex items-center gap-1.5 self-start sm:self-auto shadow-xs"
        >
          <span>Configure API Key in Settings</span>
          <span>→</span>
        </Link>
      </div>

      <!-- Main Layout: Calendar Grid + Day Details Drawer -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Calendar Grid (8 Cols on Desktop) -->
        <div class="lg:col-span-8 bg-white border border-slate-200/70 rounded-3xl overflow-hidden shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
          <!-- 7-Day Header -->
          <div class="grid grid-cols-7 border-b border-slate-100 bg-slate-50/70 text-center py-3 text-xs font-semibold text-slate-500">
            <div v-for="d in dayNames" :key="d" :class="{ 'text-slate-900 font-bold': d === 'Sun' || d === 'Sat' }">
              {{ d }}
            </div>
          </div>

          <!-- Days Grid (Cells) -->
          <div class="grid grid-cols-7 divide-x divide-y divide-slate-100 bg-white">
            <div
              v-for="(day, idx) in calendarGrid"
              :key="idx"
              @click="onSelectDay(day)"
              :class="[
                'min-h-[108px] p-2 flex flex-col justify-between transition relative cursor-pointer group',
                day.isCurrentMonth ? 'text-slate-800' : 'text-slate-300 bg-slate-50/30',
                day.isSelected ? 'bg-emerald-50/40 ring-2 ring-inset ring-[#9EE493]' : 'hover:bg-slate-50/60',
              ]"
            >
              <!-- Cell Top: Day Number & Indicators -->
              <div class="flex items-center justify-between">
                <span
                  :class="[
                    'text-xs font-mono font-bold w-6 h-6 flex items-center justify-center rounded-lg transition',
                    day.isToday
                      ? 'bg-[#9EE493] text-slate-900 shadow-sm ring-2 ring-emerald-100'
                      : day.isSelected
                      ? 'text-emerald-800 font-bold'
                      : 'text-slate-600 group-hover:text-slate-900',
                  ]"
                >
                  {{ day.dayNumber }}
                </span>

                <!-- Count badge if has events -->
                <span
                  v-if="day.schedules.length > 0"
                  class="text-[10px] font-mono font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200"
                >
                  {{ day.schedules.length }}
                </span>
              </div>

              <!-- Cell Content: Scheduled Event Chips -->
              <div class="space-y-1 my-1">
                <div
                  v-for="s in day.schedules.slice(0, 2)"
                  :key="s.id"
                  class="text-[10px] p-1.5 rounded-lg border flex items-center gap-1.5 transition truncate shadow-xs"
                  :class="[
                    s.social_account?.provider === 'linkedin'
                      ? 'bg-blue-50 border-blue-200 text-blue-800 hover:bg-blue-100'
                      : 'bg-slate-100 border-slate-200 text-slate-800 hover:bg-slate-200'
                  ]"
                >
                  <span
                    :class="[
                      'w-1.5 h-1.5 rounded-full flex-shrink-0',
                      s.social_account?.provider === 'linkedin' ? 'bg-[#0A66C2]' : 'bg-slate-900'
                    ]"
                  ></span>
                  <span class="font-mono text-[9px] text-slate-400">{{ formatTime(s.scheduled_for || s.scheduled_at) }}</span>
                  <span class="truncate font-semibold">{{ s.draft?.title || 'Post' }}</span>
                </div>

                <div
                  v-if="day.schedules.length > 2"
                  class="text-[9px] font-mono text-slate-400 pl-1"
                >
                  +{{ day.schedules.length - 2 }} more
                </div>
              </div>

              <!-- Quick add hover trigger -->
              <button
                @click.stop="openScheduleModal(day.dateKey)"
                class="opacity-0 group-hover:opacity-100 transition text-[10px] text-emerald-700 hover:text-emerald-800 font-medium flex items-center gap-0.5 mt-auto"
              >
                <Plus class="w-3 h-3" />
                <span>Slot</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Selected Day Schedule Inspector (4 Cols on Desktop) -->
        <div class="lg:col-span-4 bg-white border border-slate-200/70 rounded-3xl p-6 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-5">
          <div class="flex items-start justify-between pb-4 border-b border-slate-100">
            <div>
              <span class="text-[11px] font-mono uppercase tracking-wider text-emerald-700 font-bold">Selected Day</span>
              <h2 class="text-base font-bold text-slate-900 mt-0.5">{{ selectedDateFormatted }}</h2>
            </div>

            <button
              @click="openScheduleModal(formatDateKey(selectedDate))"
              class="p-2 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 transition"
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
              class="p-4 rounded-2xl bg-slate-50 border border-slate-200/70 space-y-2 hover:border-emerald-300 transition relative group"
            >
              <div class="flex items-center justify-between">
                <span
                  :class="[
                    'text-[10px] font-mono uppercase px-2.5 py-0.5 rounded-full font-bold',
                    s.social_account?.provider === 'linkedin'
                      ? 'bg-blue-50 text-blue-700 border border-blue-200'
                      : 'bg-slate-200 text-slate-800 border border-slate-300'
                  ]"
                >
                  {{ s.social_account?.provider === 'linkedin' ? 'LinkedIn' : 'X (Twitter)' }}
                </span>

                <span class="flex items-center gap-1 font-mono text-xs text-slate-700 bg-white px-2 py-0.5 rounded-md border border-slate-200">
                  <Clock class="w-3 h-3 text-slate-400" />
                  {{ formatTime(s.scheduled_for || s.scheduled_at) }}
                </span>
              </div>

              <h4 class="font-bold text-slate-900 text-sm leading-snug">
                {{ s.draft?.title || 'Scheduled Post' }}
              </h4>

              <p
                v-if="s.draft?.versions?.[0]?.content"
                class="text-xs text-slate-600 line-clamp-3 leading-relaxed font-sans"
              >
                {{ s.draft.versions[0].content }}
              </p>

              <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between text-xs">
                <span class="text-slate-400 text-[11px]">
                  Channel: {{ s.social_account?.name || 'Personal Profile' }}
                </span>

                <Link
                  v-if="s.draft?.id"
                  :href="`/inbox?draft=${s.draft.id}`"
                  class="text-[11px] font-semibold text-emerald-700 hover:text-emerald-800 hover:underline flex items-center gap-1"
                >
                  <span>Open in Inbox</span>
                  <ChevronRight class="w-3 h-3" />
                </Link>
              </div>
            </div>
          </div>

          <!-- Empty state for day -->
          <div v-else class="text-center py-10 space-y-3">
            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto text-slate-400">
              <CalendarIcon class="w-6 h-6" />
            </div>
            <div>
              <p class="text-xs font-semibold text-slate-700">No Dispatches Scheduled</p>
              <p class="text-[11px] text-slate-400 mt-1 max-w-[200px] mx-auto">
                No posts queued for {{ selectedDateFormatted }}. Click below to schedule a draft.
              </p>
            </div>
            <button
              @click="openScheduleModal(formatDateKey(selectedDate))"
              class="px-4 py-2 rounded-full bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs shadow-sm transition"
            >
              + Queue Post for this Day
            </button>
          </div>

          <!-- Smart AI Slot Recommendation Box -->
          <div
            v-if="hasAiKey"
            class="border border-emerald-200/70 rounded-2xl p-4 bg-emerald-50/50 space-y-2"
          >
            <div class="flex items-center gap-2 text-xs font-bold text-emerald-800">
              <Sparkles class="w-4 h-4 text-emerald-600" />
              <span>Smart AI Slot Recommendation</span>
            </div>
            <p class="text-[11px] text-slate-600 leading-relaxed">
              Based on historical LinkedIn engagement, optimal post reach for tech audiences is between <strong class="text-slate-900">08:30 AM – 10:15 AM EST</strong>.
            </p>
          </div>
          <div
            v-else
            class="border border-amber-200/80 rounded-2xl p-4 bg-amber-50/50 space-y-2 text-xs text-amber-900"
          >
            <div class="flex items-center gap-2 font-bold text-amber-800">
              <Lock class="w-4 h-4 text-amber-600" />
              <span>Smart AI Slot Recommendation Offline</span>
            </div>
            <p class="text-[11px] text-slate-600 leading-relaxed">
              No AI API Key configured in Settings. Dynamic audience reach calculation and schedule recommendations are disabled.
            </p>
            <Link href="/settings" class="text-[11px] font-semibold text-amber-900 underline block pt-0.5 hover:text-amber-950">
              Configure AI Key in Settings →
            </Link>
          </div>
        </div>
      </div>

      <!-- Quick Schedule Modal -->
      <div
        v-if="showScheduleModal"
        class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4"
      >
        <div class="bg-white border border-slate-200 rounded-3xl p-6 max-w-md w-full space-y-4 shadow-2xl">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="font-display font-bold text-slate-900 text-base flex items-center gap-2">
              <Clock class="w-5 h-5 text-emerald-600" />
              Schedule Post Dispatch
            </h3>
            <button @click="showScheduleModal = false" class="text-slate-400 hover:text-slate-600">
              <CloseIcon class="w-5 h-5" />
            </button>
          </div>

          <div class="space-y-3 text-xs">
            <div>
              <label class="block text-slate-600 mb-1 font-semibold">Target Channel</label>
              <select
                v-model="scheduleForm.social_account_id"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-slate-400"
              >
                <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                  {{ acc.name }} ({{ acc.provider === 'linkedin' ? 'LinkedIn' : 'X' }})
                </option>
              </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-slate-600 mb-1 font-semibold">Dispatch Date</label>
                <input
                  v-model="scheduleForm.date"
                  type="date"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 font-mono focus:outline-none focus:border-slate-400"
                />
              </div>

              <div>
                <label class="block text-slate-600 mb-1 font-semibold">Dispatch Time</label>
                <input
                  v-model="scheduleForm.time"
                  type="time"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 font-mono focus:outline-none focus:border-slate-400"
                />
              </div>
            </div>

            <p class="text-[11px] text-slate-500 pt-1">
              Select or create drafts in the <Link href="/inbox" class="text-emerald-700 underline font-medium">Review Inbox</Link> to associate full copy and media assets.
            </p>
          </div>

          <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
            <button
              @click="showScheduleModal = false"
              class="px-4 py-2 rounded-full text-xs font-semibold text-slate-600 hover:bg-slate-100 transition"
            >
              Cancel
            </button>
            <button
              @click="submitSchedule"
              :disabled="isSubmitting"
              class="px-5 py-2 rounded-full bg-[#9EE493] hover:bg-[#8ee082] font-semibold text-xs text-slate-900 shadow-sm transition disabled:opacity-50"
            >
              {{ isSubmitting ? 'Scheduling...' : 'Confirm Slot' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
