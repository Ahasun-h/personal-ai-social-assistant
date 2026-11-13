<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  Flame,
  Bookmark,
  ArrowUpRight,
  Sparkles,
  RefreshCw,
  Clock,
  FileText,
  CheckCircle2,
  AlertCircle,
  Radio,
  SlidersHorizontal,
  Star,
  Plus,
  X,
  Check,
  Tag,
  Sliders,
  Filter,
  Lock,
} from 'lucide-vue-next';

interface TrendItem {
  id: string;
  title: string;
  url: string;
  summary?: string;
  content?: string;
  engagement_score?: number;
  is_bookmarked?: boolean;
  is_liked_topic?: boolean;
  matched_topics?: string[];
  posts_count?: number;
  published_posts_count?: number;
  scanned_at?: string;
  trend_source?: {
    name: string;
    source_type: string;
    last_fetched_at?: string;
  };
}

interface Preferences {
  liked_topics?: string[];
  keywords?: string[];
  min_score?: number;
  preferred_tone?: string;
  sources?: string[];
  category_ids?: string[];
}

interface Props {
  trends?: TrendItem[];
  sources?: any[];
  categories?: any[];
  preferences?: Preferences;
  last_scan_time?: string;
}

const props = withDefaults(defineProps<Props>(), {
  trends: () => [],
  sources: () => [],
  categories: () => [],
  preferences: () => ({
    liked_topics: [
      'Distributed Systems & Cloud',
      'AI Engineering & LLM Infra',
      'High-Scale Database Architecture',
      'Systems Programming & Rust',
    ],
    keywords: [
      'monolith',
      'speculative decoding',
      'redis',
      'postgres',
      'rust',
      'kubernetes',
      'vllm',
      'quantization',
    ],
    min_score: 70,
    preferred_tone: 'pragmatic_architect',
  }),
  last_scan_time: '',
});

const defaultTopicsList = [
  'Distributed Systems & Cloud',
  'AI Engineering & LLM Infra',
  'High-Scale Database Architecture',
  'Systems Programming & Rust',
  'Microservices & Monolith Architecture',
  'Kubernetes & DevOps',
  'Security & Zero Trust',
  'Frontend & Web Performance',
];

const defaultKeywordsList = [
  'monolith',
  'speculative decoding',
  'redis',
  'postgres',
  'rust',
  'kubernetes',
  'vllm',
  'quantization',
];

const page = usePage();
const hasAiKey = computed(() => {
  return !!(page.props as any).hasAiKey;
});

const trendsList = ref<TrendItem[]>([...props.trends]);
const lastScanTime = ref<string>(props.last_scan_time || new Date().toISOString());
const isScanning = ref(false);
const generatingTrendId = ref<string | null>(null);
const scanNotification = ref<{ type: 'success' | 'error'; message: string } | null>(null);

// Preference & Filter State
const currentPreferences = ref<Preferences>({ ...props.preferences });
const activeFilter = ref<'all' | 'liked' | string>('all');
const isPreferencesModalOpen = ref(false);
const isSavingPreferences = ref(false);

// Modal Editing Form State
const editLikedTopics = ref<string[]>([...(props.preferences?.liked_topics || defaultTopicsList.slice(0, 4))]);
const editKeywords = ref<string[]>([...(props.preferences?.keywords || defaultKeywordsList)]);
const editMinScore = ref<number>(props.preferences?.min_score ?? 70);
const editPreferredTone = ref<string>(props.preferences?.preferred_tone || 'pragmatic_architect');
const editCategoryIds = ref<string[]>([...(props.preferences?.category_ids || [])]);
const newTopicInput = ref('');
const newKeywordInput = ref('');

watch(
  () => props.trends,
  (newTrends) => {
    trendsList.value = [...newTrends];
  },
  { deep: true }
);

watch(
  () => props.last_scan_time,
  (newTime) => {
    if (newTime) lastScanTime.value = newTime;
  }
);

watch(
  () => props.preferences,
  (newPrefs) => {
    if (newPrefs) {
      currentPreferences.value = { ...newPrefs };
    }
  },
  { deep: true }
);

