<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import CanvaImageEditor from '@/Components/CanvaImageEditor.vue';
import ClipchampVideoEditor from '@/Components/ClipchampVideoEditor.vue';
import {
  X as CloseIcon,
  Video as VideoIcon,
  Image as ImageIcon,
  Check,
  Save,
  Sparkles,
} from 'lucide-vue-next';

interface MediaAsset {
  id: string;
  filename: string;
  path: string;
  mime_type?: string;
  width?: number;
  height?: number;
}

interface Props {
  isOpen: boolean;
  asset: MediaAsset | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'saved', newAsset: any): void;
}>();

const isSaving = ref(false);
const saveSuccess = ref(false);

// Mode Toggle: Canva Image Studio vs Clipchamp Video Studio
const editorMode = ref<'canva' | 'clipchamp'>('canva');

const isVideoAsset = (asset: MediaAsset | null): boolean => {
  if (!asset) return false;
  if (asset.mime_type?.startsWith('video/')) return true;
  const ext = asset.filename.split('.').pop()?.toLowerCase();
  return ['mp4', 'mov', 'webm', 'mkv'].includes(ext || '');
};

watch(
  () => props.asset,
  (newAsset) => {
    if (newAsset) {
      editorMode.value = isVideoAsset(newAsset) ? 'clipchamp' : 'canva';
    }
  },
  { immediate: true }
);

const handleSave = async (blob: Blob, filename: string) => {
  isSaving.value = true;
  const formData = new FormData();
  formData.append('file', blob, filename);
  formData.append('alt_text', `Edited in Synthex Studio: ${filename}`);

  try {
    const res = await fetch('/api/v1/media/upload', {
      method: 'POST',
      body: formData,
    });
    if (res.ok) {
      const data = await res.json();
      saveSuccess.value = true;
      setTimeout(() => {
        saveSuccess.value = false;
        emit('saved', data.data);
      }, 1000);
    }
  } catch (e) {
    console.error('Save failed:', e);
  } finally {
    isSaving.value = false;
  }
};
</script>

<template>
  <div v-if="isOpen && asset" class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-5 bg-slate-950/85 backdrop-blur-md">
    <!-- Studio Shell Modal Container -->
    <div class="relative w-full max-w-7xl h-[94vh] bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl flex flex-col overflow-hidden text-white font-sans">
      
      <!-- Studio Global Navigation Bar -->
      <div class="flex items-center justify-between px-6 py-3.5 border-b border-slate-800 shrink-0 bg-slate-950/90 backdrop-blur-sm">
        <!-- Brand & Active Mode Indicator -->
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-[#9EE493] to-emerald-400 text-slate-950 flex items-center justify-center font-bold shadow-sm">
            <Sparkles class="w-4 h-4 text-slate-950" />
          </div>

          <div>
            <div class="flex items-center gap-2">
              <span class="font-display font-bold text-sm tracking-tight text-white">
                Synthex Creative Studio
              </span>
              <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-slate-800 text-[#9EE493] border border-slate-700">
                {{ editorMode === 'canva' ? 'Canva Photo Engine' : 'Clipchamp Video Engine' }}
              </span>
            </div>
            <p class="text-[11px] text-slate-400 truncate max-w-xs sm:max-w-md">
              {{ asset.filename }}
            </p>
          </div>
        </div>

        <!-- Center Studio Mode Switcher -->
        <div class="flex items-center gap-1 bg-slate-900 p-1 rounded-2xl border border-slate-800">
          <button
            @click="editorMode = 'canva'"
            :class="[
              'flex items-center gap-1.5 px-4 py-1.5 rounded-xl text-xs font-semibold transition',
              editorMode === 'canva' ? 'bg-[#9EE493] text-slate-950 shadow-sm' : 'text-slate-400 hover:text-white'
            ]"
          >
            <ImageIcon class="w-3.5 h-3.5" />
            <span>Canva Photo Editor</span>
          </button>

          <button
            @click="editorMode = 'clipchamp'"
            :class="[
              'flex items-center gap-1.5 px-4 py-1.5 rounded-xl text-xs font-semibold transition',
              editorMode === 'clipchamp' ? 'bg-[#9EE493] text-slate-950 shadow-sm' : 'text-slate-400 hover:text-white'
            ]"
          >
            <VideoIcon class="w-3.5 h-3.5" />
            <span>Clipchamp Video Editor</span>
          </button>
        </div>

        <!-- Right Close & Status -->
        <div class="flex items-center gap-2.5">
          <span v-if="saveSuccess" class="text-xs font-semibold text-emerald-400 bg-emerald-950/60 border border-emerald-800 px-3 py-1.5 rounded-full flex items-center gap-1.5">
            <Check class="w-3.5 h-3.5 text-emerald-400" />
            Saved to Vault!
          </span>

          <button
            @click="emit('close')"
            class="w-8 h-8 rounded-full bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center transition border border-slate-700 hover:bg-slate-700"
            title="Close Editor"
          >
            <CloseIcon class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Studio Editor Body -->
      <div class="flex-1 flex overflow-hidden">
        <!-- CANVA PHOTO / GRAPHIC STUDIO -->
        <CanvaImageEditor
          v-if="editorMode === 'canva'"
          :asset="asset"
          @save="handleSave"
          @close="emit('close')"
        />

        <!-- CLIPCHAMP VIDEO TIMELINE STUDIO -->
        <ClipchampVideoEditor
          v-else
          :asset="asset"
          @save-frame="handleSave"
          @close="emit('close')"
        />
      </div>

    </div>
  </div>
</template>
