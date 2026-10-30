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
            AES-256 encrypted credential storage, brand persona voice rules, and strict budget caps.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <span v-if="saveSuccess" class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-full flex items-center gap-1.5">
            <CheckCircle2 class="w-4 h-4 text-emerald-600" />
            Saved Successfully
          </span>

          <button
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
      <div class="flex gap-2 p-1.5 bg-white rounded-full border border-slate-200/80 w-fit shadow-sm">
        <button
          @click="activeTab = 'accounts'"
          :class="[
            'px-4 py-2 rounded-full text-xs font-semibold transition flex items-center gap-2',
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
            'px-4 py-2 rounded-full text-xs font-semibold transition flex items-center gap-2',
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
            'px-4 py-2 rounded-full text-xs font-semibold transition flex items-center gap-2',
            activeTab === 'budget'
              ? 'bg-slate-900 text-white shadow-sm'
              : 'text-slate-600 hover:text-slate-900'
          ]"
        >
          <Zap class="w-3.5 h-3.5" />
          Budget & Metered Guardrails
        </button>
      </div>

      <!-- Tab 1: Connected Accounts -->
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
          </div>
        </div>
      </div>

      <!-- Tab 2: Brand Voice Vault -->
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

      <!-- Tab 3: Budget & Metered Guardrails -->
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