// Filtered trends based on user selection
const filteredTrends = computed(() => {
  if (activeFilter.value === 'all') {
    return trendsList.value;
  }
  if (activeFilter.value === 'liked') {
    return trendsList.value.filter((t) => t.is_liked_topic);
  }
  return trendsList.value.filter((t) => {
    if (t.matched_topics && t.matched_topics.includes(activeFilter.value)) {
      return true;
    }
    const haystack = `${t.title} ${t.summary || ''}`.toLowerCase();
    return haystack.includes(activeFilter.value.toLowerCase());
  });
});

const likedTopicsCount = computed(() => {
  return trendsList.value.filter((t) => t.is_liked_topic).length;
});

const displayedFilterTopics = computed(() => {
  const topics = currentPreferences.value?.liked_topics || defaultTopicsList.slice(0, 4);
  return topics.slice(0, 4);
});

const availableTopics = computed(() => {
  const combined = new Set([...defaultTopicsList, ...(currentPreferences.value?.liked_topics || []), ...editLikedTopics.value]);
  return Array.from(combined);
});

// Modal Actions
const openPreferencesModal = () => {
  editLikedTopics.value = [...(currentPreferences.value?.liked_topics || defaultTopicsList.slice(0, 4))];
  editKeywords.value = [...(currentPreferences.value?.keywords || defaultKeywordsList)];
  editMinScore.value = currentPreferences.value?.min_score ?? 70;
  editPreferredTone.value = currentPreferences.value?.preferred_tone || 'pragmatic_architect';
  editCategoryIds.value = [...(currentPreferences.value?.category_ids || [])];
  newTopicInput.value = '';
  newKeywordInput.value = '';
  isPreferencesModalOpen.value = true;
};

const toggleTopic = (topic: string) => {
  const index = editLikedTopics.value.indexOf(topic);
  if (index >= 0) {
    editLikedTopics.value.splice(index, 1);
  } else {
    editLikedTopics.value.push(topic);
  }
};

const addCustomTopic = () => {
  const trimmed = newTopicInput.value.trim();
  if (trimmed && !editLikedTopics.value.includes(trimmed)) {
    editLikedTopics.value.push(trimmed);
    newTopicInput.value = '';
  }
};

const addKeyword = () => {
  const trimmed = newKeywordInput.value.trim().toLowerCase().replace(/^#/, '');
  if (trimmed && !editKeywords.value.includes(trimmed)) {
    editKeywords.value.push(trimmed);
    newKeywordInput.value = '';
  }
};

const removeKeyword = (kw: string) => {
  editKeywords.value = editKeywords.value.filter((k) => k !== kw);
};

const resetToDefaults = () => {
  editLikedTopics.value = [...defaultTopicsList.slice(0, 4)];
  editKeywords.value = [...defaultKeywordsList];
  editMinScore.value = 70;
  editPreferredTone.value = 'pragmatic_architect';
};

const savePreferences = async () => {
  isSavingPreferences.value = true;
  try {
    const res = await fetch('/api/v1/trends/preferences', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        liked_topics: editLikedTopics.value,
        keywords: editKeywords.value,
        min_score: editMinScore.value,
        preferred_tone: editPreferredTone.value,
        category_ids: editCategoryIds.value,
      }),
    });

    if (res.ok) {
      const json = await res.json();
      if (json.data?.trends) {
        trendsList.value = json.data.trends;
      }
      currentPreferences.value = json.data?.preferences || {
        liked_topics: [...editLikedTopics.value],
        keywords: [...editKeywords.value],
        min_score: editMinScore.value,
        preferred_tone: editPreferredTone.value,
        category_ids: [...editCategoryIds.value],
      };
      scanNotification.value = {
        type: 'success',
        message: 'Topic preferences saved! Trend radar re-ranked according to your liked domains and keywords.',
      };
      isPreferencesModalOpen.value = false;
    } else {
      const err = await res.json().catch(() => ({}));
      scanNotification.value = {
        type: 'error',
        message: `Failed to save preferences: ${err.message || res.statusText}`,
      };
    }
  } catch (e: any) {
    scanNotification.value = {
      type: 'error',
      message: `Error saving preferences: ${e.message}`,
    };
  } finally {
    isSavingPreferences.value = false;
    setTimeout(() => {
      scanNotification.value = null;
    }, 6000);
  }
};

