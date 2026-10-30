<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import {
  Sparkles,
  Video as VideoIcon,
  Image as ImageIcon,
  Type,
  UploadCloud,
  Shapes,
  Sliders,
  Layers,
  Wand2,
  Undo2,
  Redo2,
  Search,
  Download,
  Play,
  Pause,
  Check,
  X as CloseIcon,
  Plus,
  Trash2,
  ChevronDown,
  ChevronUp,
  Eye,
  EyeOff,
  RotateCcw,
  RotateCw,
  Crop,
  MoreHorizontal,
  Camera,
  Film,
  Volume2,
  VolumeX,
  Save,
  Palette,
  Maximize2,
  Move,
  Quote,
  TrendingUp,
  Tag,
  Copy,
  FlipHorizontal,
  FlipVertical,
  CheckCircle2,
  AlertCircle,
  FileText,
  CornerDownRight,
  Clock,
  LayoutTemplate,
  Square,
  Circle,
  ArrowRight,
  ExternalLink,
} from 'lucide-vue-next';

interface MediaAsset {
  id: string;
  filename: string;
  path: string;
  mime_type?: string;
  width?: number;
  height?: number;
  duration_seconds?: number;
  is_ai_generated?: boolean;
}

interface CanvasElement {
  id: string;
  type: 'text' | 'metric' | 'quote' | 'pill' | 'shape';
  text: string;
  subtext?: string;
  x: number;
  y: number;
  fontSize: number;
  fontFamily: string;
  color: string;
  bgColor: string;
  bgPadding: number;
  hasBackground: boolean;
  isBold: boolean;
  isItalic: boolean;
  opacity: number;
  visible: boolean;
}

interface Props {
  asset: MediaAsset | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  (e: 'save', blob: Blob, filename: string): void;
  (e: 'close'): void;
}>();

// ==========================================
// 1. ACTIVE ASSET & CANVAS MODE
// ==========================================
const currentAsset = ref<MediaAsset | null>(props.asset ? { ...props.asset } : null);
const projectTitle = ref<string>(props.asset?.filename ? props.asset.filename.replace(/\.[^/.]+$/, "") : 'Executive Studio Project');

const isVideoAsset = computed(() => {
  if (!currentAsset.value) return false;
  if (currentAsset.value.mime_type?.startsWith('video/')) return true;
  const ext = currentAsset.value.filename.split('.').pop()?.toLowerCase();
  return ['mp4', 'mov', 'webm', 'mkv'].includes(ext || '');
});

// ==========================================
// 2. TOOLBAR & COLLAPSIBLE FLYOUT DRAWER
// ==========================================
type ActiveTool = 'ai_video' | 'ai_image' | 'templates' | 'uploads' | 'text' | 'elements' | 'filters' | 'aspect' | 'layers';
const activeTool = ref<ActiveTool | null>('ai_video');
const isFlyoutOpen = ref(true);

const toggleTool = (tool: ActiveTool) => {
  if (activeTool.value === tool && isFlyoutOpen.value) {
    isFlyoutOpen.value = false;
  } else {
    activeTool.value = tool;
    isFlyoutOpen.value = true;
  }
};

// Universal Tool Search
const searchQuery = ref('');
const allToolsList = [
  { id: 'ai_video', name: 'AI Video Generator', category: 'AI Tools', icon: Film },
  { id: 'ai_image', name: 'AI Image Synthesizer', category: 'AI Tools', icon: Wand2 },
  { id: 'templates', name: 'Social Card Layouts', category: 'Templates', icon: LayoutTemplate },
  { id: 'uploads', name: 'Media Vault & Uploads', category: 'Assets', icon: UploadCloud },
  { id: 'text', name: 'Typography & Quotes', category: 'Design', icon: Type },
  { id: 'elements', name: 'Shapes & Metric Badges', category: 'Design', icon: Shapes },
  { id: 'filters', name: 'Effects & Color Tuning', category: 'Effects', icon: Sliders },
  { id: 'aspect', name: 'Aspect Ratios & Crop', category: 'Canvas', icon: Crop },
  { id: 'layers', name: 'Layers Stack', category: 'Manage', icon: Layers },
];

const filteredTools = computed(() => {
  if (!searchQuery.value) return [];
  const q = searchQuery.value.toLowerCase();
  return allToolsList.filter(t => t.name.toLowerCase().includes(q) || t.category.toLowerCase().includes(q));
});

// File Menu Modal / Dropdown
const isFileMenuOpen = ref(false);

// Floating Canvas Context Menu
const isContextMenuOpen = ref(false);

// Toast Notification
const toastMessage = ref<{ type: 'success' | 'error'; text: string } | null>(null);
const showToast = (text: string, type: 'success' | 'error' = 'success') => {
  toastMessage.value = { type, text };
  setTimeout(() => {
    toastMessage.value = null;
  }, 3500);
};

// ==========================================
// 3. AI VIDEO GENERATOR (Image to Video & Text to Video)
// ==========================================
const videoTab = ref<'image_to_video' | 'text_to_video'>('image_to_video');
const motionPrompt = ref('');
const textVideoPrompt = ref('');
const selectedModel = ref('seedance-2.5');
const isModelDropdownOpen = ref(false);
const isGeneratingVideo = ref(false);
const videoGenerationProgress = ref(0);

const videoModels = [
  {
    id: 'seedance-2.5',
    name: 'Seedance 2.5',
    tag: 'Multimodal & Audio, up to 30s',
    cost: '20 credits ($0.08)',
    badge: 'Popular',
  },
  {
    id: 'wan-3.0',
    name: 'WAN 3.0',
    tag: 'All-In-One Reference, up to 30s',
    cost: '10 credits ($0.04)',
    badge: 'Fast',
  },
  {
    id: 'wan-3.0-prime',
    name: 'WAN 3.0 Prime',
    tag: 'Same as WAN 3.0, up to 7x faster',
    cost: '15 credits ($0.06)',
    badge: 'Turbo',
  },
  {
    id: 'runway-gen3',
    name: 'Runway Gen-3 Alpha',
    tag: 'Cinematic Photorealism & HDR',
    cost: '25 credits ($0.10)',
    badge: 'Cinema',
  },
  {
    id: 'kling-1.5',
    name: 'Kling 1.5 Pro',
    tag: 'Complex Physics & Consistent Motion',
    cost: '18 credits ($0.07)',
    badge: 'Coherent',
  },
];

const activeModelObj = computed(() => {
  return videoModels.find((m) => m.id === selectedModel.value) || videoModels[0];
});

const cameraMotion = ref<'pan_right' | 'zoom_in' | 'zoom_out' | 'orbit' | 'static'>('zoom_in');
const videoDuration = ref<number>(10);

const quickMotionTags = [
  'Slow Cinematic Zoom',
  'Fluid Pan Across Architecture',
  'Ambient Shimmer & Reflections',
  'Dynamic Orbit 360',
  'Depth-of-field rack focus',
];

const applyMotionTag = (tag: string) => {
  if (motionPrompt.value) {
    motionPrompt.value += `, ${tag}`;
  } else {
    motionPrompt.value = tag;
  }
};

const triggerGenerateVideo = async () => {
  isGeneratingVideo.value = true;
  videoGenerationProgress.value = 15;

  const timer = setInterval(() => {
    if (videoGenerationProgress.value < 90) {
      videoGenerationProgress.value += 15;
    }
  }, 400);

  try {
    const res = await fetch('/api/v1/media/ai/video-generate', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        mode: videoTab.value,
        prompt: videoTab.value === 'image_to_video' ? motionPrompt.value : textVideoPrompt.value,
        image_url: currentAsset.value?.path,
        model: selectedModel.value,
        duration: videoDuration.value,
        aspect_ratio: selectedAspect.value === '9:16' ? '9:16' : '16:9',
      }),
    });

    clearInterval(timer);
    videoGenerationProgress.value = 100;

    if (res.ok) {
      const data = await res.json();
      currentAsset.value = data.data;
      showToast('Video synthesized successfully with ' + activeModelObj.value.name + '!');
      nextTick(() => {
        if (videoPlayer.value) {
          videoPlayer.value.load();
          videoPlayer.value.play().catch(() => {});
          isPlaying.value = true;
        }
      });
    } else {
      const err = await res.json();
      showToast(err.message || 'Video generation failed.', 'error');
    }
  } catch (e: any) {
    clearInterval(timer);
    showToast(e.message || 'Network error triggering video synthesis.', 'error');
  } finally {
    setTimeout(() => {
      isGeneratingVideo.value = false;
      videoGenerationProgress.value = 0;
    }, 500);
  }
};

// ==========================================
// 4. AI IMAGE GENERATOR
// ==========================================
const imagePrompt = ref('');
const imageStyle = ref('photorealistic');
const isGeneratingImage = ref(false);

const imageStyles = [
  { id: 'photorealistic', name: 'Photorealistic' },
  { id: 'minimalist', name: 'Clean Architecture' },
  { id: 'cyberpunk', name: 'Cyberpunk Neon' },
  { id: '3d_render', name: 'Executive 3D Glass' },
];

const triggerGenerateImage = async () => {
  if (!imagePrompt.value) return;
  isGeneratingImage.value = true;

  try {
    const res = await fetch('/api/v1/media/ai/generate', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        prompt: `${imagePrompt.value}, style: ${imageStyle.value}`,
        aspect_ratio: selectedAspect.value === 'original' ? '16:9' : selectedAspect.value,
      }),
    });

    if (res.ok) {
      const data = await res.json();
      currentAsset.value = data.data;
      showToast('New AI Visual generated and loaded onto canvas!');
      loadImage();
    } else {
      const err = await res.json();
      showToast(err.message || 'Image generation failed.', 'error');
    }
  } catch (e: any) {
    showToast(e.message || 'Error generating image.', 'error');
  } finally {
    isGeneratingImage.value = false;
  }
};

// ==========================================
// 5. UPLOADS & MEDIA VAULT GALLERY
// ==========================================
const isUploadingFile = ref(false);
const fileInputRef = ref<HTMLInputElement | null>(null);

const handleStudioUpload = async (e: Event) => {
  const target = e.target as HTMLInputElement;
  const file = target.files?.[0];
  if (!file) return;

  isUploadingFile.value = true;
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
      const data = await res.json();
      currentAsset.value = data.data;
      showToast(`${file.name} loaded to canvas!`);
      loadImage();
    } else {
      showToast('File upload failed.', 'error');
    }
  } catch (err: any) {
    showToast(err.message || 'Upload error.', 'error');
  } finally {
    isUploadingFile.value = false;
  }
};

