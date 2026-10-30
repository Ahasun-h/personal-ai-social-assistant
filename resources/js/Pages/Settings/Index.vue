<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  ShieldCheck,
  Zap,
  Sliders,
  Key,
  Radio,
  CheckCircle2,
  AlertTriangle,
  RefreshCw,
  Trash2,
  Plus,
  Save,
  ExternalLink,
  Cpu,
  Bot,
  Sparkles,
  Server,
  Play,
  Check,
  X as CloseIcon,
  Eye,
  EyeOff,
} from 'lucide-vue-next';

interface Account {
  id: string;
  provider: string;
  name: string;
  username: string;
  avatar_url?: string;
  token_expires_at?: string;
  is_active: boolean;
}

interface Category {
  id: string;
  name: string;
  slug: string;
  description: string;
  keywords?: string[];
  tone_guidelines?: string;
  is_active: boolean;
}

interface Props {
  accounts: Account[];
  categories: Category[];
  brandVoice?: Record<string, any>;
  budget?: Record<string, any>;
  aiConfig?: Record<string, any>;
}

const props = defineProps<Props>();

const activeTab = ref<'accounts' | 'ai' | 'brand' | 'budget'>('ai');

// AI Providers Configuration State
const activeProvider = ref<string>(props.aiConfig?.active_provider || 'gemini');

const aiProviders = ref<Record<string, any>>({
  gemini: {
    label: 'Google Gemini',
    badge: 'Official Google GenAI',
    api_key: props.aiConfig?.providers?.gemini?.api_key || '""',
    model: props.aiConfig?.providers?.gemini?.model || 'gemini-3.5-flash',
    models: ['gemini-3.5-flash', 'gemini-3.5-flash-lite', 'gemini-3.8-flash', 'gemini-2.5-pro'],
    description: 'High-speed multi-modal reasoning with expansive context windows.',
  },
  openai: {
    label: 'OpenAI',
    badge: 'GPT-4o & Reasoning',
    api_key: props.aiConfig?.providers?.openai?.api_key || '',
    model: props.aiConfig?.providers?.openai?.model || 'gpt-4o',
    models: ['gpt-4o', 'gpt-4o-mini', 'o1-preview', 'o1-mini', 'gpt-4-turbo'],
    description: 'Industry benchmark for nuance, structured JSON generation, and tone accuracy.',
  },
  claude: {
    label: 'Anthropic Claude',
    badge: 'Claude 3.5 Sonnet',
    api_key: props.aiConfig?.providers?.claude?.api_key || '',
    model: props.aiConfig?.providers?.claude?.model || 'claude-3-5-sonnet-20241022',
    models: ['claude-3-5-sonnet-20241022', 'claude-3-5-haiku-20241022', 'claude-3-opus-20240229'],
    description: 'Best-in-class natural prose, contrarian thought-leadership, and minimal fluff.',
  },
  z_ai: {
    label: 'Z AI',
    badge: 'High-Throughput Enterprise',
    api_key: props.aiConfig?.providers?.z_ai?.api_key || '',
    base_url: props.aiConfig?.providers?.z_ai?.base_url || 'https://api.z-ai.io/v1',
    model: props.aiConfig?.providers?.z_ai?.model || 'z-ai-large',
    models: ['z-ai-large', 'z-ai-pro', 'z-ai-coder'],
    description: 'Fast, dedicated inference endpoint powered by Z AI infrastructure.',
  },
  custom: {
    label: 'Custom OpenAI-Compatible',
    badge: 'Groq / DeepSeek / Ollama / LM Studio',
    api_key: props.aiConfig?.providers?.custom?.api_key || '',
    base_url: props.aiConfig?.providers?.custom?.base_url || 'http://localhost:11434/v1',
    model: props.aiConfig?.providers?.custom?.model || 'llama3.2',
    models: ['llama3.2', 'deepseek-chat', 'llama-3.1-70b', 'mixtral-8x7b'],
    description: 'Connect ANY OpenAI-compatible API, self-hosted Ollama server, or custom gateway.',
  },
});

