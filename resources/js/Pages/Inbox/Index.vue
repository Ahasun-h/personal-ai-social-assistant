<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { Head, router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import LinkedInCardPreview from '@/Components/LinkedInCardPreview.vue';
import XPostPreview from '@/Components/XPostPreview.vue';
import DynamicCharMeter from '@/Components/DynamicCharMeter.vue';
import {
  CheckCircle,
  RefreshCw,
  Send,
  AlertTriangle,
  Sparkles,
  Layers,
  Calendar,
  Save,
  Plus,
  Lock,
  Flame,
  Star,
  ArrowUpRight,
  Search,
  Check,
  FileText,
  Radio,
  ExternalLink,
  X as CloseIcon,
} from 'lucide-vue-next';

interface TrendingTopic {
  id: string;
  title: string;
  summary?: string;
  content?: string;
  url?: string;
  engagement_score?: number;
  is_liked_topic?: boolean;
  matched_topics?: string[];
  posts_count?: number;
  published_posts_count?: number;
  scanned_at?: string;
  trend_source?: {
    name: string;
    source_type: string;
  };
}

interface DraftVersion {
  id: string;
  platform: string;
  content: string;
  character_count?: number;
}

interface DraftItem {
  id: string;
  user_id?: string;
  trend_item_id?: string;
  title: string;
  status: string;
  versions?: DraftVersion[];
  trend_item?: {
    id: string;
    title: string;
    url?: string;
    engagement_score?: number;
  };
  post_media?: Array<{
    media_asset?: {
      path: string;
    };
  }>;
}

interface Props {
  drafts?: DraftItem[];
  accounts?: any[];
  categories?: any[];
  trendingTopics?: TrendingTopic[];
  preferences?: Record<string, any>;
}

const props = withDefaults(defineProps<Props>(), {
  drafts: () => [],
  accounts: () => [],
  categories: () => [],
  trendingTopics: () => [],
  preferences: () => ({
    liked_topics: [],
    preferred_tone: 'pragmatic_architect',
  }),
});

const page = usePage();
const hasAiKey = computed(() => {
  return !!(page.props as any).hasAiKey;
});

const draftsList = ref<DraftItem[]>([...props.drafts]);
const selectedDraftId = ref<string>(props.drafts[0]?.id || '');

watch(
  () => props.drafts,
  (newDrafts) => {
    draftsList.value = [...newDrafts];
    if (!selectedDraftId.value && newDrafts.length > 0) {
      selectDraft(newDrafts[0]);
    }
  },
  { deep: true }
);

const currentDraft = computed(() => {
  return draftsList.value.find((d) => d.id === selectedDraftId.value) || draftsList.value[0] || null;
});

const linkedinText = ref<string>(
  currentDraft.value?.versions?.find((v) => v.platform === 'linkedin')?.content ||
  "Building autonomous systems requires deterministic state machines and strict contract testing."
);

const xText = ref<string>(
  currentDraft.value?.versions?.find((v) => v.platform === 'x_twitter')?.content ||
  "Deterministic state machines beat raw prompting every single time. 👇"
);

const mediaPreviewUrl = computed(() => {
  return currentDraft.value?.post_media?.[0]?.media_asset?.path ||
    'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1200&q=80';
});

const selectDraft = (draft: DraftItem) => {
  selectedDraftId.value = draft.id;
  linkedinText.value = draft.versions?.find((v) => v.platform === 'linkedin')?.content || '';
  xText.value = draft.versions?.find((v) => v.platform === 'x_twitter')?.content || '';
};

// Trending Topics & Generation State
const trendingFilter = ref<'all' | 'liked'>('all');
const topicSearchQuery = ref('');
const selectedTrendingTopic = ref<TrendingTopic | null>(props.trendingTopics[0] || null);
const generatingTrendId = ref<string | null>(null);
const modalTab = ref<'trends' | 'custom'>('trends');

const selectedTone = ref<string>(props.preferences?.preferred_tone || 'pragmatic_architect');
const selectedPlatforms = ref<string[]>(['linkedin', 'x_twitter']);

const toneOptions = [
  { id: 'pragmatic_architect', label: 'Pragmatic Architect', desc: 'Technical depth, systems tradeoffs, no buzzwords' },
  { id: 'deep_dive_engineer', label: 'Deep Dive Engineer', desc: 'Code-level analysis, benchmarks, and edge cases' },
  { id: 'thought_leader', label: 'Thought Leader', desc: 'Visionary perspectives, strategic industry trends' },
  { id: 'contrarian', label: 'Contrarian Leader', desc: 'Challenges conventional wisdom with data' },
];

const availablePlatforms = [
  { id: 'linkedin', label: 'LinkedIn', desc: 'Long-form professional post with takeaways' },
  { id: 'x_twitter', label: 'X (Twitter)', desc: 'Concise punchy tweet + thread' },
];

const likedTopicsCount = computed(() => {
  return props.trendingTopics.filter((t) => t.is_liked_topic).length;
});

const displayedTrendingTopics = computed(() => {
  let list = props.trendingTopics;
  if (trendingFilter.value === 'liked') {
    list = list.filter((t) => t.is_liked_topic);
  }
  if (topicSearchQuery.value.trim()) {
    const q = topicSearchQuery.value.toLowerCase();
    list = list.filter((t) =>
      t.title.toLowerCase().includes(q) || (t.summary && t.summary.toLowerCase().includes(q))
    );
  }
  return list;
});

const isPublishing = ref(false);
const isApproving = ref(false);
const isGenerating = ref(false);
const newTopicPrompt = ref('');
const showNewModal = ref(false);
const toast = ref<{ type: 'success' | 'error'; message: string } | null>(null);

const showToast = (message: string, type: 'success' | 'error' = 'success') => {
  toast.value = { message, type };
  setTimeout(() => {
    toast.value = null;
  }, 5000);
};

const openTrendingModal = () => {
  if (!hasAiKey.value) return;
  modalTab.value = 'trends';
  if (!selectedTrendingTopic.value && props.trendingTopics.length > 0) {
    selectedTrendingTopic.value = props.trendingTopics[0];
  }
  showNewModal.value = true;
};

const openCustomModal = () => {
  if (!hasAiKey.value) return;
  modalTab.value = 'custom';
  showNewModal.value = true;
};

// 1-Click Generate from a Trending Topic (Saves under user account)
const generateFromTrendingTopic = async (topic: TrendingTopic) => {
  if (!hasAiKey.value) return;
  generatingTrendId.value = topic.id;
  isGenerating.value = true;

  try {
    const res = await fetch('/api/v1/drafts/ai/generate', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        topic: `${topic.title} (Source: ${topic.url || 'HN'})`,
        tone: selectedTone.value || props.preferences?.preferred_tone || 'pragmatic_architect',
        platforms: selectedPlatforms.value.length ? selectedPlatforms.value : ['linkedin', 'x_twitter'],
        trend_item_id: topic.id,
      }),
    });

    const data = await res.json();
    if (res.ok && data.success) {
      const newDraft = data.data;
      draftsList.value.unshift(newDraft);
      selectDraft(newDraft);
      showNewModal.value = false;
      showToast(`AI post generated according to trending topic "${topic.title.substring(0, 45)}..." and saved to your account!`, 'success');
      router.reload();
    } else {
      showToast(data.message || 'Generation failed', 'error');
    }
  } catch (e: any) {
    showToast(e.message || 'Generation failed', 'error');
  } finally {
    generatingTrendId.value = null;
    isGenerating.value = false;
  }
};