// ==========================================
// 6. CANVAS STAGE & INTERACTIVE ELEMENTS
// ==========================================
const canvasContainer = ref<HTMLDivElement | null>(null);
const mainCanvas = ref<HTMLCanvasElement | null>(null);
const sourceImage = ref<HTMLImageElement | null>(null);
const videoPlayer = ref<HTMLVideoElement | null>(null);

// Video Controls
const isPlaying = ref(false);
const currentTime = ref(0);
const duration = ref(10);
const isMuted = ref(false);
const playbackRate = ref(1);

const togglePlay = () => {
  if (!videoPlayer.value) return;
  if (isPlaying.value) {
    videoPlayer.value.pause();
    isPlaying.value = false;
  } else {
    videoPlayer.value.play();
    isPlaying.value = true;
  }
};

const seekVideo = (e: Event) => {
  const target = e.target as HTMLInputElement;
  const time = parseFloat(target.value);
  if (videoPlayer.value) {
    videoPlayer.value.currentTime = time;
    currentTime.value = time;
  }
};

const toggleMute = () => {
  if (!videoPlayer.value) return;
  videoPlayer.value.muted = !videoPlayer.value.muted;
  isMuted.value = videoPlayer.value.muted;
};

const setPlaybackRate = (rate: number) => {
  playbackRate.value = rate;
  if (videoPlayer.value) {
    videoPlayer.value.playbackRate = rate;
  }
};

// Social Aspect Ratios
const selectedAspect = ref<'16:9' | '1:1' | '4:5' | '9:16' | 'original'>('16:9');
const baseWidth = 1200;
const computedHeight = computed(() => {
  switch (selectedAspect.value) {
    case '1:1': return 1200;
    case '4:5': return 1500;
    case '9:16': return 2133;
    case '16:9': return 675;
    default: return 675;
  }
});

// Zoom Controls (86% default in reference screenshot)
const zoomLevel = ref(86);
const zoomIn = () => { zoomLevel.value = Math.min(200, zoomLevel.value + 10); };
const zoomOut = () => { zoomLevel.value = Math.max(30, zoomLevel.value - 10); };
const resetZoom = () => { zoomLevel.value = 86; };

// Color Fine-Tuning & Presets
const adjustments = ref({
  brightness: 100,
  contrast: 100,
  saturation: 100,
  warmth: 0,
  grayscale: 0,
  blur: 0,
  rotation: 0,
  flipH: false,
  flipV: false,
});

const applyFilterPreset = (preset: 'normal' | 'cinematic' | 'cyberpunk' | 'noir' | 'vintage' | 'vibrant') => {
  if (preset === 'cinematic') {
    adjustments.value.brightness = 95;
    adjustments.value.contrast = 130;
    adjustments.value.saturation = 115;
    adjustments.value.warmth = 20;
    adjustments.value.grayscale = 0;
  } else if (preset === 'cyberpunk') {
    adjustments.value.brightness = 105;
    adjustments.value.contrast = 140;
    adjustments.value.saturation = 175;
    adjustments.value.warmth = 0;
    adjustments.value.grayscale = 0;
  } else if (preset === 'noir') {
    adjustments.value.brightness = 90;
    adjustments.value.contrast = 150;
    adjustments.value.saturation = 0;
    adjustments.value.warmth = 0;
    adjustments.value.grayscale = 100;
  } else if (preset === 'vintage') {
    adjustments.value.brightness = 95;
    adjustments.value.contrast = 105;
    adjustments.value.saturation = 80;
    adjustments.value.warmth = 65;
    adjustments.value.grayscale = 0;
  } else if (preset === 'vibrant') {
    adjustments.value.brightness = 110;
    adjustments.value.contrast = 115;
    adjustments.value.saturation = 150;
    adjustments.value.warmth = 5;
    adjustments.value.grayscale = 0;
  } else {
    // Normal / Reset
    adjustments.value.brightness = 100;
    adjustments.value.contrast = 100;
    adjustments.value.saturation = 100;
    adjustments.value.warmth = 0;
    adjustments.value.grayscale = 0;
    adjustments.value.blur = 0;
  }
  pushHistory();
  drawCanvas();
};

const computedFilter = computed(() => {
  const a = adjustments.value;
  return `brightness(${a.brightness}%) contrast(${a.contrast}%) saturate(${a.saturation}%) sepia(${a.warmth}%) grayscale(${a.grayscale}%) blur(${a.blur}px)`;
});

const rotateCanvas = () => {
  adjustments.value.rotation = (adjustments.value.rotation + 90) % 360;
  pushHistory();
  drawCanvas();
};

const flipHorizontal = () => {
  adjustments.value.flipH = !adjustments.value.flipH;
  pushHistory();
  drawCanvas();
};

const flipVertical = () => {
  adjustments.value.flipV = !adjustments.value.flipV;
  pushHistory();
  drawCanvas();
};

// Canvas Layers (Elements)
const elements = ref<CanvasElement[]>([
  {
    id: 'el-1',
    type: 'metric',
    text: '+88.4%',
    subtext: 'Throughput & Growth Velocity',
    x: 60,
    y: 60,
    fontSize: 44,
    fontFamily: 'JetBrains Mono, monospace',
    color: '#9EE493',
    bgColor: 'rgba(15, 23, 42, 0.88)',
    bgPadding: 22,
    hasBackground: true,
    isBold: true,
    isItalic: false,
    opacity: 1,
    visible: true,
  },
  {
    id: 'el-2',
    type: 'pill',
    text: '✦ Synthex AI Command',
    x: 60,
    y: 530,
    fontSize: 20,
    fontFamily: 'Inter, sans-serif',
    color: '#FFFFFF',
    bgColor: 'rgba(15, 23, 42, 0.85)',
    bgPadding: 16,
    hasBackground: true,
    isBold: true,
    isItalic: false,
    opacity: 0.95,
    visible: true,
  },
]);

const selectedElementId = ref<string | null>(null);
const isDragging = ref(false);
const dragStartX = ref(0);
const dragStartY = ref(0);
const elementStartX = ref(0);
const elementStartY = ref(0);

const selectedElement = computed(() => {
  return elements.value.find((e) => e.id === selectedElementId.value) || null;
});

// ==========================================
// 7. REAL UNDO / REDO HISTORY STACK
// ==========================================
const historyStack = ref<string[]>([]);
const historyIndex = ref(-1);

const pushHistory = () => {
  const state = JSON.stringify({
    elements: elements.value,
    adjustments: adjustments.value,
    selectedAspect: selectedAspect.value,
  });

  // Truncate forward history if state is pushed after undo
  if (historyIndex.value < historyStack.value.length - 1) {
    historyStack.value = historyStack.value.slice(0, historyIndex.value + 1);
  }

  historyStack.value.push(state);
  if (historyStack.value.length > 30) {
    historyStack.value.shift();
  }
  historyIndex.value = historyStack.value.length - 1;
};

const undo = () => {
  if (historyIndex.value > 0) {
    historyIndex.value--;
    const state = JSON.parse(historyStack.value[historyIndex.value]);
    elements.value = state.elements;
    adjustments.value = state.adjustments;
    selectedAspect.value = state.selectedAspect;
    drawCanvas();
    showToast('Undo performed');
  }
};

const redo = () => {
  if (historyIndex.value < historyStack.value.length - 1) {
    historyIndex.value++;
    const state = JSON.parse(historyStack.value[historyIndex.value]);
    elements.value = state.elements;
    adjustments.value = state.adjustments;
    selectedAspect.value = state.selectedAspect;
    drawCanvas();
    showToast('Redo performed');
  }
};

// ==========================================
// 8. CANVAS DRAW ENGINE & RENDERING
// ==========================================
const resolvePath = (p?: string): string => {
  if (!p) return '/storage/media/monolith-vs-microservices-arch.png';
  if (p.startsWith('http://') || p.startsWith('https://')) return p;
  if (p.startsWith('/storage/')) return p;
  if (p.startsWith('storage/')) return '/' + p;
  if (p.startsWith('media/')) return '/storage/' + p;
  if (p.startsWith('/')) return p;
  return '/storage/' + p;
};

