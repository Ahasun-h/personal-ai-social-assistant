<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import {
  Type,
  Sparkles,
  Sliders,
  Ratio,
  Layers,
  Palette,
  RotateCw,
  RotateCcw,
  FlipHorizontal,
  FlipVertical,
  Plus,
  Trash2,
  Download,
  Save,
  Check,
  Undo2,
  Move,
  Quote,
  TrendingUp,
  Tag,
  Square,
  Circle,
  ArrowRight,
  Eye,
  EyeOff,
  ChevronUp,
  ChevronDown,
} from 'lucide-vue-next';

interface MediaAsset {
  id: string;
  filename: string;
  path: string;
  width?: number;
  height?: number;
}

interface CanvasElement {
  id: string;
  type: 'text' | 'metric' | 'quote' | 'pill';
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
  alignment: 'left' | 'center' | 'right';
  opacity: number;
}

interface Props {
  asset: MediaAsset | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  (e: 'save', blob: Blob, filename: string): void;
  (e: 'close'): void;
}>();

// Navigation Tabs in Canva Sidebar
const activeSidebarTab = ref<'templates' | 'text' | 'elements' | 'filters' | 'aspect' | 'layers'>('templates');

// Canvas References & Dimensions
const canvasContainer = ref<HTMLDivElement | null>(null);
const mainCanvas = ref<HTMLCanvasElement | null>(null);
const sourceImage = ref<HTMLImageElement | null>(null);

// Aspect Ratio State
const targetAspect = ref<'16:9' | '1:1' | '4:5' | '9:16' | 'original'>('16:9');
const baseWidth = 1200;
const computedHeight = computed(() => {
  switch (targetAspect.value) {
    case '1:1': return 1200;
    case '4:5': return 1500;
    case '9:16': return 2133;
    case '16:9': return 675;
    case 'original':
      if (sourceImage.value && sourceImage.value.naturalHeight) {
        return Math.round(baseWidth * (sourceImage.value.naturalHeight / sourceImage.value.naturalWidth));
      }
      return 675;
    default: return 675;
  }
});

// Image Color Adjustments
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

// Background Customization
const bgType = ref<'image' | 'color' | 'gradient'>('image');
const bgColor = ref('#0F172A');
const bgGradient = ref('linear-gradient(135deg, #0f172a 0%, #1e293b 100%)');

// Canvas Elements (Layers)
const elements = ref<CanvasElement[]>([
  {
    id: 'el-1',
    type: 'metric',
    text: '+88.4%',
    subtext: 'Throughput Under Load',
    x: 80,
    y: 80,
    fontSize: 48,
    fontFamily: 'Inter, sans-serif',
    color: '#9EE493',
    bgColor: 'rgba(15, 23, 42, 0.85)',
    bgPadding: 24,
    hasBackground: true,
    isBold: true,
    isItalic: false,
    alignment: 'left',
    opacity: 1,
  },
  {
    id: 'el-2',
    type: 'pill',
    text: '✦ Synthex Architecture Review',
    x: 80,
    y: 540,
    fontSize: 22,
    fontFamily: 'Inter, sans-serif',
    color: '#FFFFFF',
    bgColor: 'rgba(15, 23, 42, 0.8)',
    bgPadding: 16,
    hasBackground: true,
    isBold: true,
    isItalic: false,
    alignment: 'left',
    opacity: 0.95,
  },
]);

// Selection State
const selectedElementId = ref<string | null>('el-1');
const selectedElement = computed(() => elements.value.find((e) => e.id === selectedElementId.value) || null);

// Dragging State
const isDragging = ref(false);
const dragStartX = ref(0);
const dragStartY = ref(0);
const elementStartX = ref(0);
const elementStartY = ref(0);

