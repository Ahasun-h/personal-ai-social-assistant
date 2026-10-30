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

const prompt = ref('Clean modern architectural diagram contrasting distributed microservices network chatter with an in-process modular monolith boundary, dark neon aesthetic');
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

    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between pb-4 border-b border-white/10">
        <div>
          <h1 class="text-xl font-bold text-white flex items-center gap-2">
            <ImageIcon class="w-5 h-5 text-indigo-400" />
            Media Studio & Asset Vault
          </h1>
          <p class="text-xs text-gray-400 mt-0.5">
            Generate visual media independently of text, switch versions, crop aspect ratios, and manage uploaded assets.
          </p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Controls & Generator -->
        <div class="lg:col-span-5 bg-surface-elevated/70 border border-white/10 rounded-2xl p-5 space-y-4 backdrop-blur-sm">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-gray-300 uppercase tracking-wider font-mono">Visual Prompt Editor</label>
            <textarea
              v-model="prompt"
              rows="4"
              class="w-full bg-[#0B0F19] border border-white/10 rounded-xl p-3 text-xs text-gray-200 focus:outline-none focus:border-indigo-500 leading-relaxed font-sans"
            ></textarea>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-gray-300 uppercase tracking-wider font-mono">Aspect Ratio Presets</label>
            <div class="grid grid-cols-3 gap-2">
              <button
                @click="aspectRatio = '16:9'"
                class="py-2 text-xs rounded-xl font-semibold border transition"
                :class="aspectRatio === '16:9' ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-surface-card text-gray-400 border-white/10'"
              >
                16:9 (Landscape)
              </button>
              <button
                @click="aspectRatio = '1:1'"
                class="py-2 text-xs rounded-xl font-semibold border transition"
                :class="aspectRatio === '1:1' ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-surface-card text-gray-400 border-white/10'"
              >
                1:1 (Square)
              </button>
              <button
                @click="aspectRatio = '4:5'"
                class="py-2 text-xs rounded-xl font-semibold border transition"
                :class="aspectRatio === '4:5' ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-surface-card text-gray-400 border-white/10'"
              >
                4:5 (Portrait)
              </button>
            </div>
          </div>

          <button
            @click="generate"
            :disabled="isGenerating || !prompt"
            class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-semibold text-xs text-white shadow-lg shadow-indigo-600/30 flex items-center justify-center gap-2 transition disabled:opacity-50"
          >
            <Sparkles class="w-4 h-4 text-cyan-300" :class="{'animate-spin': isGenerating}" />
            <span>{{ isGenerating ? 'Synthesizing with Flux...' : 'Generate Asset ($0.003)' }}</span>
          </button>
        </div>

        <!-- Asset Gallery -->
        <div class="lg:col-span-7 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold text-gray-200 uppercase tracking-wider font-mono flex items-center gap-2">
              <Layers class="w-4 h-4 text-indigo-400" />
              Saved Assets & Generation Vault ({{ assets.length }})
            </h3>
          </div>

          <div v-if="assets.length > 0" class="grid grid-cols-2 gap-4">
            <div
              v-for="asset in assets"
              :key="asset.id"
              class="group relative rounded-2xl overflow-hidden border border-white/10 bg-surface-card/60"
            >
              <img
                :src="asset.path"
                :alt="asset.filename"
                class="w-full h-44 object-cover group-hover:scale-105 transition duration-300"
              />
              <div class="p-3 bg-surface-elevated/90 border-t border-white/5 space-y-1">
                <p class="text-xs font-medium text-white truncate">{{ asset.filename }}</p>
                <div class="flex justify-between items-center text-[10px] text-gray-400 font-mono">
                  <span>{{ asset.width }}×{{ asset.height }}</span>
                  <span class="text-indigo-300">{{ asset.is_ai_generated ? 'AI Generated' : 'Uploaded' }}</span>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-16 text-gray-500 text-xs font-mono bg-surface-elevated/30 rounded-2xl border border-white/5">
            No assets in vault. Generate visual prompts above to populate your media library.
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
