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

    <div class="max-w-7xl mx-auto space-y-6">
      <!-- Page Header -->
      <div class="flex items-center justify-between pb-4 border-b border-white/10">
        <div>
          <h1 class="text-xl font-bold text-white flex items-center gap-2">
            <Layers class="w-5 h-5 text-indigo-400" />
            Review Inbox & Dual-Platform Preview
          </h1>
          <p class="text-xs text-gray-400 mt-0.5">
            Evaluate, edit, regenerate, and explicitly approve posts before publishing.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <button
            @click="showNewModal = true"
            class="flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/15 text-white border border-white/10 transition"
          >
            <Plus class="w-3.5 h-3.5 text-indigo-400" />
            <span>Generate New AI Draft</span>
          </button>

          <button
            @click="saveDraftEdits"
            class="flex items-center gap-1.5 text-xs font-medium px-3.5 py-1.5 rounded-lg bg-surface-card hover:bg-surface-card-hover border border-white/10 text-gray-200 transition"
          >
            <Save class="w-3.5 h-3.5 text-cyan-400" />
            Save Copy
          </button>

          <button
            @click="approveDraft"
            :disabled="isApproving || currentDraft?.status === 'approved'"
            class="flex items-center gap-1.5 text-xs font-semibold px-4 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/30 transition disabled:opacity-50"
          >
            <CheckCircle class="w-4 h-4" />
            {{ currentDraft?.status === 'approved' ? 'Approved ✓' : 'Approve Draft' }}
          </button>

          <button
            @click="publishImmediately"
            :disabled="isPublishing"
            class="flex items-center gap-1.5 text-xs font-semibold px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-600/30 transition disabled:opacity-50"
          >
            <Send class="w-4 h-4" />
            {{ isPublishing ? 'Publishing...' : 'Publish Now' }}
          </button>
        </div>
      </div>

      <!-- Draft Selector Chips -->
      <div v-if="drafts.length > 0" class="flex gap-2 overflow-x-auto pb-2 border-b border-white/5">
        <button
          v-for="d in drafts"
          :key="d.id"
          @click="selectDraft(d)"
          :class="[
            'px-3.5 py-1.5 rounded-xl text-xs font-medium whitespace-nowrap transition flex items-center gap-2',
            selectedDraftId === d.id
              ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/40 shadow-sm'
              : 'bg-surface-elevated/60 text-gray-400 border border-white/5 hover:text-white'
          ]"
        >
          <span
            :class="[
              'w-2 h-2 rounded-full',
              d.status === 'approved' ? 'bg-emerald-400' : 'bg-amber-400'
            ]"
          ></span>
          <span>{{ d.title }}</span>
          <span class="text-[10px] font-mono opacity-60">({{ d.status }})</span>
        </button>
      </div>

      <!-- Warning notice for X metered links -->
      <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-3 flex items-center justify-between text-xs text-amber-300">
        <div class="flex items-center gap-2">
          <AlertTriangle class="w-4 h-4 text-amber-400 flex-shrink-0" />
          <span>Notice: Publishing on X incurs standard tier metered fees ($0.015/post). Mandatory human approval active.</span>
        </div>
        <span class="font-mono text-[11px] bg-amber-500/20 px-2 py-0.5 rounded">Zero Unauthorized Automation</span>
      </div>

      <!-- Main Editor & Split Preview Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left: Draft Editors -->
        <div class="lg:col-span-5 space-y-4">
          <!-- LinkedIn Editor Pane -->
          <div class="bg-surface-elevated/70 border border-white/10 rounded-2xl p-4 space-y-2 backdrop-blur-sm">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-blue-400 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                LinkedIn Copy (Long-form)
              </span>
              <DynamicCharMeter platform="linkedin" :current-length="linkedinText.length" />
            </div>
            <textarea
              v-model="linkedinText"
              rows="8"
              class="w-full bg-[#0B0F19] border border-white/10 rounded-xl p-3 text-xs text-gray-200 focus:outline-none focus:border-indigo-500 leading-relaxed font-sans"
            ></textarea>
          </div>

          <!-- X Editor Pane -->
          <div class="bg-surface-elevated/70 border border-white/10 rounded-2xl p-4 space-y-2 backdrop-blur-sm">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-gray-200 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-white"></span>
                X (Twitter) Copy
              </span>
              <DynamicCharMeter platform="x_twitter" :current-length="xText.length" />
            </div>
            <textarea
              v-model="xText"
              rows="4"
              class="w-full bg-[#0B0F19] border border-white/10 rounded-xl p-3 text-xs text-gray-200 focus:outline-none focus:border-indigo-500 leading-relaxed font-sans"
            ></textarea>
          </div>
        </div>

        <!-- Right: Native Side-by-Side Live Previews -->
        <div class="lg:col-span-7 space-y-4">
          <LinkedInCardPreview
            :content="linkedinText"
            :media-url="mediaPreviewUrl"
            author-name="Alex Rivera, PE"
            author-title="Staff Distributed Systems Architect"
          />

          <XPostPreview
            :content="xText"
            :media-url="mediaPreviewUrl"
            author-name="Alex Rivera"
            author-handle="alexrivera_tech"
          />
        </div>
      </div>

      <!-- New AI Draft Generation Modal -->
      <div
        v-if="showNewModal"
        class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4"
      >
        <div class="bg-surface-elevated border border-white/10 rounded-2xl p-6 max-w-lg w-full space-y-4">
          <h3 class="font-bold text-white text-base flex items-center gap-2">
            <Sparkles class="w-5 h-5 text-indigo-400" />
            Generate New Multi-Platform AI Draft
          </h3>
          <p class="text-xs text-gray-400">
            Enter a topic, architecture thesis, or article link. The AI engine will produce LinkedIn long-form and punchy X thread copy enforcing your brand tone guidelines.
          </p>

          <textarea
            v-model="newTopicPrompt"
            rows="4"
            placeholder="e.g. Why PostgreSQL 17 logical replication improvements make change data capture significantly cheaper..."
            class="w-full bg-[#0B0F19] border border-white/10 rounded-xl p-3 text-sm text-white focus:outline-none focus:border-indigo-500 font-sans"
          ></textarea>

          <div class="flex justify-end gap-3 pt-3 border-t border-white/10">
            <button
              @click="showNewModal = false"
              class="px-4 py-2 rounded-xl text-sm text-gray-400 hover:text-white"
            >
              Cancel
            </button>
            <button
              @click="generateNewAiDraft"
              :disabled="isGenerating || !newTopicPrompt"
              class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-semibold text-sm text-white shadow-lg shadow-indigo-600/30 transition disabled:opacity-50"
            >
              {{ isGenerating ? 'Generating...' : 'Generate Multi-Platform Draft' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
