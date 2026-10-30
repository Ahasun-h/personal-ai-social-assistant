<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import {
  Play,
  Pause,
  Scissors,
  Trash2,
  Volume2,
  VolumeX,
  Camera,
  RotateCcw,
  RotateCw,
  Sliders,
  Type,
  FastForward,
  Rewind,
  Maximize2,
  Layers,
  Ratio,
  Sparkles,
  Download,
  Save,
  Check,
  Split,
  ZoomIn,
  ZoomOut,
  Video as VideoIcon,
  Mic,
} from 'lucide-vue-next';

interface MediaAsset {
  id: string;
  filename: string;
  path: string;
  width?: number;
  height?: number;
}

interface VideoClip {
  id: string;
  title: string;
  startTime: number;
  endTime: number;
  duration: number;
}

interface TextOverlayTrack {
  id: string;
  text: string;
  subtext?: string;
  startTime: number;
  endTime: number;
  position: 'lower-third' | 'top-banner' | 'center';
  style: 'badge' | 'minimal' | 'bold';
}

interface Props {
  asset: MediaAsset | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  (e: 'save-frame', blob: Blob, filename: string): void;
  (e: 'close'): void;
}>();

// Video Element Reference
const videoRef = ref<HTMLVideoElement | null>(null);

// Playback States
const isPlaying = ref(false);
const isMuted = ref(false);
const volume = ref(100);
const currentTime = ref(0);
const totalDuration = ref(30); // Default fallback until metadata loads
const playbackSpeed = ref(1);

// Clipchamp Aspect Ratio Framing
const aspectRatios = ['16:9', '9:16', '1:1', '4:5'] as const;
const selectedAspect = ref<'16:9' | '9:16' | '1:1' | '4:5'>('16:9');

// Active Inspector Tab in Clipchamp Sidebar
const activeTab = ref<'trim' | 'text' | 'effects' | 'speed' | 'audio'>('trim');

// Color Filters & Effects
const filters = ref({
  preset: 'none',
  brightness: 100,
  contrast: 100,
  saturation: 100,
  sepia: 0,
  grayscale: 0,
});

const computedFilterString = computed(() => {
  const f = filters.value;
  return `brightness(${f.brightness}%) contrast(${f.contrast}%) saturate(${f.saturation}%) sepia(${f.sepia}%) grayscale(${f.grayscale}%)`;
});

// Multi-Track Video Timeline Clips
const videoClips = ref<VideoClip[]>([
  {
    id: 'clip-1',
    title: 'Main Stream A',
    startTime: 0,
    endTime: 30,
    duration: 30,
  },
]);
const selectedClipId = ref<string>('clip-1');

// Text Overlay Track (Subtitles & Lower Thirds)
const textOverlays = ref<TextOverlayTrack[]>([
  {
    id: 'text-track-1',
    text: 'Alex Rivera — Systems Architect',
    subtext: 'High-Throughput Distributed Architecture',
    startTime: 2,
    endTime: 12,
    position: 'lower-third',
    style: 'badge',
  },
]);

// Timeline Zoom Level
const timelineZoom = ref(1); // 1x to 4x

