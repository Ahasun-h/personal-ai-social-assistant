<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import {
  Lock,
  Mail,
  Eye,
  EyeOff,
  ArrowRight,
  ShieldCheck,
  Sparkles,
  Zap,
} from 'lucide-vue-next';

defineProps<{
  status?: string;
}>();

const showPassword = ref(false);

const form = useForm({
  email: 'alex@synthex.internal',
  password: 'password123',
  remember: true,
});

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
};

const fillDemo = (email = 'alex@synthex.internal', pass = 'password123') => {
  form.email = email;
  form.password = pass;
};
</script>

<template>
  <Head title="Sign In - Synthex AI Command" />

  <div class="min-h-screen bg-[#F4F6F8] flex flex-col justify-center items-center px-4 sm:px-6 lg:px-8 py-12 relative overflow-hidden font-sans selection:bg-[#9EE493] selection:text-slate-900">
    <!-- Subtle Background Glows -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-[#9EE493]/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-emerald-400/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10">
      <!-- Brand Clover Logo -->
      <div class="flex flex-col items-center mb-8 text-center">
        <div class="w-14 h-14 rounded-3xl bg-[#9EE493] flex items-center justify-center shadow-lg shadow-[#9EE493]/30 mb-3 group hover:scale-105 transition-transform">
          <div class="grid grid-cols-2 gap-1 p-1.5">
            <div class="w-3.5 h-3.5 rounded-full bg-slate-900"></div>
            <div class="w-3.5 h-3.5 rounded-full bg-slate-900"></div>
            <div class="w-3.5 h-3.5 rounded-full bg-slate-900"></div>
            <div class="w-3.5 h-3.5 rounded-full bg-slate-900"></div>
          </div>
        </div>
        <h2 class="text-2xl font-display font-bold text-slate-900 tracking-tight">
          Synthex AI Command
        </h2>
        <p class="text-xs text-slate-500 mt-1 max-w-sm">
          Autonomous executive social media curation, multi-model AI authoring, and dispatch queue.
        </p>
      </div>

      <!-- Login Card -->
      <div class="bg-white rounded-3xl p-7 sm:p-8 shadow-[0_10px_35px_rgba(0,0,0,0.04)] border border-slate-200/80">
        <div class="mb-6">
          <h3 class="text-lg font-bold text-slate-900">Sign in to your account</h3>
          <p class="text-xs text-slate-500 mt-0.5">Enter your executive workspace credentials</p>
        </div>

        <div v-if="status" class="mb-4 font-medium text-xs text-emerald-600 bg-emerald-50 p-3 rounded-xl border border-emerald-100">
          {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <!-- Email Field -->
          <div>
            <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">
              Email Address
            </label>
            <div class="relative rounded-2xl shadow-sm">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <Mail class="h-4 w-4 text-slate-400" />
              </div>
              <input
                id="email"
                v-model="form.email"
                type="email"
                autocomplete="email"
                required
                placeholder="name@synthex.internal"
                class="block w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#9EE493] focus:border-slate-300 transition"
              />
            </div>
            <p v-if="form.errors.email" class="mt-1 text-xs text-rose-500 font-medium">
              {{ form.errors.email }}
            </p>
          </div>

          <!-- Password Field -->
          <div>
            <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">
              Password
            </label>
            <div class="relative rounded-2xl shadow-sm">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <Lock class="h-4 w-4 text-slate-400" />
              </div>
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="current-password"
                required
                placeholder="••••••••••••"
                class="block w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#9EE493] focus:border-slate-300 transition"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
              >
                <EyeOff v-if="showPassword" class="h-4 w-4" />
                <Eye v-else class="h-4 w-4" />
              </button>
            </div>
            <p v-if="form.errors.password" class="mt-1 text-xs text-rose-500 font-medium">
              {{ form.errors.password }}
            </p>
          </div>

          <!-- Remember Me -->
          <div class="flex items-center justify-between pt-1">
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                v-model="form.remember"
                class="w-4 h-4 rounded text-[#10B981] border-slate-300 focus:ring-[#9EE493] focus:ring-offset-0"
              />
              <span class="text-xs text-slate-600">Remember me for 30 days</span>
            </label>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="form.processing"
            class="w-full mt-2 py-3 px-4 bg-[#9EE493] hover:bg-[#8ee082] text-slate-900 rounded-xl text-xs font-bold shadow-sm shadow-[#9EE493]/30 flex items-center justify-center gap-2 transition disabled:opacity-60"
          >
            <span v-if="form.processing" class="inline-block w-4 h-4 border-2 border-slate-900 border-t-transparent rounded-full animate-spin"></span>
            <span v-else>Sign In to Workspace</span>
            <ArrowRight v-if="!form.processing" class="w-4 h-4" />
          </button>
        </form>

        <!-- Quick Demo Credentials Banner -->
        <div class="mt-6 pt-5 border-t border-slate-100">
          <div class="bg-slate-50 rounded-2xl p-3.5 border border-slate-200/60">
            <div class="flex items-center justify-between mb-2">
              <span class="text-[11px] font-bold text-slate-700 flex items-center gap-1.5">
                <Sparkles class="w-3.5 h-3.5 text-amber-500" />
                Demo Credentials
              </span>
              <button
                type="button"
                @click="fillDemo()"
                class="text-[10px] font-semibold text-emerald-700 hover:text-emerald-800 bg-emerald-100/60 hover:bg-emerald-100 px-2 py-0.5 rounded-full transition"
              >
                1-Click Autofill
              </button>
            </div>
            <div class="font-mono text-[11px] text-slate-600 space-y-0.5">
              <div><span class="text-slate-400">Email:</span> alex@synthex.internal</div>
              <div><span class="text-slate-400">Password:</span> password123</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Security Notice -->
      <div class="flex items-center justify-center gap-2 mt-6 text-center text-[11px] text-slate-400">
        <ShieldCheck class="w-3.5 h-3.5 text-emerald-600" />
        <span>Protected with Sanctum Stateful Sessions & AES-256 Vault</span>
      </div>

      <!-- Link to Showcase Landing Page -->
      <div class="mt-4 text-center">
        <Link
          href="/"
          class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors"
        >
          <span>← Back to Showcase & Live Simulator</span>
        </Link>
      </div>
    </div>
  </div>
</template>

