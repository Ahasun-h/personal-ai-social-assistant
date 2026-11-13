<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, router, Link, usePage } from '@inertiajs/vue3';
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
  ChevronDown,
  Check,
  Cpu,
  Zap,
  ExternalLink,
  Lock,
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
  aiConfig?: Record<string, any>;
}

const props = withDefaults(defineProps<Props>(), {
  assets: () => [],
  budget: () => ({}),
  aiConfig: () => ({}),
});

interface AIModelItem {
  id: string;
  name: string;
  tag: string;
  cost: string;
}

interface AIProviderItem {
  id: string;
  name: string;
  tag: string;
  badge: string;
  costPerGen: string;
  category: 'user_vault' | 'diffusion';
  isUserSetup: boolean;
  defaultModel: string;
  models: AIModelItem[];
}

// Live AI Config state synced with /settings
const liveAiConfig = ref<any>(props.aiConfig || {});

// Dropdown UI states
const isProviderDropdownOpen = ref(false);
const isModelDropdownOpen = ref(false);

const toggleProviderDropdown = () => {
  isProviderDropdownOpen.value = !isProviderDropdownOpen.value;
  if (isProviderDropdownOpen.value) isModelDropdownOpen.value = false;
};

const toggleModelDropdown = () => {
  isModelDropdownOpen.value = !isModelDropdownOpen.value;
  if (isModelDropdownOpen.value) isProviderDropdownOpen.value = false;
};

const closeDropdowns = () => {
  isProviderDropdownOpen.value = false;
  isModelDropdownOpen.value = false;
};

const selectedProvider = ref<string>(props.aiConfig?.active_provider || 'z_ai');
const selectedModel = ref<string>('');

const syncWithVaultConfig = (cfg: any) => {
  if (!cfg) return;
  liveAiConfig.value = cfg;
  const activeKey = cfg.active_provider || 'z_ai';
  selectedProvider.value = activeKey;
  const savedModel = cfg.providers?.[activeKey]?.model;
  if (savedModel) {
    selectedModel.value = savedModel;
  }
};

watch(
  () => props.aiConfig,
  (newVal) => {
    if (newVal) syncWithVaultConfig(newVal);
  },
  { immediate: true, deep: true }
);

const fetchLiveAiConfig = async () => {
  try {
    const res = await fetch('/api/v1/ai/config', { headers: { 'Accept': 'application/json' } });
    if (res.ok) {
      const json = await res.json();
      if (json.success && json.data) {
        syncWithVaultConfig(json.data);
      }
    }
  } catch (e) {
    // fallback to props
  }
};

onMounted(() => {
  window.addEventListener('click', closeDropdowns);
  fetchLiveAiConfig();
});

onUnmounted(() => {
  window.removeEventListener('click', closeDropdowns);
});

const activeVaultKey = computed(() => liveAiConfig.value?.active_provider || props.aiConfig?.active_provider || 'z_ai');

const buildModelList = (savedModel: string, rawModels: string[], defaultCost = '$0.003') => {
  const models = rawModels.includes(savedModel) ? rawModels : [savedModel, ...rawModels];
  return models.map((m: string) => ({
    id: m,
    name: m,
    tag: m === savedModel ? 'Saved in /settings BYO-AI' : 'BYO-AI Model Variant',
    cost: m.includes('pro') || m.includes('opus') ? '$0.005' : defaultCost,
  }));
};

