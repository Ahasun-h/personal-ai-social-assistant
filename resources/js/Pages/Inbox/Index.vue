<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
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
  X as CloseIcon,
} from 'lucide-vue-next';

interface DraftVersion {
  id: string;
  platform: string;
  content: string;
  character_count?: number;
}

interface DraftItem {
  id: string;
  title: string;
  status: string;
  versions?: DraftVersion[];
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
}

const props = withDefaults(defineProps<Props>(), {
  drafts: () => [],
  accounts: () => [],
  categories: () => [],
});

const selectedDraftId = ref<string>(props.drafts[0]?.id || '');

const currentDraft = computed(() => {
  return props.drafts.find((d) => d.id === selectedDraftId.value) || props.drafts[0] || null;
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

const isPublishing = ref(false);
const isApproving = ref(false);
const isGenerating = ref(false);
const newTopicPrompt = ref('');
const showNewModal = ref(false);

const approveDraft = async () => {
  if (!currentDraft.value) return;
  isApproving.value = true;
  await fetch(`/api/v1/drafts/${currentDraft.value.id}/approve`, { method: 'POST' });
  isApproving.value = false;
  router.reload();
};

const publishImmediately = async () => {
  if (!currentDraft.value) return;
  if (currentDraft.value.status !== 'approved') {
    alert('Mandatory Security Rule: Draft must be explicitly approved by owner before publishing.');
    return;
  }
  isPublishing.value = true;
  await fetch(`/api/v1/drafts/${currentDraft.value.id}/publish-now`, { method: 'POST' });
  isPublishing.value = false;
  alert('Post dispatched to platform queues successfully!');
  router.reload();
};

const saveDraftEdits = async () => {
  if (!currentDraft.value) return;
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
  alert('Draft revisions saved successfully.');
  router.reload();
};

const generateNewAiDraft = async () => {
  if (!newTopicPrompt.value) return;
  isGenerating.value = true;
  await fetch('/api/v1/drafts/ai/generate', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      topic: newTopicPrompt.value,
      tone: 'pragmatic_architect',
      platforms: ['linkedin', 'x_twitter'],
    }),
  });
  isGenerating.value = false;
  showNewModal.value = false;
  newTopicPrompt.value = '';
  router.reload();
};
</script>