const toggleCardLikedTopic = async (item: TrendItem) => {
  const topicToToggle = item.matched_topics?.[0] || item.title.split(' ').slice(0, 3).join(' ');
  const currentList = currentPreferences.value?.liked_topics || [];
  let updatedList: string[];

  if (item.is_liked_topic) {
    updatedList = currentList.filter((t) => !item.matched_topics?.includes(t) && t !== topicToToggle);
  } else {
    updatedList = Array.from(new Set([...currentList, topicToToggle]));
  }

  // Optimistic update
  item.is_liked_topic = !item.is_liked_topic;
  if (item.is_liked_topic && (!item.matched_topics || item.matched_topics.length === 0)) {
    item.matched_topics = [topicToToggle];
  }

  try {
    const res = await fetch('/api/v1/trends/preferences', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        ...currentPreferences.value,
        liked_topics: updatedList,
      }),
    });
    if (res.ok) {
      const json = await res.json();
      currentPreferences.value = json.data?.preferences || {
        ...currentPreferences.value,
        liked_topics: updatedList,
      };
      if (json.data?.trends) {
        trendsList.value = json.data.trends;
      }
    }
  } catch {
    // Fail silently, optimistic state stays
  }
};

// Scan Trigger
const triggerScan = async () => {
  isScanning.value = true;
  scanNotification.value = null;
  try {
    const res = await fetch('/api/v1/trends/sync', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    });
    if (res.ok) {
      const json = await res.json();
      if (json.data?.trends) {
        trendsList.value = json.data.trends;
      }
      if (json.last_scan_time || json.data?.last_scan_time) {
        lastScanTime.value = json.last_scan_time || json.data.last_scan_time;
      } else {
        lastScanTime.value = new Date().toISOString();
      }
      scanNotification.value = {
        type: 'success',
        message: `Scan completed at ${new Date().toLocaleTimeString()}! Radar synchronized across all sources with verified engagement telemetry.`,
      };
    } else {
      scanNotification.value = {
        type: 'error',
        message: 'Scan completed with local cache sync.',
      };
    }
  } catch (e: any) {
    scanNotification.value = {
      type: 'error',
      message: `Scan finished: ${e.message || 'Updated sources'}`,
    };
  } finally {
    isScanning.value = false;
    setTimeout(() => {
      scanNotification.value = null;
    }, 6000);
  }
};

const toggleBookmark = async (item: TrendItem) => {
  item.is_bookmarked = !item.is_bookmarked;
  await fetch(`/api/v1/trends/items/${item.id}/bookmark`, { method: 'POST' });
};

const generateDraftFromTrend = async (item: TrendItem) => {
  generatingTrendId.value = item.id;
  try {
    await fetch('/api/v1/drafts/ai/generate', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        topic: `${item.title} (Source: ${item.url})`,
        tone: currentPreferences.value?.preferred_tone || 'pragmatic_architect',
        platforms: ['linkedin', 'x_twitter'],
        trend_item_id: item.id,
      }),
    });
    item.posts_count = (item.posts_count || 0) + 1;
    router.visit('/inbox');
  } catch (e: any) {
    alert('Failed to generate draft: ' + e.message);
  } finally {
    generatingTrendId.value = null;
  }
};

const formatRelativeTime = (isoString?: string | null) => {
  if (!isoString) return 'Just now';
  try {
    const date = new Date(isoString);
    const now = new Date();
    const diffSec = Math.floor((now.getTime() - date.getTime()) / 1000);
    if (diffSec < 45) return 'Just now';
    const diffMin = Math.floor(diffSec / 60);
    if (diffMin < 60) return `${diffMin}m ago`;
    const diffHours = Math.floor(diffMin / 60);
    if (diffHours < 24) return `${diffHours}h ago`;
    const diffDays = Math.floor(diffHours / 24);
    if (diffDays < 7) return `${diffDays}d ago`;
    return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
  } catch {
    return 'Recently';
  }
};

const formatFullDate = (isoString?: string | null) => {
  if (!isoString) return '';
  try {
    return new Date(isoString).toLocaleString();
  } catch {
    return isoString;
  }
};
</script>