const approveDraft = async () => {
  if (!currentDraft.value) return;
  isApproving.value = true;
  try {
    const res = await fetch(`/api/v1/drafts/${currentDraft.value.id}/approve`, { method: 'POST' });
    if (res.ok) {
      showToast('Draft approved! Ready for scheduling or immediate publishing.');
      router.reload();
    } else {
      const err = await res.json();
      showToast(err.message || 'Approval failed', 'error');
    }
  } catch (e: any) {
    showToast(e.message, 'error');
  } finally {
    isApproving.value = false;
  }
};

const publishImmediately = async () => {
  if (!currentDraft.value) return;
  if (currentDraft.value.status !== 'approved') {
    showToast('Mandatory Rule: Draft must be approved by owner before publishing.', 'error');
    return;
  }
  isPublishing.value = true;
  try {
    const res = await fetch(`/api/v1/drafts/${currentDraft.value.id}/publish-now`, { method: 'POST' });
    if (res.ok) {
      showToast('Post dispatched to platform queues successfully!');
      router.reload();
    } else {
      const err = await res.json();
      showToast(err.message || 'Publishing failed', 'error');
    }
  } catch (e: any) {
    showToast(e.message, 'error');
  } finally {
    isPublishing.value = false;
  }
};

const saveDraftEdits = async () => {
  if (!currentDraft.value) return;
  try {
    await fetch(`/api/v1/drafts/${currentDraft.value.id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        content: linkedinText.value,
        platform: 'linkedin',
      }),
    });
    await fetch(`/api/v1/drafts/${currentDraft.value.id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        content: xText.value,
        platform: 'x_twitter',
      }),
    });
    showToast('Draft revisions saved successfully.');
    router.reload();
  } catch (e: any) {
    showToast(e.message, 'error');
  }
};