const drawCanvas = () => {
  const canvas = mainCanvas.value;
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  const w = baseWidth;
  const h = computedHeight.value;
  canvas.width = w;
  canvas.height = h;

  ctx.clearRect(0, 0, w, h);

  // Background Image or Gradient
  if (sourceImage.value && !isVideoAsset.value) {
    ctx.save();
    ctx.filter = computedFilter.value;

    // Apply rotation & flip transforms
    ctx.translate(w / 2, h / 2);
    ctx.rotate((adjustments.value.rotation * Math.PI) / 180);
    ctx.scale(adjustments.value.flipH ? -1 : 1, adjustments.value.flipV ? -1 : 1);

    const img = sourceImage.value;
    const srcAspect = (img.naturalWidth || 1) / (img.naturalHeight || 1);
    const dstAspect = w / h;

    let sx = 0, sy = 0, sWidth = img.naturalWidth, sHeight = img.naturalHeight;
    if (srcAspect > dstAspect) {
      sWidth = img.naturalHeight * dstAspect;
      sx = (img.naturalWidth - sWidth) / 2;
    } else {
      sHeight = img.naturalWidth / dstAspect;
      sy = (img.naturalHeight - sHeight) / 2;
    }

    ctx.drawImage(img, sx, sy, sWidth, sHeight, -w / 2, -h / 2, w, h);
    ctx.restore();
  } else {
    // High-contrast modern dark slate gradient
    const grad = ctx.createLinearGradient(0, 0, w, h);
    grad.addColorStop(0, '#0f172a');
    grad.addColorStop(1, '#1e293b');
    ctx.fillStyle = grad;
    ctx.fillRect(0, 0, w, h);
  }

  // Contrast Scrim
  const scrim = ctx.createLinearGradient(0, 0, 0, h);
  scrim.addColorStop(0, 'rgba(15, 23, 42, 0.15)');
  scrim.addColorStop(1, 'rgba(15, 23, 42, 0.50)');
  ctx.fillStyle = scrim;
  ctx.fillRect(0, 0, w, h);

  // Draw Layers / Elements
  elements.value.forEach((el) => {
    if (!el.visible) return;

    ctx.save();
    ctx.globalAlpha = el.opacity ?? 1;

    const fontStyle = el.isItalic ? 'italic' : 'normal';
    const fontWeight = el.isBold ? 'bold' : 'normal';
    ctx.font = `${fontStyle} ${fontWeight} ${el.fontSize}px ${el.fontFamily}`;

    const metrics = ctx.measureText(el.text);
    let boxWidth = metrics.width + el.bgPadding * 2;
    let boxHeight = el.fontSize + el.bgPadding * 1.5;

    if (el.subtext) {
      ctx.font = `normal 500 ${Math.round(el.fontSize * 0.45)}px ${el.fontFamily}`;
      const subMetrics = ctx.measureText(el.subtext);
      boxWidth = Math.max(boxWidth, subMetrics.width + el.bgPadding * 2);
      boxHeight += Math.round(el.fontSize * 0.7);
    }

    if (el.hasBackground) {
      ctx.fillStyle = el.bgColor;
      ctx.beginPath();
      ctx.roundRect(el.x, el.y, boxWidth, boxHeight, 16);
      ctx.fill();

      ctx.strokeStyle = 'rgba(255, 255, 255, 0.14)';
      ctx.lineWidth = 1.5;
      ctx.stroke();
    }

    ctx.font = `${fontStyle} ${fontWeight} ${el.fontSize}px ${el.fontFamily}`;
    ctx.fillStyle = el.color;
    ctx.textBaseline = 'top';

    const textX = el.hasBackground ? el.x + el.bgPadding : el.x;
    const textY = el.hasBackground ? el.y + el.bgPadding * 0.7 : el.y;
    ctx.fillText(el.text, textX, textY);

    if (el.subtext) {
      ctx.font = `normal 500 ${Math.round(el.fontSize * 0.45)}px ${el.fontFamily}`;
      ctx.fillStyle = '#94A3B8';
      ctx.fillText(el.subtext, textX, textY + el.fontSize + 8);
    }

    // Active Selection Border & Corner Handles (Matching Picsart reference)
    if (selectedElementId.value === el.id) {
      ctx.strokeStyle = '#38bdf8'; // Blue selection border
      ctx.lineWidth = 2.5;
      ctx.strokeRect(el.x - 4, el.y - 4, boxWidth + 8, boxHeight + 8);

      ctx.fillStyle = '#38bdf8';
      ctx.fillRect(el.x - 7, el.y - 7, 8, 8);
      ctx.fillRect(el.x + boxWidth - 1, el.y - 7, 8, 8);
      ctx.fillRect(el.x - 7, el.y + boxHeight - 1, 8, 8);
      ctx.fillRect(el.x + boxWidth - 1, el.y + boxHeight - 1, 8, 8);
    }

    ctx.restore();
  });
};

// Canvas Mouse Events
const onCanvasMouseDown = (e: MouseEvent) => {
  const canvas = mainCanvas.value;
  if (!canvas) return;
  const rect = canvas.getBoundingClientRect();
  const scaleX = canvas.width / rect.width;
  const scaleY = canvas.height / rect.height;

  const clickX = (e.clientX - rect.left) * scaleX;
  const clickY = (e.clientY - rect.top) * scaleY;

  let found: CanvasElement | null = null;
  for (let i = elements.value.length - 1; i >= 0; i--) {
    const el = elements.value[i];
    if (!el.visible) continue;
    const w = el.fontSize * el.text.length * 0.6 + el.bgPadding * 2;
    const h = el.fontSize * 1.5 + el.bgPadding * 2;
    if (clickX >= el.x && clickX <= el.x + w && clickY >= el.y && clickY <= el.y + h) {
      found = el;
      break;
    }
  }

  if (found) {
    selectedElementId.value = found.id;
    isDragging.value = true;
    dragStartX.value = clickX;
    dragStartY.value = clickY;
    elementStartX.value = found.x;
    elementStartY.value = found.y;
  } else {
    selectedElementId.value = null;
    isContextMenuOpen.value = false;
  }
  drawCanvas();
};

const onCanvasMouseMove = (e: MouseEvent) => {
  if (!isDragging.value || !selectedElement.value) return;
  const canvas = mainCanvas.value;
  if (!canvas) return;
  const rect = canvas.getBoundingClientRect();
  const scaleX = canvas.width / rect.width;
  const scaleY = canvas.height / rect.height;

  const currentX = (e.clientX - rect.left) * scaleX;
  const currentY = (e.clientY - rect.top) * scaleY;

  selectedElement.value.x = Math.round(elementStartX.value + (currentX - dragStartX.value));
  selectedElement.value.y = Math.round(elementStartY.value + (currentY - dragStartY.value));
  drawCanvas();
};

const onCanvasMouseUp = () => {
  if (isDragging.value) {
    pushHistory();
  }
  isDragging.value = false;
};

// Image Loader
const loadImage = () => {
  drawCanvas();
  if (!currentAsset.value?.path) return;
  const url = resolvePath(currentAsset.value.path);

  const img = new Image();
  img.crossOrigin = 'anonymous';
  img.src = url;
  img.onload = () => {
    sourceImage.value = img;
    nextTick(() => {
      drawCanvas();
    });
  };
  img.onerror = () => {
    const retry = new Image();
    retry.src = url;
    retry.onload = () => {
      sourceImage.value = retry;
      nextTick(() => {
        drawCanvas();
      });
    };
    retry.onerror = () => {
      drawCanvas();
    };
  };
};

// ==========================================
// 9. LAYER CONTROLS (Add, Duplicate, Delete, Reorder)
// ==========================================
const addTextElement = (type: 'heading' | 'quote' | 'metric' | 'subheading') => {
  let newEl: CanvasElement;

  if (type === 'heading') {
    newEl = {
      id: `el-${Date.now()}`,
      type: 'text',
      text: 'Autonomous SDLC Leadership',
      x: 100,
      y: 180,
      fontSize: 44,
      fontFamily: 'Plus Jakarta Sans, sans-serif',
      color: '#FFFFFF',
      bgColor: 'rgba(15, 23, 42, 0.85)',
      bgPadding: 18,
      hasBackground: false,
      isBold: true,
      isItalic: false,
      opacity: 1,
      visible: true,
    };
  } else if (type === 'metric') {
    newEl = {
      id: `el-${Date.now()}`,
      type: 'metric',
      text: '+94.2%',
      subtext: 'Engineering Efficiency Gains',
      x: 120,
      y: 280,
      fontSize: 48,
      fontFamily: 'JetBrains Mono, monospace',
      color: '#9EE493',
      bgColor: 'rgba(15, 23, 42, 0.90)',
      bgPadding: 24,
      hasBackground: true,
      isBold: true,
      isItalic: false,
      opacity: 1,
      visible: true,
    };
  } else if (type === 'quote') {
    newEl = {
      id: `el-${Date.now()}`,
      type: 'quote',
      text: '“Great architectures eliminate complexity before writing a single line of code.”',
      subtext: '— Principal Systems Architect',
      x: 80,
      y: 240,
      fontSize: 28,
      fontFamily: 'Inter, sans-serif',
      color: '#FFFFFF',
      bgColor: 'rgba(15, 23, 42, 0.88)',
      bgPadding: 26,
      hasBackground: true,
      isBold: false,
      isItalic: true,
      opacity: 1,
      visible: true,
    };
  } else {
    newEl = {
      id: `el-${Date.now()}`,
      type: 'text',
      text: 'Data-Grounded Architecture Review',
      x: 100,
      y: 260,
      fontSize: 26,
      fontFamily: 'Inter, sans-serif',
      color: '#38BDF8',
      bgColor: 'rgba(15, 23, 42, 0.85)',
      bgPadding: 14,
      hasBackground: false,
      isBold: true,
      isItalic: false,
      opacity: 0.95,
      visible: true,
    };
  }

  elements.value.push(newEl);
  selectedElementId.value = newEl.id;
  pushHistory();
  drawCanvas();
  showToast('Added ' + type + ' to canvas');
};

const duplicateElement = () => {
  if (!selectedElement.value) return;
  const clone = {
    ...selectedElement.value,
    id: `el-${Date.now()}`,
    x: selectedElement.value.x + 30,
    y: selectedElement.value.y + 30,
  };
  elements.value.push(clone);
  selectedElementId.value = clone.id;
  pushHistory();
  drawCanvas();
  showToast('Layer duplicated');
};

const deleteElement = (id?: string) => {
  const targetId = id || selectedElementId.value;
  if (!targetId) return;
  elements.value = elements.value.filter((e) => e.id !== targetId);
  if (selectedElementId.value === targetId) {
    selectedElementId.value = null;
  }
  pushHistory();
  drawCanvas();
  showToast('Layer deleted');
};

const bringToFront = () => {
  if (!selectedElement.value) return;
  const idx = elements.value.findIndex((e) => e.id === selectedElementId.value);
  if (idx > -1) {
    const [el] = elements.value.splice(idx, 1);
    elements.value.push(el);
    pushHistory();
    drawCanvas();
    showToast('Brought to front');
  }
};

const sendToBack = () => {
  if (!selectedElement.value) return;
  const idx = elements.value.findIndex((e) => e.id === selectedElementId.value);
  if (idx > 0) {
    const [el] = elements.value.splice(idx, 1);
    elements.value.unshift(el);
    pushHistory();
    drawCanvas();
    showToast('Sent to back');
  }
};

const centerElement = () => {
  if (!selectedElement.value) return;
  selectedElement.value.x = Math.round(baseWidth / 4);
  selectedElement.value.y = Math.round(computedHeight.value / 3);
  pushHistory();
  drawCanvas();
  showToast('Centered on canvas');
};

// Snap Frame (from Video to Vault)
const snapCurrentFrame = () => {
  if (!videoPlayer.value) return;
  const video = videoPlayer.value;
  const canvas = document.createElement('canvas');
  canvas.width = video.videoWidth || 1280;
  canvas.height = video.videoHeight || 720;
  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  ctx.filter = computedFilter.value;
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

  canvas.toBlob((blob) => {
    if (!blob) return;
    const filename = `snap_${Math.floor(currentTime.value)}s_${Date.now()}.jpg`;
    emit('save', blob, filename);
    showToast('Video frame captured and saved to Media Vault!');
  }, 'image/jpeg', 0.95);
};

// ==========================================
// 10. EXPORT & SAVE DISPATCHERS
// ==========================================
const isExporting = ref(false);