const providersList = computed<AIProviderItem[]>(() => {
  const cfg = liveAiConfig.value || props.aiConfig || {};
  const providers = cfg.providers || {};
  const activeKey = activeVaultKey.value;
  const list: AIProviderItem[] = [];

  // 1. Z AI
  const zAiSavedModel = providers.z_ai?.model || 'z-ai-large';
  list.push({
    id: 'z_ai',
    name: 'Z AI',
    tag: 'Enterprise High-Throughput Inference',
    badge: activeKey === 'z_ai' ? '★ Active in /settings' : 'Saved in /settings',
    costPerGen: '$0.003 / prompt',
    category: 'user_vault',
    isUserSetup: true,
    defaultModel: zAiSavedModel,
    models: buildModelList(zAiSavedModel, providers.z_ai?.models || ['z-ai-large', 'z-ai-pro', 'z-ai-coder'], '$0.003'),
  });

  // 2. Google Gemini
  const geminiSavedModel = providers.gemini?.model || 'gemini-3.1-flash-lite';
  list.push({
    id: 'gemini',
    name: 'Google Gemini',
    tag: 'Official Google GenAI Multimodal Engine',
    badge: activeKey === 'gemini' ? '★ Active in /settings' : 'Saved in /settings',
    costPerGen: '$0.003 / prompt',
    category: 'user_vault',
    isUserSetup: true,
    defaultModel: geminiSavedModel,
    models: buildModelList(geminiSavedModel, providers.gemini?.models || ['gemini-3.1-flash-lite', 'gemini-3.5-flash', 'gemini-3.8-flash', 'gemini-2.5-pro'], '$0.003'),
  });

  // 3. OpenAI
  const openAiSavedModel = providers.openai?.model || 'gpt-4o';
  list.push({
    id: 'openai',
    name: 'OpenAI',
    tag: 'Benchmark GPT-4o & Structured Vision',
    badge: activeKey === 'openai' ? '★ Active in /settings' : 'Saved in /settings',
    costPerGen: '$0.005 / prompt',
    category: 'user_vault',
    isUserSetup: true,
    defaultModel: openAiSavedModel,
    models: buildModelList(openAiSavedModel, providers.openai?.models || ['gpt-4o', 'gpt-4o-mini', 'o1-preview', 'o1-mini', 'gpt-4-turbo'], '$0.005'),
  });

  // 4. Anthropic Claude
  const claudeSavedModel = providers.claude?.model || 'claude-3-5-sonnet-20241022';
  list.push({
    id: 'claude',
    name: 'Anthropic Claude',
    tag: 'Natural prose, nuance & brand fidelity',
    badge: activeKey === 'claude' ? '★ Active in /settings' : 'Saved in /settings',
    costPerGen: '$0.005 / prompt',
    category: 'user_vault',
    isUserSetup: true,
    defaultModel: claudeSavedModel,
    models: buildModelList(claudeSavedModel, providers.claude?.models || ['claude-3-5-sonnet-20241022', 'claude-3-5-haiku-20241022', 'claude-3-opus-20240229'], '$0.005'),
  });

  // 5. Custom OpenAI-Compatible
  const customSavedModel = providers.custom?.model || 'llama3.2';
  list.push({
    id: 'custom',
    name: 'Custom LLM / OpenAI Compatible',
    tag: 'Self-hosted Ollama, Groq, or DeepSeek API',
    badge: activeKey === 'custom' ? '★ Active in /settings' : 'Saved in /settings',
    costPerGen: 'Local / Free',
    category: 'user_vault',
    isUserSetup: true,
    defaultModel: customSavedModel,
    models: buildModelList(customSavedModel, providers.custom?.models || ['llama3.2', 'deepseek-chat', 'llama-3.1-70b', 'mixtral-8x7b'], 'Local / Free'),
  });

  // Active provider from /settings is always placed at the very top!
  return list.sort((a, b) => {
    if (a.id === activeKey) return -1;
    if (b.id === activeKey) return 1;
    return 0;
  });
});

const activeProviderObj = computed(() => {
  return providersList.value.find(p => p.id === selectedProvider.value) || providersList.value[0];
});

// Initialize or update selectedModel when provider changes
const activeModelObj = computed(() => {
  const prov = activeProviderObj.value;
  if (!prov || !prov.models.length) {
    return { id: 'standard', name: 'standard', tag: 'Standard model', cost: '$0.003' };
  }
  return prov.models.find(m => m.id === selectedModel.value) || prov.models[0];
});

// Initialize selectedModel from activeProvider
if (!selectedModel.value && activeProviderObj.value) {
  selectedModel.value = activeProviderObj.value.defaultModel;
}

const selectProvider = (provId: string) => {
  selectedProvider.value = provId;
  const prov = providersList.value.find(p => p.id === provId);
  if (prov) {
    selectedModel.value = prov.defaultModel;
  }
  isProviderDropdownOpen.value = false;
};

const selectModel = (modelId: string) => {
  selectedModel.value = modelId;
  isModelDropdownOpen.value = false;
};

const page = usePage();
const hasGlobalAiKey = computed(() => {
  return !!(page.props as any).hasAiKey;
});

