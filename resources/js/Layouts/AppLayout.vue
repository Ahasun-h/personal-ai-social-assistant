<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
  Inbox,
  Calendar,
  Flame,
  Image as ImageIcon,
  BarChart3,
  Settings,
  Bell,
  Sparkles,
  Search,
  Zap,
  CheckCircle2,
  SlidersHorizontal,
} from 'lucide-vue-next';

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

const navItems = computed(() => [
  { name: 'Dashboard', href: '/dashboard', active: activePath.value === '/' || activePath.value.startsWith('/dashboard') },
  { name: 'Review Inbox', href: '/inbox', active: activePath.value.startsWith('/inbox'), count: inboxCount.value },
  { name: 'Calendar', href: '/calendar', active: activePath.value.startsWith('/calendar') },
  { name: 'Trend Intel', href: '/trends', active: activePath.value.startsWith('/trends') },
  { name: 'Media Studio', href: '/media', active: activePath.value.startsWith('/media') },
  { name: 'Analytics', href: '/analytics', active: activePath.value.startsWith('/analytics') },
  { name: 'Settings Vault', href: '/settings', active: activePath.value.startsWith('/settings') },
]);

const searchQuery = ref('');
</script>

<template>
  <div class="min-h-screen bg-[#F4F6F8] text-slate-800 flex flex-col font-sans selection:bg-[#9EE493] selection:text-slate-900">
    <!-- Top Navigation Bar (Inspired by Lordbank & Botrix Header) -->
    <header class="sticky top-0 z-50 bg-[#F4F6F8]/90 backdrop-blur-md pt-3 pb-2 px-6">
      <div class="max-w-[1600px] mx-auto flex items-center justify-between gap-4">
        
        <!-- Left: Brand Clover Logo Pill -->
        <Link href="/dashboard" class="flex items-center gap-2.5 shrink-0 group">
          <div class="w-10 h-10 rounded-2xl bg-[#9EE493] flex items-center justify-center shadow-sm shadow-[#9EE493]/30 transition group-hover:scale-105">
            <!-- 4-leaf clover / spark brand icon matching reference image 1 -->
            <div class="grid grid-cols-2 gap-0.5 p-1">
              <div class="w-2.5 h-2.5 rounded-full bg-slate-900"></div>
              <div class="w-2.5 h-2.5 rounded-full bg-slate-900"></div>
              <div class="w-2.5 h-2.5 rounded-full bg-slate-900"></div>
              <div class="w-2.5 h-2.5 rounded-full bg-slate-900"></div>
            </div>
          </div>
          <div class="hidden sm:block">
            <span class="font-display font-bold text-base tracking-tight text-slate-900 block leading-tight">
              Synthex
            </span>
            <span class="text-[10px] text-slate-400 font-medium block -mt-0.5 tracking-wider uppercase">
              AI Command
            </span>
          </div>
        </Link>

        <!-- Center: Floating Pill Navigation (Matching Image 1 & 2) -->
        <nav class="hidden md:flex items-center gap-1 bg-white/80 p-1.5 rounded-full border border-slate-200/80 shadow-[0_2px_12px_rgba(0,0,0,0.03)] backdrop-blur-sm">
          <Link
            v-for="item in navItems"
            :key="item.name"
            :href="item.href"
            :class="[
              'px-4 py-2 rounded-full text-xs font-medium transition-all duration-150 flex items-center gap-2 whitespace-nowrap',
              item.active
                ? 'bg-[#9EE493] text-slate-900 font-semibold shadow-sm'
                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70'
            ]"
          >
            <span>{{ item.name }}</span>
            <span
              v-if="item.count && item.count > 0"
              class="px-1.5 py-0.2 text-[10px] rounded-full bg-slate-900 text-white font-mono"
            >
              {{ item.count }}
            </span>
          </Link>
        </nav>

        <!-- Right: Utility Controls (Search, Budget, Notification, Profile) -->
        <div class="flex items-center gap-3 shrink-0">
          <!-- Search Pill (Matching Image 1) -->
          <div class="hidden lg:flex items-center gap-2 bg-white px-3.5 py-2 rounded-full border border-slate-200/80 shadow-sm text-xs text-slate-400 focus-within:border-slate-400 focus-within:text-slate-600 transition">
            <Search class="w-3.5 h-3.5 text-slate-400" />
            <input
              type="text"
              v-model="searchQuery"
              placeholder="Search posts, trends..."
              class="bg-transparent border-none p-0 text-xs text-slate-800 placeholder-slate-400 focus:outline-none w-32 focus:w-44 transition-all"
            />
          </div>

          <!-- Connected Accounts Pills (LinkedIn & X) -->
          <div class="hidden xl:flex items-center gap-1.5">
            <div
              v-for="acc in accounts"
              :key="acc.id"
              class="flex items-center gap-1.5 text-[11px] font-medium bg-white px-2.5 py-1.5 rounded-full border border-slate-200/80 shadow-sm text-slate-600"
            >
              <span
                :class="[
                  'w-2 h-2 rounded-full',
                  acc.provider === 'linkedin' ? 'bg-[#0A66C2]' : 'bg-slate-900'
                ]"
              ></span>
              <span>{{ acc.provider === 'linkedin' ? 'LinkedIn' : 'X (Twitter)' }}</span>
            </div>
          </div>

          <!-- Budget Pill -->
          <Link
            href="/settings"
            class="hidden sm:flex items-center gap-1.5 bg-white border border-slate-200/80 px-3 py-1.5 rounded-full text-xs font-mono text-slate-700 shadow-sm hover:border-slate-300 transition"
            title="Monthly AI & API Budget"
          >
            <Zap class="w-3.5 h-3.5 text-amber-500" />
            <span>${{ spentUsd }} / ${{ capUsd }}</span>
          </Link>

          <!-- Notification Bell -->
          <button class="relative w-9 h-9 rounded-full bg-white border border-slate-200/80 shadow-sm flex items-center justify-center text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">
            <Bell class="w-4 h-4" />
            <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-rose-500 ring-2 ring-white"></span>
          </button>

          <!-- User Avatar -->
          <Link href="/settings" class="relative block group">
            <img
              :src="user?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=128&q=80'"
              :alt="user?.name || 'User'"
              class="w-9 h-9 rounded-full object-cover ring-2 ring-white shadow-sm group-hover:ring-[#9EE493] transition"
            />
            <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-[#10B981] ring-2 ring-white"></span>
          </Link>
        </div>

      </div>

      <!-- Mobile Navigation Drawer Strip -->
      <div class="flex md:hidden items-center gap-1 overflow-x-auto py-2 px-1 scrollbar-none mt-2">
        <Link
          v-for="item in navItems"
          :key="item.name"
          :href="item.href"
          :class="[
            'px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap',
            item.active
              ? 'bg-[#9EE493] text-slate-900 font-semibold'
              : 'bg-white text-slate-600 border border-slate-200/80'
          ]"
        >
          {{ item.name }}
        </Link>
      </div>
    </header>

    <!-- Main Workspace View -->
    <main class="flex-1 px-4 sm:px-6 lg:px-8 py-5 max-w-[1600px] mx-auto w-full">
      <slot />
    </main>
  </div>
</template>