const exportToVault = () => {
  const canvas = mainCanvas.value;
  if (!canvas) return;

  selectedElementId.value = null;
  drawCanvas();
  isExporting.value = true;

  try {
    canvas.toBlob((blob) => {
      if (!blob) return;
      const raw = currentAsset.value?.filename ? currentAsset.value.filename.replace(/\.[^/.]+$/, "") : 'studio_creation';
      const filename = `synthex_${Date.now()}_${raw}.jpg`;
      emit('save', blob, filename);
      isExporting.value = false;
      showToast('Saved to your Media Vault!');
    }, 'image/jpeg', 0.95);
  } catch (e) {
    console.warn('Canvas export fallback:', e);
    isExporting.value = false;
  }
};

const downloadDirect = () => {
  const canvas = mainCanvas.value;
  if (!canvas) return;

  selectedElementId.value = null;
  drawCanvas();

  const link = document.createElement('a');
  link.download = `${projectTitle.value || 'synthex_design'}_${Date.now()}.jpg`;
  link.href = canvas.toDataURL('image/jpeg', 0.95);
  link.click();
  showToast('Download started');
};

// ==========================================
// 11. KEYBOARD SHORTCUTS
// ==========================================
const handleKeyDown = (e: KeyboardEvent) => {
  if (['input', 'textarea'].includes((e.target as HTMLElement).tagName.toLowerCase())) {
    return;
  }

  // Delete / Backspace
  if (e.key === 'Delete' || e.key === 'Backspace') {
    if (selectedElementId.value) {
      deleteElement();
      e.preventDefault();
    }
  }

  // Escape
  if (e.key === 'Escape') {
    selectedElementId.value = null;
    isContextMenuOpen.value = false;
    isFileMenuOpen.value = false;
    drawCanvas();
  }

  // Undo / Redo
  if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'z') {
    if (e.shiftKey) {
      redo();
    } else {
      undo();
    }
    e.preventDefault();
  }

  // Spacebar toggle video play
  if (e.code === 'Space' && isVideoAsset.value) {
    togglePlay();
    e.preventDefault();
  }
};

onMounted(() => {
  loadImage();
  pushHistory();
  window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown);
});

watch(() => props.asset, (newA) => {
  if (newA) {
    currentAsset.value = { ...newA };
    loadImage();
  }
});

watch([selectedAspect, adjustments], () => {
  drawCanvas();
}, { deep: true });
</script>