const hasSelectedProviderKey = computed(() => {
  const pId = selectedProvider.value;
  const cfg = liveAiConfig.value || props.aiConfig || {};
  const providerConf = cfg.providers?.[pId];
  if (!providerConf) return false;
  if (pId === 'custom') {
    return !!(providerConf.api_key || providerConf.base_url);
  }
  return !!(providerConf.api_key && providerConf.api_key.trim().length > 0);
});

const canGenerateMedia = computed(() => {
  return hasGlobalAiKey.value && hasSelectedProviderKey.value;
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
        provider: selectedProvider.value,
        model: selectedModel.value,
      }),
    });
    if (res.ok) {
      uploadMessage.value = {
        type: 'success',
        text: `Asset synthesized with ${activeProviderObj.value.name} (${activeModelObj.value.name}) and saved to vault!`,
      };
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
      let errorMsg = 'Upload failed.';
      try {
        const err = await res.json();
        if (err.errors) {
          errorMsg = Object.values(err.errors).flat().join(', ');
        } else if (err.message) {
          errorMsg = err.message;
        }
      } catch {
        if (res.status === 413) errorMsg = 'File is too large (exceeds 128MB limit).';
      }
      uploadMessage.value = { type: 'error', text: errorMsg };
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
    const res = await fetch(`/api/v1/media/${id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
      },
    });
    if (res.ok) {
      uploadMessage.value = { type: 'success', text: 'Asset removed from vault.' };
      router.reload();
    } else {
      const err = await res.json().catch(() => ({}));
      uploadMessage.value = { type: 'error', text: err.message || 'Failed to remove media asset.' };
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
          <!-- Launch Photo Editor directly -->
          <button
            @click="openBlankStudio"
            class="px-4 py-2 rounded-full bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs flex items-center gap-2 shadow-sm transition shrink-0"
          >
            <SlidersHorizontal class="w-4 h-4 text-[#9EE493]" />
            <span>Open Photo Editor</span>
          </button>

          <!-- Notification Banner -->
          <div v-if="uploadMessage" class="flex items-center gap-2 px-4 py-2 rounded-full text-xs font-semibold shadow-sm transition" :class="uploadMessage.type === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200'">
            <CheckCircle2 v-if="uploadMessage.type === 'success'" class="w-4 h-4 text-emerald-600" />
            <AlertCircle v-else class="w-4 h-4 text-rose-600" />
            <span>{{ uploadMessage.text }}</span>
          </div>
        </div>
      </div>

      <!-- Missing Global AI API Key Warning Banner -->
      <div
        v-if="!hasGlobalAiKey"
        class="bg-amber-50 border border-amber-300 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-amber-900 shadow-sm"
      >
        <div class="flex items-center gap-2.5">
          <AlertCircle class="w-4 h-4 text-amber-600 shrink-0" />
          <span>No AI API Key configured in Settings. Visual AI synthesis is disabled until an active provider key is saved.</span>
        </div>
        <Link
          href="/settings"
          class="shrink-0 px-3.5 py-1.5 rounded-full bg-amber-200 hover:bg-amber-300 text-amber-950 font-semibold text-xs transition inline-flex items-center gap-1.5 self-start sm:self-auto shadow-xs"
        >
          <span>Configure API Key in Settings</span>
          <span>→</span>
        </Link>
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
            <!-- Bring Your Own AI Model (BYO-AI) from /settings -->
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <label class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                  <Sparkles class="w-3.5 h-3.5 text-emerald-600" />
                  <span>Bring Your Own AI Model (BYO-AI)</span>
                </label>
                <a
                  href="/settings"
                  class="text-[10px] font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1 transition"
                  title="Configure Bring Your Own AI Model (BYO-AI) in /settings"
                >
                  <span>Configure in /settings</span>
                  <ExternalLink class="w-2.5 h-2.5" />
                </a>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 relative">
                <!-- Dropdown 1: BYO-AI Provider -->
                <div class="relative">
                  <button
                    type="button"
                    @click.stop="toggleProviderDropdown"
                    class="w-full px-3 py-2 rounded-2xl border border-slate-200 bg-slate-50 hover:bg-white text-left transition flex items-center justify-between shadow-2xs focus:outline-none focus:ring-1 focus:ring-emerald-400 cursor-pointer"
                  >
                    <div class="truncate mr-2">
                      <div class="text-[9px] text-slate-400 font-mono uppercase tracking-wider font-semibold">BYO-AI Provider</div>
                      <div class="text-xs font-bold text-slate-900 truncate flex items-center gap-1.5 mt-0.5">
                        <span class="truncate">{{ activeProviderObj.name }}</span>
                        <span
                          class="text-[9px] font-semibold px-1.5 py-0.2 rounded-full shrink-0"
                          :class="activeProviderObj.badge.includes('Active') ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700'"
                        >
                          {{ activeProviderObj.badge }}
                        </span>
                      </div>
                    </div>
                    <ChevronDown class="w-4 h-4 text-slate-400 shrink-0 transition" :class="{'rotate-180': isProviderDropdownOpen}" />
                  </button>

                  <!-- Provider Dropdown Menu -->
                  <div
                    v-if="isProviderDropdownOpen"
                    class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-2xl shadow-xl z-50 p-2 space-y-1 max-h-80 overflow-y-auto"
                  >
                    <div class="px-2 py-1 text-[10px] font-bold tracking-wider text-emerald-800 uppercase flex items-center gap-1">
                      <Sparkles class="w-3 h-3 text-emerald-600" />
                      <span>Saved in /settings (BYO-AI)</span>
                    </div>
                    <div
                      v-for="prov in providersList"
                      :key="prov.id"
                      @click.stop="selectProvider(prov.id)"
                      class="p-2 rounded-xl cursor-pointer flex items-center justify-between text-left transition"
                      :class="selectedProvider === prov.id ? 'bg-emerald-50/90 border border-emerald-300' : 'hover:bg-slate-50 border border-transparent'"
                    >
                      <div class="pr-2 truncate">
                        <div class="text-xs font-bold text-slate-900 flex items-center gap-1.5 truncate">
                          <span class="truncate">{{ prov.name }}</span>
                          <span
                            class="text-[9px] font-semibold px-1.5 py-0.2 rounded shrink-0"
                            :class="prov.badge.includes('Active') ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'"
                          >
                            {{ prov.badge }}
                          </span>
                        </div>
                        <div class="text-[10px] text-slate-500 font-mono truncate">Model: {{ prov.defaultModel }} • {{ prov.costPerGen }}</div>
                      </div>
                      <Check v-if="selectedProvider === prov.id" class="w-4 h-4 text-emerald-600 shrink-0" />
                    </div>
                  </div>
                </div>

                <!-- Dropdown 2: BYO-AI Model -->
                <div class="relative">
                  <button
                    type="button"
                    @click.stop="toggleModelDropdown"
                    class="w-full px-3 py-2 rounded-2xl border border-slate-200 bg-slate-50 hover:bg-white text-left transition flex items-center justify-between shadow-2xs focus:outline-none focus:ring-1 focus:ring-emerald-400 cursor-pointer"
                  >
                    <div class="truncate mr-2">
                      <div class="text-[9px] text-slate-400 font-mono uppercase tracking-wider font-semibold">BYO-AI Model</div>
                      <div class="text-xs font-bold text-slate-900 truncate mt-0.5">
                        {{ activeModelObj.name }}
                      </div>
                    </div>
                    <ChevronDown class="w-4 h-4 text-slate-400 shrink-0 transition" :class="{'rotate-180': isModelDropdownOpen}" />
                  </button>

                  <!-- Model Dropdown Menu -->
                  <div
                    v-if="isModelDropdownOpen"
                    class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-2xl shadow-xl z-50 p-2 space-y-1 max-h-72 overflow-y-auto"
                  >
                    <div class="px-2 py-1 text-[10px] font-bold tracking-wider text-slate-400 uppercase">
                      Available {{ activeProviderObj.name }} Models
                    </div>
                    <div
                      v-for="model in activeProviderObj.models"
                      :key="model.id"
                      @click.stop="selectModel(model.id)"
                      class="p-2 rounded-xl cursor-pointer flex items-center justify-between text-left transition"
                      :class="selectedModel === model.id ? 'bg-emerald-50 border border-emerald-300' : 'hover:bg-slate-50 border border-transparent'"
                    >
                      <div class="pr-2 truncate">
                        <div class="text-xs font-bold text-slate-900 flex items-center gap-1.5 truncate">
                          <span class="truncate">{{ model.name }}</span>
                          <span v-if="model.id === activeProviderObj.defaultModel" class="text-[9px] font-semibold px-1 py-0.2 rounded bg-emerald-100 text-emerald-800 shrink-0">
                            Saved in /settings
                          </span>
                        </div>
                        <div class="text-[10px] text-slate-500 truncate">{{ model.tag }}</div>
                        <div class="text-[9px] font-mono text-emerald-600 font-semibold mt-0.5">{{ model.cost }}</div>
                      </div>
                      <Check v-if="selectedModel === model.id" class="w-4 h-4 text-emerald-600 shrink-0" />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Vault Status Pill -->
              <div
                v-if="hasSelectedProviderKey"
                class="flex items-center justify-between px-3 py-1.5 rounded-xl bg-emerald-50/80 border border-emerald-200/80 text-[11px] text-emerald-800 shadow-2xs"
              >
                <div class="flex items-center gap-1.5 truncate mr-2">
                  <span class="w-2 h-2 rounded-full bg-[#10B981] animate-pulse shrink-0"></span>
                  <span class="truncate">
                    Active BYO-AI: <strong>{{ activeProviderObj.name }}</strong> ({{ activeModelObj.name }}) · Saved in /settings
                  </span>
                </div>
                <span class="font-mono text-[10px] font-bold text-emerald-700 shrink-0">{{ activeModelObj.cost }}</span>
              </div>
              <div
                v-else
                class="flex items-center justify-between px-3 py-1.5 rounded-xl bg-amber-50 border border-amber-300 text-[11px] text-amber-900 shadow-2xs"
              >
                <div class="flex items-center gap-1.5 truncate mr-2">
                  <AlertCircle class="w-3.5 h-3.5 text-amber-600 shrink-0" />
                  <span class="truncate">
                    No API Key configured for <strong>{{ activeProviderObj.name }}</strong> in /settings.
                  </span>
                </div>
                <Link href="/settings" class="text-[10px] font-bold text-amber-900 underline shrink-0 hover:text-amber-950">
                  Add Key
                </Link>
              </div>
            </div>

            <!-- Visual Prompt Editor -->
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
              @click="canGenerateMedia ? generate() : null"
              :disabled="!canGenerateMedia || isGenerating || !prompt"
              :class="[
                'w-full py-2.5 rounded-full font-semibold text-xs shadow-sm flex items-center justify-center gap-2 transition',
                canGenerateMedia
                  ? 'bg-[#9EE493] hover:bg-[#8ee082] text-slate-900 cursor-pointer disabled:opacity-50'
                  : 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60'
              ]"
              :title="canGenerateMedia ? 'Synthesize asset' : 'Configure API Key for this provider in Settings to enable synthesis'"
            >
              <Lock v-if="!canGenerateMedia" class="w-4 h-4 text-slate-400" />
              <Sparkles v-else class="w-4 h-4 text-slate-900" :class="{'animate-spin': isGenerating}" />
              <span>
                {{
                  !hasGlobalAiKey
                    ? 'AI Synthesis Disabled (No API Key in Settings)'
                    : !hasSelectedProviderKey
                    ? `No API Key for ${activeProviderObj.name} (Add in Settings)`
                    : isGenerating
                    ? `Synthesizing with ${activeModelObj.name}...`
                    : `Generate Asset with ${activeProviderObj.name} (${activeModelObj.cost})`
                }}
              </span>
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
                    <span>{{ isVideo(asset) ? 'Open Video Editor' : 'Open Photo Editor' }}</span>
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
                    <span class="text-emerald-700 font-medium truncate" :title="asset.metadata?.provider || (asset.is_ai_generated ? 'AI Generated' : 'Uploaded')">
                      {{ asset.metadata?.provider || (asset.is_ai_generated ? 'AI Generated' : 'Uploaded') }}
                    </span>
                  </div>
                </div>

                <div class="flex items-center gap-1 shrink-0">
                  <button
                    @click="openEditor(asset)"
                    class="flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 transition"
                    :title="isVideo(asset) ? 'Edit in Video Studio' : 'Edit in Photo Editor'"
                  >
                    <Wand2 class="w-3.5 h-3.5 text-emerald-600" />
                    <span>{{ isVideo(asset) ? 'Edit Video' : 'Photo Editor' }}</span>
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