const showKeys = ref<Record<string, boolean>>({
  gemini: false,
  openai: false,
  claude: false,
  z_ai: false,
  custom: false,
});

const isTesting = ref(false);
const testResult = ref<any>(null);

const testAiConnection = async (providerName: string) => {
  isTesting.value = true;
  testResult.value = null;
  try {
    const creds = aiProviders.value[providerName];
    const res = await fetch('/api/v1/ai/test', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        provider: providerName,
        credentials: {
          api_key: creds.api_key,
          base_url: creds.base_url,
          model: creds.model,
        },
      }),
    });
    const text = await res.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch {
      data = {
        success: false,
        error: `Server responded with HTTP ${res.status}: Check that your API key and target model are valid.`,
      };
    }
    testResult.value = data;
  } catch (err: any) {
    testResult.value = { success: false, error: err.message };
  } finally {
    isTesting.value = false;
  }
};

const saveAiConfig = async () => {
  isSaving.value = true;
  try {
    await fetch('/api/v1/ai/config', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        active_provider: activeProvider.value,
        providers: aiProviders.value,
      }),
    });
    saveSuccess.value = true;
    setTimeout(() => {
      saveSuccess.value = false;
    }, 3000);
  } finally {
    isSaving.value = false;
  }
};

// Brand & Budget Form States
const brandForm = ref({
  tone: props.brandVoice?.tone || 'Architectural, pragmatic, data-grounded, zero-hype',
  bannedWords: props.brandVoice?.banned_buzzwords?.join(', ') || 'revolutionary, game-changer, supercharge, paradigm-shift, delve',
  targetAudiences: props.brandVoice?.target_audiences?.join(', ') || 'Staff Engineers, Founding Engineers, CTOs, Tech Leads',
});

const budgetForm = ref({
  dailyLlmCap: props.budget?.daily_llm_cap_usd || 10.0,
  monthlyLlmCap: props.budget?.monthly_llm_cap_usd || 150.0,
  dailyMediaCap: props.budget?.daily_media_cap_usd || 15.0,
  monthlyMediaCap: props.budget?.monthly_media_cap_usd || 200.0,
  dailyXPosts: props.budget?.daily_x_post_limit || 50,
});

const isSaving = ref(false);
const saveSuccess = ref(false);
const showConnectModal = ref(false);

const newAccount = ref({
  provider: 'linkedin',
  name: '',
  username: '',
  access_token: '',
  refresh_token: '',
});

const isConnecting = ref(false);

const saveSettings = () => {
  isSaving.value = true;
  fetch('/api/v1/settings', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      brand_voice: {
        tone: brandForm.value.tone,
        banned_buzzwords: brandForm.value.bannedWords.split(',').map((w: string) => w.trim()),
        target_audiences: brandForm.value.targetAudiences.split(',').map((w: string) => w.trim()),
      },
      budget_guardrails: {
        daily_llm_cap_usd: budgetForm.value.dailyLlmCap,
        monthly_llm_cap_usd: budgetForm.value.monthlyLlmCap,
        daily_media_cap_usd: budgetForm.value.dailyMediaCap,
        monthly_media_cap_usd: budgetForm.value.monthlyMediaCap,
        daily_x_post_limit: budgetForm.value.dailyXPosts,
      },
    }),
  }).then(() => {
    isSaving.value = false;
    saveSuccess.value = true;
    setTimeout(() => {
      saveSuccess.value = false;
    }, 3000);
  });
};

const disconnectAccount = (id: string) => {
  if (confirm('Disconnect this account? Stored tokens will be permanently revoked.')) {
    fetch(`/api/v1/accounts/${id}/disconnect`, { method: 'DELETE' }).then(() => {
      router.reload();
    });
  }
};

const startOAuth = (provider: 'linkedin' | 'x_twitter') => {
  fetch(`/api/v1/accounts/oauth/${provider}/redirect`)
    .then((r) => r.json())
    .then((data) => {
      const url = data.auth_url || data.data?.authorization_url || data.data?.auth_url;
      if (url) {
        window.location.href = url;
      }
    });
};