const generateNewAiDraft = async () => {
  if (modalTab.value === 'trends') {
    if (selectedTrendingTopic.value) {
      await generateFromTrendingTopic(selectedTrendingTopic.value);
    }
    return;
  }

  if (!newTopicPrompt.value) return;
  isGenerating.value = true;
  try {
    const res = await fetch('/api/v1/drafts/ai/generate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        topic: newTopicPrompt.value,
        tone: selectedTone.value || 'pragmatic_architect',
        platforms: selectedPlatforms.value.length ? selectedPlatforms.value : ['linkedin', 'x_twitter'],
      }),
    });
    if (res.ok) {
      const data = await res.json();
      if (data.data) {
        draftsList.value.unshift(data.data);
        selectDraft(data.data);
      }
      showToast('New AI draft synthesized and placed in Review Inbox!');
      showNewModal.value = false;
      newTopicPrompt.value = '';
      router.reload();
    } else {
      const err = await res.json();
      showToast(err.message || 'Generation failed', 'error');
    }
  } catch (e: any) {
    showToast(e.message, 'error');
  } finally {
    isGenerating.value = false;
  }
};

onMounted(() => {
  const urlParams = new URLSearchParams(window.location.search);
  const draftParam = urlParams.get('draft');
  const trendParam = urlParams.get('trend');

  if (draftParam) {
    const found = draftsList.value.find((d) => d.id === draftParam);
    if (found) selectDraft(found);
  } else if (trendParam && props.trendingTopics.length > 0) {
    const foundTrend = props.trendingTopics.find((t) => t.id === trendParam);
    if (foundTrend) {
      selectedTrendingTopic.value = foundTrend;
      modalTab.value = 'trends';
      showNewModal.value = true;
    }
  }
});
</script>