// Templates Library
const applyTemplate = (templateName: string) => {
  if (templateName === 'quote') {
    elements.value = [
      {
        id: 'quote-1',
        type: 'quote',
        text: '“Deterministic state machines beat raw prompting every single time in production.”',
        subtext: '— Alex Rivera, Lead Systems Architect',
        x: 100,
        y: 220,
        fontSize: 34,
        fontFamily: 'Georgia, serif',
        color: '#FFFFFF',
        bgColor: 'rgba(15, 23, 42, 0.9)',
        bgPadding: 32,
        hasBackground: true,
        isBold: true,
        isItalic: true,
        alignment: 'left',
        opacity: 1,
      },
    ];
  } else if (templateName === 'metric') {
    elements.value = [
      {
        id: 'metric-1',
        type: 'metric',
        text: '10,000,000+',
        subtext: 'Audience Impressions Generated',
        x: 120,
        y: 180,
        fontSize: 64,
        fontFamily: 'Inter, sans-serif',
        color: '#9EE493',
        bgColor: 'rgba(15, 23, 42, 0.9)',
        bgPadding: 36,
        hasBackground: true,
        isBold: true,
        isItalic: false,
        alignment: 'center',
        opacity: 1,
      },
    ];
  } else if (templateName === 'announcement') {
    elements.value = [
      {
        id: 'ann-1',
        type: 'pill',
        text: '⚡ NEW RELEASE • AUTONOMOUS SDLC',
        x: 80,
        y: 80,
        fontSize: 20,
        fontFamily: 'Inter, sans-serif',
        color: '#0F172A',
        bgColor: '#9EE493',
        bgPadding: 16,
        hasBackground: true,
        isBold: true,
        isItalic: false,
        alignment: 'left',
        opacity: 1,
      },
      {
        id: 'ann-2',
        type: 'text',
        text: 'Zero-Friction Autonomous Completion Protocol',
        x: 80,
        y: 180,
        fontSize: 42,
        fontFamily: 'Inter, sans-serif',
        color: '#FFFFFF',
        bgColor: 'rgba(15, 23, 42, 0.85)',
        bgPadding: 24,
        hasBackground: true,
        isBold: true,
        isItalic: false,
        alignment: 'left',
        opacity: 1,
      },
    ];
  }
  selectedElementId.value = elements.value[0]?.id || null;
  drawCanvas();
};

// Add New Elements
const addTextElement = () => {
  const newEl: CanvasElement = {
    id: `text-${Date.now()}`,
    type: 'text',
    text: 'Click to edit your executive headline',
    x: 150,
    y: 250,
    fontSize: 32,
    fontFamily: 'Inter, sans-serif',
    color: '#FFFFFF',
    bgColor: 'rgba(15, 23, 42, 0.8)',
    bgPadding: 20,
    hasBackground: true,
    isBold: true,
    isItalic: false,
    alignment: 'left',
    opacity: 1,
  };
  elements.value.push(newEl);
  selectedElementId.value = newEl.id;
  drawCanvas();
};

const addMetricElement = () => {
  const newEl: CanvasElement = {
    id: `metric-${Date.now()}`,
    type: 'metric',
    text: '+24.5%',
    subtext: 'Engagement Surge vs Last Month',
    x: 120,
    y: 120,
    fontSize: 48,
    fontFamily: 'Inter, sans-serif',
    color: '#9EE493',
    bgColor: 'rgba(15, 23, 42, 0.9)',
    bgPadding: 24,
    hasBackground: true,
    isBold: true,
    isItalic: false,
    alignment: 'left',
    opacity: 1,
  };
  elements.value.push(newEl);
  selectedElementId.value = newEl.id;
  drawCanvas();
};

const addQuoteElement = () => {
  const newEl: CanvasElement = {
    id: `quote-${Date.now()}`,
    type: 'quote',
    text: '“Simplicity is prerequisite for reliability.”',
    subtext: '— Edsger W. Dijkstra',
    x: 100,
    y: 200,
    fontSize: 32,
    fontFamily: 'Georgia, serif',
    color: '#FFFFFF',
    bgColor: 'rgba(15, 23, 42, 0.85)',
    bgPadding: 28,
    hasBackground: true,
    isBold: true,
    isItalic: true,
    alignment: 'left',
    opacity: 1,
  };
  elements.value.push(newEl);
  selectedElementId.value = newEl.id;
  drawCanvas();
};

const deleteElement = (id: string) => {
  elements.value = elements.value.filter((e) => e.id !== id);
  if (selectedElementId.value === id) {
    selectedElementId.value = elements.value[0]?.id || null;
  }
  drawCanvas();
};

const moveLayer = (id: string, direction: 'up' | 'down') => {
  const index = elements.value.findIndex((e) => e.id === id);
  if (index < 0) return;
  if (direction === 'up' && index < elements.value.length - 1) {
    const temp = elements.value[index];
    elements.value[index] = elements.value[index + 1];
    elements.value[index + 1] = temp;
  } else if (direction === 'down' && index > 0) {
    const temp = elements.value[index];
    elements.value[index] = elements.value[index - 1];
    elements.value[index - 1] = temp;
  }
  drawCanvas();
};