const clearDemo = async () => {
  if (confirm('Clear demo accounts? Only your real connected accounts will be shown.')) {
    await fetch('/api/v1/accounts/clear-demo', { method: 'POST' });
    router.reload();
  }
};

const saveManualAccount = () => {
  if (!newAccount.value.name || !newAccount.value.username || !newAccount.value.access_token) {
    alert('Please fill in Name, Username, and Access Token.');
    return;
  }
  isConnecting.value = true;
  fetch('/api/v1/accounts', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(newAccount.value),
  })
    .then((r) => r.json())
    .then(() => {
      isConnecting.value = false;
      showConnectModal.value = false;
      newAccount.value = {
        provider: 'linkedin',
        name: '',
        username: '',
        access_token: '',
        refresh_token: '',
      };
      router.reload();
    })
    .catch((err) => {
      isConnecting.value = false;
      alert('Error connecting account: ' + err.message);
    });
};
</script>

<template>
  <AppLayout title="Settings Vault">
    <Head title="Settings & Account Vault" />

    <div class="space-y-6">
      <!-- Title & Save Action Bar -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-2">
        <div>
          <h1 class="text-xl sm:text-2xl font-display font-bold text-slate-900 tracking-tight flex items-center gap-2">
            <ShieldCheck class="w-5 h-5 text-emerald-600" />
            Security Vault & Configuration
          </h1>
          <p class="text-xs text-slate-500 mt-1">
            AES-256 encrypted credential storage, BYO-AI model configurations, brand persona rules, and strict budget caps.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <span v-if="saveSuccess" class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-full flex items-center gap-1.5">
            <CheckCircle2 class="w-4 h-4 text-emerald-600" />
            Saved Successfully
          </span>

          <button
            v-if="activeTab === 'ai'"
            @click="saveAiConfig"
            :disabled="isSaving"
            class="flex items-center gap-2 px-5 py-2 rounded-full bg-[#9EE493] hover:bg-[#8ee082] text-slate-900 font-semibold text-xs shadow-sm transition disabled:opacity-50"
          >
            <Save class="w-3.5 h-3.5" />
            <span>{{ isSaving ? 'Saving...' : 'Save AI Credentials' }}</span>
          </button>

          <button
            v-else
            @click="saveSettings"
            :disabled="isSaving"
            class="flex items-center gap-2 px-5 py-2 rounded-full bg-[#9EE493] hover:bg-[#8ee082] text-slate-900 font-semibold text-xs shadow-sm transition disabled:opacity-50"
          >
            <Save class="w-3.5 h-3.5" />
            <span>{{ isSaving ? 'Saving...' : 'Save Vault Settings' }}</span>
          </button>
        </div>
      </div>

      <!-- Navigation Tabs Pill Bar -->
      <div class="flex gap-2 p-1.5 bg-white rounded-full border border-slate-200/80 w-fit shadow-sm overflow-x-auto">
        <button
          @click="activeTab = 'ai'"
          :class="[
            'px-4 py-2 rounded-full text-xs font-semibold transition flex items-center gap-2 whitespace-nowrap',
            activeTab === 'ai'
              ? 'bg-slate-900 text-white shadow-sm'
              : 'text-slate-600 hover:text-slate-900'
          ]"
        >
          <Cpu class="w-3.5 h-3.5 text-[#9EE493]" />
          AI Models & Providers
        </button>

        <button
          @click="activeTab = 'accounts'"
          :class="[
            'px-4 py-2 rounded-full text-xs font-semibold transition flex items-center gap-2 whitespace-nowrap',
            activeTab === 'accounts'
              ? 'bg-slate-900 text-white shadow-sm'
              : 'text-slate-600 hover:text-slate-900'
          ]"
        >
          <Radio class="w-3.5 h-3.5" />
          Connected Accounts
        </button>

        <button
          @click="activeTab = 'brand'"
          :class="[
            'px-4 py-2 rounded-full text-xs font-semibold transition flex items-center gap-2 whitespace-nowrap',
            activeTab === 'brand'
              ? 'bg-slate-900 text-white shadow-sm'
              : 'text-slate-600 hover:text-slate-900'
          ]"
        >
          <ShieldCheck class="w-3.5 h-3.5" />
          Brand Voice Vault
        </button>

        <button
          @click="activeTab = 'budget'"
          :class="[
            'px-4 py-2 rounded-full text-xs font-semibold transition flex items-center gap-2 whitespace-nowrap',
            activeTab === 'budget'
              ? 'bg-slate-900 text-white shadow-sm'
              : 'text-slate-600 hover:text-slate-900'
          ]"
        >
          <Zap class="w-3.5 h-3.5" />
          Budget & Metered Guardrails
        </button>
      </div>

      <!-- Tab: AI Models & Providers (BYO-AI) -->
      <div v-if="activeTab === 'ai'" class="space-y-6">
        <!-- Overview Header Card -->
        <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <h2 class="font-display font-bold text-base text-slate-900 flex items-center gap-2">
                <Sparkles class="w-4 h-4 text-emerald-600" />
                Bring Your Own AI Model (BYO-AI)
              </h2>
              <p class="text-xs text-slate-500 mt-1">
                Select your preferred LLM provider or connect a custom OpenAI-compatible server. All API keys are encrypted at rest with AES-256-GCM.
              </p>
            </div>

            <!-- Active Provider Indicator Pill -->
            <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 px-3.5 py-1.5 rounded-full shrink-0">
              <span class="w-2 h-2 rounded-full bg-[#10B981] animate-pulse"></span>
              <span class="text-xs font-semibold text-emerald-800">
                Active: {{ aiProviders[activeProvider]?.label }} ({{ aiProviders[activeProvider]?.model }})
              </span>
            </div>
          </div>

          <!-- Provider Selector Cards (5 Cards) -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 pt-2">
            <div
              v-for="(prov, key) in aiProviders"
              :key="key"
              @click="activeProvider = key"
              :class="[
                'p-4 rounded-2xl border transition cursor-pointer relative flex flex-col justify-between space-y-3',
                activeProvider === key
                  ? 'bg-slate-900 text-white border-slate-900 shadow-md ring-2 ring-emerald-400/40'
                  : 'bg-slate-50/70 border-slate-200 hover:bg-slate-100 text-slate-800'
              ]"
            >
              <div>
                <div class="flex items-center justify-between">
                  <span class="text-[10px] font-mono uppercase font-bold px-2 py-0.5 rounded-md" :class="activeProvider === key ? 'bg-white/10 text-[#9EE493]' : 'bg-slate-200 text-slate-700'">
                    {{ key.toUpperCase() }}
                  </span>
                  <Check v-if="activeProvider === key" class="w-4 h-4 text-[#9EE493]" />
                </div>
                <h3 class="font-display font-bold text-sm mt-2 leading-tight">{{ prov.label }}</h3>
                <p class="text-[11px] mt-1 line-clamp-2" :class="activeProvider === key ? 'text-white/70' : 'text-slate-500'">
                  {{ prov.description }}
                </p>
              </div>

              <div class="pt-2 border-t text-[10px] font-mono truncate" :class="activeProvider === key ? 'border-white/10 text-[#9EE493]' : 'border-slate-200 text-slate-500'">
                Model: {{ prov.model }}
              </div>
            </div>
          </div>
        </div>

        <!-- Active Provider Configuration Details -->
        <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-5">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
              <h3 class="font-display font-bold text-base text-slate-900 flex items-center gap-2">
                <Bot class="w-4 h-4 text-emerald-600" />
                Configure {{ aiProviders[activeProvider]?.label }}
              </h3>
              <p class="text-xs text-slate-500 mt-0.5">
                Set credentials and preferred generation model for {{ aiProviders[activeProvider]?.label }}.
              </p>
            </div>

            <!-- Test Connection Button -->
            <button
              @click="testAiConnection(activeProvider)"
              :disabled="isTesting"
              class="flex items-center gap-1.5 px-4 py-2 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold text-xs shadow-xs transition disabled:opacity-50"
            >
              <Play class="w-3 h-3 text-emerald-600" />
              <span>{{ isTesting ? 'Testing Latency...' : 'Test Connection' }}</span>
            </button>
          </div>

          <!-- Test Output Alert Banner -->
          <div
            v-if="testResult"
            :class="[
              'p-4 rounded-2xl border text-xs space-y-1',
              testResult.success ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-rose-50 border-rose-200 text-rose-900'
            ]"
          >
            <div class="flex items-center justify-between font-bold">
              <span class="flex items-center gap-1.5">
                <CheckCircle2 v-if="testResult.success" class="w-4 h-4 text-emerald-600" />
                <AlertTriangle v-else class="w-4 h-4 text-rose-600" />
                <span>{{ testResult.success ? 'Connection Verified Successfully' : 'Connection Failed' }}</span>
              </span>
              <span class="font-mono text-[11px]">{{ testResult.latency_ms }}ms latency</span>
            </div>
            <p v-if="testResult.sample_output" class="text-[11px] font-sans opacity-90 pt-1 italic">
              "{{ testResult.sample_output }}"
            </p>
            <p v-if="testResult.error" class="text-[11px] font-mono text-rose-700 pt-1">
              {{ testResult.error }}
            </p>
          </div>

          <!-- Inputs -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-xs">
            <!-- API Key -->
            <div class="space-y-1.5">
              <div class="flex items-center justify-between">
                <label class="font-semibold text-slate-700">API Key / Access Token</label>
                <button
                  type="button"
                  @click="showKeys[activeProvider] = !showKeys[activeProvider]"
                  class="text-[11px] text-slate-400 hover:text-slate-600 flex items-center gap-1"
                >
                  <component :is="showKeys[activeProvider] ? EyeOff : Eye" class="w-3 h-3" />
                  <span>{{ showKeys[activeProvider] ? 'Hide' : 'Show' }}</span>
                </button>
              </div>
              <input
                v-model="aiProviders[activeProvider].api_key"
                :type="showKeys[activeProvider] ? 'text' : 'password'"
                placeholder="sk-... or API Token"
                class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-slate-400 font-mono"
              />
            </div>

            <!-- Model Selection -->
            <div class="space-y-1.5">
              <label class="font-semibold text-slate-700">Target Model</label>
              <div class="flex gap-2">
                <select
                  v-model="aiProviders[activeProvider].model"
                  class="bg-slate-50 border border-slate-200 rounded-2xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-slate-400 flex-1 font-mono"
                >
                  <option v-for="m in aiProviders[activeProvider].models" :key="m" :value="m">
                    {{ m }}
                  </option>
                </select>
                <input
                  v-model="aiProviders[activeProvider].model"
                  type="text"
                  placeholder="Custom model id"
                  class="bg-slate-50 border border-slate-200 rounded-2xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-slate-400 w-36 font-mono"
                />
              </div>
            </div>

            <!-- Base URL (For Z AI and Custom) -->
            <div v-if="activeProvider === 'z_ai' || activeProvider === 'custom'" class="md:col-span-2 space-y-1.5">
              <label class="font-semibold text-slate-700">Endpoint Base URL</label>
              <input
                v-model="aiProviders[activeProvider].base_url"
                type="text"
                placeholder="https://api.z-ai.io/v1 or http://localhost:11434/v1"
                class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-slate-400 font-mono"
              />
              <p class="text-[11px] text-slate-400">
                Compatible with any OpenAI-compliant API format (Groq, DeepSeek, Together AI, Mistral, Ollama, vLLM).
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab: Connected Accounts -->
      <div v-if="activeTab === 'accounts'" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div
            v-for="acc in accounts"
            :key="acc.id"
            class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-[0_4px_20px_rgba(0,0,0,0.03)] relative overflow-hidden space-y-4"
          >
            <div class="flex items-start justify-between">
              <div class="flex items-center gap-4">
                <img
                  :src="acc.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=128&q=80'"
                  class="w-12 h-12 rounded-2xl object-cover border border-slate-200"
                />
                <div>
                  <div class="flex items-center gap-2">
                    <h3 class="font-bold text-slate-900">{{ acc.name }}</h3>
                    <span
                      :class="[
                        'text-[10px] font-mono uppercase px-2.5 py-0.5 rounded-full font-bold',
                        acc.provider === 'linkedin' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-slate-100 text-slate-800 border border-slate-200'
                      ]"
                    >
                      {{ acc.provider === 'linkedin' ? 'LinkedIn' : 'X (Twitter)' }}
                    </span>
                  </div>
                  <p class="text-xs text-slate-400 font-mono mt-0.5">@{{ acc.username }}</p>
                </div>
              </div>

              <div class="flex items-center gap-2">
                <span
                  v-if="acc.is_active"
                  class="flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800"
                >
                  <span class="w-2 h-2 rounded-full bg-[#10B981]"></span>
                  Connected
                </span>
                <span
                  v-else
                  class="text-xs font-semibold px-3 py-1 rounded-full bg-rose-50 border border-rose-200 text-rose-700"
                >
                  Disconnected
                </span>
              </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
              <div class="flex items-center gap-1">
                <span>OAuth Token:</span>
                <span class="text-slate-900 font-semibold font-mono">Encrypted (AES-256-GCM)</span>
              </div>

              <button
                @click="disconnectAccount(acc.id)"
                class="flex items-center gap-1 text-rose-600 hover:text-rose-700 font-medium transition"
              >
                <Trash2 class="w-3.5 h-3.5" />
                <span>Disconnect</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Connection Quick Action Cards -->
        <div class="border border-slate-200/80 rounded-3xl p-6 bg-white shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-4">
          <h3 class="font-display font-bold text-slate-900 text-sm">Add Social Media Channel</h3>
          <p class="text-xs text-slate-500">
            Connect your personal LinkedIn profile or X developer account. The application uses official OAuth 2.0 PKCE with automated token refresh.
          </p>
          <div class="flex flex-wrap gap-3 pt-2">
            <button
              @click="startOAuth('linkedin')"
              class="flex items-center gap-2 px-5 py-2.5 rounded-full bg-[#0A66C2] hover:bg-[#095196] text-white font-semibold text-xs transition shadow-sm"
            >
              <ExternalLink class="w-4 h-4" />
              <span>OAuth Connect LinkedIn</span>
            </button>

            <button
              @click="startOAuth('x_twitter')"
              class="flex items-center gap-2 px-5 py-2.5 rounded-full bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs transition shadow-sm"
            >
              <ExternalLink class="w-4 h-4" />
              <span>OAuth Connect X (Twitter)</span>
            </button>

            <button
              @click="showConnectModal = true"
              class="flex items-center gap-2 px-5 py-2.5 rounded-full bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs border border-slate-200/80 shadow-sm transition"
            >
              <Key class="w-4 h-4 text-emerald-600" />
              <span>Enter Developer Token Manually</span>
            </button>

            <button
              @click="clearDemo"
              class="flex items-center gap-2 px-5 py-2.5 rounded-full bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold text-xs border border-rose-200 shadow-sm transition ml-auto"
              title="Remove pre-seeded sample accounts so you can connect your own"
            >
              <Trash2 class="w-4 h-4 text-rose-600" />
              <span>Clear Sample Demo Accounts</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Tab: Brand Voice Vault -->
      <div v-if="activeTab === 'brand'" class="space-y-6">
        <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-5">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-2">
              Primary Brand Persona & Tone
            </label>
            <input
              v-model="brandForm.tone"
              type="text"
              class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-slate-400 transition"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-2">
              Banned AI Buzzwords & Cliches (Comma-separated)
            </label>
            <textarea
              v-model="brandForm.bannedWords"
              rows="3"
              class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-slate-400 transition font-mono"
            ></textarea>
            <p class="text-[11px] text-slate-400 mt-1">
              AI text generation passes will automatically prune and rewrite any draft containing these words.
            </p>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-2">
              Target Readership Audiences
            </label>
            <input
              v-model="brandForm.targetAudiences"
              type="text"
              class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-slate-400 transition"
            />
          </div>
        </div>
      </div>

      <!-- Tab: Budget & Metered Guardrails -->
      <div v-if="activeTab === 'budget'" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-4">
            <h3 class="font-display font-bold text-slate-900 flex items-center gap-2">
              <Zap class="w-4 h-4 text-amber-500" />
              LLM Inference Budget Caps
            </h3>

            <div class="space-y-3">
              <div>
                <label class="block text-xs text-slate-500 mb-1 font-semibold">Daily Cap ($ USD)</label>
                <input
                  v-model.number="budgetForm.dailyLlmCap"
                  type="number"
                  step="0.5"
                  class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-3.5 py-2 text-xs text-slate-900 font-mono"
                />
              </div>

              <div>
                <label class="block text-xs text-slate-500 mb-1 font-semibold">Monthly Cap ($ USD)</label>
                <input
                  v-model.number="budgetForm.monthlyLlmCap"
                  type="number"
                  step="5"
                  class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-3.5 py-2 text-xs text-slate-900 font-mono"
                />
              </div>
            </div>
          </div>

          <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-4">
            <h3 class="font-display font-bold text-slate-900 flex items-center gap-2">
              <Key class="w-4 h-4 text-emerald-600" />
              Media & X Metered Caps
            </h3>

            <div class="space-y-3">
              <div>
                <label class="block text-xs text-slate-500 mb-1 font-semibold">Daily Media Cap ($ USD)</label>
                <input
                  v-model.number="budgetForm.dailyMediaCap"
                  type="number"
                  step="0.5"
                  class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-3.5 py-2 text-xs text-slate-900 font-mono"
                />
              </div>

              <div>
                <label class="block text-xs text-slate-500 mb-1 font-semibold">Daily X Dispatch Limit</label>
                <input
                  v-model.number="budgetForm.dailyXPosts"
                  type="number"
                  step="1"
                  class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-3.5 py-2 text-xs text-slate-900 font-mono"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Manual Connection Modal -->
      <div
        v-if="showConnectModal"
        class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4"
      >
        <div class="bg-white border border-slate-200 rounded-3xl p-6 max-w-lg w-full space-y-4 shadow-2xl">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="font-display font-bold text-slate-900 text-base">Connect Account (Manual Vault)</h3>
            <button @click="showConnectModal = false" class="text-slate-400 hover:text-slate-600">
              <CloseIcon class="w-5 h-5" />
            </button>
          </div>

          <form @submit.prevent="saveManualAccount" class="space-y-4 text-xs">
            <div>
              <label class="block text-slate-600 font-semibold mb-1">Platform</label>
              <select
                v-model="newAccount.provider"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800"
              >
                <option value="linkedin">LinkedIn</option>
                <option value="x_twitter">X (Twitter)</option>
              </select>
            </div>

            <div>
              <label class="block text-slate-600 font-semibold mb-1">Account Owner Display Name</label>
              <input
                v-model="newAccount.name"
                type="text"
                placeholder="e.g. Alex Rivera"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800"
                required
              />
            </div>

            <div>
              <label class="block text-slate-600 font-semibold mb-1">Handle / Username</label>
              <input
                v-model="newAccount.username"
                type="text"
                placeholder="e.g. alexrivera_tech"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 font-mono"
                required
              />
            </div>

            <div>
              <label class="block text-slate-600 font-semibold mb-1">OAuth Access Token</label>
              <input
                v-model="newAccount.access_token"
                type="password"
                placeholder="Bearer AQ..."
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 font-mono"
                required
              />
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
              <button
                type="button"
                @click="showConnectModal = false"
                class="px-4 py-2 rounded-full text-slate-600 hover:bg-slate-100 font-semibold"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="isConnecting"
                class="px-5 py-2 rounded-full bg-[#9EE493] hover:bg-[#8ee082] text-slate-900 font-semibold shadow-sm transition disabled:opacity-50"
              >
                {{ isConnecting ? 'Saving...' : 'Save & Encrypt' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