<template>
  <AppLayout title="Review Inbox">
    <Head title="Review Inbox & Dual-Platform Preview" />

    <div class="space-y-6">
      <!-- Page Header with Action Pills -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-2">
        <div>
          <h1 class="text-xl sm:text-2xl font-display font-bold text-slate-900 tracking-tight flex items-center gap-2">
            <Layers class="w-5 h-5 text-emerald-600" />
            <span>Review Inbox & Dual-Platform Preview</span>
          </h1>
          <p class="text-xs text-slate-500 mt-1">
            Generate posts according to trending topics using AI, review dual-platform copy, and approve for dispatch.
          </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
          <!-- Primary Action: Generate from Trending Topics -->
          <button
            @click="openTrendingModal"
            :disabled="!hasAiKey"
            :class="[
              'flex items-center gap-1.5 text-xs font-semibold px-4 py-2 rounded-full border shadow-sm transition',
              hasAiKey
                ? 'bg-slate-900 hover:bg-slate-800 text-white border-slate-900 cursor-pointer shadow-slate-900/10'
                : 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed opacity-60'
            ]"
            :title="hasAiKey ? 'Generate post from real-time trending topics' : 'Configure AI API Key in Settings to enable AI synthesis'"
          >
            <Lock v-if="!hasAiKey" class="w-3.5 h-3.5 text-slate-400" />
            <Flame v-else class="w-3.5 h-3.5 text-amber-400" />
            <span>Generate from Trending Topics</span>
          </button>

          <!-- Secondary Action: Custom Prompt Draft -->
          <button
            @click="openCustomModal"
            :disabled="!hasAiKey"
            :class="[
              'flex items-center gap-1.5 text-xs font-semibold px-4 py-2 rounded-full border shadow-sm transition',
              hasAiKey
                ? 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200/80 cursor-pointer'
                : 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed opacity-60'
            ]"
            :title="hasAiKey ? 'Generate draft with custom prompt' : 'Configure AI API Key in Settings to enable AI synthesis'"
          >
            <Lock v-if="!hasAiKey" class="w-3.5 h-3.5 text-slate-400" />
            <Plus v-else class="w-3.5 h-3.5 text-emerald-600" />
            <span>+ Custom Prompt</span>
          </button>

          <button
            @click="saveDraftEdits"
            class="flex items-center gap-1.5 text-xs font-semibold px-4 py-2 rounded-full bg-white hover:bg-slate-50 border border-slate-200/80 text-slate-700 shadow-sm transition cursor-pointer"
          >
            <Save class="w-3.5 h-3.5 text-slate-500" />
            <span>Save Copy</span>
          </button>

          <button
            @click="approveDraft"
            :disabled="isApproving || currentDraft?.status === 'approved'"
            class="flex items-center gap-1.5 text-xs font-semibold px-5 py-2 rounded-full bg-[#9EE493] hover:bg-[#8ee082] text-slate-900 shadow-sm transition disabled:opacity-50 cursor-pointer"
          >
            <CheckCircle class="w-4 h-4" />
            <span>{{ currentDraft?.status === 'approved' ? 'Approved ✓' : 'Approve Draft' }}</span>
          </button>

          <button
            @click="publishImmediately"
            :disabled="isPublishing"
            class="flex items-center gap-1.5 text-xs font-semibold px-5 py-2 rounded-full bg-slate-900 hover:bg-slate-800 text-white shadow-sm transition disabled:opacity-50 cursor-pointer"
          >
            <Send class="w-4 h-4 text-[#9EE493]" />
            <span>{{ isPublishing ? 'Publishing...' : 'Publish Now' }}</span>
          </button>
        </div>
      </div>

      <!-- Missing AI API Key Warning Banner -->
      <div
        v-if="!hasAiKey"
        class="bg-amber-50 border border-amber-300 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-amber-900 shadow-sm"
      >
        <div class="flex items-center gap-2.5">
          <AlertTriangle class="w-4 h-4 text-amber-600 shrink-0" />
          <span>No AI API Key configured in Settings. AI draft synthesis is disabled until an active provider key is saved.</span>
        </div>
        <Link
          href="/settings"
          class="shrink-0 px-3.5 py-1.5 rounded-full bg-amber-200 hover:bg-amber-300 text-amber-950 font-semibold text-xs transition inline-flex items-center gap-1.5 self-start sm:self-auto shadow-xs"
        >
          <span>Configure API Key in Settings</span>
          <span>→</span>
        </Link>
      </div>

      <!-- Toast Banner -->
      <div
        v-if="toast"
        class="flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-semibold shadow-sm transition"
        :class="toast.type === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200'"
      >
        <CheckCircle v-if="toast.type === 'success'" class="w-4 h-4 text-emerald-600" />
        <AlertTriangle v-else class="w-4 h-4 text-rose-600" />
        <span>{{ toast.message }}</span>
      </div>

      <!-- Trending Topics AI Quick-Generation Radar Strip -->
      <div
        v-if="trendingTopics && trendingTopics.length > 0"
        class="bg-gradient-to-r from-slate-900 via-[#132A30] to-[#1E3A40] text-white rounded-3xl p-5 border border-white/10 shadow-contrast space-y-3.5"
      >
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-white/10 pb-3">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-full bg-amber-500/20 border border-amber-400/30 flex items-center justify-center shrink-0">
              <Flame class="w-4 h-4 text-amber-400" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h3 class="font-display font-bold text-sm text-white">Generate Posts According to Trending Topics</h3>
                <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-[#9EE493]/20 text-[#9EE493] font-semibold border border-[#9EE493]/30">
                  AI Synthesizer
                </span>
              </div>
              <p class="text-[11px] text-slate-300 mt-0.5">
                Click any trending topic to synthesize dual-platform posts (LinkedIn & X) and save directly to your account.
              </p>
            </div>
          </div>

          <!-- Quick Filters & Full Radar Link -->
          <div class="flex items-center gap-2 flex-wrap">
            <button
              @click="trendingFilter = 'all'"
              :class="[
                'px-3 py-1 rounded-full text-xs font-semibold transition cursor-pointer',
                trendingFilter === 'all'
                  ? 'bg-white text-slate-900 shadow-sm'
                  : 'bg-white/10 text-slate-300 hover:bg-white/20'
              ]"
            >
              All Trends ({{ trendingTopics.length }})
            </button>
            <button
              @click="trendingFilter = 'liked'"
              :class="[
                'px-3 py-1 rounded-full text-xs font-semibold transition flex items-center gap-1 cursor-pointer',
                trendingFilter === 'liked'
                  ? 'bg-amber-400 text-slate-900 shadow-sm'
                  : 'bg-white/10 text-slate-300 hover:bg-white/20'
              ]"
            >
              <Star class="w-3 h-3" :class="trendingFilter === 'liked' ? 'fill-slate-900 text-slate-900' : 'text-amber-400'" />
              <span>Liked ({{ likedTopicsCount }})</span>
            </button>
            <Link
              href="/trends"
              class="text-[11px] text-[#9EE493] hover:underline flex items-center gap-1 font-semibold ml-1 shrink-0"
              title="Open full Trend Radar"
            >
              <span>Full Radar</span>
              <ArrowUpRight class="w-3 h-3" />
            </Link>
          </div>
        </div>

        <!-- 3 Highlight Trending Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div
            v-for="topic in displayedTrendingTopics.slice(0, 3)"
            :key="topic.id"
            class="bg-white/5 hover:bg-white/10 border border-white/10 hover:border-[#9EE493]/50 rounded-2xl p-3.5 space-y-2.5 transition flex flex-col justify-between group"
          >
            <div class="space-y-1.5">
              <!-- Card Top Badges -->
              <div class="flex items-center justify-between text-[10px] font-mono">
                <span class="text-amber-300 bg-amber-400/10 border border-amber-400/30 px-2 py-0.5 rounded-full flex items-center gap-1 font-semibold">
                  <Flame class="w-3 h-3" />
                  <span>{{ Number(topic.engagement_score || 94).toFixed(0) }} Score</span>
                </span>
                <span class="text-slate-400 truncate max-w-[140px]">
                  {{ topic.trend_source?.name || 'Hacker News' }}
                </span>
              </div>

              <!-- Topic Title -->
              <h4 class="text-xs font-bold text-white group-hover:text-[#9EE493] transition line-clamp-2 leading-snug">
                {{ topic.title }}
              </h4>

              <!-- Topic Summary -->
              <p class="text-[11px] text-slate-300 line-clamp-2 leading-relaxed font-sans">
                {{ topic.summary || topic.content || 'Technical retrospective and architecture debate covering distributed performance guarantees.' }}
              </p>
            </div>

            <!-- 1-Click Action Row -->
            <div class="pt-2 border-t border-white/10 flex items-center justify-between">
              <span class="text-[10px] font-mono text-slate-400 flex items-center gap-1">
                <FileText class="w-3 h-3 text-slate-400" />
                <span>{{ topic.posts_count || 0 }} in Topic</span>
              </span>

              <button
                @click="generateFromTrendingTopic(topic)"
                :disabled="!hasAiKey || generatingTrendId === topic.id"
                :class="[
                  'px-3 py-1.5 rounded-full text-xs font-semibold flex items-center gap-1.5 transition shadow-sm',
                  hasAiKey
                    ? 'bg-[#9EE493] hover:bg-[#8ee082] text-slate-900 cursor-pointer'
                    : 'bg-white/10 text-slate-400 border border-white/10 cursor-not-allowed opacity-60'
                ]"
                :title="hasAiKey ? '1-Click generate and save to your account' : 'Configure AI API Key in Settings'"
              >
                <Lock v-if="!hasAiKey" class="w-3 h-3 text-slate-400" />
                <Sparkles v-else class="w-3 h-3 text-slate-900" :class="{ 'animate-spin': generatingTrendId === topic.id }" />
                <span>{{ generatingTrendId === topic.id ? 'Synthesizing...' : '1-Click AI Post →' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Draft Selector Chips -->
      <div v-if="draftsList.length > 0" class="space-y-2">
        <div class="flex items-center justify-between text-xs text-slate-500 font-medium px-1">
          <span>Active Review Queue ({{ draftsList.length }} Posts saved in your account)</span>
          <span class="text-[11px] font-mono text-slate-400">Click post to preview & edit</span>
        </div>

        <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-none">
          <button
            v-for="d in draftsList"
            :key="d.id"
            @click="selectDraft(d)"
            :class="[
              'px-4 py-2 rounded-full text-xs font-medium whitespace-nowrap transition flex items-center gap-2 shadow-sm cursor-pointer',
              selectedDraftId === d.id
                ? 'bg-slate-900 text-white font-semibold shadow-slate-900/10'
                : 'bg-white text-slate-600 border border-slate-200/80 hover:bg-slate-50'
            ]"
          >
            <span
              :class="[
                'w-2 h-2 rounded-full shrink-0',
                d.status === 'approved' ? 'bg-[#10B981]' : 'bg-amber-400'
              ]"
            ></span>
            <span v-if="d.trend_item_id" class="text-[10px] text-amber-500" title="Generated from Trending Topic">🔥</span>
            <span class="max-w-[200px] truncate">{{ d.title }}</span>
            <span class="text-[10px] font-mono opacity-60">({{ d.status }})</span>
          </button>
        </div>
      </div>

      <!-- Notice badge for X metered links -->
      <div class="bg-amber-50 border border-amber-200/70 rounded-2xl p-3.5 flex items-center justify-between text-xs text-amber-800 shadow-xs">
        <div class="flex items-center gap-2.5">
          <AlertTriangle class="w-4 h-4 text-amber-600 shrink-0" />
          <span>Publishing on X incurs standard tier metered fees ($0.015/post). Mandatory human approval active.</span>
        </div>
        <span class="font-mono text-[11px] bg-amber-100 text-amber-800 px-2.5 py-0.5 rounded-full font-medium">
          Zero Unauthorized Automation
        </span>
      </div>

      <!-- Main Editor & Split Preview Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Left: Draft Editors -->
        <div class="lg:col-span-5 space-y-5">
          <!-- Active Draft Source & Trend Info Banner -->
          <div
            v-if="currentDraft?.trend_item || currentDraft?.trend_item_id"
            class="bg-emerald-50/80 border border-emerald-200 rounded-2xl p-3 text-xs text-emerald-900 flex items-center justify-between shadow-2xs"
          >
            <div class="flex items-center gap-2 min-w-0">
              <Flame class="w-4 h-4 text-amber-500 shrink-0" />
              <div class="truncate">
                <span class="font-bold text-[10px] uppercase tracking-wider text-emerald-800 block">Generated From Trending Topic</span>
                <span class="truncate font-semibold">{{ currentDraft.trend_item?.title || currentDraft.title }}</span>
              </div>
            </div>
            <a
              v-if="currentDraft.trend_item?.url"
              :href="currentDraft.trend_item.url"
              target="_blank"
              rel="noopener noreferrer"
              class="shrink-0 text-[10px] font-semibold text-emerald-800 hover:underline flex items-center gap-1 bg-white px-2 py-1 rounded-lg border border-emerald-200"
            >
              <span>Source</span>
              <ExternalLink class="w-2.5 h-2.5" />
            </a>
          </div>

          <!-- LinkedIn Editor Pane -->
          <div class="bg-white border border-slate-200/80 rounded-3xl p-5 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-[#0A66C2] flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-[#0A66C2]"></span>
                LinkedIn Copy (Long-form)
              </span>
              <DynamicCharMeter platform="linkedin" :current-length="linkedinText.length" />
            </div>
            <textarea
              v-model="linkedinText"
              rows="8"
              class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-3.5 text-xs text-slate-800 focus:outline-none focus:border-slate-400 leading-relaxed font-sans"
            ></textarea>
          </div>

          <!-- X Editor Pane -->
          <div class="bg-white border border-slate-200/80 rounded-3xl p-5 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-slate-900"></span>
                X (Twitter) Copy
              </span>
              <DynamicCharMeter platform="x_twitter" :current-length="xText.length" />
            </div>
            <textarea
              v-model="xText"
              rows="5"
              class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-3.5 text-xs text-slate-800 focus:outline-none focus:border-slate-400 leading-relaxed font-sans"
            ></textarea>
          </div>
        </div>

        <!-- Right: Native Side-by-Side Live Previews -->
        <div class="lg:col-span-7 space-y-5">
          <LinkedInCardPreview
            :content="linkedinText"
            :media-url="mediaPreviewUrl"
            author-name="Alex Rivera"
            author-headline="Founder & Lead AI Systems Architect"
          />

          <XPostPreview
            :content="xText"
            :media-url="mediaPreviewUrl"
            author-name="Alex Rivera"
            author-handle="@alexrivera_tech"
          />
        </div>
      </div>

      <!-- AI Draft Generation Modal (Trending Topics & Custom Modes) -->
      <div
        v-if="showNewModal"
        class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4"
      >
        <div class="bg-white border border-slate-200 rounded-3xl p-6 max-w-2xl w-full space-y-5 shadow-2xl max-h-[90vh] overflow-y-auto">
          <!-- Modal Header -->
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-full bg-[#9EE493]/20 flex items-center justify-center">
                <Sparkles class="w-4 h-4 text-emerald-700" />
              </div>
              <div>
                <h3 class="font-display font-bold text-slate-900 text-base">
                  Generate Posts with AI & Save to Account
                </h3>
                <p class="text-[11px] text-slate-500">Dual-platform synthesis optimized for LinkedIn and X</p>
              </div>
            </div>
            <button @click="showNewModal = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
              <CloseIcon class="w-5 h-5" />
            </button>
          </div>

          <!-- Mode Tabs -->
          <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-full w-fit">
            <button
              @click="modalTab = 'trends'"
              :class="[
                'px-4 py-1.5 rounded-full text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer',
                modalTab === 'trends' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'
              ]"
            >
              <Flame class="w-3.5 h-3.5 text-amber-500" />
              <span>Trending Topics ({{ trendingTopics.length }})</span>
            </button>
            <button
              @click="modalTab = 'custom'"
              :class="[
                'px-4 py-1.5 rounded-full text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer',
                modalTab === 'custom' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'
              ]"
            >
              <Plus class="w-3.5 h-3.5 text-emerald-600" />
              <span>Custom Topic / Prompt</span>
            </button>
          </div>

          <!-- TAB 1: Trending Topics Selector -->
          <div v-if="modalTab === 'trends'" class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
              <!-- Filter Buttons -->
              <div class="flex items-center gap-1.5">
                <button
                  @click="trendingFilter = 'all'"
                  :class="[
                    'px-3 py-1 rounded-full text-xs font-medium transition cursor-pointer',
                    trendingFilter === 'all' ? 'bg-slate-900 text-white font-semibold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                  ]"
                >
                  All Topics
                </button>
                <button
                  @click="trendingFilter = 'liked'"
                  :class="[
                    'px-3 py-1 rounded-full text-xs font-medium transition flex items-center gap-1 cursor-pointer',
                    trendingFilter === 'liked' ? 'bg-amber-400 text-slate-900 font-semibold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                  ]"
                >
                  <Star class="w-3 h-3" />
                  <span>Liked Topics ({{ likedTopicsCount }})</span>
                </button>
              </div>

              <!-- Search Bar -->
              <div class="relative w-full sm:w-64">
                <Search class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5" />
                <input
                  v-model="topicSearchQuery"
                  type="text"
                  placeholder="Search trending topics..."
                  class="w-full bg-slate-50 border border-slate-200 rounded-full pl-8 pr-3 py-1.5 text-xs text-slate-800 focus:outline-none focus:border-slate-400"
                />
              </div>
            </div>

            <!-- Scrollable List of Trending Topics -->
            <div class="border border-slate-200/80 rounded-2xl max-h-60 overflow-y-auto divide-y divide-slate-100 bg-slate-50/50">
              <div
                v-for="t in displayedTrendingTopics"
                :key="t.id"
                @click="selectedTrendingTopic = t"
                :class="[
                  'p-3.5 transition cursor-pointer flex items-start gap-3',
                  selectedTrendingTopic?.id === t.id
                    ? 'bg-emerald-50/90 border-l-4 border-l-emerald-500'
                    : 'hover:bg-white'
                ]"
              >
                <!-- Radio Indicator -->
                <div class="mt-0.5">
                  <div
                    :class="[
                      'w-4 h-4 rounded-full border flex items-center justify-center transition',
                      selectedTrendingTopic?.id === t.id
                        ? 'border-emerald-600 bg-emerald-600 text-white'
                        : 'border-slate-300 bg-white'
                    ]"
                  >
                    <Check v-if="selectedTrendingTopic?.id === t.id" class="w-2.5 h-2.5" />
                  </div>
                </div>

                <!-- Topic Details -->
                <div class="min-w-0 flex-1 space-y-1">
                  <div class="flex items-center gap-2">
                    <span class="text-[10px] font-mono font-semibold px-2 py-0.2 rounded-full bg-amber-100 text-amber-800">
                      {{ Number(t.engagement_score || 94).toFixed(0) }} Score
                    </span>
                    <span v-if="t.is_liked_topic" class="text-[10px] font-semibold text-amber-600 flex items-center gap-0.5">
                      <Star class="w-2.5 h-2.5 fill-amber-500 text-amber-500" />
                      <span>Liked Topic</span>
                    </span>
                    <span class="text-[10px] text-slate-400 truncate">
                      {{ t.trend_source?.name || 'Hacker News' }}
                    </span>
                  </div>

                  <h4 class="text-xs font-bold text-slate-900 leading-snug">
                    {{ t.title }}
                  </h4>

                  <p class="text-[11px] text-slate-500 line-clamp-2 leading-relaxed">
                    {{ t.summary || t.content || 'Technical retrospective covering systems architecture and performance.' }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- TAB 2: Custom Prompt Input -->
          <div v-else class="space-y-2">
            <label class="text-xs font-semibold text-slate-700">Custom Topic Prompt or Article Idea</label>
            <textarea
              v-model="newTopicPrompt"
              rows="4"
              placeholder="e.g. Why PostgreSQL 17 logical replication improvements make change data capture significantly cheaper..."
              class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-3.5 text-xs text-slate-800 focus:outline-none focus:border-slate-400 font-sans"
            ></textarea>
          </div>

          <!-- Tone & Platform Controls -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
            <!-- Tone Selection -->
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-800">Editorial Brand Voice / Tone</label>
              <select
                v-model="selectedTone"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-slate-400"
              >
                <option v-for="tone in toneOptions" :key="tone.id" :value="tone.id">
                  {{ tone.label }} — {{ tone.desc }}
                </option>
              </select>
            </div>

            <!-- Target Channels -->
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-800">Target Social Channels</label>
              <div class="flex items-center gap-3 pt-1 text-xs">
                <label class="flex items-center gap-1.5 cursor-pointer font-medium text-slate-700">
                  <input
                    type="checkbox"
                    value="linkedin"
                    v-model="selectedPlatforms"
                    class="rounded text-emerald-600 focus:ring-emerald-400"
                  />
                  <span>LinkedIn</span>
                </label>
                <label class="flex items-center gap-1.5 cursor-pointer font-medium text-slate-700">
                  <input
                    type="checkbox"
                    value="x_twitter"
                    v-model="selectedPlatforms"
                    class="rounded text-emerald-600 focus:ring-emerald-400"
                  />
                  <span>X (Twitter)</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Missing Key Warning inside Modal -->
          <div v-if="!hasAiKey" class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 flex items-center justify-between">
            <span>AI synthesis requires a configured API key in Settings.</span>
            <Link href="/settings" class="font-semibold underline text-amber-900">Go to Settings</Link>
          </div>

          <!-- Modal Action Buttons -->
          <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
            <button
              @click="showNewModal = false"
              class="px-4 py-2 rounded-full text-xs font-semibold text-slate-600 hover:bg-slate-100 transition cursor-pointer"
            >
              Cancel
            </button>

            <button
              @click="generateNewAiDraft"
              :disabled="!hasAiKey || isGenerating || (modalTab === 'trends' ? !selectedTrendingTopic : !newTopicPrompt)"
              class="px-5 py-2 rounded-full bg-[#9EE493] hover:bg-[#8ee082] font-semibold text-xs text-slate-900 shadow-sm transition disabled:opacity-50 flex items-center gap-2 cursor-pointer"
            >
              <Sparkles class="w-3.5 h-3.5 text-slate-900" :class="{ 'animate-spin': isGenerating }" />
              <span>
                {{
                  isGenerating
                    ? 'Synthesizing...'
                    : modalTab === 'trends'
                    ? 'Generate AI Post from Topic & Save'
                    : 'Generate Custom Draft & Save'
                }}
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