<template>
  <AppLayout title="Review Inbox">
    <Head title="Review Inbox" />

    <div class="space-y-6">
      <!-- Page Header with Action Pills -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-2">
        <div>
          <h1 class="text-xl sm:text-2xl font-display font-bold text-slate-900 tracking-tight flex items-center gap-2">
            <Layers class="w-5 h-5 text-emerald-600" />
            Review Inbox & Dual-Platform Preview
          </h1>
          <p class="text-xs text-slate-500 mt-1">
            Evaluate, edit, regenerate, and explicitly approve posts before publishing.
          </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
          <button
            @click="showNewModal = true"
            class="flex items-center gap-1.5 text-xs font-semibold px-4 py-2 rounded-full bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/80 shadow-sm transition"
          >
            <Plus class="w-3.5 h-3.5 text-emerald-600" />
            <span>Generate New AI Draft</span>
          </button>

          <button
            @click="saveDraftEdits"
            class="flex items-center gap-1.5 text-xs font-semibold px-4 py-2 rounded-full bg-white hover:bg-slate-50 border border-slate-200/80 text-slate-700 shadow-sm transition"
          >
            <Save class="w-3.5 h-3.5 text-slate-500" />
            <span>Save Copy</span>
          </button>

          <button
            @click="approveDraft"
            :disabled="isApproving || currentDraft?.status === 'approved'"
            class="flex items-center gap-1.5 text-xs font-semibold px-5 py-2 rounded-full bg-[#9EE493] hover:bg-[#8ee082] text-slate-900 shadow-sm transition disabled:opacity-50"
          >
            <CheckCircle class="w-4 h-4" />
            <span>{{ currentDraft?.status === 'approved' ? 'Approved ✓' : 'Approve Draft' }}</span>
          </button>

          <button
            @click="publishImmediately"
            :disabled="isPublishing"
            class="flex items-center gap-1.5 text-xs font-semibold px-5 py-2 rounded-full bg-slate-900 hover:bg-slate-800 text-white shadow-sm transition disabled:opacity-50"
          >
            <Send class="w-4 h-4 text-[#9EE493]" />
            <span>{{ isPublishing ? 'Publishing...' : 'Publish Now' }}</span>
          </button>
        </div>
      </div>

      <!-- Draft Selector Chips -->
      <div v-if="drafts.length > 0" class="flex gap-2 overflow-x-auto pb-1 scrollbar-none">
        <button
          v-for="d in drafts"
          :key="d.id"
          @click="selectDraft(d)"
          :class="[
            'px-4 py-2 rounded-full text-xs font-medium whitespace-nowrap transition flex items-center gap-2 shadow-sm',
            selectedDraftId === d.id
              ? 'bg-slate-900 text-white font-semibold'
              : 'bg-white text-slate-600 border border-slate-200/80 hover:bg-slate-50'
          ]"
        >
          <span
            :class="[
              'w-2 h-2 rounded-full',
              d.status === 'approved' ? 'bg-[#10B981]' : 'bg-amber-400'
            ]"
          ></span>
          <span>{{ d.title }}</span>
          <span class="text-[10px] font-mono opacity-60">({{ d.status }})</span>
        </button>
      </div>

      <!-- Notice badge for X metered links -->
      <div class="bg-amber-50 border border-amber-200/70 rounded-2xl p-3.5 flex items-center justify-between text-xs text-amber-800 shadow-xs">
        <div class="flex items-center gap-2.5">
          <AlertTriangle class="w-4 h-4 text-amber-600 shrink-0" />
          <span>Publishing on X incurs standard tier metered fees ($0.015/post). Mandatory human approval active.</span>
        </div>
        <span class="font-mono text-[11px] bg-amber-100 text-amber-800 px-2.5 py-0.5 rounded-full font-medium">Zero Unauthorized Automation</span>
      </div>

      <!-- Main Editor & Split Preview Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Left: Draft Editors -->
        <div class="lg:col-span-5 space-y-5">
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

      <!-- New AI Draft Generation Modal -->
      <div
        v-if="showNewModal"
        class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4"
      >
        <div class="bg-white border border-slate-200 rounded-3xl p-6 max-w-lg w-full space-y-4 shadow-2xl">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="font-display font-bold text-slate-900 text-base flex items-center gap-2">
              <Sparkles class="w-5 h-5 text-emerald-600" />
              Generate New Multi-Platform AI Draft
            </h3>
            <button @click="showNewModal = false" class="text-slate-400 hover:text-slate-600">
              <CloseIcon class="w-5 h-5" />
            </button>
          </div>

          <p class="text-xs text-slate-500">
            Enter a topic, architecture thesis, or article link. The AI engine will produce LinkedIn long-form and punchy X thread copy enforcing your brand tone guidelines.
          </p>

          <textarea
            v-model="newTopicPrompt"
            rows="4"
            placeholder="e.g. Why PostgreSQL 17 logical replication improvements make change data capture significantly cheaper..."
            class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-3.5 text-xs text-slate-800 focus:outline-none focus:border-slate-400 font-sans"
          ></textarea>

          <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
            <button
              @click="showNewModal = false"
              class="px-4 py-2 rounded-full text-xs font-semibold text-slate-600 hover:bg-slate-100 transition"
            >
              Cancel
            </button>
            <button
              @click="generateNewAiDraft"
              :disabled="isGenerating || !newTopicPrompt"
              class="px-5 py-2 rounded-full bg-[#9EE493] hover:bg-[#8ee082] font-semibold text-xs text-slate-900 shadow-sm transition disabled:opacity-50"
            >
              {{ isGenerating ? 'Generating...' : 'Generate Multi-Platform Draft' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