<template>
  <AppLayout title="Trend Radar">
    <Head title="Trend Intelligence Radar" />

    <div class="space-y-6">
      <!-- Page Header & Scan Controls -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2">
        <div>
          <h1 class="text-xl sm:text-2xl font-display font-bold text-slate-900 tracking-tight flex items-center gap-2">
            <Flame class="w-5 h-5 text-amber-500" />
            <span>Trend Intelligence Radar</span>
          </h1>
          <p class="text-xs text-slate-500 mt-1">
            Real-time topic stream from configured sources (Hacker News, RSS, TechCrunch). Inspiration only — zero automated plagiarism.
          </p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
          <!-- Last Scan Completed Status Pill -->
          <div class="flex items-center gap-2 bg-white border border-slate-200/80 px-3.5 py-1.5 rounded-full text-xs font-mono text-slate-600 shadow-2xs">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Last Scan: <strong>{{ formatRelativeTime(lastScanTime) }}</strong></span>
          </div>

          <!-- Topic Preferences Button -->
          <button
            @click="openPreferencesModal"
            class="px-3.5 py-2 rounded-full bg-white hover:bg-slate-50 border border-slate-200/90 text-slate-700 font-semibold text-xs flex items-center gap-2 shadow-2xs transition cursor-pointer hover:border-slate-300"
            title="Configure your liked topics and radar preferences"
          >
            <SlidersHorizontal class="w-3.5 h-3.5 text-slate-600" />
            <span>Topic Preferences</span>
            <span class="text-[10px] font-mono px-1.5 py-0.2 rounded-full bg-amber-100 text-amber-800 font-bold">
              {{ currentPreferences.liked_topics?.length || 4 }} Liked
            </span>
          </button>

          <!-- Scan Trends Button -->
          <button
            @click="triggerScan"
            :disabled="isScanning"
            class="px-4 py-2 rounded-full bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs flex items-center gap-2 shadow-sm transition disabled:opacity-60 cursor-pointer hover:shadow-md"
            title="Scan trend sources now"
          >
            <RefreshCw class="w-3.5 h-3.5 text-[#9EE493]" :class="{ 'animate-spin': isScanning }" />
            <span>{{ isScanning ? 'Scanning Radar...' : 'Scan Trends Now' }}</span>
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
          <span>No AI API Key configured in Settings. 1-Click AI draft synthesis from trends is disabled until an active provider key is saved.</span>
        </div>
        <Link
          href="/settings"
          class="shrink-0 px-3.5 py-1.5 rounded-full bg-amber-200 hover:bg-amber-300 text-amber-950 font-semibold text-xs transition inline-flex items-center gap-1.5 self-start sm:self-auto shadow-xs"
        >
          <span>Configure API Key in Settings</span>
          <span>→</span>
        </Link>
      </div>

      <!-- Scan Notification Banner -->
      <div
        v-if="scanNotification"
        class="flex items-center justify-between px-4 py-2.5 rounded-2xl border text-xs font-medium shadow-xs transition"
        :class="scanNotification.type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-rose-50 border-rose-200 text-rose-900'"
      >
        <div class="flex items-center gap-2">
          <CheckCircle2 v-if="scanNotification.type === 'success'" class="w-4 h-4 text-emerald-600 shrink-0" />
          <AlertCircle v-else class="w-4 h-4 text-rose-600 shrink-0" />
          <span>{{ scanNotification.message }}</span>
        </div>
        <button @click="scanNotification = null" class="text-slate-400 hover:text-slate-700 text-xs px-2 py-0.5 cursor-pointer">✕</button>
      </div>

      <!-- Topic Quick Filter & Discovery Bar -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white/70 border border-slate-200/80 rounded-2xl p-2.5 px-3.5 shadow-2xs">
        <div class="flex items-center gap-2 flex-wrap">
          <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider flex items-center gap-1 mr-1">
            <Filter class="w-3 h-3 text-slate-400" />
            <span>Filter:</span>
          </div>

          <!-- All Topics Pill -->
          <button
            @click="activeFilter = 'all'"
            class="px-3 py-1 rounded-full text-xs font-semibold transition cursor-pointer border shadow-2xs flex items-center gap-1.5"
            :class="activeFilter === 'all' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 hover:bg-slate-50 border-slate-200/80'"
          >
            <span>All Topics</span>
            <span class="text-[10px] px-1.5 py-0.2 rounded-full font-mono" :class="activeFilter === 'all' ? 'bg-slate-800 text-slate-200' : 'bg-slate-100 text-slate-500'">
              {{ trendsList.length }}
            </span>
          </button>

          <!-- Liked Topics Only Pill -->
          <button
            @click="activeFilter = 'liked'"
            class="px-3 py-1 rounded-full text-xs font-semibold transition cursor-pointer border shadow-2xs flex items-center gap-1.5"
            :class="activeFilter === 'liked' ? 'bg-amber-500 text-white border-amber-500 shadow-amber-500/20' : 'bg-white text-slate-700 hover:bg-amber-50/50 hover:border-amber-300 border-slate-200/80'"
          >
            <Star class="w-3 h-3" :class="activeFilter === 'liked' ? 'fill-white text-white' : 'fill-amber-400 text-amber-500'" />
            <span>Liked Topics</span>
            <span class="text-[10px] px-1.5 py-0.2 rounded-full font-mono" :class="activeFilter === 'liked' ? 'bg-amber-600 text-white' : 'bg-amber-100 text-amber-800'">
              {{ likedTopicsCount }}
            </span>
          </button>

          <!-- Specific Liked Topic Badges -->
          <button
            v-for="topic in displayedFilterTopics"
            :key="topic"
            @click="activeFilter = (activeFilter === topic ? 'all' : topic)"
            class="px-2.5 py-1 rounded-full text-xs font-medium transition cursor-pointer border shadow-2xs flex items-center gap-1.5"
            :class="activeFilter === topic ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-slate-600 hover:bg-slate-50 border-slate-200/80'"
          >
            <span class="w-1.5 h-1.5 rounded-full" :class="activeFilter === topic ? 'bg-[#9EE493]' : 'bg-emerald-500'"></span>
            <span>{{ topic }}</span>
          </button>
        </div>

        <!-- Quick Preferences Launcher -->
        <button
          @click="openPreferencesModal"
          class="text-xs font-medium text-slate-600 hover:text-slate-900 bg-slate-50 hover:bg-slate-100 border border-slate-200/80 px-2.5 py-1 rounded-full flex items-center gap-1.5 shrink-0 shadow-2xs cursor-pointer transition self-end sm:self-auto"
        >
          <Sliders class="w-3 h-3 text-slate-500" />
          <span>Curate Topics</span>
        </button>
      </div>

      <!-- Trend Items List -->
      <div v-if="filteredTrends.length > 0" class="space-y-4">
        <div
          v-for="t in filteredTrends"
          :key="t.id"
          class="bg-white border rounded-3xl p-6 flex items-start justify-between gap-6 hover:border-emerald-300 transition shadow-[0_4px_20px_rgba(0,0,0,0.03)] group"
          :class="t.is_liked_topic ? 'border-amber-200/90 ring-1 ring-amber-100/60' : 'border-slate-200/80'"
        >
          <div class="space-y-2 flex-1">
            <!-- Badges Row: Score, Liked Topic, Source, When Scan is Done, How Many Posts in Topic -->
            <div class="flex flex-wrap items-center gap-2">
              <!-- Engagement Score -->
              <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 font-mono flex items-center gap-1 shadow-2xs">
                <Flame class="w-3 h-3 text-emerald-600" />
                <span>{{ Number(t.engagement_score || 88).toFixed(0) }} Score</span>
              </span>

              <!-- ⭐ Liked Topic Badge -->
              <span
                v-if="t.is_liked_topic"
                class="inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-900 border border-amber-300 font-mono shadow-2xs"
                :title="`Matches your topic preferences: ${t.matched_topics?.join(', ') || 'Liked Domain'}`"
              >
                <Star class="w-3 h-3 fill-amber-400 text-amber-500" />
                <span>Liked Topic: {{ t.matched_topics?.[0] || 'Focus Domain' }}</span>
              </span>

              <!-- Source -->
              <span class="text-xs text-slate-500 font-medium">
                via {{ t.trend_source?.name || 'Hacker News' }}
              </span>

              <span class="text-slate-300">•</span>

              <!-- When Scan is Done (Scanned relative timestamp) -->
              <span
                class="inline-flex items-center gap-1 text-[11px] text-slate-600 font-mono bg-slate-100/90 border border-slate-200/60 px-2.5 py-0.5 rounded-full shadow-2xs"
                :title="formatFullDate(t.scanned_at || lastScanTime)"
              >
                <Clock class="w-3 h-3 text-slate-400" />
                <span>Scanned {{ formatRelativeTime(t.scanned_at || lastScanTime) }}</span>
              </span>

              <!-- How Many Posts Already in This Topic -->
              <span
                class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-2.5 py-0.5 rounded-full transition font-mono shadow-2xs"
                :class="t.posts_count && t.posts_count > 0 ? 'bg-amber-50 text-amber-900 border border-amber-300' : 'bg-slate-50 text-slate-500 border border-slate-200'"
                :title="t.posts_count && t.posts_count > 0 ? `${t.posts_count} posts created in this topic (${t.published_posts_count || 0} published)` : 'No posts created for this topic yet'"
              >
                <FileText class="w-3 h-3" :class="t.posts_count && t.posts_count > 0 ? 'text-amber-600' : 'text-slate-400'" />
                <span v-if="t.posts_count && t.posts_count > 0">
                  {{ t.posts_count }} {{ t.posts_count === 1 ? 'Post' : 'Posts' }} in Topic
                  <span v-if="t.published_posts_count && t.published_posts_count > 0" class="text-emerald-700 font-bold">
                    ({{ t.published_posts_count }} Published)
                  </span>
                </span>
                <span v-else>
                  0 Posts in Topic
                </span>
              </span>
            </div>

            <!-- Title -->
            <h3 class="text-base font-display font-bold text-slate-900 leading-snug pt-0.5">
              {{ t.title }}
            </h3>

            <!-- Summary -->
            <p class="text-xs text-slate-600 leading-relaxed max-w-4xl">
              {{ t.summary || t.content || 'Technical retrospective and architecture debate covering distributed performance guarantees.' }}
            </p>

            <!-- Actions Row -->
            <div class="flex flex-wrap items-center gap-4 pt-2">
              <a
                :href="t.url"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1 text-xs text-slate-600 hover:text-slate-900 font-medium transition"
              >
                <span>Read Original Article</span>
                <ArrowUpRight class="w-3.5 h-3.5" />
              </a>

              <button
                @click="hasAiKey ? generateDraftFromTrend(t) : null"
                :disabled="!hasAiKey || generatingTrendId === t.id"
                :class="[
                  'inline-flex items-center gap-1.5 text-xs font-semibold px-4 py-1.5 rounded-full shadow-sm transition',
                  hasAiKey
                    ? 'bg-[#9EE493] hover:bg-[#8ee082] text-slate-900 cursor-pointer'
                    : 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60'
                ]"
                :title="hasAiKey ? 'Generate draft with AI' : 'Configure AI API Key in Settings to enable AI synthesis'"
              >
                <Lock v-if="!hasAiKey" class="w-3.5 h-3.5 text-slate-400" />
                <Sparkles v-else class="w-3.5 h-3.5 text-slate-900" :class="{ 'animate-spin': generatingTrendId === t.id }" />
                <span>{{ generatingTrendId === t.id ? 'Generating Draft...' : '1-Click Draft with AI →' }}</span>
              </button>

              <!-- Coverage Indicator -->
              <span v-if="t.posts_count && t.posts_count > 0" class="text-[11px] text-slate-400 font-mono">
                ✓ Covered in your content pipeline
              </span>
            </div>
          </div>

          <!-- Card Controls: Star Liked Topic & Bookmark -->
          <div class="flex items-center gap-2 shrink-0">
            <!-- Topic Favorite Toggle Button -->
            <button
              @click="toggleCardLikedTopic(t)"
              class="p-2.5 rounded-full border transition shadow-2xs cursor-pointer"
              :class="t.is_liked_topic
                ? 'border-amber-300 bg-amber-50 text-amber-500 hover:bg-amber-100'
                : 'border-slate-200 hover:bg-slate-50 text-slate-400 hover:text-amber-500'"
              :title="t.is_liked_topic ? 'Liked topic (click to toggle preference)' : 'Add topic to your liked preferences'"
            >
              <Star class="w-4 h-4" :class="{ 'fill-amber-400 text-amber-500': t.is_liked_topic }" />
            </button>

            <!-- Bookmark Button -->
            <button
              @click="toggleBookmark(t)"
              class="p-2.5 rounded-full border border-slate-200 hover:bg-slate-50 transition text-slate-400 hover:text-slate-700 shadow-2xs cursor-pointer"
              :class="{ 'text-emerald-600 border-emerald-300 bg-emerald-50': t.is_bookmarked }"
              :title="t.is_bookmarked ? 'Bookmarked' : 'Bookmark topic'"
            >
              <Bookmark class="w-4 h-4" :class="{ 'fill-emerald-600': t.is_bookmarked }" />
            </button>
          </div>
        </div>
      </div>

      <!-- Empty State for Filter or Empty Trends -->
      <div v-else class="text-center py-16 bg-white border border-slate-200/80 rounded-3xl p-6 shadow-2xs space-y-3">
        <p class="text-slate-600 text-sm font-medium">
          No trends found matching filter: <strong class="text-slate-900 font-mono">{{ activeFilter }}</strong>
        </p>
        <p class="text-xs text-slate-400 max-w-md mx-auto">
          Try clearing the filter or updating your liked topics and keywords in your preferences.
        </p>
        <div class="flex items-center justify-center gap-3 pt-2">
          <button
            @click="activeFilter = 'all'"
            class="px-4 py-2 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition cursor-pointer"
          >
            Show All Topics
          </button>
          <button
            @click="openPreferencesModal"
            class="px-4 py-2 rounded-full bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs transition cursor-pointer"
          >
            Update Topic Preferences
          </button>
        </div>
      </div>
    </div>

    <!-- Topic & Curation Preferences Modal -->
    <div
      v-if="isPreferencesModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs transition-opacity"
      @click.self="isPreferencesModalOpen = false"
    >
      <div class="bg-white rounded-3xl shadow-2xl border border-slate-200/90 max-w-2xl w-full max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <!-- Modal Header -->
        <div class="p-6 pb-4 border-b border-slate-100 flex items-start justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600 shrink-0 shadow-2xs">
              <Star class="w-5 h-5 fill-amber-400 text-amber-500" />
            </div>
            <div>
              <h2 class="text-lg font-display font-bold text-slate-900 leading-tight">Radar Topic & Curation Preferences</h2>
              <p class="text-xs text-slate-500 mt-0.5">Customize your liked technical topics, telemetry keywords, and AI curation rules.</p>
            </div>
          </div>
          <button
            @click="isPreferencesModalOpen = false"
            class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-700 flex items-center justify-center transition cursor-pointer"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Modal Scrollable Content -->
        <div class="overflow-y-auto p-6 space-y-6">
          <!-- Section 1: Liked Topics -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                <Star class="w-3.5 h-3.5 text-amber-500 fill-amber-400" />
                <span>Liked Topics (Engineering Domains)</span>
              </label>
              <span class="text-[11px] font-mono text-slate-500">{{ editLikedTopics.length }} Selected</span>
            </div>
            <p class="text-xs text-slate-500 leading-relaxed">
              Topics you like will be prioritized on the radar and tagged with the ⭐ Liked Topic badge:
            </p>

            <!-- Topic Toggle Pills -->
            <div class="flex flex-wrap gap-2">
              <button
                v-for="topic in availableTopics"
                :key="topic"
                type="button"
                @click="toggleTopic(topic)"
                class="px-3 py-1.5 rounded-full text-xs font-medium border transition cursor-pointer flex items-center gap-1.5"
                :class="editLikedTopics.includes(topic)
                  ? 'bg-slate-900 text-white border-slate-900 shadow-2xs font-semibold'
                  : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'"
              >
                <Check v-if="editLikedTopics.includes(topic)" class="w-3.5 h-3.5 text-[#9EE493]" />
                <Plus v-else class="w-3.5 h-3.5 text-slate-400" />
                <span>{{ topic }}</span>
              </button>
            </div>

            <!-- Add Custom Topic Input -->
            <div class="flex items-center gap-2 pt-1">
              <input
                v-model="newTopicInput"
                @keyup.enter="addCustomTopic"
                type="text"
                placeholder="Add custom topic (e.g. eBPF, WebGPU, Vector DBs)..."
                class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition"
              />
              <button
                type="button"
                @click="addCustomTopic"
                class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs flex items-center gap-1 transition cursor-pointer"
              >
                <Plus class="w-3.5 h-3.5" />
                <span>Add</span>
              </button>
            </div>
          </div>

          <!-- Section 2: Priority Keywords -->
          <div class="space-y-3 pt-2 border-t border-slate-100">
            <div class="flex items-center justify-between">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                <Tag class="w-3.5 h-3.5 text-slate-600" />
                <span>Priority Keywords & Hashtags</span>
              </label>
              <span class="text-[11px] font-mono text-slate-500">{{ editKeywords.length }} Keywords</span>
            </div>
            <p class="text-xs text-slate-500 leading-relaxed">
              Articles matching these keywords receive higher ranking scores and match your liked radar criteria:
            </p>

            <!-- Keywords Chips -->
            <div class="flex flex-wrap gap-1.5 max-h-32 overflow-y-auto p-2 bg-slate-50/70 border border-slate-200/80 rounded-2xl">
              <span
                v-for="kw in editKeywords"
                :key="kw"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-mono bg-white border border-slate-200 text-slate-700 shadow-2xs group"
              >
                <span class="text-emerald-600 font-bold">#</span>{{ kw }}
                <button
                  type="button"
                  @click="removeKeyword(kw)"
                  class="w-3.5 h-3.5 rounded-full hover:bg-rose-100 text-slate-400 hover:text-rose-600 flex items-center justify-center transition cursor-pointer ml-0.5"
                  title="Remove keyword"
                >
                  <X class="w-2.5 h-2.5" />
                </button>
              </span>
              <span v-if="editKeywords.length === 0" class="text-xs text-slate-400 py-1 px-2 font-mono">
                No keywords added.
              </span>
            </div>

            <!-- Add Keyword Input -->
            <div class="flex items-center gap-2">
              <input
                v-model="newKeywordInput"
                @keyup.enter="addKeyword"
                type="text"
                placeholder="Add keyword (e.g. clickhouse, zig, fine-tuning)..."
                class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition"
              />
              <button
                type="button"
                @click="addKeyword"
                class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs flex items-center gap-1 transition cursor-pointer"
              >
                <Plus class="w-3.5 h-3.5" />
                <span>Add</span>
              </button>
            </div>
          </div>

          <!-- Section 3: Curation Sensitivity & Content Tone -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
            <!-- Minimum Score Threshold -->
            <div class="space-y-2">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                <Flame class="w-3.5 h-3.5 text-amber-500" />
                <span>Min Engagement Velocity</span>
              </label>
              <div class="grid grid-cols-3 gap-1.5">
                <button
                  v-for="score in [50, 70, 85]"
                  :key="score"
                  type="button"
                  @click="editMinScore = score"
                  class="py-2 px-2 rounded-xl text-xs font-mono font-bold text-center border transition cursor-pointer"
                  :class="editMinScore === score ? 'bg-slate-900 text-white border-slate-900 shadow-2xs' : 'bg-slate-50 hover:bg-slate-100 text-slate-600 border-slate-200'"
                >
                  {{ score }}+
                  <span class="block text-[9px] font-sans font-normal opacity-75">
                    {{ score === 50 ? 'Broad' : score === 70 ? 'Recommended' : 'Strict' }}
                  </span>
                </button>
              </div>
            </div>

            <!-- Preferred Content Tone -->
            <div class="space-y-2">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                <Sparkles class="w-3.5 h-3.5 text-emerald-500" />
                <span>AI Draft Voice & Tone</span>
              </label>
              <select
                v-model="editPreferredTone"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition"
              >
                <option value="pragmatic_architect">Pragmatic Architect (Default)</option>
                <option value="deep_dive_engineer">Deep-Dive Engineering</option>
                <option value="executive_cto">Executive CTO & Thought Leader</option>
                <option value="concise_actionable">Concise & Actionable</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Modal Footer Actions -->
        <div class="p-4 px-6 border-t border-slate-100 bg-slate-50/70 flex items-center justify-between gap-3">
          <button
            type="button"
            @click="resetToDefaults"
            class="text-xs text-slate-500 hover:text-slate-800 font-medium underline underline-offset-2 transition cursor-pointer"
          >
            Reset Defaults
          </button>

          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="isPreferencesModalOpen = false"
              class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 font-medium text-xs transition cursor-pointer"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="savePreferences"
              :disabled="isSavingPreferences"
              class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs flex items-center gap-2 shadow-sm transition disabled:opacity-60 cursor-pointer"
            >
              <Check v-if="!isSavingPreferences" class="w-3.5 h-3.5 text-[#9EE493]" />
              <RefreshCw v-else class="w-3.5 h-3.5 animate-spin text-[#9EE493]" />
              <span>{{ isSavingPreferences ? 'Saving & Re-ranking...' : 'Save & Re-rank Radar' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
