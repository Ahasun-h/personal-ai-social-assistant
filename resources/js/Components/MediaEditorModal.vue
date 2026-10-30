<script setup lang="ts">
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import {
  X as CloseIcon,
  Crop,
  Sliders,
  RotateCw,
  RotateCcw,
  FlipHorizontal,
  FlipVertical,
  Type,
  Download,
  Save,
  Play,
  Pause,
  Volume2,
  VolumeX,
  Camera,
  Check,
  Undo2,
  Sparkles,
  Video as VideoIcon,
  Image as ImageIcon,
  Sun,
  Layers,
  Ratio,
} from 'lucide-vue-next';

interface MediaAsset {
  id: string;
  filename: string;
  path: string;
  mime_type?: string;
  width?: number;
  height?: number;
  aspect_ratio?: string;
  is_ai_generated?: boolean;
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

// Editor Tab State
const activeTab = ref<'filters' | 'crop' | 'transform' | 'text' | 'video'>('filters');
const isSaving = ref(false);
const saveSuccess = ref(false);

// Filter & Color Adjustments
const brightness = ref(100); // 50 to 150
const contrast = ref(100);   // 50 to 150
const saturation = ref(100); // 0 to 200
const sepia = ref(0);        // 0 to 100
const grayscale = ref(0);    // 0 to 100
const blur = ref(0);         // 0 to 10px

// Transforms
const rotation = ref(0); // 0, 90, 180, 270
const flipH = ref(false);
const flipV = ref(false);

// Crop / Aspect Ratio Preset
const selectedAspectRatio = ref<'original' | '1:1' | '16:9' | '4:5' | '9:16'>('original');

// Watermark / Text Overlay
const overlayText = ref('');
const textPosition = ref<'bottom-left' | 'bottom-right' | 'top-left' | 'top-right' | 'center'>('bottom-right');
const textColor = ref('#FFFFFF');
const textSize = ref(28);
const showTextBadge = ref(true);

// Video Player State
const videoRef = ref<HTMLVideoElement | null>(null);
const isPlaying = ref(false);
const isMuted = ref(false);
const currentTime = ref(0);
const duration = ref(0);
const playbackRate = ref(1);

// Canvas Reference
const canvasRef = ref<HTMLCanvasElement | null>(null);
const sourceImage = ref<HTMLImageElement | null>(null);

// Determine Media Type
const isVideo = computed(() => {
  if (!props.asset) return false;
  if (props.asset.mime_type?.startsWith('video/')) return true;
  const ext = props.asset.filename.split('.').pop()?.toLowerCase();
  return ['mp4', 'mov', 'webm'].includes(ext || '');
});

// CSS Filter String
const computedFilterStyle = computed(() => {
  return `brightness(${brightness.value}%) contrast(${contrast.value}%) saturate(${saturation.value}%) sepia(${sepia.value}%) grayscale(${grayscale.value}%) blur(${blur.value}px)`;
});

// Presets
const applyPreset = (preset: string) => {
  resetAdjustments();
  if (preset === 'vibrant') {
    saturation.value = 145;
    contrast.value = 115;
    brightness.value = 105;
  } else if (preset === 'mono') {
    grayscale.value = 100;
    contrast.value = 125;
  } else if (preset === 'sepia') {
    sepia.value = 75;
    saturation.value = 85;
    contrast.value = 95;
  } else if (preset === 'moody') {
    contrast.value = 135;
    brightness.value = 90;
    saturation.value = 110;
  } else if (preset === 'warm') {
    sepia.value = 30;
    saturation.value = 120;
    brightness.value = 105;
  }
  renderImageToCanvas();
};

const resetAdjustments = () => {
  brightness.value = 100;
  contrast.value = 100;
  saturation.value = 100;
  sepia.value = 0;
  grayscale.value = 0;
  blur.value = 0;
  rotation.value = 0;
  flipH.value = false;
  flipV.value = false;
  selectedAspectRatio.value = 'original';
  overlayText.value = '';
  renderImageToCanvas();
};

// Rotate Actions
const rotateRight = () => {
  rotation.value = (rotation.value + 90) % 360;
  renderImageToCanvas();
};

const rotateLeft = () => {
  rotation.value = (rotation.value + 270) % 360;
  renderImageToCanvas();
};

const toggleFlipH = () => {
  flipH.value = !flipH.value;
  renderImageToCanvas();
};

const toggleFlipV = () => {
  flipV.value = !flipV.value;
  renderImageToCanvas();
};

// Video Controls
const togglePlay = () => {
  if (!videoRef.value) return;
  if (isPlaying.value) {
    videoRef.value.pause();
    isPlaying.value = false;
  } else {
    videoRef.value.play();
    isPlaying.value = true;
  }
};

const toggleMute = () => {
  if (!videoRef.value) return;
  videoRef.value.muted = !videoRef.value.muted;
  isMuted.value = videoRef.value.muted;
};

const onTimeUpdate = () => {
  if (!videoRef.value) return;
  currentTime.value = videoRef.value.currentTime;
};

const onLoadedMetadata = () => {
  if (!videoRef.value) return;
  duration.value = videoRef.value.duration;
};

const seekVideo = (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (!videoRef.value) return;
  videoRef.value.currentTime = Number(target.value);
};

const setSpeed = (speed: number) => {
  playbackRate.value = speed;
  if (videoRef.value) {
    videoRef.value.playbackRate = speed;
  }
};

// Frame Capture from Video
const captureVideoFrame = () => {
  if (!videoRef.value) return;
  const video = videoRef.value;
  const canvas = document.createElement('canvas');
  canvas.width = video.videoWidth || 1280;
  canvas.height = video.videoHeight || 720;
  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  // Apply active filters to captured canvas
  ctx.filter = computedFilterStyle.value;
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

  canvas.toBlob((blob) => {
    if (!blob) return;
    uploadEditedBlob(blob, `frame_snap_${Math.floor(currentTime.value)}s_${props.asset?.filename || 'video'}.jpg`);
  }, 'image/jpeg', 0.95);
};

// Load Image onto Canvas
const loadAssetImage = () => {
  if (!props.asset || isVideo.value) return;
  const img = new Image();
  img.crossOrigin = 'anonymous';
  img.src = props.asset.path;
  img.onload = () => {
    sourceImage.value = img;
    nextTick(() => {
      renderImageToCanvas();
    });
  };
};

// Render transformed image with filters and text overlay to Canvas
const renderImageToCanvas = () => {
  const canvas = canvasRef.value;
  const img = sourceImage.value;
  if (!canvas || !img) return;

  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  // Determine Dimensions based on Aspect Ratio Preset
  let targetWidth = img.naturalWidth || 800;
  let targetHeight = img.naturalHeight || 600;

  if (selectedAspectRatio.value === '1:1') {
    const minDim = Math.min(targetWidth, targetHeight);
    targetWidth = minDim;
    targetHeight = minDim;
  } else if (selectedAspectRatio.value === '16:9') {
    targetHeight = Math.round(targetWidth * (9 / 16));
  } else if (selectedAspectRatio.value === '4:5') {
    targetHeight = Math.round(targetWidth * (5 / 4));
  } else if (selectedAspectRatio.value === '9:16') {
    targetWidth = Math.round(targetHeight * (9 / 16));
  }

  // Set canvas size (swap dimensions if rotated 90 or 270)
  const isRotated90or270 = rotation.value === 90 || rotation.value === 270;
  canvas.width = isRotated90or270 ? targetHeight : targetWidth;
  canvas.height = isRotated90or270 ? targetWidth : targetHeight;

  ctx.clearRect(0, 0, canvas.width, canvas.height);
  ctx.save();

  // Apply Filters
  ctx.filter = computedFilterStyle.value;

  // Position at Center for Rotation & Scaling
  ctx.translate(canvas.width / 2, canvas.height / 2);
  ctx.rotate((rotation.value * Math.PI) / 180);
  ctx.scale(flipH.value ? -1 : 1, flipV.value ? -1 : 1);

  // Draw Cropped Image
  const srcAspect = (img.naturalWidth || 1) / (img.naturalHeight || 1);
  const dstAspect = targetWidth / targetHeight;

  let sx = 0, sy = 0, sWidth = img.naturalWidth, sHeight = img.naturalHeight;

  if (srcAspect > dstAspect) {
    sWidth = img.naturalHeight * dstAspect;
    sx = (img.naturalWidth - sWidth) / 2;
  } else {
    sHeight = img.naturalWidth / dstAspect;
    sy = (img.naturalHeight - sHeight) / 2;
  }

  ctx.drawImage(
    img,
    sx,
    sy,
    sWidth,
    sHeight,
    -targetWidth / 2,
    -targetHeight / 2,
    targetWidth,
    targetHeight
  );

  ctx.restore();

  // Draw Text / Watermark Overlay
  if (overlayText.value.trim()) {
    ctx.save();
    const padding = 20;
    const text = overlayText.value;
    const dynamicSize = Math.max(16, Math.round((canvas.width / 1000) * textSize.value));
    ctx.font = `bold ${dynamicSize}px Inter, sans-serif`;

    const metrics = ctx.measureText(text);
    const boxWidth = metrics.width + 24;
    const boxHeight = dynamicSize + 16;

    let x = padding;
    let y = canvas.height - padding;

    if (textPosition.value === 'bottom-right') {
      x = canvas.width - boxWidth - padding;
      y = canvas.height - padding;
    } else if (textPosition.value === 'top-left') {
      x = padding;
      y = padding + boxHeight;
    } else if (textPosition.value === 'top-right') {
      x = canvas.width - boxWidth - padding;
      y = padding + boxHeight;
    } else if (textPosition.value === 'center') {
      x = (canvas.width - boxWidth) / 2;
      y = (canvas.height + boxHeight) / 2;
    }

    if (showTextBadge.value) {
      ctx.fillStyle = 'rgba(15, 23, 42, 0.75)'; // Slate-900 with alpha
      ctx.beginPath();
      ctx.roundRect(x, y - boxHeight, boxWidth, boxHeight, 8);
      ctx.fill();
    }

    ctx.fillStyle = textColor.value;
    ctx.textBaseline = 'middle';
    ctx.fillText(text, x + 12, y - boxHeight / 2);
    ctx.restore();
  }
};

// Export to Download
const downloadAsset = () => {
  if (isVideo.value) {
    if (!props.asset) return;
    const a = document.createElement('a');
    a.href = props.asset.path;
    a.download = props.asset.filename;
    a.click();
    return;
  }

  const canvas = canvasRef.value;
  if (!canvas) return;
  const link = document.createElement('a');
  link.download = `edited_${props.asset?.filename || 'image.jpg'}`;
  link.href = canvas.toDataURL('image/jpeg', 0.95);
  link.click();
};

// Save edited canvas as new asset to Media Vault
const saveEditedAsset = () => {
  if (isVideo.value) {
    // For video, capture frame or confirm edits
    captureVideoFrame();
    return;
  }

  const canvas = canvasRef.value;
  if (!canvas) return;

  isSaving.value = true;
  canvas.toBlob((blob) => {
    if (!blob) {
      isSaving.value = false;
      return;
    }
    uploadEditedBlob(blob, `edited_${props.asset?.filename || 'image.jpg'}`);
  }, 'image/jpeg', 0.95);
};

const uploadEditedBlob = async (blob: Blob, filename: string) => {
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
      }, 1500);
    }
  } catch (e) {
    console.error('Error saving edited asset', e);
  } finally {
    isSaving.value = false;
  }
};

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      nextTick(() => {
        if (!isVideo.value) {
          loadAssetImage();
        } else {
          activeTab.value = 'video';
        }
      });
    }
  }
);

