<script setup lang="ts">
import { ref } from 'vue';
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
} from 'lucide-vue-next';

interface DraftItem {
  id: string;
  title: string;
  status: string;
  linkedinContent: string;
  xContent: string;
  mediaUrl?: string;
  costEstimate: number;
}

const activeDraft = ref<DraftItem>({
  id: 'drf_01h8923jk291',
  title: 'Autonomous Multi-Agent SDLC in 2026',
  status: 'in_review',
  linkedinContent: `Building production-ready autonomous systems in 2026 is 10% prompt engineering and 90% deterministic state machines.\n\nMost teams make the mistake of using single-agent loops with unbounded execution. Here is the 5-stage architecture that actually scales:\n\n1. Intake & Discovery (Unambiguous Given-When-Then criteria)\n2. Universal Architecture & OpenAPI Contracts\n3. Isolated Sandbox TDD Execution Loops\n4. Maker-Checker Verification Gates\n5. Immutable Audit Trails & Metered Cost Caps\n\nWhat is your biggest blocker when running agents in production?\n\n#SoftwareEngineering #AIAgents #AutonomousSDLC`,
  xContent: `Building production AI agents is 10% prompting and 90% deterministic state machines.\n\nHere's why maker-checker pipelines outperform unbounded loops: 👇\n\n#DevOps #AI`,
  mediaUrl: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1200&q=80',
  costEstimate: 0.042,
});

const isPublishing = ref(false);
const isRegenerating = ref(false);

const approveAndSchedule = () => {
  activeDraft.value.status = 'approved';
  alert('Draft approved! Scheduled for next available calendar slot.');
};

const publishImmediately = async () => {
  if (activeDraft.value.status !== 'approved') {
    alert('Strict Rule: Please click "Approve" before publishing.');
    return;
  }
  isPublishing.value = true;
  setTimeout(() => {
    isPublishing.value = false;
    activeDraft.value.status = 'published';
    alert('Dispatched to LinkedIn and X successfully!');
  }, 1200);
};

const regenerateCopy = () => {
  isRegenerating.value = true;
  setTimeout(() => {
    isRegenerating.value = false;
    activeDraft.value.xContent = "Stop using unconstrained agent loops.\n\n2026 belongs to maker-checker SDLC pipelines with deterministic state machines and isolated test loops.\n\n#AI #Engineering";
  }, 800);
};
</script>

<template>
  <AppLayout>
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
          <!-- Cost badge -->
          <div class="text-xs font-mono text-gray-400 bg-white/5 px-3 py-1.5 rounded-lg border border-white/10">
            Est. Cost: <span class="text-cyan-400 font-semibold">${{ activeDraft.costEstimate.toFixed(3) }}</span>
          </div>

          <button
            @click="regenerateCopy"
            :disabled="isRegenerating"
            class="flex items-center gap-1.5 text-xs font-medium px-3.5 py-1.5 rounded-lg bg-surface-card hover:bg-surface-card-hover border border-white/10 text-gray-200 transition"
          >
            <RefreshCw class="w-3.5 h-3.5" :class="{'animate-spin': isRegenerating}" />
            Regenerate
          </button>

          <button
            @click="approveAndSchedule"
            class="flex items-center gap-1.5 text-xs font-semibold px-4 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/30 transition"
          >
            <CheckCircle class="w-4 h-4" />
            {{ activeDraft.status === 'approved' ? 'Approved ✓' : 'Approve Draft' }}
          </button>

          <button
            @click="publishImmediately"
            :disabled="isPublishing"
            class="flex items-center gap-1.5 text-xs font-semibold px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-600/30 transition"
          >
            <Send class="w-4 h-4" />
            {{ isPublishing ? 'Publishing...' : 'Publish Now' }}
          </button>
        </div>
      </div>

      <!-- Warning notice for X metered links -->
      <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-3 flex items-center justify-between text-xs text-amber-300">
        <div class="flex items-center gap-2">
          <AlertTriangle class="w-4 h-4 text-amber-400 flex-shrink-0" />
          <span>Notice: Publishing on X incurs standard tier metered fees ($0.015/post). No unapproved automation runs.</span>
        </div>
        <span class="font-mono text-[11px] bg-amber-500/20 px-2 py-0.5 rounded">Human-in-the-Loop Enforced</span>
      </div>

      <!-- Main Editor & Split Preview Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left: Draft Editors -->
        <div class="lg:col-span-5 space-y-4">
          <!-- LinkedIn Editor Pane -->
          <div class="bg-surface-card border border-white/10 rounded-xl p-4 space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-blue-400 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                LinkedIn Copy (Long-form)
              </span>
              <DynamicCharMeter platform="linkedin" :current-length="activeDraft.linkedinContent.length" />
            </div>
            <textarea
              v-model="activeDraft.linkedinContent"
              rows="7"
              class="w-full bg-canvas border border-white/10 rounded-lg p-3 text-xs text-gray-200 focus:outline-none focus:border-indigo-500 leading-relaxed font-sans"
            ></textarea>
          </div>

          <!-- X Editor Pane -->
          <div class="bg-surface-card border border-white/10 rounded-xl p-4 space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-gray-200 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-white"></span>
                X (Twitter) Copy
              </span>
              <DynamicCharMeter platform="x_twitter" :current-length="activeDraft.xContent.length" />
            </div>
            <textarea
              v-model="activeDraft.xContent"
              rows="4"
              class="w-full bg-canvas border border-white/10 rounded-lg p-3 text-xs text-gray-200 focus:outline-none focus:border-indigo-500 leading-relaxed font-sans"
            ></textarea>
          </div>

          <!-- Media Controls -->
          <div class="bg-surface-card border border-white/10 rounded-xl p-4 space-y-2">
            <span class="text-xs font-bold text-gray-200 flex items-center gap-1.5">
              <Sparkles class="w-3.5 h-3.5 text-cyan-400" />
              Attached Media Asset
            </span>
            <div class="flex items-center gap-3">
              <img :src="activeDraft.mediaUrl" alt="Thumb" class="w-16 h-12 rounded object-cover border border-white/10" />
              <div class="flex-1 text-[11px] text-gray-400 space-y-0.5">
                <p class="text-gray-200 font-medium">Flux.1 Schnell AI Render (16:9)</p>
                <p>Resolution: 1200x675 • Format: JPEG</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Side-by-Side Platform Previews -->
        <div class="lg:col-span-7 space-y-4">
          <div class="flex items-center justify-between text-xs text-gray-400 pb-1">
            <span class="font-semibold uppercase tracking-wider text-[11px]">Real-Time Native Previews</span>
            <span>Desktop Layout Emulation</span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- LinkedIn Native Preview -->
            <div class="space-y-1.5">
              <div class="text-[11px] font-semibold text-blue-400 flex items-center gap-1">
                LinkedIn Feed Preview
              </div>
              <LinkedInCardPreview
                :content="activeDraft.linkedinContent"
                :media-url="activeDraft.mediaUrl"
              />
            </div>

            <!-- X Native Preview -->
            <div class="space-y-1.5">
              <div class="text-[11px] font-semibold text-gray-300 flex items-center gap-1">
                X (Twitter) Feed Preview
              </div>
              <XPostPreview
                :content="activeDraft.xContent"
                :media-url="activeDraft.mediaUrl"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
