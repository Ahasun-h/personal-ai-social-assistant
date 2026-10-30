<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Image as ImageIcon, Sparkles, Upload, RefreshCw } from 'lucide-vue-next';

const prompt = ref('Minimalist dark mode isometric diagram of two autonomous AI agents collaborating across a terminal code editor, glowing cyan and indigo wires');
const aspectRatio = ref('16:9');
const isGenerating = ref(false);

const generate = () => {
  isGenerating.value = true;
  setTimeout(() => {
    isGenerating.value = false;
    alert('AI Media generated successfully at optimal 16:9 aspect ratio!');
  }, 1000);
};
</script>

<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between pb-4 border-b border-white/10">
        <div>
          <h1 class="text-xl font-bold text-white flex items-center gap-2">
            <ImageIcon class="w-5 h-5 text-indigo-400" />
            Media Studio & Self-Service Regeneration
          </h1>
          <p class="text-xs text-gray-400 mt-0.5">Generate visual assets independently of text, switch versions, crop aspect ratios, or upload custom files.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Controls -->
        <div class="lg:col-span-5 bg-surface-card border border-white/10 rounded-xl p-4 space-y-4">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-gray-300">Visual Prompt Editor</label>
            <textarea
              v-model="prompt"
              rows="4"
              class="w-full bg-canvas border border-white/10 rounded-lg p-3 text-xs text-gray-200 focus:outline-none focus:border-indigo-500 leading-relaxed font-sans"
            ></textarea>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-gray-300">Platform Aspect Ratio Preset</label>
            <div class="grid grid-cols-3 gap-2">
              <button
                @click="aspectRatio = '16:9'"
                class="py-2 text-xs rounded-lg font-semibold border transition"
                :class="aspectRatio === '16:9' ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-surface-elevated text-gray-400 border-white/10'"
              >
                16:9 (X / Feed)
              </button>
              <button
                @click="aspectRatio = '1:1'"
                class="py-2 text-xs rounded-lg font-semibold border transition"
                :class="aspectRatio === '1:1' ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-surface-elevated text-gray-400 border-white/10'"
              >
                1:1 (Square)
              </button>
              <button
                @click="aspectRatio = '4:5'"
                class="py-2 text-xs rounded-lg font-semibold border transition"
                :class="aspectRatio === '4:5' ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-surface-elevated text-gray-400 border-white/10'"
              >
                4:5 (LinkedIn Mobile)
              </button>
            </div>
          </div>

          <div class="flex items-center gap-3 pt-2">
            <button
              @click="generate"
              :disabled="isGenerating"
              class="flex-1 flex items-center justify-center gap-2 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-lg shadow-indigo-600/30 transition"
            >
              <Sparkles class="w-4 h-4" />
              {{ isGenerating ? 'Generating...' : 'Generate Iteration ($0.040)' }}
            </button>

            <button class="px-4 py-2 rounded-lg border border-white/10 text-xs font-medium hover:bg-white/5 transition flex items-center gap-1.5 text-gray-300">
              <Upload class="w-3.5 h-3.5" /> Upload File
            </button>
          </div>
        </div>

        <!-- Preview Canvas -->
        <div class="lg:col-span-7 bg-surface-card border border-white/10 rounded-xl p-4 flex flex-col items-center justify-center space-y-4">
          <div class="w-full rounded-lg overflow-hidden border border-white/10 bg-canvas flex items-center justify-center max-h-[360px]">
            <img
              src="https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1200&q=80"
              alt="Generated preview"
              class="w-full h-full object-cover"
            />
          </div>
          <div class="text-[11px] text-gray-400 flex items-center justify-between w-full px-2">
            <span>Model: Flux.1 Schnell • Format: WebP High-DPI</span>
            <span>Version: v2 (Active) • Cost: $0.040</span>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