watch(
  () => props.asset,
  () => {
    if (props.isOpen && !isVideo.value) {
      loadAssetImage();
    }
  }
);
</script>

<template>
  <div v-if="isOpen && asset" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-slate-950/80 backdrop-blur-md">
    <!-- Studio Editor Window -->
    <div class="relative w-full max-w-6xl max-h-[92vh] bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl flex flex-col overflow-hidden text-white font-sans">
      
      <!-- Studio Header Bar -->
      <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800 shrink-0 bg-slate-900/90 backdrop-blur-sm">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#9EE493] to-emerald-400 text-slate-950 flex items-center justify-center shadow-sm">
            <VideoIcon v-if="isVideo" class="w-4 h-4 text-slate-950" />
            <ImageIcon v-else class="w-4 h-4 text-slate-950" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h2 class="font-display font-bold text-sm text-slate-100 truncate max-w-sm">
                {{ asset.filename }}
              </h2>
              <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 border border-slate-700">
                {{ isVideo ? 'Video Studio' : 'Photo Studio' }}
              </span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5">
              {{ isVideo ? 'Preview, filter, and extract high-resolution cover frames.' : 'Aspect cropping, color grading, orientation, and watermark overlays.' }}
            </p>
          </div>
        </div>

        <!-- Top Actions -->
        <div class="flex items-center gap-2.5">
          <span v-if="saveSuccess" class="text-xs font-semibold text-emerald-400 bg-emerald-950/50 border border-emerald-800/80 px-3 py-1.5 rounded-full flex items-center gap-1.5">
            <Check class="w-3.5 h-3.5 text-emerald-400" />
            Saved to Vault
          </span>

          <button
            @click="resetAdjustments"
            class="hidden sm:flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-medium text-slate-400 hover:text-white bg-slate-800/80 hover:bg-slate-800 border border-slate-700 transition"
          >
            <Undo2 class="w-3.5 h-3.5" />
            <span>Reset</span>
          </button>

          <button
            @click="downloadAsset"
            class="flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-semibold text-slate-200 hover:text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 transition"
            title="Download locally"
          >
            <Download class="w-3.5 h-3.5" />
            <span>Export</span>
          </button>

          <button
            @click="saveEditedAsset"
            :disabled="isSaving"
            class="flex items-center gap-2 px-5 py-1.5 rounded-full text-xs font-semibold text-slate-950 bg-[#9EE493] hover:bg-[#8ee082] shadow-sm transition disabled:opacity-50"
          >
            <Save class="w-3.5 h-3.5" />
            <span>{{ isSaving ? 'Saving...' : (isVideo ? 'Snap Frame to Vault' : 'Save as New Asset') }}</span>
          </button>

          <button
            @click="emit('close')"
            class="w-8 h-8 rounded-full bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center transition border border-slate-700 ml-2"
          >
            <CloseIcon class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Studio Body (2 Columns) -->
      <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 overflow-hidden min-h-[500px]">
        
        <!-- Center Stage: Canvas or Video Viewport -->
        <div class="lg:col-span-8 bg-slate-950/80 p-6 flex flex-col items-center justify-center relative overflow-hidden select-none">
          
          <!-- Image Canvas Stage -->
          <div v-if="!isVideo" class="relative max-w-full max-h-[60vh] flex items-center justify-center rounded-2xl shadow-2xl overflow-hidden border border-slate-800/80 bg-[radial-gradient(#1e293b_1px,transparent_1px)] [background-size:16px_16px]">
            <canvas
              ref="canvasRef"
              class="max-w-full max-h-[56vh] object-contain rounded-lg transition"
            ></canvas>
          </div>

          <!-- Video Stage -->
          <div v-else class="w-full max-w-2xl flex flex-col items-center space-y-4">
            <div class="relative w-full rounded-2xl overflow-hidden bg-black shadow-2xl border border-slate-800">
              <video
                ref="videoRef"
                :src="asset.path"
                class="w-full max-h-[46vh] object-contain transition"
                :style="{ filter: computedFilterStyle }"
                @timeupdate="onTimeUpdate"
                @loadedmetadata="onLoadedMetadata"
                @ended="isPlaying = false"
              ></video>
            </div>

            <!-- Video Timeline & Controls -->
            <div class="w-full bg-slate-900 border border-slate-800 rounded-2xl p-4 space-y-3">
              <!-- Scrubber Bar -->
              <div class="flex items-center gap-3">
                <span class="text-[11px] font-mono text-slate-400 w-12 text-right">
                  {{ Math.floor(currentTime) }}s
                </span>
                <input
                  type="range"
                  :min="0"
                  :max="duration || 100"
                  step="0.1"
                  :value="currentTime"
                  @input="seekVideo"
                  class="flex-1 accent-[#9EE493] h-1.5 bg-slate-800 rounded-lg cursor-pointer"
                />
                <span class="text-[11px] font-mono text-slate-400 w-12">
                  {{ Math.floor(duration) }}s
                </span>
              </div>

              <!-- Playback Control Strip -->
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <button
                    @click="togglePlay"
                    class="w-9 h-9 rounded-full bg-slate-800 hover:bg-slate-700 text-white flex items-center justify-center transition"
                  >
                    <Pause v-if="isPlaying" class="w-4 h-4 text-[#9EE493]" />
                    <Play v-else class="w-4 h-4 text-[#9EE493] ml-0.5" />
                  </button>

                  <button
                    @click="toggleMute"
                    class="w-9 h-9 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-300 flex items-center justify-center transition"
                  >
                    <VolumeX v-if="isMuted" class="w-4 h-4 text-rose-400" />
                    <Volume2 v-else class="w-4 h-4" />
                  </button>

                  <!-- Speed Selector -->
                  <div class="flex items-center bg-slate-800/80 rounded-full p-1 text-[10px] font-mono font-bold">
                    <button
                      v-for="s in [0.5, 1, 1.5, 2]"
                      :key="s"
                      @click="setSpeed(s)"
                      :class="['px-2 py-0.5 rounded-full transition', playbackRate === s ? 'bg-[#9EE493] text-slate-950' : 'text-slate-400 hover:text-white']"
                    >
                      {{ s }}x
                    </button>
                  </div>
                </div>

                <!-- Frame Capture Button -->
                <button
                  @click="captureVideoFrame"
                  :disabled="isSaving"
                  class="flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-semibold bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 transition disabled:opacity-50"
                  title="Extract current frame as high-res cover image"
                >
                  <Camera class="w-3.5 h-3.5" />
                  <span>Snap Cover Frame</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Bottom Status Pill -->
          <div class="mt-4 flex items-center gap-3 text-xs text-slate-400 font-mono">
            <span>Dimensions: {{ asset.width || 1200 }}×{{ asset.height || 630 }}</span>
            <span>•</span>
            <span>Aspect: {{ selectedAspectRatio.toUpperCase() }}</span>
            <span v-if="rotation !== 0">•</span>
            <span v-if="rotation !== 0">{{ rotation }}° Rotated</span>
          </div>
        </div>

        <!-- Right Tool Drawer (4 Cols on desktop) -->
        <div class="lg:col-span-4 bg-slate-900 border-l border-slate-800 p-5 flex flex-col space-y-5 overflow-y-auto max-h-[75vh]">
          
          <!-- Tool Navigation Tabs -->
          <div class="flex gap-1 p-1 bg-slate-950 rounded-2xl border border-slate-800/80">
            <button
              @click="activeTab = 'filters'"
              :class="[
                'flex-1 py-1.5 rounded-xl text-xs font-semibold transition flex items-center justify-center gap-1',
                activeTab === 'filters' ? 'bg-slate-800 text-white shadow-xs' : 'text-slate-400 hover:text-slate-200'
              ]"
            >
              <Sliders class="w-3 h-3 text-[#9EE493]" />
              <span>Color</span>
            </button>

            <button
              v-if="!isVideo"
              @click="activeTab = 'crop'"
              :class="[
                'flex-1 py-1.5 rounded-xl text-xs font-semibold transition flex items-center justify-center gap-1',
                activeTab === 'crop' ? 'bg-slate-800 text-white shadow-xs' : 'text-slate-400 hover:text-slate-200'
              ]"
            >
              <Ratio class="w-3 h-3 text-[#9EE493]" />
              <span>Aspect</span>
            </button>

            <button
              v-if="!isVideo"
              @click="activeTab = 'transform'"
              :class="[
                'flex-1 py-1.5 rounded-xl text-xs font-semibold transition flex items-center justify-center gap-1',
                activeTab === 'transform' ? 'bg-slate-800 text-white shadow-xs' : 'text-slate-400 hover:text-slate-200'
              ]"
            >
              <RotateCw class="w-3 h-3 text-[#9EE493]" />
              <span>Rotate</span>
            </button>

            <button
              v-if="!isVideo"
              @click="activeTab = 'text'"
              :class="[
                'flex-1 py-1.5 rounded-xl text-xs font-semibold transition flex items-center justify-center gap-1',
                activeTab === 'text' ? 'bg-slate-800 text-white shadow-xs' : 'text-slate-400 hover:text-slate-200'
              ]"
            >
              <Type class="w-3 h-3 text-[#9EE493]" />
              <span>Brand</span>
            </button>
          </div>

          <!-- TAB 1: FILTERS & ADJUSTMENTS -->
          <div v-if="activeTab === 'filters'" class="space-y-4">
            <!-- Preset Pills -->
            <div class="space-y-1.5">
              <label class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider font-mono">
                Visual Presets
              </label>
              <div class="grid grid-cols-3 gap-1.5">
                <button
                  v-for="p in ['vibrant', 'mono', 'sepia', 'moody', 'warm']"
                  :key="p"
                  @click="applyPreset(p)"
                  class="py-1.5 px-2 text-[11px] rounded-xl bg-slate-800/80 hover:bg-slate-800 border border-slate-700/80 font-medium capitalize transition"
                >
                  {{ p }}
                </button>
              </div>
            </div>

            <!-- Sliders -->
            <div class="space-y-3 pt-2">
              <div class="space-y-1">
                <div class="flex justify-between text-xs text-slate-300">
                  <span>Brightness</span>
                  <span class="font-mono text-slate-400">{{ brightness }}%</span>
                </div>
                <input
                  type="range"
                  min="50"
                  max="150"
                  v-model.number="brightness"
                  @input="renderImageToCanvas"
                  class="w-full accent-[#9EE493] h-1.5 bg-slate-800 rounded-lg cursor-pointer"
                />
              </div>

              <div class="space-y-1">
                <div class="flex justify-between text-xs text-slate-300">
                  <span>Contrast</span>
                  <span class="font-mono text-slate-400">{{ contrast }}%</span>
                </div>
                <input
                  type="range"
                  min="50"
                  max="150"
                  v-model.number="contrast"
                  @input="renderImageToCanvas"
                  class="w-full accent-[#9EE493] h-1.5 bg-slate-800 rounded-lg cursor-pointer"
                />
              </div>

              <div class="space-y-1">
                <div class="flex justify-between text-xs text-slate-300">
                  <span>Saturation</span>
                  <span class="font-mono text-slate-400">{{ saturation }}%</span>
                </div>
                <input
                  type="range"
                  min="0"
                  max="200"
                  v-model.number="saturation"
                  @input="renderImageToCanvas"
                  class="w-full accent-[#9EE493] h-1.5 bg-slate-800 rounded-lg cursor-pointer"
                />
              </div>

              <div class="space-y-1">
                <div class="flex justify-between text-xs text-slate-300">
                  <span>Warmth / Sepia</span>
                  <span class="font-mono text-slate-400">{{ sepia }}%</span>
                </div>
                <input
                  type="range"
                  min="0"
                  max="100"
                  v-model.number="sepia"
                  @input="renderImageToCanvas"
                  class="w-full accent-[#9EE493] h-1.5 bg-slate-800 rounded-lg cursor-pointer"
                />
              </div>

              <div class="space-y-1">
                <div class="flex justify-between text-xs text-slate-300">
                  <span>Grayscale</span>
                  <span class="font-mono text-slate-400">{{ grayscale }}%</span>
                </div>
                <input
                  type="range"
                  min="0"
                  max="100"
                  v-model.number="grayscale"
                  @input="renderImageToCanvas"
                  class="w-full accent-[#9EE493] h-1.5 bg-slate-800 rounded-lg cursor-pointer"
                />
              </div>

              <div class="space-y-1">
                <div class="flex justify-between text-xs text-slate-300">
                  <span>Blur</span>
                  <span class="font-mono text-slate-400">{{ blur }}px</span>
                </div>
                <input
                  type="range"
                  min="0"
                  max="10"
                  v-model.number="blur"
                  @input="renderImageToCanvas"
                  class="w-full accent-[#9EE493] h-1.5 bg-slate-800 rounded-lg cursor-pointer"
                />
              </div>
            </div>
          </div>

          <!-- TAB 2: ASPECT RATIO & CROPPING -->
          <div v-if="activeTab === 'crop'" class="space-y-4">
            <p class="text-xs text-slate-400">
              Standard social media target aspect ratios:
            </p>

            <div class="space-y-2">
              <button
                @click="selectedAspectRatio = 'original'; renderImageToCanvas()"
                :class="[
                  'w-full p-3 rounded-2xl border text-left flex items-center justify-between transition',
                  selectedAspectRatio === 'original' ? 'bg-[#9EE493]/10 border-[#9EE493] text-white' : 'bg-slate-800/80 border-slate-700/80 text-slate-300 hover:bg-slate-800'
                ]"
              >
                <div>
                  <div class="font-semibold text-xs">Original Resolution</div>
                  <div class="text-[10px] text-slate-400">Preserves original dimensions</div>
                </div>
                <Check v-if="selectedAspectRatio === 'original'" class="w-4 h-4 text-[#9EE493]" />
              </button>

              <button
                @click="selectedAspectRatio = '16:9'; renderImageToCanvas()"
                :class="[
                  'w-full p-3 rounded-2xl border text-left flex items-center justify-between transition',
                  selectedAspectRatio === '16:9' ? 'bg-[#9EE493]/10 border-[#9EE493] text-white' : 'bg-slate-800/80 border-slate-700/80 text-slate-300 hover:bg-slate-800'
                ]"
              >
                <div>
                  <div class="font-semibold text-xs">16:9 Landscape</div>
                  <div class="text-[10px] text-slate-400">Optimized for X (Twitter) & YouTube</div>
                </div>
                <Check v-if="selectedAspectRatio === '16:9'" class="w-4 h-4 text-[#9EE493]" />
              </button>

              <button
                @click="selectedAspectRatio = '1:1'; renderImageToCanvas()"
                :class="[
                  'w-full p-3 rounded-2xl border text-left flex items-center justify-between transition',
                  selectedAspectRatio === '1:1' ? 'bg-[#9EE493]/10 border-[#9EE493] text-white' : 'bg-slate-800/80 border-slate-700/80 text-slate-300 hover:bg-slate-800'
                ]"
              >
                <div>
                  <div class="font-semibold text-xs">1:1 Square</div>
                  <div class="text-[10px] text-slate-400">LinkedIn Feed & Instagram Carousel</div>
                </div>
                <Check v-if="selectedAspectRatio === '1:1'" class="w-4 h-4 text-[#9EE493]" />
              </button>

              <button
                @click="selectedAspectRatio = '4:5'; renderImageToCanvas()"
                :class="[
                  'w-full p-3 rounded-2xl border text-left flex items-center justify-between transition',
                  selectedAspectRatio === '4:5' ? 'bg-[#9EE493]/10 border-[#9EE493] text-white' : 'bg-slate-800/80 border-slate-700/80 text-slate-300 hover:bg-slate-800'
                ]"
              >
                <div>
                  <div class="font-semibold text-xs">4:5 Portrait</div>
                  <div class="text-[10px] text-slate-400">High-Engagement Mobile Feed Height</div>
                </div>
                <Check v-if="selectedAspectRatio === '4:5'" class="w-4 h-4 text-[#9EE493]" />
              </button>

              <button
                @click="selectedAspectRatio = '9:16'; renderImageToCanvas()"
                :class="[
                  'w-full p-3 rounded-2xl border text-left flex items-center justify-between transition',
                  selectedAspectRatio === '9:16' ? 'bg-[#9EE493]/10 border-[#9EE493] text-white' : 'bg-slate-800/80 border-slate-700/80 text-slate-300 hover:bg-slate-800'
                ]"
              >
                <div>
                  <div class="font-semibold text-xs">9:16 Full Vertical</div>
                  <div class="text-[10px] text-slate-400">TikTok, Instagram Reels & Stories</div>
                </div>
                <Check v-if="selectedAspectRatio === '9:16'" class="w-4 h-4 text-[#9EE493]" />
              </button>
            </div>
          </div>

          <!-- TAB 3: ORIENTATION & TRANSFORMS -->
          <div v-if="activeTab === 'transform'" class="space-y-4">
            <div class="space-y-2">
              <label class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider font-mono">
                Rotation Controls
              </label>
              <div class="grid grid-cols-2 gap-2">
                <button
                  @click="rotateLeft"
                  class="flex items-center justify-center gap-2 py-2.5 rounded-2xl bg-slate-800 hover:bg-slate-750 border border-slate-700 text-xs font-medium transition"
                >
                  <RotateCcw class="w-4 h-4 text-[#9EE493]" />
                  <span>90° Left</span>
                </button>

                <button
                  @click="rotateRight"
                  class="flex items-center justify-center gap-2 py-2.5 rounded-2xl bg-slate-800 hover:bg-slate-750 border border-slate-700 text-xs font-medium transition"
                >
                  <RotateCw class="w-4 h-4 text-[#9EE493]" />
                  <span>90° Right</span>
                </button>
              </div>
            </div>

            <div class="space-y-2 pt-2">
              <label class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider font-mono">
                Axis Flipping
              </label>
              <div class="grid grid-cols-2 gap-2">
                <button
                  @click="toggleFlipH"
                  :class="[
                    'flex items-center justify-center gap-2 py-2.5 rounded-2xl border text-xs font-medium transition',
                    flipH ? 'bg-[#9EE493]/15 border-[#9EE493] text-[#9EE493]' : 'bg-slate-800 border-slate-700 text-slate-300 hover:bg-slate-750'
                  ]"
                >
                  <FlipHorizontal class="w-4 h-4" />
                  <span>Flip Horizontal</span>
                </button>

                <button
                  @click="toggleFlipV"
                  :class="[
                    'flex items-center justify-center gap-2 py-2.5 rounded-2xl border text-xs font-medium transition',
                    flipV ? 'bg-[#9EE493]/15 border-[#9EE493] text-[#9EE493]' : 'bg-slate-800 border-slate-700 text-slate-300 hover:bg-slate-750'
                  ]"
                >
                  <FlipVertical class="w-4 h-4" />
                  <span>Flip Vertical</span>
                </button>
              </div>
            </div>
          </div>

          <!-- TAB 4: WATERMARK & BRAND TYPOGRAPHY -->
          <div v-if="activeTab === 'text'" class="space-y-4">
            <div class="space-y-1.5">
              <label class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider font-mono">
                Watermark or Executive Headline
              </label>
              <input
                type="text"
                v-model="overlayText"
                @input="renderImageToCanvas"
                placeholder="e.g. Synthex • @alexrivera_tech"
                class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-[#9EE493]"
              />
            </div>

            <div class="space-y-1.5">
              <label class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider font-mono">
                Position
              </label>
              <div class="grid grid-cols-2 gap-1.5 text-xs">
                <button
                  v-for="pos in [
                    { id: 'bottom-right', label: 'Bottom Right' },
                    { id: 'bottom-left', label: 'Bottom Left' },
                    { id: 'top-right', label: 'Top Right' },
                    { id: 'top-left', label: 'Top Left' },
                    { id: 'center', label: 'Center' },
                  ]"
                  :key="pos.id"
                  @click="textPosition = pos.id as any; renderImageToCanvas()"
                  :class="[
                    'py-2 px-2.5 rounded-xl border text-center transition',
                    textPosition === pos.id ? 'bg-[#9EE493] text-slate-950 font-bold border-[#9EE493]' : 'bg-slate-800 border-slate-700 text-slate-300 hover:bg-slate-750'
                  ]"
                >
                  {{ pos.label }}
                </button>
              </div>
            </div>

            <div class="space-y-2 pt-2">
              <div class="flex items-center justify-between">
                <span class="text-xs text-slate-300">Frosted Glass Badge</span>
                <input
                  type="checkbox"
                  v-model="showTextBadge"
                  @change="renderImageToCanvas"
                  class="rounded accent-[#9EE493] cursor-pointer"
                />
              </div>

              <div class="space-y-1">
                <div class="flex justify-between text-xs text-slate-300">
                  <span>Font Scale</span>
                  <span class="font-mono text-slate-400">{{ textSize }}pt</span>
                </div>
                <input
                  type="range"
                  min="16"
                  max="64"
                  v-model.number="textSize"
                  @input="renderImageToCanvas"
                  class="w-full accent-[#9EE493] h-1.5 bg-slate-800 rounded-lg cursor-pointer"
                />
              </div>
            </div>
          </div>

        </div>

      </div>

    </div>
  </div>
</template>