// Formatted Timecodes (MM:SS.ms)
const formatTime = (seconds: number): string => {
  const mins = Math.floor(seconds / 60);
  const secs = Math.floor(seconds % 60);
  const ms = Math.floor((seconds % 1) * 10);
  return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}.${ms}`;
};

// Video Lifecycle Events
const onLoadedMetadata = () => {
  if (!videoRef.value) return;
  const dur = videoRef.value.duration || 30;
  totalDuration.value = dur;
  if (videoClips.value.length === 1 && videoClips.value[0].endTime === 30) {
    videoClips.value[0].endTime = dur;
    videoClips.value[0].duration = dur;
  }
};

const onTimeUpdate = () => {
  if (!videoRef.value) return;
  currentTime.value = videoRef.value.currentTime;
};

// Playhead & Control Actions
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

const seekToTime = (time: number) => {
  if (!videoRef.value) return;
  const clamped = Math.max(0, Math.min(time, totalDuration.value));
  videoRef.value.currentTime = clamped;
  currentTime.value = clamped;
};

const jumpTime = (delta: number) => {
  seekToTime(currentTime.value + delta);
};

const toggleMute = () => {
  if (!videoRef.value) return;
  videoRef.value.muted = !videoRef.value.muted;
  isMuted.value = videoRef.value.muted;
};

const updateVolume = () => {
  if (!videoRef.value) return;
  videoRef.value.volume = volume.value / 100;
  if (volume.value === 0) {
    isMuted.value = true;
  } else if (isMuted.value) {
    isMuted.value = false;
    videoRef.value.muted = false;
  }
};

const setSpeed = (speed: number) => {
  playbackSpeed.value = speed;
  if (videoRef.value) {
    videoRef.value.playbackRate = speed;
  }
};

// CLIPCHAMP EDITING: Split Clip at Playhead
const splitClipAtPlayhead = () => {
  const splitTime = currentTime.value;
  const targetIndex = videoClips.value.findIndex(
    (c) => splitTime > c.startTime && splitTime < c.endTime
  );

  if (targetIndex === -1) return;

  const original = videoClips.value[targetIndex];
  const firstPart: VideoClip = {
    id: original.id,
    title: `${original.title} (Part 1)`,
    startTime: original.startTime,
    endTime: splitTime,
    duration: splitTime - original.startTime,
  };

  const secondPart: VideoClip = {
    id: `clip-${Date.now()}`,
    title: `${original.title} (Part 2)`,
    startTime: splitTime,
    endTime: original.endTime,
    duration: original.endTime - splitTime,
  };

  videoClips.value.splice(targetIndex, 1, firstPart, secondPart);
  selectedClipId.value = secondPart.id;
};

// Delete Selected Clip
const deleteSelectedClip = () => {
  if (videoClips.value.length <= 1) return;
  videoClips.value = videoClips.value.filter((c) => c.id !== selectedClipId.value);
  selectedClipId.value = videoClips.value[0]?.id || '';
};

// Snap Current Video Frame to Media Vault
const captureFrame = () => {
  if (!videoRef.value) return;
  const video = videoRef.value;
  const canvas = document.createElement('canvas');
  canvas.width = video.videoWidth || 1280;
  canvas.height = video.videoHeight || 720;
  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  ctx.filter = computedFilterString.value;
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

  canvas.toBlob((blob) => {
    if (!blob) return;
    const filename = `clipchamp_snap_${Math.floor(currentTime.value)}s_${props.asset?.filename || 'video'}.jpg`;
    emit('save-frame', blob, filename);
  }, 'image/jpeg', 0.95);
};

// Apply Filter Presets
const applyFilterPreset = (preset: string) => {
  filters.value.preset = preset;
  if (preset === 'cinematic') {
    filters.value.contrast = 125;
    filters.value.brightness = 95;
    filters.value.saturation = 110;
    filters.value.sepia = 15;
    filters.value.grayscale = 0;
  } else if (preset === 'noir') {
    filters.value.contrast = 140;
    filters.value.brightness = 90;
    filters.value.grayscale = 100;
    filters.value.saturation = 0;
    filters.value.sepia = 0;
  } else if (preset === 'cyberpunk') {
    filters.value.contrast = 135;
    filters.value.saturation = 160;
    filters.value.brightness = 105;
    filters.value.sepia = 0;
    filters.value.grayscale = 0;
  } else if (preset === 'vintage') {
    filters.value.sepia = 60;
    filters.value.saturation = 85;
    filters.value.contrast = 105;
    filters.value.brightness = 95;
    filters.value.grayscale = 0;
  } else {
    // Normal
    filters.value.contrast = 100;
    filters.value.brightness = 100;
    filters.value.saturation = 100;
    filters.value.sepia = 0;
    filters.value.grayscale = 0;
  }
};

// Active Overlay Display Check
const activeOverlay = computed(() => {
  const t = currentTime.value;
  return textOverlays.value.find((ov) => t >= ov.startTime && t <= ov.endTime) || null;
});

// Timeline Click to Seek
const onTimelineClick = (e: MouseEvent) => {
  const target = e.currentTarget as HTMLElement;
  const rect = target.getBoundingClientRect();
  const clickPercent = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
  const newTime = clickPercent * totalDuration.value;
  seekToTime(newTime);
};
</script>

<template>
  <div class="flex-1 flex flex-col overflow-hidden bg-slate-950 font-sans select-none text-white">
    
    <!-- TOP TOOLBAR (Clipchamp Style: Aspect Switcher, Timecode, Actions) -->
    <div class="px-6 py-3 bg-slate-900 border-b border-slate-800 flex items-center justify-between shrink-0">
      <div class="flex items-center gap-4">
        <!-- Logo / Title -->
        <div class="flex items-center gap-2">
          <div class="w-7 h-7 rounded-lg bg-emerald-500 text-slate-950 flex items-center justify-center font-bold text-xs">
            ▶
          </div>
          <span class="font-display font-bold text-xs tracking-tight text-white">
            Clipchamp Timeline Engine
          </span>
        </div>

        <!-- Aspect Ratio Selector Pills -->
        <div class="flex items-center gap-1 bg-slate-950 p-1 rounded-xl border border-slate-800">
          <button
            v-for="aspect in aspectRatios"
            :key="aspect"
            @click="selectedAspect = aspect"
            :class="[
              'px-2.5 py-1 rounded-lg text-xs font-mono font-bold transition',
              selectedAspect === aspect ? 'bg-[#9EE493] text-slate-950' : 'text-slate-400 hover:text-white'
            ]"
          >
            {{ aspect }}
          </button>
        </div>
      </div>

      <!-- Timecode Counter -->
      <div class="flex items-center gap-3">
        <div class="bg-slate-950 px-3.5 py-1.5 rounded-xl border border-slate-800 font-mono text-xs font-bold text-[#9EE493]">
          {{ formatTime(currentTime) }} / {{ formatTime(totalDuration) }}
        </div>

        <button
          @click="captureFrame"
          class="flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-semibold text-slate-200 transition"
          title="Snap frame as high-res cover image"
        >
          <Camera class="w-3.5 h-3.5 text-emerald-400" />
          <span>Snap Frame</span>
        </button>
      </div>
    </div>

    <!-- MIDDLE WORKSPACE (Video Player & Right Controls Drawer) -->
    <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 overflow-hidden min-h-[360px]">
      
      <!-- Video Viewport Stage -->
      <div class="lg:col-span-8 bg-slate-950 p-6 flex flex-col items-center justify-center relative overflow-hidden">
        
        <!-- Framing Container based on Aspect Ratio -->
        <div
          class="relative rounded-2xl overflow-hidden shadow-2xl border border-slate-800 bg-black flex items-center justify-center transition-all duration-300"
          :class="[
            selectedAspect === '16:9' ? 'aspect-video w-full max-w-2xl' : '',
            selectedAspect === '9:16' ? 'aspect-[9/16] h-[48vh]' : '',
            selectedAspect === '1:1' ? 'aspect-square h-[48vh]' : '',
            selectedAspect === '4:5' ? 'aspect-[4/5] h-[48vh]' : '',
          ]"
        >
          <!-- HTML5 Video Player -->
          <video
            ref="videoRef"
            :src="asset?.path"
            class="w-full h-full object-contain transition"
            :style="{ filter: computedFilterString }"
            @loadedmetadata="onLoadedMetadata"
            @timeupdate="onTimeUpdate"
            @ended="isPlaying = false"
          ></video>

          <!-- Safe Margins Social Guide Overlay -->
          <div class="absolute inset-4 border border-dashed border-white/20 pointer-events-none rounded-xl"></div>

          <!-- Live Timed Subtitle / Lower Third Overlay -->
          <div
            v-if="activeOverlay"
            class="absolute bottom-6 left-6 right-6 transition-all duration-300"
          >
            <div class="bg-slate-950/85 backdrop-blur-md border border-white/20 rounded-2xl p-3.5 shadow-2xl inline-block max-w-lg">
              <div class="font-display font-bold text-sm text-white flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#9EE493]"></span>
                <span>{{ activeOverlay.text }}</span>
              </div>
              <p v-if="activeOverlay.subtext" class="text-xs text-slate-300 mt-0.5">
                {{ activeOverlay.subtext }}
              </p>
            </div>
          </div>
        </div>

      </div>

      <!-- Right Inspector Sidebar (Clipchamp Tools) -->
      <div class="lg:col-span-4 bg-slate-900 border-l border-slate-800 p-5 flex flex-col space-y-4 overflow-y-auto">
        
        <!-- Tabs Header -->
        <div class="flex gap-1 p-1 bg-slate-950 rounded-2xl border border-slate-800">
          <button
            v-for="t in [
              { id: 'trim', label: 'Timeline', icon: Scissors },
              { id: 'effects', label: 'Effects', icon: Sparkles },
              { id: 'text', label: 'Captions', icon: Type },
              { id: 'speed', label: 'Speed', icon: FastForward },
              { id: 'audio', label: 'Audio', icon: Volume2 },
            ]"
            :key="t.id"
            @click="activeTab = t.id as any"
            :class="[
              'flex-1 py-1.5 rounded-xl text-xs font-semibold flex items-center justify-center gap-1 transition',
              activeTab === t.id ? 'bg-slate-800 text-white shadow-xs' : 'text-slate-400 hover:text-white'
            ]"
          >
            <component :is="t.icon" class="w-3 h-3 text-[#9EE493]" />
            <span>{{ t.label }}</span>
          </button>
        </div>

        <!-- TAB: TIMELINE & SPLIT INFO -->
        <div v-if="activeTab === 'trim'" class="space-y-4 pt-1">
          <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider font-mono">
            Clip Sequence & Slicing
          </h3>
          <p class="text-[11px] text-slate-400">
            Use the scissor icon on the timeline to slice video at the playhead position.
          </p>

          <div class="space-y-2">
            <div
              v-for="clip in videoClips"
              :key="clip.id"
              @click="selectedClipId = clip.id; seekToTime(clip.startTime)"
              :class="[
                'p-3 rounded-2xl border flex items-center justify-between cursor-pointer transition',
                selectedClipId === clip.id ? 'bg-[#9EE493]/15 border-[#9EE493] text-white' : 'bg-slate-800/80 border-slate-700/80 text-slate-300'
              ]"
            >
              <div>
                <div class="font-bold text-xs">{{ clip.title }}</div>
                <div class="text-[10px] text-slate-400 font-mono">
                  {{ formatTime(clip.startTime) }} → {{ formatTime(clip.endTime) }} ({{ clip.duration.toFixed(1) }}s)
                </div>
              </div>
              <Check v-if="selectedClipId === clip.id" class="w-4 h-4 text-[#9EE493]" />
            </div>
          </div>
        </div>

        <!-- TAB: EFFECTS & COLOR GRADING -->
        <div v-if="activeTab === 'effects'" class="space-y-4 pt-1">
          <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider font-mono">
            Cinematic Color Grade
          </h3>

          <div class="grid grid-cols-2 gap-2">
            <button
              v-for="pr in ['none', 'cinematic', 'cyberpunk', 'noir', 'vintage']"
              :key="pr"
              @click="applyFilterPreset(pr)"
              :class="[
                'py-2 px-3 rounded-xl border text-xs font-semibold capitalize transition',
                filters.preset === pr ? 'bg-[#9EE493] text-slate-950 border-[#9EE493]' : 'bg-slate-800 border-slate-700 text-slate-300 hover:bg-slate-750'
              ]"
            >
              {{ pr }}
            </button>
          </div>

          <div class="space-y-3 pt-2">
            <div class="space-y-1">
              <div class="flex justify-between text-xs text-slate-300">
                <span>Brightness</span>
                <span class="font-mono text-slate-400">{{ filters.brightness }}%</span>
              </div>
              <input
                type="range"
                min="50"
                max="150"
                v-model.number="filters.brightness"
                class="w-full accent-[#9EE493] h-1.5 bg-slate-800 rounded-lg cursor-pointer"
              />
            </div>

            <div class="space-y-1">
              <div class="flex justify-between text-xs text-slate-300">
                <span>Contrast</span>
                <span class="font-mono text-slate-400">{{ filters.contrast }}%</span>
              </div>
              <input
                type="range"
                min="50"
                max="150"
                v-model.number="filters.contrast"
                class="w-full accent-[#9EE493] h-1.5 bg-slate-800 rounded-lg cursor-pointer"
              />
            </div>

            <div class="space-y-1">
              <div class="flex justify-between text-xs text-slate-300">
                <span>Saturation</span>
                <span class="font-mono text-slate-400">{{ filters.saturation }}%</span>
              </div>
              <input
                type="range"
                min="0"
                max="200"
                v-model.number="filters.saturation"
                class="w-full accent-[#9EE493] h-1.5 bg-slate-800 rounded-lg cursor-pointer"
              />
            </div>
          </div>
        </div>

        <!-- TAB: CAPTIONS & SUBTITLES -->
        <div v-if="activeTab === 'text'" class="space-y-4 pt-1">
          <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider font-mono">
            Lower Third & Subtitles
          </h3>

          <div v-if="textOverlays[0]" class="p-3.5 rounded-2xl bg-slate-950 border border-slate-800 space-y-3">
            <div>
              <label class="text-[10px] text-slate-400 block mb-1">Speaker / Title</label>
              <input
                v-model="textOverlays[0].text"
                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:border-[#9EE493]"
              />
            </div>

            <div>
              <label class="text-[10px] text-slate-400 block mb-1">Role / Description</label>
              <input
                v-model="textOverlays[0].subtext"
                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:border-[#9EE493]"
              />
            </div>

            <div class="grid grid-cols-2 gap-2 text-xs font-mono">
              <div>
                <span class="text-[10px] text-slate-400 block mb-1">Start Time (s)</span>
                <input
                  type="number"
                  v-model.number="textOverlays[0].startTime"
                  class="w-full bg-slate-900 border border-slate-700 rounded-xl px-2.5 py-1 text-white"
                />
              </div>

              <div>
                <span class="text-[10px] text-slate-400 block mb-1">End Time (s)</span>
                <input
                  type="number"
                  v-model.number="textOverlays[0].endTime"
                  class="w-full bg-slate-900 border border-slate-700 rounded-xl px-2.5 py-1 text-white"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- TAB: SPEED -->
        <div v-if="activeTab === 'speed'" class="space-y-4 pt-1">
          <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider font-mono">
            Playback Speed Ramp
          </h3>

          <div class="grid grid-cols-3 gap-2">
            <button
              v-for="s in [0.25, 0.5, 0.75, 1, 1.25, 1.5, 2]"
              :key="s"
              @click="setSpeed(s)"
              :class="[
                'py-2 rounded-xl border text-xs font-mono font-bold transition',
                playbackSpeed === s ? 'bg-[#9EE493] text-slate-950 border-[#9EE493]' : 'bg-slate-800 border-slate-700 text-slate-300 hover:bg-slate-750'
              ]"
            >
              {{ s }}x
            </button>
          </div>
        </div>

        <!-- TAB: AUDIO -->
        <div v-if="activeTab === 'audio'" class="space-y-4 pt-1">
          <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider font-mono">
            Audio Track & Volume
          </h3>

          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-300">Mute Audio</span>
            <button
              @click="toggleMute"
              class="px-3 py-1 rounded-xl text-xs font-semibold border transition"
              :class="isMuted ? 'bg-rose-500/20 text-rose-400 border-rose-500/40' : 'bg-slate-800 text-slate-300 border-slate-700'"
            >
              {{ isMuted ? 'Muted' : 'Unmuted' }}
            </button>
          </div>

          <div class="space-y-1">
            <div class="flex justify-between text-xs text-slate-300">
              <span>Master Volume</span>
              <span class="font-mono text-slate-400">{{ volume }}%</span>
            </div>
            <input
              type="range"
              min="0"
              max="200"
              v-model.number="volume"
              @input="updateVolume"
              class="w-full accent-[#9EE493] h-1.5 bg-slate-800 rounded-lg cursor-pointer"
            />
          </div>
        </div>

      </div>

    </div>

    <!-- BOTTOM CLIPCHAMP MULTI-TRACK TIMELINE -->
    <div class="h-64 bg-slate-900 border-t border-slate-800 flex flex-col shrink-0">
      
      <!-- Timeline Control Bar -->
      <div class="px-6 py-2.5 bg-slate-950 border-b border-slate-800/80 flex items-center justify-between shrink-0">
        <!-- Play / Scrubber Controls -->
        <div class="flex items-center gap-2">
          <button
            @click="jumpTime(-5)"
            class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition"
            title="Rewind 5s"
          >
            <Rewind class="w-4 h-4" />
          </button>

          <button
            @click="togglePlay"
            class="w-8 h-8 rounded-full bg-[#9EE493] text-slate-950 flex items-center justify-center font-bold hover:scale-105 transition shadow-sm"
          >
            <Pause v-if="isPlaying" class="w-4 h-4 fill-current" />
            <Play v-else class="w-4 h-4 fill-current ml-0.5" />
          </button>

          <button
            @click="jumpTime(5)"
            class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition"
            title="Fast Forward 5s"
          >
            <FastForward class="w-4 h-4" />
          </button>

          <div class="h-4 w-[1px] bg-slate-800 mx-2"></div>

          <!-- ✂️ Split Clip Action -->
          <button
            @click="splitClipAtPlayhead"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-750 text-white border border-slate-700 text-xs font-semibold transition"
            title="Slice video clip at current playhead"
          >
            <Scissors class="w-3.5 h-3.5 text-[#9EE493]" />
            <span>Split (Cut)</span>
          </button>

          <!-- 🗑️ Delete Segment -->
          <button
            @click="deleteSelectedClip"
            :disabled="videoClips.length <= 1"
            class="flex items-center gap-1 px-3 py-1.5 rounded-xl text-rose-400 hover:text-rose-300 hover:bg-rose-950/40 border border-slate-800 text-xs font-semibold transition disabled:opacity-30"
            title="Delete active clip"
          >
            <Trash2 class="w-3.5 h-3.5" />
            <span>Delete Clip</span>
          </button>
        </div>

        <!-- Timeline Zoom -->
        <div class="flex items-center gap-2 text-xs text-slate-400 font-mono">
          <ZoomOut class="w-3.5 h-3.5" />
          <input
            type="range"
            min="1"
            max="3"
            step="0.5"
            v-model.number="timelineZoom"
            class="w-24 accent-[#9EE493] h-1.5 bg-slate-800 rounded-lg cursor-pointer"
          />
          <ZoomIn class="w-3.5 h-3.5" />
        </div>
      </div>

      <!-- Timeline Tracks Viewport -->
      <div
        class="flex-1 overflow-x-auto overflow-y-hidden p-4 relative cursor-pointer select-none bg-slate-950/60"
        @click="onTimelineClick"
      >
        <!-- Time Ruler -->
        <div class="relative w-full h-5 border-b border-slate-800 flex items-center text-[10px] font-mono text-slate-500 mb-2">
          <div class="absolute left-0">00:00</div>
          <div class="absolute left-1/4">00:07</div>
          <div class="absolute left-2/4">00:15</div>
          <div class="absolute left-3/4">00:22</div>
          <div class="absolute right-0">{{ formatTime(totalDuration) }}</div>
        </div>

        <!-- TRACK 1: Text & Lower Third Overlays -->
        <div class="w-full h-8 bg-slate-900/60 rounded-xl border border-slate-800 mb-2 relative flex items-center px-2 overflow-hidden">
          <div class="text-[9px] font-mono uppercase text-slate-500 w-16 shrink-0">Text</div>
          <div
            v-for="txt in textOverlays"
            :key="txt.id"
            class="absolute h-6 rounded-lg bg-indigo-500/30 border border-indigo-400/50 flex items-center px-2 text-[10px] font-semibold text-indigo-200 truncate"
            :style="{
              left: `${(txt.startTime / totalDuration) * 100}%`,
              width: `${((txt.endTime - txt.startTime) / totalDuration) * 100}%`,
            }"
          >
            <span>💬 {{ txt.text }}</span>
          </div>
        </div>

        <!-- TRACK 2: Video Clips Track -->
        <div class="w-full h-12 bg-slate-900 rounded-xl border border-slate-800 mb-2 relative flex items-center px-1 gap-1 overflow-hidden">
          <div class="text-[9px] font-mono uppercase text-slate-500 w-16 shrink-0 pl-1">Video</div>
          
          <div
            v-for="clip in videoClips"
            :key="clip.id"
            @click.stop="selectedClipId = clip.id"
            :class="[
              'h-9 rounded-lg border flex items-center justify-between px-3 text-xs font-bold transition relative group',
              selectedClipId === clip.id ? 'bg-[#9EE493]/25 border-[#9EE493] text-white shadow-sm' : 'bg-slate-800 border-slate-700 text-slate-300'
            ]"
            :style="{
              width: `${(clip.duration / totalDuration) * 100}%`,
            }"
          >
            <div class="flex items-center gap-1.5 truncate">
              <VideoIcon class="w-3.5 h-3.5 text-[#9EE493]" />
              <span class="truncate">{{ clip.title }}</span>
            </div>
            <span class="text-[10px] font-mono text-slate-400">{{ clip.duration.toFixed(1) }}s</span>

            <!-- Trim Handles (Left / Right) -->
            <div class="absolute left-0 inset-y-0 w-1.5 bg-[#9EE493] opacity-0 group-hover:opacity-100 rounded-l-lg"></div>
            <div class="absolute right-0 inset-y-0 w-1.5 bg-[#9EE493] opacity-0 group-hover:opacity-100 rounded-r-lg"></div>
          </div>
        </div>

        <!-- TRACK 3: Audio Waveform Track -->
        <div class="w-full h-8 bg-slate-900/60 rounded-xl border border-slate-800 relative flex items-center px-2 overflow-hidden">
          <div class="text-[9px] font-mono uppercase text-slate-500 w-16 shrink-0">Audio</div>
          <!-- Waveform Simulation Bars -->
          <div class="flex-1 flex items-center gap-0.5 h-full opacity-60">
            <div
              v-for="n in 60"
              :key="n"
              class="flex-1 bg-emerald-400 rounded-full"
              :style="{ height: `${Math.max(15, (Math.sin(n * 0.4) * 0.5 + 0.5) * 85)}%` }"
            ></div>
          </div>
        </div>

        <!-- VERTICAL RED PLAYHEAD SCRUBBER -->
        <div
          class="absolute top-0 bottom-0 w-0.5 bg-rose-500 z-30 pointer-events-none transition-all duration-75"
          :style="{ left: `${(currentTime / totalDuration) * 100}%` }"
        >
          <!-- Playhead Top Knob -->
          <div class="w-3.5 h-3.5 bg-rose-500 -ml-1.5 rotate-45 rounded-sm shadow-md"></div>
        </div>

      </div>

    </div>

  </div>
</template>