// Canvas Drawing Engine
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

  // 1. Draw Background
  if (bgType.value === 'color') {
    ctx.fillStyle = bgColor.value;
    ctx.fillRect(0, 0, w, h);
  } else if (bgType.value === 'gradient') {
    const grad = ctx.createLinearGradient(0, 0, w, h);
    grad.addColorStop(0, '#0f172a');
    grad.addColorStop(1, '#1e293b');
    ctx.fillStyle = grad;
    ctx.fillRect(0, 0, w, h);
  } else if (sourceImage.value) {
    ctx.save();
    // Apply filters
    const f = adjustments.value;
    ctx.filter = `brightness(${f.brightness}%) contrast(${f.contrast}%) saturate(${f.saturation}%) sepia(${f.warmth}%) grayscale(${f.grayscale}%) blur(${f.blur}px)`;

    // Rotation & flip
    ctx.translate(w / 2, h / 2);
    ctx.rotate((f.rotation * Math.PI) / 180);
    ctx.scale(f.flipH ? -1 : 1, f.flipV ? -1 : 1);

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
    // Elegant dark slate background when image is loading or unavailable
    const grad = ctx.createLinearGradient(0, 0, w, h);
    grad.addColorStop(0, '#0f172a');
    grad.addColorStop(1, '#1e293b');
    ctx.fillStyle = grad;
    ctx.fillRect(0, 0, w, h);
  }

  // 2. Draw Subtle Dark Vignette / Scrim for contrast
  const scrim = ctx.createLinearGradient(0, 0, 0, h);
  scrim.addColorStop(0, 'rgba(15, 23, 42, 0.2)');
  scrim.addColorStop(1, 'rgba(15, 23, 42, 0.6)');
  ctx.fillStyle = scrim;
  ctx.fillRect(0, 0, w, h);

  // 3. Draw Elements (Layers)
  elements.value.forEach((el) => {
    ctx.save();
    ctx.globalAlpha = el.opacity ?? 1;

    const fontStyle = el.isItalic ? 'italic' : 'normal';
    const fontWeight = el.isBold ? 'bold' : 'normal';
    ctx.font = `${fontStyle} ${fontWeight} ${el.fontSize}px ${el.fontFamily}`;

    // Measure Text
    const metrics = ctx.measureText(el.text);
    let boxWidth = metrics.width + el.bgPadding * 2;
    let boxHeight = el.fontSize + el.bgPadding * 1.5;

    let subMetrics = null;
    if (el.subtext) {
      ctx.font = `normal 500 ${Math.round(el.fontSize * 0.45)}px ${el.fontFamily}`;
      subMetrics = ctx.measureText(el.subtext);
      boxWidth = Math.max(boxWidth, subMetrics.width + el.bgPadding * 2);
      boxHeight += Math.round(el.fontSize * 0.7);
    }

    // Draw Background Pill / Badge
    if (el.hasBackground) {
      ctx.fillStyle = el.bgColor;
      ctx.beginPath();
      ctx.roundRect(el.x, el.y, boxWidth, boxHeight, 16);
      ctx.fill();

      // Outline border
      ctx.strokeStyle = 'rgba(255, 255, 255, 0.12)';
      ctx.lineWidth = 1.5;
      ctx.stroke();
    }

    // Draw Primary Text
    ctx.font = `${fontStyle} ${fontWeight} ${el.fontSize}px ${el.fontFamily}`;
    ctx.fillStyle = el.color;
    ctx.textBaseline = 'top';

    const textX = el.hasBackground ? el.x + el.bgPadding : el.x;
    const textY = el.hasBackground ? el.y + el.bgPadding * 0.7 : el.y;

    ctx.fillText(el.text, textX, textY);

    // Draw Subtext if present
    if (el.subtext) {
      ctx.font = `normal 500 ${Math.round(el.fontSize * 0.45)}px ${el.fontFamily}`;
      ctx.fillStyle = el.type === 'metric' ? '#94A3B8' : 'rgba(255, 255, 255, 0.75)';
      ctx.fillText(el.subtext, textX, textY + el.fontSize + 8);
    }

    // Draw Selection Bounding Box if active
    if (selectedElementId.value === el.id) {
      ctx.strokeStyle = '#9EE493';
      ctx.lineWidth = 2.5;
      ctx.setLineDash([6, 4]);
      ctx.strokeRect(el.x - 4, el.y - 4, boxWidth + 8, boxHeight + 8);

      // Corner handles
      ctx.setLineDash([]);
      ctx.fillStyle = '#9EE493';
      ctx.fillRect(el.x - 8, el.y - 8, 8, 8);
      ctx.fillRect(el.x + boxWidth, el.y - 8, 8, 8);
      ctx.fillRect(el.x - 8, el.y + boxHeight, 8, 8);
      ctx.fillRect(el.x + boxWidth, el.y + boxHeight, 8, 8);
    }

    ctx.restore();
  });
};

