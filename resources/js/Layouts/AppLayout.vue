<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
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
} from 'lucide-vue-next';
import TokenCountdownBadge from '@/Components/TokenCountdownBadge.vue';

interface Props {
  title?: string;
}

defineProps<Props>();
</script>

<template>
  <div class="min-h-screen bg-canvas text-gray-100 flex flex-col font-sans selection:bg-indigo-500 selection:text-white">
    <!-- Top Bar -->
    <header class="h-14 border-b border-white/10 bg-surface-elevated/80 backdrop-blur-md sticky top-0 z-50 flex items-center justify-between px-6">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-indigo-500 to-cyan-400 flex items-center justify-center shadow-lg shadow-indigo-500/20">
          <Sparkles class="w-4 h-4 text-white" />
        </div>
        <span class="font-bold text-sm tracking-wide bg-gradient-to-r from-white via-gray-200 to-gray-400 bg-clip-text text-transparent">
          Personal AI Social Assistant
        </span>
      </div>

      <!-- Right Nav Status & Badges -->
      <div class="flex items-center gap-4">
        <!-- Connected accounts pills -->
        <div class="hidden md:flex items-center gap-2">
          <div class="flex items-center gap-1.5 text-xs text-gray-400 bg-white/5 px-2.5 py-1 rounded-full border border-white/5">
            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
            <span>LinkedIn</span>
            <TokenCountdownBadge :days-remaining="54" platform="linkedin" />
          </div>
          <div class="flex items-center gap-1.5 text-xs text-gray-400 bg-white/5 px-2.5 py-1 rounded-full border border-white/5">
            <span class="w-2 h-2 rounded-full bg-slate-300"></span>
            <span>X (Twitter)</span>
            <span class="text-emerald-400 font-mono text-[11px]">Active</span>
          </div>
        </div>

        <!-- Budget Gauge indicator -->
        <div class="flex items-center gap-2 bg-indigo-500/10 border border-indigo-500/20 px-3 py-1 rounded-full text-xs font-mono text-indigo-300">
          <Zap class="w-3.5 h-3.5 text-indigo-400" />
          <span>$24.50 / $50.00</span>
        </div>

        <button class="p-1.5 rounded-lg hover:bg-white/5 text-gray-400 hover:text-white transition">
          <Bell class="w-4 h-4" />
        </button>

        <img
          src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=128&q=80"
          alt="Avatar"
          class="w-7 h-7 rounded-full border border-white/20 object-cover"
        />
      </div>
    </header>

    <div class="flex-1 flex">
      <!-- Sidebar -->
      <aside class="w-56 border-r border-white/10 bg-surface-elevated/40 flex flex-col p-3 gap-1">
        <Link
          href="/inbox"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium hover:bg-white/5 hover:text-white transition"
          :class="$page.url.startsWith('/inbox') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-gray-400'"
        >
          <Inbox class="w-4 h-4" />
          <span>Review Inbox</span>
        </Link>

        <Link
          href="/calendar"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium hover:bg-white/5 hover:text-white transition"
          :class="$page.url.startsWith('/calendar') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-gray-400'"
        >
          <Calendar class="w-4 h-4" />
          <span>Calendar</span>
        </Link>

        <Link
          href="/trends"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium hover:bg-white/5 hover:text-white transition"
          :class="$page.url.startsWith('/trends') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-gray-400'"
        >
          <Flame class="w-4 h-4" />
          <span>Trend Intel</span>
        </Link>

        <Link
          href="/media"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium hover:bg-white/5 hover:text-white transition"
          :class="$page.url.startsWith('/media') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-gray-400'"
        >
          <Image class="w-4 h-4" />
          <span>Media Studio</span>
        </Link>

        <Link
          href="/analytics"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium hover:bg-white/5 hover:text-white transition"
          :class="$page.url.startsWith('/analytics') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-gray-400'"
        >
          <BarChart3 class="w-4 h-4" />
          <span>Analytics</span>
        </Link>

        <Link
          href="/settings"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium hover:bg-white/5 hover:text-white transition"
          :class="$page.url.startsWith('/settings') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30' : 'text-gray-400'"
        >
          <Settings class="w-4 h-4" />
          <span>Settings Vault</span>
        </Link>
      </aside>

      <!-- Main Content -->
      <main class="flex-1 p-6 overflow-y-auto">
        <slot />
      </main>
    </div>
  </div>
</template>
