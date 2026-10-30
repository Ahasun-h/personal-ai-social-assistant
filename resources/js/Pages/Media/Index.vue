<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import MediaEditorModal from '@/Components/MediaEditorModal.vue';
import {
  Image as ImageIcon,
  Sparkles,
  Upload,
  RefreshCw,
  Layers,
  Trash2,
  CheckCircle2,
  AlertCircle,
  FileUp,
  SlidersHorizontal,
  Video as VideoIcon,
  Play,
  Wand2,
} from 'lucide-vue-next';

interface MediaAsset {
  id: string;
  filename: string;
  path: string;
  mime_type?: string;
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

const activeMode = ref<'generate' | 'upload'>('generate');
const prompt = ref('Clean modern architectural diagram contrasting distributed microservices network chatter with an in-process modular monolith boundary, soft minimalist aesthetic');
const aspectRatio = ref('16:9');
const isGenerating = ref(false);
const isUploading = ref(false);
const uploadMessage = ref<{ type: 'success' | 'error'; text: string } | null>(null);

const fileInputRef = ref<HTMLInputElement | null>(null);

// Studio Editor State
const isEditorOpen = ref(false);
const editingAsset = ref<MediaAsset | null>(null);

const openEditor = (asset: MediaAsset) => {
  editingAsset.value = {
    ...asset,
    path: resolveAssetUrl(asset.path),
  };
  isEditorOpen.value = true;
};

const openBlankStudio = () => {
  editingAsset.value = {
    id: 'studio-new',
    filename: 'executive-design.png',
    path: '/storage/media/monolith-vs-microservices-arch.png',
    mime_type: 'image/png',
    width: 1200,
    height: 675,
    is_ai_generated: true,
  };
  isEditorOpen.value = true;
};

const onAssetSaved = (newAsset: any) => {
  isEditorOpen.value = false;
  uploadMessage.value = { type: 'success', text: 'Edited asset saved to your Media Vault!' };
  router.reload();
  setTimeout(() => (uploadMessage.value = null), 4000);
};

const isVideo = (asset: MediaAsset) => {
  if (asset.mime_type?.startsWith('video/')) return true;
  const ext = asset.filename.split('.').pop()?.toLowerCase();
  return ['mp4', 'mov', 'webm'].includes(ext || '');
};

const resolveAssetUrl = (path: string) => {
  if (!path) return 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1080&q=80';
  if (path.startsWith('http://') || path.startsWith('https://')) return path;
  if (path.startsWith('/storage/')) return path;
  if (path.startsWith('storage/')) return '/' + path;
  if (path.startsWith('media/')) return '/storage/' + path;
  if (path.startsWith('/')) return path;
  return '/storage/' + path;
};

const generate = async () => {
  if (!prompt.value) return;
  isGenerating.value = true;
  try {
    const res = await fetch('/api/v1/media/ai/generate', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        prompt: prompt.value,
        aspect_ratio: aspectRatio.value,
        provider: 'flux-schnell',
      }),
    });
    if (res.ok) {
      uploadMessage.value = { type: 'success', text: 'Asset generated and added to vault!' };
      router.reload();
    } else {
      let msg = 'Generation failed';
      try {
        const err = await res.json();
        msg = err.message || msg;
      } catch {}
      uploadMessage.value = { type: 'error', text: msg };
    }
  } catch (e: any) {
    uploadMessage.value = { type: 'error', text: e.message };
  } finally {
    isGenerating.value = false;
    setTimeout(() => (uploadMessage.value = null), 4000);
  }
};

const handleFileUpload = async (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (!file) return;

  isUploading.value = true;
  const formData = new FormData();
  formData.append('file', file);
  formData.append('alt_text', file.name);

  try {
    const res = await fetch('/api/v1/media/upload', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
      },
      body: formData,
    });
    if (res.ok) {
      uploadMessage.value = { type: 'success', text: `${file.name} uploaded successfully!` };
      router.reload();
    } else {
      const err = await res.json();
      uploadMessage.value = { type: 'error', text: err.message || 'Upload failed' };
    }
  } catch (e: any) {
    uploadMessage.value = { type: 'error', text: e.message };
  } finally {
    isUploading.value = false;
    if (fileInputRef.value) fileInputRef.value.value = '';
    setTimeout(() => (uploadMessage.value = null), 4000);
  }
};