// Canvas Mouse Interactions (Dragging & Selecting)
const onCanvasMouseDown = (e: MouseEvent) => {
  const canvas = mainCanvas.value;
  if (!canvas) return;
  const rect = canvas.getBoundingClientRect();
  const scaleX = canvas.width / rect.width;
  const scaleY = canvas.height / rect.height;

  const clickX = (e.clientX - rect.left) * scaleX;
  const clickY = (e.clientY - rect.top) * scaleY;

  // Detect which element was clicked (checking top-to-bottom of stack)
  let found: CanvasElement | null = null;
  for (let i = elements.value.length - 1; i >= 0; i--) {
    const el = elements.value[i];
    // Approximation of bounding box
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

  const dx = currentX - dragStartX.value;
  const dy = currentY - dragStartY.value;

  selectedElement.value.x = Math.round(elementStartX.value + dx);
  selectedElement.value.y = Math.round(elementStartY.value + dy);

  drawCanvas();
};

const onCanvasMouseUp = () => {
  isDragging.value = false;
};

// Helper to resolve asset path reliably
const resolveImagePath = (p?: string): string => {
  if (!p) return '';
  if (p.startsWith('http://') || p.startsWith('https://')) return p;
  if (p.startsWith('/storage/')) return p;
  if (p.startsWith('storage/')) return '/' + p;
  if (p.startsWith('media/')) return '/storage/' + p;
  if (p.startsWith('/')) return p;
  return '/storage/' + p;
};

// Load Image
const loadImage = () => {
  drawCanvas(); // Render immediately with background & default layers
  if (!props.asset?.path) return;
  const path = resolveImagePath(props.asset.path);

  const img = new Image();
  img.crossOrigin = 'anonymous';
  img.src = path;
  img.onload = () => {
    sourceImage.value = img;
    nextTick(() => {
      drawCanvas();
    });
  };
  img.onerror = () => {
    // Retry without crossOrigin attribute in case server lacks CORS headers
    const fallback = new Image();
    fallback.src = path;
    fallback.onload = () => {
      sourceImage.value = fallback;
      nextTick(() => {
        drawCanvas();
      });
    };
    fallback.onerror = () => {
      console.warn('Canvas source image could not be loaded:', path);
      drawCanvas();
    };
  };
};

// Export & Save
const exportImage = () => {
  const canvas = mainCanvas.value;
  if (!canvas) return;

  // Clear selection box for clean export
  selectedElementId.value = null;
  drawCanvas();

  try {
    canvas.toBlob((blob) => {
      if (!blob) {
        fallbackExport();
        return;
      }
      const rawName = props.asset?.filename ? props.asset.filename.replace(/\.[^/.]+$/, "") : 'design';
      const filename = `canva_${Date.now()}_${rawName}.jpg`;
      emit('save', blob, filename);
    }, 'image/jpeg', 0.95);
  } catch (e) {
    console.warn('Direct canvas export tainted by external CORS image, running clean fallback:', e);
    fallbackExport();
  }
};

const fallbackExport = () => {
  const originalType = bgType.value;
  bgType.value = 'gradient';
  drawCanvas();

  try {
    mainCanvas.value?.toBlob((blob) => {
      bgType.value = originalType;
      drawCanvas();
      if (!blob) return;
      const rawName = props.asset?.filename ? props.asset.filename.replace(/\.[^/.]+$/, "") : 'design';
      emit('save', blob, `canva_safe_${Date.now()}_${rawName}.jpg`);
    }, 'image/jpeg', 0.95);
  } catch {
    bgType.value = originalType;
    drawCanvas();
  }
};

const downloadLocally = () => {
  const canvas = mainCanvas.value;
  if (!canvas) return;

  selectedElementId.value = null;
  drawCanvas();

  try {
    const link = document.createElement('a');
    link.download = `canva_export_${Date.now()}.jpg`;
    link.href = canvas.toDataURL('image/jpeg', 0.95);
    link.click();
  } catch (e) {
    console.warn('Direct download tainted, using vector fallback:', e);
    const originalType = bgType.value;
    bgType.value = 'gradient';
    drawCanvas();
    try {
      const link = document.createElement('a');
      link.download = `canva_export_${Date.now()}.jpg`;
      link.href = canvas.toDataURL('image/jpeg', 0.95);
      link.click();
    } finally {
      bgType.value = originalType;
      drawCanvas();
    }
  }
};

onMounted(() => {
  loadImage();
});

watch(() => props.asset, () => {
  loadImage();
});

watch([targetAspect, adjustments, bgType, bgColor], () => {
  drawCanvas();
}, { deep: true });
</script>

<template>
  <div class="flex-1 flex flex-col lg:flex-row overflow-hidden bg-slate-950 font-sans select-none">
    
    <!-- LEFT CANVA SIDEBAR (Tools, Elements, Layers) -->
    <div class="w-full lg:w-80 bg-slate-900 border-r border-slate-800 flex flex-col shrink-0 overflow-y-auto">
      
      <!-- Canva Navigation Tabs Strip -->
      <div class="flex items-center gap-1 p-2 bg-slate-950 border-b border-slate-800 overflow-x-auto scrollbar-none">
        <button
          v-for="tab in [
            { id: 'templates', label: 'Layouts', icon: Sparkles },
            { id: 'text', label: 'Text', icon: Type },
            { id: 'elements', label: 'Badges', icon: Tag },
            { id: 'filters', label: 'Tune', icon: Sliders },
            { id: 'aspect', label: 'Aspect', icon: Ratio },
            { id: 'layers', label: 'Layers', icon: Layers },
          ]"
          :key="tab.id"
          @click="activeSidebarTab = tab.id as any"
          :class="[
            'flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition',
            activeSidebarTab === tab.id ? 'bg-[#9EE493] text-slate-950 shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800'
          ]"
        >
          <component :is="tab.icon" class="w-3.5 h-3.5" />
          <span>{{ tab.label }}</span>
        </button>
      </div>

      <!-- SIDEBAR TAB CONTENTS -->
      <div class="p-4 space-y-5 flex-1">
        
        <!-- 1. TEMPLATES / PRESETS -->
        <div v-if="activeSidebarTab === 'templates'" class="space-y-3">
          <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider font-mono">
            Social Card Presets
          </h3>
          <p class="text-[11px] text-slate-400">
            Click to apply an executive design framework to your image.
          </p>

          <div class="grid grid-cols-1 gap-2.5 pt-1">
            <button
              @click="applyTemplate('quote')"
              class="p-3.5 rounded-2xl bg-slate-800/80 hover:bg-slate-800 border border-slate-700/80 text-left transition group"
            >
              <div class="flex items-center gap-2 text-xs font-bold text-white group-hover:text-[#9EE493]">
                <Quote class="w-4 h-4 text-[#9EE493]" />
                <span>Executive Thought Leader Quote</span>
              </div>
              <p class="text-[11px] text-slate-400 mt-1 line-clamp-2">
                Frosted glass badge with large quotation marks and author attribution.
              </p>
            </button>

            <button
              @click="applyTemplate('metric')"
              class="p-3.5 rounded-2xl bg-slate-800/80 hover:bg-slate-800 border border-slate-700/80 text-left transition group"
            >
              <div class="flex items-center gap-2 text-xs font-bold text-white group-hover:text-[#9EE493]">
                <TrendingUp class="w-4 h-4 text-emerald-400" />
                <span>High-Impact Stat Callout</span>
              </div>
              <p class="text-[11px] text-slate-400 mt-1 line-clamp-2">
                Large bold metric (e.g. +88.4%) with descriptive subtitle.
              </p>
            </button>

            <button
              @click="applyTemplate('announcement')"
              class="p-3.5 rounded-2xl bg-slate-800/80 hover:bg-slate-800 border border-slate-700/80 text-left transition group"
            >
              <div class="flex items-center gap-2 text-xs font-bold text-white group-hover:text-[#9EE493]">
                <Sparkles class="w-4 h-4 text-amber-400" />
                <span>Product Announcement Banner</span>
              </div>
              <p class="text-[11px] text-slate-400 mt-1 line-clamp-2">
                Vibrant pill badge + dual-tone headline typography.
              </p>
            </button>
          </div>
        </div>

        <!-- 2. TEXT ADDER & TYPOGRAPHY -->
        <div v-if="activeSidebarTab === 'text'" class="space-y-4">
          <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider font-mono">
            Add Typography
          </h3>

          <div class="space-y-2">
            <button
              @click="addTextElement"
              class="w-full py-2.5 px-3 rounded-2xl bg-slate-800 hover:bg-slate-750 border border-slate-700 text-xs font-bold text-white flex items-center justify-center gap-2 transition"
            >
              <Plus class="w-4 h-4 text-[#9EE493]" />
              <span>Add Headline Text</span>
            </button>

            <button
              @click="addMetricElement"
              class="w-full py-2.5 px-3 rounded-2xl bg-slate-800 hover:bg-slate-750 border border-slate-700 text-xs font-bold text-white flex items-center justify-center gap-2 transition"
            >
              <Plus class="w-4 h-4 text-[#9EE493]" />
              <span>Add Stat Metric Block</span>
            </button>

            <button
              @click="addQuoteElement"
              class="w-full py-2.5 px-3 rounded-2xl bg-slate-800 hover:bg-slate-750 border border-slate-700 text-xs font-bold text-white flex items-center justify-center gap-2 transition"
            >
              <Plus class="w-4 h-4 text-[#9EE493]" />
              <span>Add Quote Container</span>
            </button>
          </div>

          <!-- Inspector for Selected Text Element -->
          <div v-if="selectedElement" class="p-3.5 rounded-2xl bg-slate-950 border border-slate-800 space-y-3 pt-3">
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold text-slate-300 font-mono">Active Text Properties</span>
              <button @click="deleteElement(selectedElement.id)" class="text-rose-400 hover:text-rose-300 p-1">
                <Trash2 class="w-3.5 h-3.5" />
              </button>
            </div>

            <div>
              <label class="text-[10px] text-slate-400 block mb-1">Text Content</label>
              <textarea
                v-model="selectedElement.text"
                @input="drawCanvas"
                rows="2"
                class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2 text-xs text-white focus:outline-none focus:border-[#9EE493]"
              ></textarea>
            </div>

            <div v-if="selectedElement.type === 'metric' || selectedElement.type === 'quote'">
              <label class="text-[10px] text-slate-400 block mb-1">Subtitle / Author</label>
              <input
                v-model="selectedElement.subtext"
                @input="drawCanvas"
                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-2.5 py-1.5 text-xs text-white focus:outline-none focus:border-[#9EE493]"
              />
            </div>

            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="text-[10px] text-slate-400 block mb-1">Font Size ({{ selectedElement.fontSize }}px)</label>
                <input
                  type="range"
                  min="16"
                  max="90"
                  v-model.number="selectedElement.fontSize"
                  @input="drawCanvas"
                  class="w-full accent-[#9EE493] h-1.5 bg-slate-800 rounded-lg cursor-pointer"
                />
              </div>

              <div>
                <label class="text-[10px] text-slate-400 block mb-1">Text Color</label>
                <div class="flex items-center gap-1.5">
                  <input
                    type="color"
                    v-model="selectedElement.color"
                    @input="drawCanvas"
                    class="w-7 h-7 rounded-lg border-0 cursor-pointer bg-transparent"
                  />
                  <span class="text-[10px] font-mono text-slate-400">{{ selectedElement.color }}</span>
                </div>
              </div>
            </div>

            <div class="flex items-center justify-between pt-1">
              <span class="text-xs text-slate-300">Frosted Badge Pill</span>
              <input
                type="checkbox"
                v-model="selectedElement.hasBackground"
                @change="drawCanvas"
                class="rounded accent-[#9EE493] cursor-pointer"
              />
            </div>
          </div>
        </div>

        <!-- 3. BADGES & SHAPES -->
        <div v-if="activeSidebarTab === 'elements'" class="space-y-4">
          <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider font-mono">
            Executive Badges & Accents
          </h3>

          <div class="grid grid-cols-2 gap-2">
            <button
              @click="addTextElement"
              class="p-3 rounded-2xl bg-slate-800 hover:bg-slate-750 border border-slate-700 text-left transition"
            >
              <div class="text-xs font-bold text-white">✦ Sparkle Tag</div>
              <p class="text-[10px] text-slate-400 mt-0.5">Feature announcement</p>
            </button>

            <button
              @click="addMetricElement"
              class="p-3 rounded-2xl bg-slate-800 hover:bg-slate-750 border border-slate-700 text-left transition"
            >
              <div class="text-xs font-bold text-[#9EE493]">✓ Verified Pill</div>
              <p class="text-[10px] text-slate-400 mt-0.5">Trust & authority badge</p>
            </button>
          </div>
        </div>

        <!-- 4. IMAGE ADJUSTMENTS & FILTERS -->
        <div v-if="activeSidebarTab === 'filters'" class="space-y-3">
          <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider font-mono">
            Image Fine-Tuning
          </h3>

          <div class="space-y-3 pt-1">
            <div class="space-y-1">
              <div class="flex justify-between text-xs text-slate-300">
                <span>Brightness</span>
                <span class="font-mono text-slate-400">{{ adjustments.brightness }}%</span>
              </div>
              <input
                type="range"
                min="50"
                max="150"
                v-model.number="adjustments.brightness"
                @input="drawCanvas"
                class="w-full accent-[#9EE493] h-1.5 bg-slate-800 rounded-lg cursor-pointer"
              />
            </div>

            <div class="space-y-1">
              <div class="flex justify-between text-xs text-slate-300">
                <span>Contrast</span>
                <span class="font-mono text-slate-400">{{ adjustments.contrast }}%</span>
              </div>
              <input
                type="range"
                min="50"
                max="150"
                v-model.number="adjustments.contrast"
                @input="drawCanvas"
                class="w-full accent-[#9EE493] h-1.5 bg-slate-800 rounded-lg cursor-pointer"
              />
            </div>

            <div class="space-y-1">
              <div class="flex justify-between text-xs text-slate-300">
                <span>Saturation</span>
                <span class="font-mono text-slate-400">{{ adjustments.saturation }}%</span>
              </div>
              <input
                type="range"
                min="0"
                max="200"
                v-model.number="adjustments.saturation"
                @input="drawCanvas"
                class="w-full accent-[#9EE493] h-1.5 bg-slate-800 rounded-lg cursor-pointer"
              />
            </div>

            <div class="space-y-1">
              <div class="flex justify-between text-xs text-slate-300">
                <span>Warmth</span>
                <span class="font-mono text-slate-400">{{ adjustments.warmth }}%</span>
              </div>
              <input
                type="range"
                min="0"
                max="100"
                v-model.number="adjustments.warmth"
                @input="drawCanvas"
                class="w-full accent-[#9EE493] h-1.5 bg-slate-800 rounded-lg cursor-pointer"
              />
            </div>

            <div class="space-y-1">
              <div class="flex justify-between text-xs text-slate-300">
                <span>Grayscale (B&W)</span>
                <span class="font-mono text-slate-400">{{ adjustments.grayscale }}%</span>
              </div>
              <input
                type="range"
                min="0"
                max="100"
                v-model.number="adjustments.grayscale"
                @input="drawCanvas"
                class="w-full accent-[#9EE493] h-1.5 bg-slate-800 rounded-lg cursor-pointer"
              />
            </div>
          </div>
        </div>

        <!-- 5. ASPECT RATIO PRESETS -->
        <div v-if="activeSidebarTab === 'aspect'" class="space-y-3">
          <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider font-mono">
            Social Canvas Dimensions
          </h3>

          <div class="space-y-2">
            <button
              v-for="asp in [
                { id: '16:9', label: '16:9 Landscape', desc: 'X (Twitter) Feed & YouTube' },
                { id: '1:1', label: '1:1 Square', desc: 'LinkedIn Feed & Carousels' },
                { id: '4:5', label: '4:5 Portrait', desc: 'Max Mobile Screen Real Estate' },
                { id: '9:16', label: '9:16 Vertical', desc: 'Stories, Reels & Shorts' },
                { id: 'original', label: 'Original Ratio', desc: 'Match source media' },
              ]"
              :key="asp.id"
              @click="targetAspect = asp.id as any"
              :class="[
                'w-full p-3 rounded-2xl border text-left flex items-center justify-between transition',
                targetAspect === asp.id ? 'bg-[#9EE493]/15 border-[#9EE493] text-white' : 'bg-slate-800/80 border-slate-700/80 text-slate-300 hover:bg-slate-800'
              ]"
            >
              <div>
                <div class="font-bold text-xs">{{ asp.label }}</div>
                <div class="text-[10px] text-slate-400">{{ asp.desc }}</div>
              </div>
              <Check v-if="targetAspect === asp.id" class="w-4 h-4 text-[#9EE493]" />
            </button>
          </div>
        </div>

        <!-- 6. LAYERS STACK -->
        <div v-if="activeSidebarTab === 'layers'" class="space-y-3">
          <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider font-mono">
            Layer Stack ({{ elements.length }})
          </h3>

          <div class="space-y-1.5">
            <div
              v-for="(el, index) in elements"
              :key="el.id"
              @click="selectedElementId = el.id; drawCanvas()"
              :class="[
                'p-2.5 rounded-xl border flex items-center justify-between transition cursor-pointer text-xs',
                selectedElementId === el.id ? 'bg-[#9EE493]/20 border-[#9EE493] text-white' : 'bg-slate-800 border-slate-700 text-slate-300'
              ]"
            >
              <div class="flex items-center gap-2 truncate">
                <span class="font-mono text-[10px] text-slate-400">#{{ index + 1 }}</span>
                <span class="truncate max-w-[120px] font-medium">{{ el.text }}</span>
              </div>

              <div class="flex items-center gap-1">
                <button @click.stop="moveLayer(el.id, 'up')" class="p-1 hover:text-white text-slate-400">
                  <ChevronUp class="w-3.5 h-3.5" />
                </button>
                <button @click.stop="moveLayer(el.id, 'down')" class="p-1 hover:text-white text-slate-400">
                  <ChevronDown class="w-3.5 h-3.5" />
                </button>
                <button @click.stop="deleteElement(el.id)" class="p-1 hover:text-rose-400 text-slate-400">
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          </div>
        </div>

      </div>

    </div>

    <!-- CENTER CANVA WORKSPACE -->
    <div class="flex-1 flex flex-col items-center justify-center p-6 bg-slate-950 overflow-hidden relative">
      
      <!-- Top Action Bar -->
      <div class="w-full flex items-center justify-between pb-4 max-w-4xl">
        <div class="flex items-center gap-2 text-xs font-mono text-slate-400">
          <span class="text-[#9EE493] font-bold">Canva Studio Engine</span>
          <span>•</span>
          <span>{{ targetAspect.toUpperCase() }} Canvas</span>
          <span>•</span>
          <span>{{ baseWidth }}×{{ computedHeight }}px</span>
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="downloadLocally"
            class="px-4 py-1.5 rounded-full bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white font-semibold text-xs flex items-center gap-1.5 transition"
          >
            <Download class="w-3.5 h-3.5" />
            <span>Download</span>
          </button>

          <button
            @click="exportImage"
            class="px-5 py-1.5 rounded-full bg-[#9EE493] hover:bg-[#8ee082] text-slate-950 font-bold text-xs flex items-center gap-1.5 shadow-md transition"
          >
            <Save class="w-3.5 h-3.5" />
            <span>Save to Media Vault</span>
          </button>
        </div>
      </div>

      <!-- Canvas Stage Frame -->
      <div
        ref="canvasContainer"
        class="relative max-w-full max-h-[68vh] flex items-center justify-center shadow-2xl rounded-2xl overflow-hidden border border-slate-800 bg-[radial-gradient(#1e293b_1px,transparent_1px)] [background-size:16px_16px]"
      >
        <canvas
          ref="mainCanvas"
          class="max-w-full max-h-[64vh] object-contain cursor-crosshair rounded-lg"
          @mousedown="onCanvasMouseDown"
          @mousemove="onCanvasMouseMove"
          @mouseup="onCanvasMouseUp"
          @mouseleave="onCanvasMouseUp"
        ></canvas>
      </div>

      <!-- Footer Help Pill -->
      <div class="mt-4 text-[11px] text-slate-400 font-mono flex items-center gap-2">
        <Move class="w-3.5 h-3.5 text-[#9EE493]" />
        <span>Click and drag any text or metric badge directly on the canvas to reposition.</span>
      </div>

    </div>

  </div>
</template>

