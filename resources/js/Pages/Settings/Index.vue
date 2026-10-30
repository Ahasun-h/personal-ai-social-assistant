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
  X as CloseIcon,
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
}

const props = defineProps<Props>();

const activeTab = ref<'accounts' | 'brand' | 'budget'>('accounts');

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
      if (data.auth_url) {
        window.location.href = data.auth_url;
      }
    });
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
      router.reload();
    })
    .catch(() => {
      isConnecting.value = false;
    });
};
</script>

<template>
  <AppLayout title="Settings & Guardrails">
    <Head title="Settings & Guardrails" />

    <div class="p-2 max-w-7xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
            <Sliders class="w-6 h-6 text-indigo-400" />
            Settings & Operational Guardrails
          </h1>
          <p class="text-sm text-gray-400 mt-1">
            Configure social OAuth credentials, brand voice parameters, and strict API cost caps.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <button
            v-if="activeTab === 'accounts'"
            @click="showConnectModal = true"
            class="flex items-center gap-2 px-4 py-2 rounded-xl bg-white/10 hover:bg-white/15 text-white font-medium text-sm border border-white/10 transition"
          >
            <Plus class="w-4 h-4 text-indigo-400" />
            <span>Connect Account</span>
          </button>

          <button
            @click="saveSettings"
            :disabled="isSaving"
            class="flex items-center gap-2 px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-semibold text-sm text-white shadow-lg shadow-indigo-600/30 transition disabled:opacity-50"
          >
            <Save class="w-4 h-4" />
            <span>{{ isSaving ? 'Saving...' : 'Save Changes' }}</span>
          </button>
        </div>
      </div>

      <!-- Success Alert Banner -->
      <div
        v-if="saveSuccess"
        class="bg-emerald-500/10 border border-emerald-500/30 px-4 py-3 rounded-xl flex items-center gap-3 text-emerald-300 text-sm"
      >
        <CheckCircle2 class="w-5 h-5 text-emerald-400" />
        Settings updated successfully. All background workers have synchronized.
      </div>

      <!-- Navigation Tabs -->
      <div class="flex gap-2 border-b border-white/10 pb-4">
        <button
          @click="activeTab = 'accounts'"
          :class="[
            'px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2',
            activeTab === 'accounts'
              ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30'
              : 'text-gray-400 hover:text-white hover:bg-white/5'
          ]"
        >
          <Radio class="w-4 h-4" />
          Connected Accounts ({{ accounts.length }})
        </button>

        <button
          @click="activeTab = 'brand'"
          :class="[
            'px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2',
            activeTab === 'brand'
              ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30'
              : 'text-gray-400 hover:text-white hover:bg-white/5'
          ]"
        >
          <ShieldCheck class="w-4 h-4" />
          Brand Voice Vault
        </button>

        <button
          @click="activeTab = 'budget'"
          :class="[
            'px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2',
            activeTab === 'budget'
              ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30'
              : 'text-gray-400 hover:text-white hover:bg-white/5'
          ]"
        >
          <Zap class="w-4 h-4" />
          Budget & Metered Guardrails
        </button>
      </div>

      <!-- Tab 1: Connected Accounts -->
      <div v-if="activeTab === 'accounts'" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div
            v-for="acc in accounts"
            :key="acc.id"
            class="bg-surface-elevated/70 border border-white/10 rounded-2xl p-6 backdrop-blur-sm relative overflow-hidden space-y-4"
          >
            <div class="flex items-start justify-between">
              <div class="flex items-center gap-4">
                <img
                  :src="acc.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=128&q=80'"
                  class="w-12 h-12 rounded-xl object-cover border border-white/10"
                />
                <div>
                  <div class="flex items-center gap-2">
                    <h3 class="font-semibold text-white">{{ acc.name }}</h3>
                    <span
                      :class="[
                        'text-[10px] font-mono uppercase px-2 py-0.5 rounded-full font-bold',
                        acc.provider === 'linkedin' ? 'bg-blue-500/20 text-blue-400 border border-blue-500/30' : 'bg-slate-700 text-slate-300 border border-slate-600'
                      ]"
                    >
                      {{ acc.provider === 'linkedin' ? 'LinkedIn' : 'X (Twitter)' }}
                    </span>
                  </div>
                  <p class="text-xs text-gray-400 font-mono mt-0.5">@{{ acc.username }}</p>
                </div>
              </div>

              <div class="flex items-center gap-2">
                <span
                  v-if="acc.is_active"
                  class="flex items-center gap-1.5 text-xs font-mono font-medium px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                  Connected
                </span>
                <span
                  v-else
                  class="text-xs font-mono font-medium px-2.5 py-1 rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-400"
                >
                  Disconnected
                </span>
              </div>
            </div>

            <div class="pt-4 border-t border-white/5 flex items-center justify-between text-xs text-gray-400">
              <div class="flex items-center gap-1">
                <span>OAuth Token:</span>
                <span class="text-gray-200 font-mono">Encrypted (AES-256-GCM)</span>
              </div>

              <button
                @click="disconnectAccount(acc.id)"
                class="flex items-center gap-1 text-rose-400 hover:text-rose-300 transition"
              >
                <Trash2 class="w-3.5 h-3.5" />
                <span>Disconnect</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Connection Quick Action Cards -->
        <div class="border border-white/10 rounded-2xl p-6 bg-gradient-to-r from-indigo-950/20 to-blue-950/20 space-y-4">
          <h3 class="font-bold text-white text-sm">Add Social Media Channel</h3>
          <p class="text-xs text-gray-400">
            Connect your personal LinkedIn profile or X developer account. The application uses official OAuth 2.0 PKCE with automated token refresh.
          </p>
          <div class="flex flex-wrap gap-3 pt-2">
            <button
              @click="startOAuth('linkedin')"
              class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs transition shadow-lg shadow-blue-600/20"
            >
              <ExternalLink class="w-4 h-4" />
              <span>OAuth Connect LinkedIn</span>
            </button>

            <button
              @click="startOAuth('x_twitter')"
              class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs border border-white/10 transition"
            >
              <ExternalLink class="w-4 h-4" />
              <span>OAuth Connect X (Twitter)</span>
            </button>

            <button
              @click="showConnectModal = true"
              class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600/30 hover:bg-indigo-600/40 text-indigo-300 font-semibold text-xs border border-indigo-500/30 transition"
            >
              <Key class="w-4 h-4" />
              <span>Enter Developer Token Manually</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Tab 2: Brand Voice Vault -->
      <div v-if="activeTab === 'brand'" class="space-y-6">
        <div class="bg-surface-elevated/70 border border-white/10 rounded-2xl p-6 backdrop-blur-sm space-y-5">
          <div>
            <label class="block text-xs font-mono uppercase tracking-wider text-gray-400 mb-2">
              Primary Brand Persona & Tone
            </label>
            <input
              v-model="brandForm.tone"
              type="text"
              class="w-full bg-[#0B0F19] border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500 transition"
            />
          </div>

          <div>
            <label class="block text-xs font-mono uppercase tracking-wider text-gray-400 mb-2">
              Banned AI Buzzwords & Cliches (Comma-separated)
            </label>
            <textarea
              v-model="brandForm.bannedWords"
              rows="3"
              class="w-full bg-[#0B0F19] border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500 transition font-mono"
            ></textarea>
            <p class="text-[11px] text-gray-500 mt-1">
              AI text generation passes will automatically prune and rewrite any draft containing these words.
            </p>
          </div>

          <div>
            <label class="block text-xs font-mono uppercase tracking-wider text-gray-400 mb-2">
              Target Readership Audiences
            </label>
            <input
              v-model="brandForm.targetAudiences"
              type="text"
              class="w-full bg-[#0B0F19] border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500 transition"
            />
          </div>
        </div>
      </div>

      <!-- Tab 3: Budget & Metered Guardrails -->
      <div v-if="activeTab === 'budget'" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="bg-surface-elevated/70 border border-white/10 rounded-2xl p-6 backdrop-blur-sm space-y-4">
            <h3 class="font-semibold text-white flex items-center gap-2">
              <Zap class="w-4 h-4 text-amber-400" />
              LLM Inference Budget Caps
            </h3>

            <div class="space-y-3">
              <div>
                <label class="block text-xs text-gray-400 mb-1 font-mono">Daily Cap ($ USD)</label>
                <input
                  v-model.number="budgetForm.dailyLlmCap"
                  type="number"
                  step="0.5"
                  class="w-full bg-[#0B0F19] border border-white/10 rounded-xl px-3 py-2 text-sm text-white font-mono"
                />
              </div>

              <div>
                <label class="block text-xs text-gray-400 mb-1 font-mono">Monthly Cap ($ USD)</label>
                <input
                  v-model.number="budgetForm.monthlyLlmCap"
                  type="number"
                  step="5"
                  class="w-full bg-[#0B0F19] border border-white/10 rounded-xl px-3 py-2 text-sm text-white font-mono"
                />
              </div>
            </div>
          </div>

          <div class="bg-surface-elevated/70 border border-white/10 rounded-2xl p-6 backdrop-blur-sm space-y-4">
            <h3 class="font-semibold text-white flex items-center gap-2">
              <Key class="w-4 h-4 text-cyan-400" />
              Media & X Metered Caps
            </h3>

            <div class="space-y-3">
              <div>
                <label class="block text-xs text-gray-400 mb-1 font-mono">Daily Media Cap ($ USD)</label>
                <input
                  v-model.number="budgetForm.dailyMediaCap"
                  type="number"
                  step="0.5"
                  class="w-full bg-[#0B0F19] border border-white/10 rounded-xl px-3 py-2 text-sm text-white font-mono"
                />
              </div>

              <div>
                <label class="block text-xs text-gray-400 mb-1 font-mono">Daily X Dispatch Limit</label>
                <input
                  v-model.number="budgetForm.dailyXPosts"
                  type="number"
                  step="1"
                  class="w-full bg-[#0B0F19] border border-white/10 rounded-xl px-3 py-2 text-sm text-white font-mono"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Manual Connection Modal -->
      <div
        v-if="showConnectModal"
        class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4"
      >
        <div class="bg-surface-elevated border border-white/10 rounded-2xl p-6 max-w-lg w-full space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-white/10">
            <h3 class="font-bold text-white text-base">Connect Account (Manual Vault)</h3>
            <button @click="showConnectModal = false" class="text-gray-400 hover:text-white">
              <CloseIcon class="w-5 h-5" />
            </button>
          </div>

          <div class="space-y-3">
            <div>
              <label class="block text-xs text-gray-400 mb-1">Platform</label>
              <select
                v-model="newAccount.provider"
                class="w-full bg-[#0B0F19] border border-white/10 rounded-xl px-3 py-2 text-sm text-white"
              >
                <option value="linkedin">LinkedIn</option>
                <option value="x_twitter">X (Twitter)</option>
              </select>
            </div>

            <div>
              <label class="block text-xs text-gray-400 mb-1">Display Name</label>
              <input
                v-model="newAccount.name"
                placeholder="e.g. Alex Rivera"
                class="w-full bg-[#0B0F19] border border-white/10 rounded-xl px-3 py-2 text-sm text-white"
              />
            </div>

            <div>
              <label class="block text-xs text-gray-400 mb-1">Username / Handle</label>
              <input
                v-model="newAccount.username"
                placeholder="e.g. alexrivera_tech"
                class="w-full bg-[#0B0F19] border border-white/10 rounded-xl px-3 py-2 text-sm text-white font-mono"
              />
            </div>

            <div>
              <label class="block text-xs text-gray-400 mb-1">Access Token / Bearer Token</label>
              <textarea
                v-model="newAccount.access_token"
                rows="3"
                placeholder="Paste token string..."
                class="w-full bg-[#0B0F19] border border-white/10 rounded-xl px-3 py-2 text-sm text-white font-mono"
              ></textarea>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-3 border-t border-white/10">
            <button
              @click="showConnectModal = false"
              class="px-4 py-2 rounded-xl text-sm text-gray-400 hover:text-white"
            >
              Cancel
            </button>
            <button
              @click="saveManualAccount"
              :disabled="isConnecting"
              class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-semibold text-sm text-white shadow-lg shadow-indigo-600/30 transition disabled:opacity-50"
            >
              {{ isConnecting ? 'Saving...' : 'Save & Encrypt' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