const deleteAsset = async (id: string) => {
  if (!confirm('Are you sure you want to remove this media asset?')) return;
  try {
    const res = await fetch(`/api/v1/media/${id}`, { method: 'DELETE' });
    if (res.ok) {
      uploadMessage.value = { type: 'success', text: 'Asset removed from vault.' };
      router.reload();
    }
  } catch (e: any) {
    uploadMessage.value = { type: 'error', text: e.message };
  } finally {
    setTimeout(() => (uploadMessage.value = null), 3000);
  }
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
            Generate visual media with Flux, edit photos and videos directly in the studio, crop social aspect ratios, and manage brand assets.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <!-- Launch Creative Studio directly -->
          <button
            @click="openBlankStudio"
            class="px-4 py-2 rounded-full bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs flex items-center gap-2 shadow-sm transition shrink-0"
          >
            <Sparkles class="w-4 h-4 text-[#9EE493]" />
            <span>Open Creative Studio</span>
          </button>

          <!-- Notification Banner -->
          <div v-if="uploadMessage" class="flex items-center gap-2 px-4 py-2 rounded-full text-xs font-semibold shadow-sm transition" :class="uploadMessage.type === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200'">
            <CheckCircle2 v-if="uploadMessage.type === 'success'" class="w-4 h-4 text-emerald-600" />
            <AlertCircle v-else class="w-4 h-4 text-rose-600" />
            <span>{{ uploadMessage.text }}</span>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Controls & Generator/Uploader Card -->
        <div class="lg:col-span-5 bg-white border border-slate-200/80 rounded-3xl p-6 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-5">
          <!-- Toggle Tabs -->
          <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-full w-fit">
            <button
              @click="activeMode = 'generate'"
              :class="[
                'px-4 py-1.5 rounded-full text-xs font-semibold transition flex items-center gap-1.5',
                activeMode === 'generate' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'
              ]"
            >
              <Sparkles class="w-3.5 h-3.5 text-emerald-600" />
              <span>AI Synthesize</span>
            </button>
            <button
              @click="activeMode = 'upload'"
              :class="[
                'px-4 py-1.5 rounded-full text-xs font-semibold transition flex items-center gap-1.5',
                activeMode === 'upload' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'
              ]"
            >
              <Upload class="w-3.5 h-3.5 text-slate-700" />
              <span>Upload File</span>
            </button>
          </div>

          <!-- Mode: AI Synthesizer -->
          <div v-if="activeMode === 'generate'" class="space-y-4">
            <div class="space-y-1.5">
              <label class="text-xs font-semibold text-slate-700">Visual Prompt Editor</label>
              <textarea
                v-model="prompt"
                rows="4"
                placeholder="Describe your visual concept in detail..."
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
                  16:9 Landscape
                </button>
                <button
                  @click="aspectRatio = '1:1'"
                  class="py-2 text-xs rounded-full font-semibold border transition shadow-xs"
                  :class="aspectRatio === '1:1' ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                >
                  1:1 Square
                </button>
                <button
                  @click="aspectRatio = '4:5'"
                  class="py-2 text-xs rounded-full font-semibold border transition shadow-xs"
                  :class="aspectRatio === '4:5' ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                >
                  4:5 Portrait
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

          <!-- Mode: Direct File Upload -->
          <div v-else class="space-y-4">
            <div
              @click="fileInputRef?.click()"
              class="border-2 border-dashed border-slate-200 hover:border-emerald-400 rounded-3xl p-8 text-center cursor-pointer transition bg-slate-50/50 hover:bg-emerald-50/20 group"
            >
              <input
                ref="fileInputRef"
                type="file"
                accept="image/*,video/*"
                class="hidden"
                @change="handleFileUpload"
              />
              <div class="w-12 h-12 rounded-2xl bg-white border border-slate-200 flex items-center justify-center mx-auto mb-3 shadow-xs group-hover:scale-105 transition">
                <FileUp class="w-6 h-6 text-emerald-600" />
              </div>
              <p class="text-xs font-semibold text-slate-800">
                {{ isUploading ? 'Uploading asset...' : 'Click to browse or drag & drop image/video' }}
              </p>
              <p class="text-[11px] text-slate-400 mt-1">
                PNG, JPG, WebP, GIF, or MP4 (up to 100MB)
              </p>
            </div>
          </div>
        </div>

        <!-- Asset Gallery -->
        <div class="lg:col-span-7 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider font-mono flex items-center gap-2">
              <Layers class="w-4 h-4 text-emerald-600" />
              Saved Assets & Generation Vault ({{ assets.length }})
            </h3>
          </div>

          <div v-if="assets.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div
              v-for="asset in assets"
              :key="asset.id"
              class="group relative rounded-3xl overflow-hidden border border-slate-200/80 bg-white shadow-[0_4px_20px_rgba(0,0,0,0.03)] hover:shadow-lg transition-all"
            >
              <!-- Media Preview Viewport -->
              <div class="relative w-full h-44 bg-slate-950 overflow-hidden cursor-pointer" @click="openEditor(asset)">
                <video
                  v-if="isVideo(asset)"
                  :src="resolveAssetUrl(asset.path)"
                  class="w-full h-full object-cover group-hover:scale-105 transition duration-300 opacity-90"
                  muted
                  preload="metadata"
                ></video>
                <img
                  v-else
                  :src="resolveAssetUrl(asset.path)"
                  :alt="asset.filename"
                  class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                  @error="(e: any) => { (e.target as HTMLImageElement).src = 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1080&q=80'; }"
                />

                <!-- Media Type Badge -->
                <div class="absolute top-2.5 left-2.5 bg-slate-900/80 backdrop-blur-md px-2.5 py-1 rounded-full text-[10px] font-mono text-white flex items-center gap-1.5 shadow-sm">
                  <VideoIcon v-if="isVideo(asset)" class="w-3 h-3 text-[#9EE493]" />
                  <ImageIcon v-else class="w-3 h-3 text-[#9EE493]" />
                  <span>{{ isVideo(asset) ? 'Video' : 'Photo' }}</span>
                </div>

                <!-- Hover Overlay with Launch Studio -->
                <div class="absolute inset-0 bg-slate-950/40 backdrop-blur-xs opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-2">
                  <button
                    @click.stop="openEditor(asset)"
                    class="px-4 py-2 rounded-full bg-[#9EE493] text-slate-950 font-bold text-xs flex items-center gap-1.5 shadow-md hover:scale-105 transition"
                  >
                    <SlidersHorizontal class="w-3.5 h-3.5 text-slate-950" />
                    <span>Open in Editor</span>
                  </button>
                </div>
              </div>

              <!-- Card Details & Actions Footer -->
              <div class="p-3.5 bg-white border-t border-slate-100 flex items-center justify-between gap-3">
                <div class="space-y-0.5 overflow-hidden">
                  <p class="text-xs font-semibold text-slate-900 truncate" :title="asset.filename">
                    {{ asset.filename }}
                  </p>
                  <div class="flex items-center gap-2 text-[10px] text-slate-400 font-mono">
                    <span v-if="asset.width && asset.height">{{ asset.width }}×{{ asset.height }}</span>
                    <span class="text-emerald-700 font-medium">{{ asset.is_ai_generated ? 'AI Generated' : 'Uploaded' }}</span>
                  </div>
                </div>

                <div class="flex items-center gap-1 shrink-0">
                  <button
                    @click="openEditor(asset)"
                    class="flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-800 transition"
                    title="Edit in Studio"
                  >
                    <Wand2 class="w-3.5 h-3.5 text-emerald-600" />
                    <span>Edit</span>
                  </button>

                  <button
                    @click="deleteAsset(asset.id)"
                    class="p-1.5 rounded-full text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                    title="Remove asset"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-16 text-slate-400 text-xs font-mono bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            No assets in vault. Generate visual prompts above or upload your branded media.
          </div>
        </div>
      </div>
    </div>

    <!-- Interactive Studio Photo & Video Editor Modal -->
    <MediaEditorModal
      :isOpen="isEditorOpen"
      :asset="editingAsset"
      @close="isEditorOpen = false"
      @saved="onAssetSaved"
    />
  </AppLayout>
</template>