<template>
  <div class="fixed inset-0 z-50 flex flex-col bg-[#F3F4F6] text-slate-900 font-sans select-none overflow-hidden">
    
    <!-- ======================================================== -->
    <!-- 1. TOP HEADER (Picsart Style Header Bar)                  -->
    <!-- ======================================================== -->
    <header class="h-14 bg-white border-b border-slate-200 px-4 flex items-center justify-between shrink-0 z-30 shadow-xs relative">
      
      <!-- Left: Logo, File Menu & Pro Badge -->
      <div class="flex items-center gap-3">
        <!-- Clover Brand Pill -->
        <div class="flex items-center gap-2 cursor-pointer" @click="emit('close')">
          <div class="w-8 h-8 rounded-xl bg-[#9EE493] flex items-center justify-center shadow-xs hover:scale-105 transition">
            <div class="grid grid-cols-2 gap-0.5 p-0.5">
              <div class="w-2 h-2 rounded-full bg-slate-900"></div>
              <div class="w-2 h-2 rounded-full bg-slate-900"></div>
              <div class="w-2 h-2 rounded-full bg-slate-900"></div>
              <div class="w-2 h-2 rounded-full bg-slate-900"></div>
            </div>
          </div>
          <span class="font-display font-extrabold text-base tracking-tight text-slate-900">
            Synthex
          </span>
        </div>

        <!-- File Menu Dropdown -->
        <div class="relative">
          <button
            @click="isFileMenuOpen = !isFileMenuOpen"
            class="text-xs font-semibold text-slate-600 hover:text-slate-900 px-2.5 py-1 rounded-lg hover:bg-slate-100 flex items-center gap-1 transition"
          >
            <span>File</span>
            <ChevronDown class="w-3 h-3 text-slate-400" />
          </button>

          <!-- File Menu Popup -->
          <div
            v-if="isFileMenuOpen"
            class="absolute left-0 top-full mt-1.5 w-52 bg-white border border-slate-200 rounded-2xl shadow-xl z-50 p-1.5 space-y-0.5 text-xs text-slate-700"
          >
            <button
              @click="elements = []; pushHistory(); drawCanvas(); isFileMenuOpen = false; showToast('Canvas cleared')"
              class="w-full px-3 py-2 rounded-xl text-left hover:bg-slate-50 flex items-center justify-between"
            >
              <span>Clear Canvas Elements</span>
              <Trash2 class="w-3.5 h-3.5 text-slate-400" />
            </button>
            <button
              @click="toggleTool('uploads'); isFileMenuOpen = false"
              class="w-full px-3 py-2 rounded-xl text-left hover:bg-slate-50 flex items-center justify-between"
            >
              <span>Upload New File</span>
              <UploadCloud class="w-3.5 h-3.5 text-slate-400" />
            </button>
            <button
              @click="exportToVault(); isFileMenuOpen = false"
              class="w-full px-3 py-2 rounded-xl text-left hover:bg-slate-50 flex items-center justify-between font-semibold text-fuchsia-600"
            >
              <span>Save to Media Vault</span>
              <Save class="w-3.5 h-3.5 text-fuchsia-600" />
            </button>
            <button
              @click="downloadDirect(); isFileMenuOpen = false"
              class="w-full px-3 py-2 rounded-xl text-left hover:bg-slate-50 flex items-center justify-between"
            >
              <span>Download Image</span>
              <Download class="w-3.5 h-3.5 text-slate-400" />
            </button>
          </div>
        </div>

        <!-- Picsart-style Gradient Pro Badge -->
        <div class="px-3 py-1 rounded-full bg-gradient-to-r from-fuchsia-600 to-pink-500 text-white font-bold text-[11px] shadow-xs flex items-center gap-1 cursor-pointer hover:opacity-90 transition">
          <Sparkles class="w-3 h-3 text-white" />
          <span>Pro Studio</span>
        </div>

        <!-- Editable Project Title -->
        <input
          v-model="projectTitle"
          class="hidden lg:block bg-transparent text-xs font-semibold text-slate-700 border border-transparent hover:border-slate-200 focus:border-slate-300 rounded-lg px-2 py-1 focus:outline-none transition max-w-[200px]"
          title="Click to rename project"
        />

        <!-- Undo & Redo Arrows -->
        <div class="flex items-center gap-1 border-l border-slate-200 pl-2">
          <button
            @click="undo"
            :disabled="historyIndex <= 0"
            class="p-1.5 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 disabled:opacity-30 transition"
            title="Undo (Ctrl+Z)"
          >
            <Undo2 class="w-4 h-4" />
          </button>
          <button
            @click="redo"
            :disabled="historyIndex >= historyStack.length - 1"
            class="p-1.5 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 disabled:opacity-30 transition"
            title="Redo (Ctrl+Shift+Z)"
          >
            <Redo2 class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Center: Universal Tool Search Bar -->
      <div class="hidden md:flex items-center w-72 lg:w-96 relative">
        <div class="relative w-full">
          <Search class="w-3.5 h-3.5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            v-model="searchQuery"
            placeholder="Find a tool, effect or preset..."
            class="w-full bg-slate-100/90 hover:bg-slate-100 focus:bg-white border border-slate-200/80 rounded-full pl-9 pr-4 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-fuchsia-500 transition shadow-2xs"
          />
        </div>

        <!-- Search Results Dropdown -->
        <div
          v-if="filteredTools.length > 0"
          class="absolute left-0 right-0 top-full mt-2 bg-white border border-slate-200 rounded-2xl shadow-xl z-50 p-2 space-y-1"
        >
          <div
            v-for="item in filteredTools"
            :key="item.id"
            @click="toggleTool(item.id as any); searchQuery = ''"
            class="p-2 rounded-xl hover:bg-fuchsia-50 cursor-pointer flex items-center justify-between text-xs transition"
          >
            <div class="flex items-center gap-2">
              <component :is="item.icon" class="w-4 h-4 text-fuchsia-600" />
              <span class="font-bold text-slate-800">{{ item.name }}</span>
            </div>
            <span class="text-[10px] text-slate-400">{{ item.category }}</span>
          </div>
        </div>
      </div>

      <!-- Right: User Avatar & Export Action Button -->
      <div class="flex items-center gap-3">
        <!-- Toast Notification Alert -->
        <div
          v-if="toastMessage"
          class="flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold shadow-sm transition animate-in fade-in"
          :class="toastMessage.type === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200'"
        >
          <CheckCircle2 v-if="toastMessage.type === 'success'" class="w-3.5 h-3.5 text-emerald-600" />
          <AlertCircle v-else class="w-3.5 h-3.5 text-rose-600" />
          <span>{{ toastMessage.text }}</span>
        </div>

        <!-- Download Directly to Desktop -->
        <button
          @click="downloadDirect"
          class="p-2 rounded-full border border-slate-200 hover:bg-slate-100 text-slate-700 transition"
          title="Download to Computer"
        >
          <Download class="w-4 h-4" />
        </button>

        <!-- Vibrant Pink/Fuchsia Export Button Matching Picsart UI -->
        <button
          @click="exportToVault"
          :disabled="isExporting"
          class="px-5 py-1.5 rounded-full bg-gradient-to-r from-[#d946ef] to-[#ec4899] hover:from-[#c026d3] hover:to-[#db2777] text-white font-bold text-xs shadow-md shadow-pink-500/20 flex items-center gap-2 transition hover:scale-105 active:scale-95 disabled:opacity-50"
        >
          <Save class="w-3.5 h-3.5 text-white" />
          <span>{{ isExporting ? 'Exporting...' : 'Export' }}</span>
        </button>

        <!-- Close Studio Modal -->
        <button
          @click="emit('close')"
          class="p-2 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition"
          title="Close Studio"
        >
          <CloseIcon class="w-5 h-5" />
        </button>
      </div>

    </header>

    <!-- ======================================================== -->
    <!-- 2. MAIN STUDIO WORKSPACE (Left Strip + Flyout + Stage)     -->
    <!-- ======================================================== -->
    <div class="flex-1 flex overflow-hidden relative">
      
      <!-- A. LEFT ICON TOOL STRIP (Picsart Vertical Tool Strip) -->
      <nav class="w-16 bg-white border-r border-slate-200 flex flex-col items-center py-3 space-y-2 shrink-0 z-20 shadow-2xs">
        
        <button
          @click="toggleTool('ai_video')"
          :class="[
            'w-12 h-12 rounded-2xl flex flex-col items-center justify-center transition group relative',
            activeTool === 'ai_video' ? 'text-fuchsia-600 bg-fuchsia-50' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100'
          ]"
          title="AI Video Generator"
        >
          <Film class="w-5 h-5" />
          <span class="text-[9px] font-medium mt-0.5">AI Video</span>
          <div v-if="activeTool === 'ai_video'" class="absolute -right-0 top-1/2 -translate-y-1/2 w-1 h-6 bg-fuchsia-600 rounded-l-full"></div>
        </button>

        <button
          @click="toggleTool('ai_image')"
          :class="[
            'w-12 h-12 rounded-2xl flex flex-col items-center justify-center transition group relative',
            activeTool === 'ai_image' ? 'text-fuchsia-600 bg-fuchsia-50' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100'
          ]"
          title="AI Image Synthesizer"
        >
          <Wand2 class="w-5 h-5" />
          <span class="text-[9px] font-medium mt-0.5">AI Image</span>
        </button>

        <button
          @click="toggleTool('templates')"
          :class="[
            'w-12 h-12 rounded-2xl flex flex-col items-center justify-center transition group relative',
            activeTool === 'templates' ? 'text-fuchsia-600 bg-fuchsia-50' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100'
          ]"
          title="Executive Layouts"
        >
          <LayoutTemplate class="w-5 h-5" />
          <span class="text-[9px] font-medium mt-0.5">Layouts</span>
        </button>

        <button
          @click="toggleTool('uploads')"
          :class="[
            'w-12 h-12 rounded-2xl flex flex-col items-center justify-center transition group relative',
            activeTool === 'uploads' ? 'text-fuchsia-600 bg-fuchsia-50' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100'
          ]"
          title="Upload Media & Assets"
        >
          <UploadCloud class="w-5 h-5" />
          <span class="text-[9px] font-medium mt-0.5">Upload</span>
        </button>

        <button
          @click="toggleTool('text')"
          :class="[
            'w-12 h-12 rounded-2xl flex flex-col items-center justify-center transition group relative',
            activeTool === 'text' ? 'text-fuchsia-600 bg-fuchsia-50' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100'
          ]"
          title="Text & Typography"
        >
          <Type class="w-5 h-5" />
          <span class="text-[9px] font-medium mt-0.5">Text</span>
        </button>

        <button
          @click="toggleTool('elements')"
          :class="[
            'w-12 h-12 rounded-2xl flex flex-col items-center justify-center transition group relative',
            activeTool === 'elements' ? 'text-fuchsia-600 bg-fuchsia-50' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100'
          ]"
          title="Elements, Badges & Shapes"
        >
          <Shapes class="w-5 h-5" />
          <span class="text-[9px] font-medium mt-0.5">Shapes</span>
        </button>

        <button
          @click="toggleTool('filters')"
          :class="[
            'w-12 h-12 rounded-2xl flex flex-col items-center justify-center transition group relative',
            activeTool === 'filters' ? 'text-fuchsia-600 bg-fuchsia-50' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100'
          ]"
          title="Effects & Color Grading"
        >
          <Sliders class="w-5 h-5" />
          <span class="text-[9px] font-medium mt-0.5">Effects</span>
        </button>

        <button
          @click="toggleTool('aspect')"
          :class="[
            'w-12 h-12 rounded-2xl flex flex-col items-center justify-center transition group relative',
            activeTool === 'aspect' ? 'text-fuchsia-600 bg-fuchsia-50' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100'
          ]"
          title="Aspect Ratios"
        >
          <Crop class="w-5 h-5" />
          <span class="text-[9px] font-medium mt-0.5">Canvas</span>
        </button>

        <button
          @click="toggleTool('layers')"
          :class="[
            'w-12 h-12 rounded-2xl flex flex-col items-center justify-center transition group relative',
            activeTool === 'layers' ? 'text-fuchsia-600 bg-fuchsia-50' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100'
          ]"
          title="Layer Management"
        >
          <Layers class="w-5 h-5" />
          <span class="text-[9px] font-medium mt-0.5">Layers</span>
        </button>

      </nav>

      <!-- B. SECONDARY TOOL DRAWER (Flyout Panel - Complete Picsart Toolset!) -->
      <aside
        v-if="isFlyoutOpen && activeTool"
        class="w-80 md:w-88 bg-white border-r border-slate-200 flex flex-col shrink-0 z-10 shadow-lg overflow-y-auto animate-in slide-in-from-left duration-200"
      >
        
        <!-- 1. AI VIDEO GENERATOR DRAWER (Exact Picsart Layout!) -->
        <div v-if="activeTool === 'ai_video'" class="p-5 flex-1 flex flex-col space-y-4">
          
          <div class="flex items-center justify-between pb-1">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
              <Film class="w-4 h-4 text-fuchsia-600" />
              <span>AI Video Generator</span>
            </h2>
            <button @click="isFlyoutOpen = false" class="text-slate-400 hover:text-slate-600">
              <CloseIcon class="w-4 h-4" />
            </button>
          </div>

          <!-- Mode Toggle Tabs -->
          <div class="grid grid-cols-2 p-1 bg-slate-100 rounded-xl text-xs font-semibold">
            <button
              @click="videoTab = 'image_to_video'"
              :class="[
                'py-1.5 rounded-lg transition text-center',
                videoTab === 'image_to_video' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'
              ]"
            >
              Image to Video
            </button>
            <button
              @click="videoTab = 'text_to_video'"
              :class="[
                'py-1.5 rounded-lg transition text-center',
                videoTab === 'text_to_video' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'
              ]"
            >
              Text to Video
            </button>
          </div>

          <!-- Input Media Box (Image to Video) -->
          <div v-if="videoTab === 'image_to_video'" class="space-y-1.5">
            <div class="flex items-center justify-between">
              <label class="text-xs font-semibold text-slate-700">Input media</label>
              <span class="text-[10px] text-slate-400">Up to 40 media</span>
            </div>

            <div class="relative w-full h-24 rounded-2xl border border-slate-200 bg-slate-50 flex items-center p-2.5 gap-3 group hover:border-slate-300 transition">
              <div class="w-20 h-full rounded-xl overflow-hidden bg-slate-200 shrink-0 border border-slate-200/80 shadow-xs">
                <img
                  :src="resolvePath(currentAsset?.path)"
                  class="w-full h-full object-cover"
                  alt="Input preview"
                />
              </div>

              <div class="space-y-0.5 text-xs overflow-hidden">
                <p class="font-bold text-slate-800 truncate">{{ currentAsset?.filename || 'Active Canvas Asset' }}</p>
                <p class="text-[10px] text-slate-400 font-mono">1200×675 • High Fidelity Seed</p>
                <span class="inline-block text-[10px] text-fuchsia-600 font-semibold bg-fuchsia-50 px-2 py-0.5 rounded-full border border-fuchsia-100">
                  Ready to Animate
                </span>
              </div>
            </div>
          </div>

          <!-- Motion & Sound Description Textarea -->
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-slate-700">
              {{ videoTab === 'image_to_video' ? '(Optional) Describe motion & sound:' : 'Video Generation Prompt:' }}
            </label>
            <textarea
              v-if="videoTab === 'image_to_video'"
              v-model="motionPrompt"
              rows="3"
              placeholder='(Optional) Describe any motion & sound you&#39;d like to include: "Busy coffee shop ambience and milk swirling around the cup."'
              class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-3 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-fuchsia-500 focus:bg-white transition leading-relaxed resize-none"
            ></textarea>
            <textarea
              v-else
              v-model="textVideoPrompt"
              rows="3"
              placeholder='Describe the video scene: "A high-tech neural network computing complex with holographic blueprints floating in air."'
              class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-3 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-fuchsia-500 focus:bg-white transition leading-relaxed resize-none"
            ></textarea>

            <!-- Quick Motion Tag Pills -->
            <div class="flex flex-wrap gap-1 pt-1">
              <button
                v-for="tag in quickMotionTags"
                :key="tag"
                @click="applyMotionTag(tag)"
                class="text-[10px] bg-slate-100 hover:bg-fuchsia-50 hover:text-fuchsia-700 px-2 py-0.5 rounded-full text-slate-600 transition"
              >
                + {{ tag }}
              </button>
            </div>
          </div>

          <!-- Model Selection Accordion (Picsart Reference Dropdown!) -->
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-slate-700">Generation Model</label>
            
            <div class="relative">
              <button
                @click="isModelDropdownOpen = !isModelDropdownOpen"
                class="w-full p-3 rounded-2xl border border-slate-200 bg-slate-50 hover:bg-white flex items-center justify-between text-left transition"
              >
                <div class="space-y-0.5">
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-900">{{ activeModelObj.name }}</span>
                    <span class="text-[10px] font-semibold px-2 py-0.2 rounded-full bg-fuchsia-100 text-fuchsia-700">
                      {{ activeModelObj.badge }}
                    </span>
                  </div>
                  <p class="text-[10px] text-slate-400 truncate">{{ activeModelObj.tag }}</p>
                </div>
                <ChevronDown class="w-4 h-4 text-slate-400" :class="{'rotate-180': isModelDropdownOpen}" />
              </button>

              <!-- Dropdown List of Video Models -->
              <div
                v-if="isModelDropdownOpen"
                class="absolute left-0 right-0 top-full mt-2 bg-white border border-slate-200 rounded-2xl shadow-xl z-30 p-2 space-y-1"
              >
                <div
                  v-for="model in videoModels"
                  :key="model.id"
                  @click="selectedModel = model.id; isModelDropdownOpen = false"
                  :class="[
                    'p-2.5 rounded-xl cursor-pointer flex items-center justify-between text-left transition',
                    selectedModel === model.id ? 'bg-fuchsia-50 border border-fuchsia-200' : 'hover:bg-slate-50'
                  ]"
                >
                  <div>
                    <div class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                      <span>{{ model.name }}</span>
                      <span class="text-[9px] font-mono text-slate-400">• {{ model.cost }}</span>
                    </div>
                    <div class="text-[10px] text-slate-400">{{ model.tag }}</div>
                  </div>
                  <Check v-if="selectedModel === model.id" class="w-4 h-4 text-fuchsia-600" />
                </div>
              </div>
            </div>
          </div>

          <!-- Camera Movement & Duration -->
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-slate-700">Camera Dynamics</label>
            <div class="grid grid-cols-3 gap-1.5 text-[11px] font-medium">
              <button
                v-for="cam in [
                  { id: 'zoom_in', label: 'Zoom In' },
                  { id: 'pan_right', label: 'Pan Right' },
                  { id: 'orbit', label: 'Orbit 360' },
                ]"
                :key="cam.id"
                @click="cameraMotion = cam.id as any"
                :class="[
                  'py-1.5 rounded-xl border text-center transition',
                  cameraMotion === cam.id ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'
                ]"
              >
                {{ cam.label }}
              </button>
            </div>
          </div>

          <!-- Bottom Pink Gradient Generate Action Button -->
          <div class="pt-2 mt-auto space-y-2">
            <!-- Animated Progress Bar -->
            <div v-if="isGeneratingVideo" class="space-y-1">
              <div class="flex justify-between text-[10px] font-mono text-fuchsia-600 font-semibold">
                <span>Synthesizing Video...</span>
                <span>{{ videoGenerationProgress }}%</span>
              </div>
              <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-fuchsia-500 to-pink-500 transition-all duration-300" :style="{ width: `${videoGenerationProgress}%` }"></div>
              </div>
            </div>

            <button
              @click="triggerGenerateVideo"
              :disabled="isGeneratingVideo"
              class="w-full py-3 rounded-2xl bg-gradient-to-r from-[#d946ef] to-[#ec4899] hover:from-[#c026d3] hover:to-[#db2777] text-white font-bold text-xs shadow-md shadow-pink-500/20 flex items-center justify-center gap-2 transition hover:scale-101 active:scale-98 disabled:opacity-50"
            >
              <Sparkles class="w-4 h-4 text-white" :class="{'animate-spin': isGeneratingVideo}" />
              <span>{{ isGeneratingVideo ? 'Rendering Video Frame...' : 'Generate Video (20 credits)' }}</span>
            </button>

            <p class="text-[10px] text-center text-slate-400 leading-tight">
              By continuing, you agree to our <a href="#" class="underline text-slate-500">Gen AI ToU</a>
            </p>
          </div>

        </div>

        <!-- 2. AI IMAGE GENERATOR DRAWER -->
        <div v-else-if="activeTool === 'ai_image'" class="p-5 flex-1 flex flex-col space-y-4">
          <div class="flex items-center justify-between pb-1">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
              <Wand2 class="w-4 h-4 text-fuchsia-600" />
              <span>AI Image Synthesizer</span>
            </h2>
            <button @click="isFlyoutOpen = false" class="text-slate-400 hover:text-slate-600">
              <CloseIcon class="w-4 h-4" />
            </button>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-slate-700">Image Prompt</label>
            <textarea
              v-model="imagePrompt"
              rows="4"
              placeholder="Describe your visual concept in detail: High-throughput microservices diagram with dark glowing neon traces and executive metric badges..."
              class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-3 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-fuchsia-500 focus:bg-white transition leading-relaxed resize-none"
            ></textarea>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-slate-700">Artistic Style</label>
            <div class="grid grid-cols-2 gap-2 text-xs">
              <button
                v-for="st in imageStyles"
                :key="st.id"
                @click="imageStyle = st.id"
                :class="[
                  'p-2.5 rounded-xl border text-center font-semibold transition',
                  imageStyle === st.id ? 'bg-fuchsia-50 border-fuchsia-300 text-fuchsia-800' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'
                ]"
              >
                {{ st.name }}
              </button>
            </div>
          </div>

          <div class="pt-2 mt-auto">
            <button
              @click="triggerGenerateImage"
              :disabled="isGeneratingImage || !imagePrompt"
              class="w-full py-3 rounded-2xl bg-gradient-to-r from-[#d946ef] to-[#ec4899] hover:from-[#c026d3] hover:to-[#db2777] text-white font-bold text-xs shadow-md flex items-center justify-center gap-2 transition disabled:opacity-50"
            >
              <Sparkles class="w-4 h-4 text-white" :class="{'animate-spin': isGeneratingImage}" />
              <span>{{ isGeneratingImage ? 'Synthesizing with Flux...' : 'Generate Visual ($0.04)' }}</span>
            </button>
          </div>
        </div>

        <!-- 3. TEMPLATES & LAYOUTS DRAWER -->
        <div v-else-if="activeTool === 'templates'" class="p-5 flex-1 space-y-4">
          <div class="flex items-center justify-between pb-1">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
              <LayoutTemplate class="w-4 h-4 text-fuchsia-600" />
              <span>Executive Card Presets</span>
            </h2>
            <button @click="isFlyoutOpen = false" class="text-slate-400 hover:text-slate-600">
              <CloseIcon class="w-4 h-4" />
            </button>
          </div>

          <p class="text-[11px] text-slate-500">
            Click any layout to apply proven executive thought-leadership frameworks directly to your canvas.
          </p>

          <div class="space-y-2.5 pt-1">
            <button
              @click="addTextElement('quote')"
              class="w-full p-3.5 rounded-2xl bg-slate-50 hover:bg-fuchsia-50/50 border border-slate-200 text-left transition group"
            >
              <div class="flex items-center gap-2 text-xs font-bold text-slate-800 group-hover:text-fuchsia-700">
                <Quote class="w-4 h-4 text-fuchsia-600" />
                <span>Executive Thought Leader Quote</span>
              </div>
              <p class="text-[11px] text-slate-400 mt-1">
                Frosted glass quote pill with quotation marks and author attribution.
              </p>
            </button>

            <button
              @click="addTextElement('metric')"
              class="w-full p-3.5 rounded-2xl bg-slate-50 hover:bg-fuchsia-50/50 border border-slate-200 text-left transition group"
            >
              <div class="flex items-center gap-2 text-xs font-bold text-slate-800 group-hover:text-fuchsia-700">
                <TrendingUp class="w-4 h-4 text-emerald-600" />
                <span>Impact Metric Callout Card</span>
              </div>
              <p class="text-[11px] text-slate-400 mt-1">
                High-contrast stat badge with growth metrics and subtext.
              </p>
            </button>

            <button
              @click="addTextElement('heading')"
              class="w-full p-3.5 rounded-2xl bg-slate-50 hover:bg-fuchsia-50/50 border border-slate-200 text-left transition group"
            >
              <div class="flex items-center gap-2 text-xs font-bold text-slate-800 group-hover:text-fuchsia-700">
                <Tag class="w-4 h-4 text-blue-600" />
                <span>Strategic Thesis Banner</span>
              </div>
              <p class="text-[11px] text-slate-400 mt-1">
                Authoritative headline for technical architectural insights.
              </p>
            </button>
          </div>
        </div>

        <!-- 4. UPLOADS & MEDIA VAULT DRAWER -->
        <div v-else-if="activeTool === 'uploads'" class="p-5 flex-1 flex flex-col space-y-4">
          <div class="flex items-center justify-between pb-1">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
              <UploadCloud class="w-4 h-4 text-fuchsia-600" />
              <span>Media Vault & Uploads</span>
            </h2>
            <button @click="isFlyoutOpen = false" class="text-slate-400 hover:text-slate-600">
              <CloseIcon class="w-4 h-4" />
            </button>
          </div>

          <!-- Drag and Drop Box -->
          <div
            @click="fileInputRef?.click()"
            class="border-2 border-dashed border-slate-200 hover:border-fuchsia-400 rounded-3xl p-6 text-center cursor-pointer transition bg-slate-50 hover:bg-fuchsia-50/20 group"
          >
            <input
              ref="fileInputRef"
              type="file"
              accept="image/*,video/*"
              class="hidden"
              @change="handleStudioUpload"
            />
            <div class="w-10 h-10 rounded-2xl bg-white border border-slate-200 flex items-center justify-center mx-auto mb-2 shadow-2xs group-hover:scale-105 transition">
              <UploadCloud class="w-5 h-5 text-fuchsia-600" />
            </div>
            <p class="text-xs font-bold text-slate-800">
              {{ isUploadingFile ? 'Uploading to Studio...' : 'Upload Image or Video' }}
            </p>
            <p class="text-[10px] text-slate-400 mt-0.5">PNG, JPG, WebP, MP4 up to 100MB</p>
          </div>

          <div class="space-y-2 pt-2">
            <label class="text-xs font-semibold text-slate-700">Active Canvas Asset</label>
            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 flex items-center gap-3">
              <img :src="resolvePath(currentAsset?.path)" class="w-12 h-12 rounded-xl object-cover border border-slate-200" />
              <div class="overflow-hidden text-xs">
                <p class="font-bold text-slate-800 truncate">{{ currentAsset?.filename || 'Untitled' }}</p>
                <p class="text-[10px] text-slate-400 font-mono">{{ isVideoAsset ? 'Video MP4' : 'Image Graphic' }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- 5. TEXT & TYPOGRAPHY DRAWER -->
        <div v-else-if="activeTool === 'text'" class="p-5 flex-1 space-y-4">
          <div class="flex items-center justify-between pb-1">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
              <Type class="w-4 h-4 text-fuchsia-600" />
              <span>Text & Typography</span>
            </h2>
            <button @click="isFlyoutOpen = false" class="text-slate-400 hover:text-slate-600">
              <CloseIcon class="w-4 h-4" />
            </button>
          </div>

          <!-- Add Text Buttons -->
          <div class="space-y-2 pt-1">
            <button
              @click="addTextElement('heading')"
              class="w-full p-3.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-left font-display font-bold text-base text-slate-900 transition flex items-center justify-between"
            >
              <span>Add a Heading</span>
              <Plus class="w-4 h-4 text-slate-500" />
            </button>

            <button
              @click="addTextElement('subheading')"
              class="w-full p-3 rounded-2xl bg-slate-100 hover:bg-slate-200 text-left text-xs font-semibold text-slate-800 transition flex items-center justify-between"
            >
              <span>Add Subheading</span>
              <Plus class="w-4 h-4 text-slate-500" />
            </button>

            <button
              @click="addTextElement('metric')"
              class="w-full p-3.5 rounded-2xl bg-slate-900 text-left font-mono font-bold text-sm text-[#9EE493] transition flex items-center justify-between shadow-xs"
            >
              <span>Add Metric Callout (+94%)</span>
              <Plus class="w-4 h-4 text-[#9EE493]" />
            </button>

            <button
              @click="addTextElement('quote')"
              class="w-full p-3.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-left text-xs italic font-medium text-slate-700 transition flex items-center justify-between"
            >
              <span>Add Leader Quote</span>
              <Plus class="w-4 h-4 text-slate-500" />
            </button>
          </div>

          <!-- Selected Text Inspector Controls -->
          <div v-if="selectedElement" class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-3 pt-3">
            <div class="flex items-center justify-between text-xs font-bold text-slate-800">
              <span>Text Properties</span>
              <button @click="deleteElement()" class="text-rose-500 hover:text-rose-700">
                <Trash2 class="w-3.5 h-3.5" />
              </button>
            </div>

            <textarea
              v-model="selectedElement.text"
              @input="drawCanvas"
              rows="2"
              class="w-full bg-white border border-slate-200 rounded-xl p-2.5 text-xs text-slate-900 focus:outline-none focus:border-fuchsia-500"
            ></textarea>

            <div v-if="selectedElement.subtext !== undefined" class="space-y-1">
              <label class="text-[10px] text-slate-500 font-semibold">Subtext / Attribution</label>
              <input
                v-model="selectedElement.subtext"
                @input="drawCanvas"
                class="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs text-slate-900 focus:outline-none focus:border-fuchsia-500"
              />
            </div>

            <!-- Font Size & Color -->
            <div class="space-y-1">
              <div class="flex justify-between text-[11px] text-slate-600">
                <span>Font Size</span>
                <span class="font-mono text-slate-400">{{ selectedElement.fontSize }}px</span>
              </div>
              <div class="flex items-center gap-2">
                <input
                  type="color"
                  v-model="selectedElement.color"
                  @input="drawCanvas"
                  class="w-8 h-8 rounded-lg cursor-pointer border border-slate-200 p-0.5 shrink-0"
                />
                <input
                  type="range"
                  min="16"
                  max="90"
                  v-model.number="selectedElement.fontSize"
                  @input="drawCanvas"
                  class="flex-1 accent-fuchsia-600 cursor-pointer"
                />
              </div>
            </div>

            <!-- Styling Toggles (Bold, Italic, Pill) -->
            <div class="flex items-center gap-2 pt-1">
              <button
                @click="selectedElement.isBold = !selectedElement.isBold; drawCanvas()"
                :class="[
                  'px-3 py-1.5 rounded-xl border text-xs font-bold transition',
                  selectedElement.isBold ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-200'
                ]"
              >
                B
              </button>
              <button
                @click="selectedElement.isItalic = !selectedElement.isItalic; drawCanvas()"
                :class="[
                  'px-3 py-1.5 rounded-xl border text-xs italic font-medium transition',
                  selectedElement.isItalic ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-200'
                ]"
              >
                I
              </button>
              <button
                @click="selectedElement.hasBackground = !selectedElement.hasBackground; drawCanvas()"
                :class="[
                  'px-3 py-1.5 rounded-xl border text-xs font-medium transition',
                  selectedElement.hasBackground ? 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-300' : 'bg-white text-slate-700 border-slate-200'
                ]"
              >
                Pill Badge
              </button>
            </div>
          </div>
        </div>

        <!-- 6. ELEMENTS & SHAPES DRAWER -->
        <div v-else-if="activeTool === 'elements'" class="p-5 flex-1 space-y-4">
          <div class="flex items-center justify-between pb-1">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
              <Shapes class="w-4 h-4 text-fuchsia-600" />
              <span>Badges & Callouts</span>
            </h2>
            <button @click="isFlyoutOpen = false" class="text-slate-400 hover:text-slate-600">
              <CloseIcon class="w-4 h-4" />
            </button>
          </div>

          <div class="space-y-2 pt-1">
            <button
              v-for="b in [
                { text: '10x Pipeline Velocity', sub: 'Validated by Synthex Engine', color: '#9EE493' },
                { text: '99.99% Availability', sub: 'Zero Downtime Cluster', color: '#38BDF8' },
                { text: 'Zero Trust Security', sub: 'OWASP ASVS Compliant', color: '#F59E0B' },
              ]"
              :key="b.text"
              @click="addTextElement('metric')"
              class="w-full p-3 rounded-2xl bg-slate-900 text-left border border-slate-800 transition hover:scale-101"
            >
              <div class="text-xs font-bold font-mono" :style="{ color: b.color }">{{ b.text }}</div>
              <div class="text-[10px] text-slate-400">{{ b.sub }}</div>
            </button>
          </div>
        </div>

        <!-- 7. EFFECTS & COLOR TUNING DRAWER -->
        <div v-else-if="activeTool === 'filters'" class="p-5 flex-1 space-y-4">
          <div class="flex items-center justify-between pb-1">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
              <Sliders class="w-4 h-4 text-fuchsia-600" />
              <span>Color Tuning & Filters</span>
            </h2>
            <button @click="isFlyoutOpen = false" class="text-slate-400 hover:text-slate-600">
              <CloseIcon class="w-4 h-4" />
            </button>
          </div>

          <!-- Presets -->
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-slate-700">Filter Presets</label>
            <div class="grid grid-cols-3 gap-1.5 text-xs font-medium">
              <button
                v-for="p in [
                  { id: 'normal', name: 'Reset' },
                  { id: 'cinematic', name: 'Cinematic' },
                  { id: 'cyberpunk', name: 'Cyberpunk' },
                  { id: 'noir', name: 'Noir B&W' },
                  { id: 'vintage', name: 'Vintage' },
                  { id: 'vibrant', name: 'Vibrant' },
                ]"
                :key="p.id"
                @click="applyFilterPreset(p.id as any)"
                class="py-2 rounded-xl border border-slate-200 bg-slate-50 hover:bg-fuchsia-50 text-slate-700 hover:text-fuchsia-800 transition text-center"
              >
                {{ p.name }}
              </button>
            </div>
          </div>

          <!-- Fine-Tuning Sliders -->
          <div class="space-y-3 pt-2">
            <div class="space-y-1">
              <div class="flex justify-between text-xs text-slate-600">
                <span>Brightness</span>
                <span class="font-mono text-slate-400">{{ adjustments.brightness }}%</span>
              </div>
              <input
                type="range"
                min="50"
                max="150"
                v-model.number="adjustments.brightness"
                @input="drawCanvas"
                class="w-full accent-fuchsia-600 h-1.5 bg-slate-200 rounded-lg cursor-pointer"
              />
            </div>

            <div class="space-y-1">
              <div class="flex justify-between text-xs text-slate-600">
                <span>Contrast</span>
                <span class="font-mono text-slate-400">{{ adjustments.contrast }}%</span>
              </div>
              <input
                type="range"
                min="50"
                max="150"
                v-model.number="adjustments.contrast"
                @input="drawCanvas"
                class="w-full accent-fuchsia-600 h-1.5 bg-slate-200 rounded-lg cursor-pointer"
              />
            </div>

            <div class="space-y-1">
              <div class="flex justify-between text-xs text-slate-600">
                <span>Saturation</span>
                <span class="font-mono text-slate-400">{{ adjustments.saturation }}%</span>
              </div>
              <input
                type="range"
                min="0"
                max="200"
                v-model.number="adjustments.saturation"
                @input="drawCanvas"
                class="w-full accent-fuchsia-600 h-1.5 bg-slate-200 rounded-lg cursor-pointer"
              />
            </div>

            <div class="space-y-1">
              <div class="flex justify-between text-xs text-slate-600">
                <span>Warmth / Sepia</span>
                <span class="font-mono text-slate-400">{{ adjustments.warmth }}%</span>
              </div>
              <input
                type="range"
                min="0"
                max="100"
                v-model.number="adjustments.warmth"
                @input="drawCanvas"
                class="w-full accent-fuchsia-600 h-1.5 bg-slate-200 rounded-lg cursor-pointer"
              />
            </div>
          </div>
        </div>

        <!-- 8. ASPECT RATIOS DRAWER -->
        <div v-else-if="activeTool === 'aspect'" class="p-5 flex-1 space-y-4">
          <div class="flex items-center justify-between pb-1">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
              <Crop class="w-4 h-4 text-fuchsia-600" />
              <span>Canvas Dimensions</span>
            </h2>
            <button @click="isFlyoutOpen = false" class="text-slate-400 hover:text-slate-600">
              <CloseIcon class="w-4 h-4" />
            </button>
          </div>

          <div class="space-y-2">
            <button
              v-for="asp in [
                { id: '16:9', label: '16:9 Landscape', desc: 'X (Twitter) Feed & YouTube' },
                { id: '1:1', label: '1:1 Square', desc: 'LinkedIn Feed & Carousels' },
                { id: '4:5', label: '4:5 Portrait', desc: 'Max Mobile Screen Real Estate' },
                { id: '9:16', label: '9:16 Vertical', desc: 'TikTok, Stories & Reels' },
              ]"
              :key="asp.id"
              @click="selectedAspect = asp.id as any; pushHistory(); drawCanvas()"
              :class="[
                'w-full p-3 rounded-2xl border text-left flex items-center justify-between transition',
                selectedAspect === asp.id ? 'bg-fuchsia-50 border-fuchsia-400 text-slate-900' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'
              ]"
            >
              <div>
                <div class="font-bold text-xs">{{ asp.label }}</div>
                <div class="text-[10px] text-slate-400">{{ asp.desc }}</div>
              </div>
              <Check v-if="selectedAspect === asp.id" class="w-4 h-4 text-fuchsia-600" />
            </button>
          </div>
        </div>

        <!-- 9. LAYERS STACK DRAWER -->
        <div v-else-if="activeTool === 'layers'" class="p-5 flex-1 space-y-4">
          <div class="flex items-center justify-between pb-1">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
              <Layers class="w-4 h-4 text-fuchsia-600" />
              <span>Layer Stack ({{ elements.length }})</span>
            </h2>
            <button @click="isFlyoutOpen = false" class="text-slate-400 hover:text-slate-600">
              <CloseIcon class="w-4 h-4" />
            </button>
          </div>

          <div class="space-y-1.5">
            <div
              v-for="(el, index) in elements"
              :key="el.id"
              @click="selectedElementId = el.id; drawCanvas()"
              :class="[
                'p-2.5 rounded-xl border flex items-center justify-between text-xs cursor-pointer transition',
                selectedElementId === el.id ? 'bg-fuchsia-50 border-fuchsia-300 text-slate-900 font-bold' : 'bg-slate-50 border-slate-200 text-slate-600'
              ]"
            >
              <div class="flex items-center gap-2 truncate">
                <span class="font-mono text-[10px] text-slate-400">#{{ index + 1 }}</span>
                <span class="truncate max-w-[130px]">{{ el.text }}</span>
              </div>

              <div class="flex items-center gap-1">
                <button
                  @click.stop="el.visible = !el.visible; drawCanvas()"
                  class="p-1 text-slate-400 hover:text-slate-700"
                  title="Toggle Visibility"
                >
                  <Eye v-if="el.visible" class="w-3.5 h-3.5" />
                  <EyeOff v-else class="w-3.5 h-3.5 text-slate-300" />
                </button>
                <button
                  @click.stop="deleteElement(el.id)"
                  class="p-1 text-slate-400 hover:text-rose-600"
                  title="Delete Layer"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          </div>
        </div>

      </aside>

      <!-- C. CANVAS VIEWPORT & WORKSPACE (Center Area Matching Picsart!) -->
      <main class="flex-1 flex flex-col items-center justify-between p-4 bg-[#E5E7EB] overflow-hidden relative">
        
        <!-- Canvas Top Sub-bar (Fill, Crop, Rotate, Page Title) -->
        <div class="w-full flex items-center justify-between max-w-4xl px-2 pb-2 text-xs text-slate-600 shrink-0">
          <div class="flex items-center gap-1.5">
            <button
              @click="rotateCanvas"
              class="flex items-center gap-1 px-3 py-1 bg-white hover:bg-slate-50 rounded-lg border border-slate-200 shadow-2xs font-semibold transition"
              title="Rotate 90 degrees"
            >
              <RotateCw class="w-3.5 h-3.5 text-slate-600" />
              <span>Rotate</span>
            </button>
            <button
              @click="flipHorizontal"
              class="flex items-center gap-1 px-3 py-1 bg-white hover:bg-slate-50 rounded-lg border border-slate-200 shadow-2xs font-semibold transition"
              title="Flip Horizontal"
            >
              <FlipHorizontal class="w-3.5 h-3.5 text-slate-600" />
              <span>Flip H</span>
            </button>
            <button
              @click="flipVertical"
              class="flex items-center gap-1 px-3 py-1 bg-white hover:bg-slate-50 rounded-lg border border-slate-200 shadow-2xs font-semibold transition"
              title="Flip Vertical"
            >
              <FlipVertical class="w-3.5 h-3.5 text-slate-600" />
              <span>Flip V</span>
            </button>
          </div>

          <!-- Page Label -->
          <div class="font-semibold text-slate-500 text-xs flex items-center gap-1.5">
            <span class="text-fuchsia-600 font-bold">Page 1</span>
            <span>-</span>
            <span>{{ projectTitle }}</span>
          </div>

          <div class="flex items-center gap-2 text-xs font-mono text-slate-500">
            <span>{{ selectedAspect }}</span>
            <span>•</span>
            <span>{{ baseWidth }}×{{ computedHeight }}</span>
          </div>
        </div>

        <!-- Center Canvas Frame with Blue Picsart Selection Bounding Box -->
        <div class="relative flex-1 flex items-center justify-center w-full overflow-hidden p-2">
          
          <div
            ref="canvasContainer"
            class="relative border-2 border-blue-500 shadow-2xl rounded-sm overflow-hidden bg-white transition-all"
            :style="{ transform: `scale(${zoomLevel / 100})`, transformOrigin: 'center center' }"
          >
            <!-- Floating Top [...] Action Capsule Pill (Directly from Picsart Reference!) -->
            <div
              @click="isContextMenuOpen = !isContextMenuOpen"
              class="absolute top-2.5 left-1/2 -translate-x-1/2 z-30 bg-white/95 backdrop-blur-md border border-slate-200 rounded-full px-3 py-1 shadow-md flex items-center gap-2 cursor-pointer hover:bg-slate-50 transition"
              title="Layer Quick Actions"
            >
              <MoreHorizontal class="w-4 h-4 text-slate-700" />
            </div>

            <!-- Floating Context Menu Dropdown -->
            <div
              v-if="isContextMenuOpen"
              class="absolute top-10 left-1/2 -translate-x-1/2 z-40 w-48 bg-white border border-slate-200 rounded-2xl shadow-2xl p-1.5 space-y-0.5 text-xs text-slate-700"
            >
              <button
                @click="duplicateElement(); isContextMenuOpen = false"
                class="w-full px-3 py-1.5 rounded-xl text-left hover:bg-slate-50 flex items-center justify-between"
              >
                <span>Duplicate Layer</span>
                <Copy class="w-3.5 h-3.5 text-slate-400" />
              </button>
              <button
                @click="bringToFront(); isContextMenuOpen = false"
                class="w-full px-3 py-1.5 rounded-xl text-left hover:bg-slate-50 flex items-center justify-between"
              >
                <span>Bring to Front</span>
                <ChevronUp class="w-3.5 h-3.5 text-slate-400" />
              </button>
              <button
                @click="sendToBack(); isContextMenuOpen = false"
                class="w-full px-3 py-1.5 rounded-xl text-left hover:bg-slate-50 flex items-center justify-between"
              >
                <span>Send to Back</span>
                <ChevronDown class="w-3.5 h-3.5 text-slate-400" />
              </button>
              <button
                @click="centerElement(); isContextMenuOpen = false"
                class="w-full px-3 py-1.5 rounded-xl text-left hover:bg-slate-50 flex items-center justify-between"
              >
                <span>Center Layer</span>
                <Move class="w-3.5 h-3.5 text-slate-400" />
              </button>
              <button
                @click="deleteElement(); isContextMenuOpen = false"
                class="w-full px-3 py-1.5 rounded-xl text-left hover:bg-rose-50 text-rose-600 flex items-center justify-between font-semibold"
              >
                <span>Delete Layer</span>
                <Trash2 class="w-3.5 h-3.5 text-rose-600" />
              </button>
            </div>

            <!-- Video Player Mode -->
            <div v-if="isVideoAsset" class="relative max-w-full max-h-[65vh] flex items-center justify-center bg-black">
              <video
                ref="videoPlayer"
                :src="resolvePath(currentAsset?.path)"
                class="max-w-full max-h-[65vh] object-contain"
                :style="{ filter: computedFilter }"
                @timeupdate="currentTime = videoPlayer?.currentTime || 0"
                @loadedmetadata="duration = videoPlayer?.duration || 10"
                @ended="isPlaying = false"
                playsinline
              ></video>
            </div>

            <!-- HTML5 Interactive Graphics Canvas -->
            <canvas
              v-else
              ref="mainCanvas"
              class="max-w-full max-h-[65vh] object-contain cursor-crosshair block"
              @mousedown="onCanvasMouseDown"
              @mousemove="onCanvasMouseMove"
              @mouseup="onCanvasMouseUp"
              @mouseleave="onCanvasMouseUp"
            ></canvas>
          </div>

        </div>

        <!-- Canvas Bottom Status Bar (Playback / Zoom / Controls) -->
        <div class="w-full max-w-4xl flex items-center justify-between pt-2 px-2 shrink-0">
          
          <!-- Left: Video Playhead Scrubber & Snap Frame -->
          <div class="flex items-center gap-3">
            <div v-if="isVideoAsset" class="flex items-center gap-2 bg-white border border-slate-200 px-3 py-1 rounded-full shadow-2xs">
              <button
                @click="togglePlay"
                class="p-1 rounded-full text-slate-700 hover:text-fuchsia-600 transition"
                title="Play/Pause (Space)"
              >
                <Play v-if="!isPlaying" class="w-4 h-4 fill-slate-700" />
                <Pause v-else class="w-4 h-4 fill-slate-700" />
              </button>

              <input
                type="range"
                min="0"
                :max="duration"
                step="0.1"
                :value="currentTime"
                @input="seekVideo"
                class="w-32 accent-fuchsia-600 cursor-pointer h-1 bg-slate-200 rounded-lg"
              />

              <span class="text-xs font-mono text-slate-500">
                {{ Math.floor(currentTime) }}s / {{ Math.floor(duration) }}s
              </span>

              <button
                @click="toggleMute"
                class="p-1 text-slate-500 hover:text-slate-800"
                title="Mute Audio"
              >
                <VolumeX v-if="isMuted" class="w-3.5 h-3.5" />
                <Volume2 v-else class="w-3.5 h-3.5" />
              </button>

              <button
                @click="snapCurrentFrame"
                class="flex items-center gap-1 text-[11px] font-bold text-fuchsia-600 hover:text-fuchsia-700 bg-fuchsia-50 hover:bg-fuchsia-100 px-2 py-0.5 rounded-full transition ml-1"
                title="Save this frame as high-res photo"
              >
                <Camera class="w-3 h-3" />
                <span>Snap Frame</span>
              </button>
            </div>

            <div v-else class="hidden sm:flex items-center gap-1.5 text-[11px] text-slate-500 font-mono">
              <Move class="w-3.5 h-3.5 text-fuchsia-600" />
              <span>Drag elements • Backspace to delete</span>
            </div>
          </div>

          <!-- Right: Zoom Controls matching 86% in screenshot -->
          <div class="flex items-center gap-2 bg-white border border-slate-200 px-3 py-1 rounded-full shadow-2xs">
            <button class="text-slate-400 hover:text-slate-700" title="Hide Overlays">
              <Eye class="w-3.5 h-3.5" />
            </button>
            <span class="text-slate-300">|</span>
            <button @click="zoomOut" class="text-slate-500 hover:text-slate-900 font-bold text-xs">-</button>
            <span @click="resetZoom" class="font-mono font-bold text-xs text-slate-700 cursor-pointer">{{ zoomLevel }}%</span>
            <button @click="zoomIn" class="text-slate-500 hover:text-slate-900 font-bold text-xs">+</button>
            <span class="text-slate-300">|</span>
            <button @click="toggleTool('layers')" class="text-slate-400 hover:text-slate-700" title="Layers Stack">
              <Layers class="w-3.5 h-3.5" />
            </button>
          </div>

        </div>

      </main>

    </div>

  </div>
</template>
