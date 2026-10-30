<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
  Inbox,
  Calendar,
  Flame,
  Image,
  BarChart3,
  Settings,
  Bell,
  Sparkles,
  Zap,
  LayoutDashboard,
} from 'lucide-vue-next';
import TokenCountdownBadge from '@/Components/TokenCountdownBadge.vue';

interface Props {
  title?: string;
}

defineProps<Props>();

const page = usePage();

const user = computed(() => (page.props as any).auth?.user);
const accounts = computed(() => (page.props as any).connectedAccounts || []);
const budget = computed(() => (page.props as any).budget || {});
const inboxCount = computed(() => (page.props as any).inboxCount || 0);

const spentUsd = computed(() => {
  const spent = budget.value.current_month_llm_spend_usd ?? 18.42;
  return Number(spent).toFixed(2);
});

const capUsd = computed(() => {
  const cap = budget.value.monthly_llm_cap_usd ?? 150.0;
  return Number(cap).toFixed(2);
});

const activePath = computed(() => page.url);
</script>

<template>
  <div class="min-h-screen bg-canvas text-gray-100 flex flex-col font-sans selection:bg-indigo-500 selection:text-white">
    <!-- Top Bar -->
    <header class="h-14 border-b border-white/10 bg-surface-elevated/80 backdrop-blur-md sticky top-0 z-50 flex items-center justify-between px-6">
      <Link href="/dashboard" class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-indigo-500 to-cyan-400 flex items-center justify-center shadow-lg shadow-indigo-500/20">
          <Sparkles class="w-4 h-4 text-white" />
        </div>
        <span class="font-bold text-sm tracking-wide bg-gradient-to-r from-white via-gray-200 to-gray-400 bg-clip-text text-transparent">
          Personal AI Social Assistant
        </span>
      </Link>

      <!-- Right Nav Status & Badges -->
      <div class="flex items-center gap-4">
        <!-- Connected accounts pills -->
        <div class="hidden md:flex items-center gap-2">
          <div
            v-for="acc in accounts"
            :key="acc.id"
            class="flex items-center gap-1.5 text-xs text-gray-400 bg-white/5 px-2.5 py-1 rounded-full border border-white/5"
          >
            <span
              :class="[
                'w-2 h-2 rounded-full',
                acc.provider === 'linkedin' ? 'bg-blue-500' : 'bg-slate-300'
              ]"
            ></span>
            <span class="font-medium text-gray-300">{{ acc.provider === 'linkedin' ? 'LinkedIn' : 'X (Twitter)' }}</span>
            <TokenCountdownBadge
              v-if="acc.days_remaining !== null"
              :days-remaining="acc.days_remaining"
              :platform="acc.provider"
            />
            <span v-else class="text-emerald-400 font-mono text-[11px]">Active</span>
          </div>

          <Link
            v-if="accounts.length === 0"
            href="/settings"
            class="text-xs text-indigo-400 hover:text-indigo-300 font-mono bg-indigo-500/10 px-2.5 py-1 rounded-full border border-indigo-500/20"
          >
            + Connect Accounts
          </Link>
        </div>

        <!-- Budget Gauge indicator -->
        <Link
          href="/settings"
          class="flex items-center gap-2 bg-indigo-500/10 border border-indigo-500/20 px-3 py-1 rounded-full text-xs font-mono text-indigo-300 hover:bg-indigo-500/20 transition"
        >
          <Zap class="w-3.5 h-3.5 text-indigo-400" />
          <span>${{ spentUsd }} / ${{ capUsd }}</span>
        </Link>

        <button class="p-1.5 rounded-lg hover:bg-white/5 text-gray-400 hover:text-white transition">
          <Bell class="w-4 h-4" />
        </button>

        <img
          :src="user?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=128&q=80'"
          :alt="user?.name || 'User'"
          class="w-7 h-7 rounded-full border border-white/20 object-cover"
        />
      </div>
    </header>

    <div class="flex-1 flex">
      <!-- Sidebar -->
      <aside class="w-56 border-r border-white/10 bg-surface-elevated/40 flex flex-col p-3 gap-1">
        <Link
          href="/dashboard"
          :class="[
            'flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium transition',
            activePath === '/' || activePath.startsWith('/dashboard')
              ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30'
              : 'text-gray-400 hover:bg-white/5 hover:text-white'
          ]"
        >
          <LayoutDashboard class="w-4 h-4 text-indigo-400" />
          <span>Dashboard</span>
        </Link>

        <Link
          href="/inbox"
          :class="[
            'flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium transition',
            activePath.startsWith('/inbox')
              ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30'
              : 'text-gray-400 hover:bg-white/5 hover:text-white'
          ]"
        >
          <div class="flex items-center gap-3">
            <Inbox class="w-4 h-4 text-blue-400" />
            <span>Review Inbox</span>
          </div>
          <span
            v-if="inboxCount > 0"
            class="px-1.5 py-0.5 text-[10px] font-mono rounded bg-indigo-500/30 text-indigo-200 border border-indigo-500/40"
          >
            {{ inboxCount }}
          </span>
        </Link>

        <Link
          href="/calendar"
          :class="[
            'flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium transition',
            activePath.startsWith('/calendar')
              ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30'
              : 'text-gray-400 hover:bg-white/5 hover:text-white'
          ]"
        >
          <Calendar class="w-4 h-4 text-emerald-400" />
          <span>Calendar & Queue</span>
        </Link>

        <Link
          href="/trends"
          :class="[
            'flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium transition',
            activePath.startsWith('/trends')
              ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30'
              : 'text-gray-400 hover:bg-white/5 hover:text-white'
          ]"
        >
          <Flame class="w-4 h-4 text-amber-400" />
          <span>Trend Feed</span>
        </Link>

        <Link
          href="/media"
          :class="[
            'flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium transition',
            activePath.startsWith('/media')
              ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30'
              : 'text-gray-400 hover:bg-white/5 hover:text-white'
          ]"
        >
          <Image class="w-4 h-4 text-cyan-400" />
          <span>Media Studio</span>
        </Link>

        <Link
          href="/analytics"
          :class="[
            'flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium transition',
            activePath.startsWith('/analytics')
              ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30'
              : 'text-gray-400 hover:bg-white/5 hover:text-white'
          ]"
        >
          <BarChart3 class="w-4 h-4 text-purple-400" />
          <span>Analytics</span>
        </Link>

        <div class="mt-auto border-t border-white/5 pt-2">
          <Link
            href="/settings"
            :class="[
              'flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium transition',
              activePath.startsWith('/settings')
                ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30'
                : 'text-gray-400 hover:bg-white/5 hover:text-white'
            ]"
          >
            <Settings class="w-4 h-4 text-gray-400" />
            <span>Settings & Vault</span>
          </Link>
        </div>
      </aside>

      <!-- Main Workspace View -->
      <main class="flex-1 p-8 overflow-y-auto">
        <slot />
      </main>
    </div>
  </div>
</template>
