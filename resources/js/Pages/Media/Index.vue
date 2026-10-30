<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Image as ImageIcon, Sparkles, Upload, RefreshCw, Layers } from 'lucide-vue-next';

interface MediaAsset {
  id: string;
  filename: string;
  path: string;
  width?: number;
  height?: number;
  aspect_ratio?: string;
  is_ai_generated: boolean;
  metadata?: any;
}

interface Props {
  assets?: MediaAsset[];
  budget?: any;
}

const props = withDefaults(defineProps<Props>(), {
  assets: () => [],
  budget: () => ({}),
});

const prompt = ref('Clean modern architectural diagram contrasting distributed microservices network chatter with an in-process modular monolith boundary, soft minimalist aesthetic');
const aspectRatio = ref('16:9');
const isGenerating = ref(false);

const generate = async () => {
  if (!prompt.value) return;
  isGenerating.value = true;
  await fetch('/api/v1/media/ai/generate', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      prompt: prompt.value,
      aspect_ratio: aspectRatio.value,
      provider: 'flux-schnell',
    }),
  });
  isGenerating.value = false;
  router.reload();
};
</script>

<template>
  <AppLayout title="Media Studio">
    <Head title="Media Studio & Asset Vault" />

    <div class="space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2">
        <div>
          <h1 class="text-xl sm:text-2xl font-display font-bold text-slate-900 tracking-tight flex items-center gap-2">
            <ImageIcon class="w-5 h-5 text-emerald-600" />
            Media Studio & Asset Vault
          </h1>
          <p class="text-xs text-slate-500 mt-1">
            Generate visual media independently of text, switch versions, crop aspect ratios, and manage uploaded assets.
          </p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Controls & Generator -->
        <div class="lg:col-span-5 bg-white border border-slate-200/80 rounded-3xl p-6 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-4">
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-slate-700">Visual Prompt Editor</label>
            <textarea
              v-model="prompt"
              rows="4"
              class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-3.5 text-xs text-slate-800 focus:outline-none focus:border-slate-400 leading-relaxed font-sans"
            ></textarea>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-slate-700">Aspect Ratio Presets</label>
            <div class="grid grid-cols-3 gap-2">
              <button
                @click="aspectRatio = '16:9'"
                class="py-2 text-xs rounded-full font-semibold border transition shadow-xs"
                :class="aspectRatio === '16:9' ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
              >
                16:9 (Landscape)
              </button>
              <button
                @click="aspectRatio = '1:1'"
                class="py-2 text-xs rounded-full font-semibold border transition shadow-xs"
                :class="aspectRatio === '1:1' ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
              >
                1:1 (Square)
              </button>
              <button
                @click="aspectRatio = '4:5'"
                class="py-2 text-xs rounded-full font-semibold border transition shadow-xs"
                :class="aspectRatio === '4:5' ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
              >
                4:5 (Portrait)
              </button>
            </div>
          </div>

          <button
            @click="generate"
            :disabled="isGenerating || !prompt"
            class="w-full py-2.5 rounded-full bg-[#9EE493] hover:bg-[#8ee082] font-semibold text-xs text-slate-900 shadow-sm flex items-center justify-center gap-2 transition disabled:opacity-50"
          >
            <Sparkles class="w-4 h-4 text-slate-900" :class="{'animate-spin': isGenerating}" />
            <span>{{ isGenerating ? 'Synthesizing with Flux...' : 'Generate Asset ($0.003)' }}</span>
          </button>
        </div>

        <!-- Asset Gallery -->
        <div class="lg:col-span-7 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider font-mono flex items-center gap-2">
              <Layers class="w-4 h-4 text-emerald-600" />
              Saved Assets & Generation Vault ({{ assets.length }})
            </h3>
          </div>

          <div v-if="assets.length > 0" class="grid grid-cols-2 gap-4">
            <div
              v-for="asset in assets"
              :key="asset.id"
              class="group relative rounded-3xl overflow-hidden border border-slate-200/80 bg-white shadow-[0_4px_20px_rgba(0,0,0,0.03)]"
            >
              <img
                :src="asset.path"
                :alt="asset.filename"
                class="w-full h-44 object-cover group-hover:scale-105 transition duration-300"
              />
              <div class="p-3.5 bg-white border-t border-slate-100 space-y-1">
                <p class="text-xs font-semibold text-slate-900 truncate">{{ asset.filename }}</p>
                <div class="flex justify-between items-center text-[10px] text-slate-400 font-mono">
                  <span>{{ asset.width }}×{{ asset.height }}</span>
                  <span class="text-emerald-700 font-medium">{{ asset.is_ai_generated ? 'AI Generated' : 'Uploaded' }}</span>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-16 text-slate-400 text-xs font-mono bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            No assets in vault. Generate visual prompts above to populate your media library.
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
