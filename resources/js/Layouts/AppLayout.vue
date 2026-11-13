<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { onClickOutside } from '@vueuse/core';
import {
  Inbox,
  Calendar,
  Flame,
  Image as ImageIcon,
  BarChart3,
  Settings,
  Bell,
  BellOff,
  Sparkles,
  Search,
  Zap,
  CheckCircle2,
  SlidersHorizontal,
  LogOut,
  User as UserIcon,
  ShieldCheck,
  Share2,
  AlertTriangle,
  KeyRound,
  ChevronRight,
  ExternalLink,
  Eye,
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

const rawNotifications = computed(() => (page.props as any).notifications || []);
const initialUnreadCount = computed(() => (page.props as any).unreadNotificationsCount || 0);

// Local reactive notifications copy for instant UI feedback
const notificationsList = ref<any[]>([]);
const unreadCount = ref(0);

watch(
  [rawNotifications, initialUnreadCount],
  ([newNotifs, newUnread]) => {
    notificationsList.value = [...(newNotifs || [])];
    unreadCount.value = newUnread || 0;
  },
  { immediate: true }
);

const isProfileOpen = ref(false);
const isNotificationsOpen = ref(false);

const profileDropdownRef = ref<HTMLElement | null>(null);
const notificationsDropdownRef = ref<HTMLElement | null>(null);

onClickOutside(profileDropdownRef, () => {
  isProfileOpen.value = false;
});

onClickOutside(notificationsDropdownRef, () => {
  isNotificationsOpen.value = false;
});

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

const formatRelativeTime = (isoString?: string) => {
  if (!isoString) return '';
  try {
    const diff = Math.max(0, (Date.now() - new Date(isoString).getTime()) / 1000);
    if (diff < 60) return 'Just now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)}d ago`;
  } catch {
    return isoString;
  }
};

const markNotificationRead = async (item: any) => {
  if (item.read_at) return;
  item.read_at = new Date().toISOString();
  if (unreadCount.value > 0) unreadCount.value--;

  try {
    await fetch(`/api/v1/notifications/${item.id}/read`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
  } catch (err) {
    console.error('Failed to mark notification as read:', err);
  }
};

const markAllAsRead = async () => {
  notificationsList.value.forEach((n) => {
    n.read_at = new Date().toISOString();
  });
  unreadCount.value = 0;

  try {
    await fetch('/api/v1/notifications/read-all', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
  } catch (err) {
    console.error('Failed to mark all notifications as read:', err);
  }
};

const handleLogout = () => {
  isProfileOpen.value = false;
  router.post('/logout');
};
</script>

<template>
  <div class="min-h-screen bg-[#F4F6F8] text-slate-800 flex flex-col font-sans selection:bg-[#9EE493] selection:text-slate-900">
    <!-- Top Navigation Bar -->
    <header class="sticky top-0 z-50 bg-[#F4F6F8]/90 backdrop-blur-md pt-3 pb-2 px-6">
      <div class="max-w-[1600px] mx-auto flex items-center justify-between gap-4">
        
        <!-- Left: Brand Clover Logo Pill -->
        <Link href="/dashboard" class="flex items-center gap-2.5 shrink-0 group">
          <div class="w-10 h-10 rounded-2xl bg-[#9EE493] flex items-center justify-center shadow-sm shadow-[#9EE493]/30 transition group-hover:scale-105">
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

        <!-- Center: Floating Pill Navigation -->
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
          <!-- Search Pill -->
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

          <!-- Notification Bell with Interactive Dropdown -->
          <div class="relative" ref="notificationsDropdownRef">
            <button
              @click="isNotificationsOpen = !isNotificationsOpen"
              type="button"
              class="relative w-9 h-9 rounded-full bg-white border border-slate-200/80 shadow-sm flex items-center justify-center text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition focus:outline-none"
              aria-label="Notifications"
            >
              <Bell class="w-4 h-4" />
              <span
                v-if="unreadCount > 0"
                class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-rose-500 ring-2 ring-white"
              ></span>
            </button>

            <!-- Notifications Dropdown Menu -->
            <Transition
              enter-active-class="transition duration-150 ease-out"
              enter-from-class="transform scale-95 opacity-0"
              enter-to-class="transform scale-100 opacity-100"
              leave-active-class="transition duration-100 ease-in"
              leave-from-class="transform scale-100 opacity-100"
              leave-to-class="transform scale-95 opacity-0"
            >
              <div
                v-if="isNotificationsOpen"
                class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-3xl shadow-xl border border-slate-200/80 overflow-hidden z-50 text-slate-800"
              >
                <!-- Header -->
                <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-900 uppercase tracking-wider">Notifications</span>
                    <span
                      v-if="unreadCount > 0"
                      class="px-2 py-0.5 text-[10px] font-semibold bg-[#9EE493] text-slate-900 rounded-full font-mono"
                    >
                      {{ unreadCount }} new
                    </span>
                  </div>
                  <button
                    v-if="unreadCount > 0"
                    @click="markAllAsRead"
                    type="button"
                    class="text-[11px] font-semibold text-slate-500 hover:text-slate-800 transition"
                  >
                    Mark all as read
                  </button>
                </div>

                <!-- Notifications Items List -->
                <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                  <div
                    v-if="notificationsList.length === 0"
                    class="px-4 py-8 text-center text-slate-400 text-xs"
                  >
                    <BellOff class="w-7 h-7 mx-auto mb-2 text-slate-300" />
                    No notifications yet. You're all caught up!
                  </div>

                  <div
                    v-for="item in notificationsList"
                    :key="item.id"
                    @click="markNotificationRead(item)"
                    :class="[
                      'px-4 py-3 flex items-start gap-3 text-xs transition cursor-pointer',
                      !item.read_at ? 'bg-emerald-50/40 hover:bg-emerald-50/70' : 'hover:bg-slate-50'
                    ]"
                  >
                    <!-- Type Indicator Icon -->
                    <div
                      :class="[
                        'w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5',
                        item.type === 'publish_success' ? 'bg-emerald-100 text-emerald-600' :
                        item.type === 'budget_warning' || item.type === 'budget_exceeded' ? 'bg-amber-100 text-amber-600' :
                        item.type === 'token_expiring' ? 'bg-blue-100 text-blue-600' :
                        'bg-[#9EE493]/30 text-slate-800'
                      ]"
                    >
                      <CheckCircle2 v-if="item.type === 'publish_success'" class="w-4 h-4" />
                      <AlertTriangle v-else-if="item.type === 'budget_warning' || item.type === 'budget_exceeded'" class="w-4 h-4" />
                      <KeyRound v-else-if="item.type === 'token_expiring'" class="w-4 h-4" />
                      <Sparkles v-else class="w-4 h-4" />
                    </div>

                    <div class="flex-1 min-w-0">
                      <div class="flex items-center justify-between gap-1">
                        <p class="font-semibold text-slate-900 truncate">
                          {{ item.title }}
                        </p>
                        <span
                          v-if="!item.read_at"
                          class="w-2 h-2 rounded-full bg-[#10B981] shrink-0"
                        ></span>
                      </div>
                      <p class="text-slate-600 text-[11px] mt-0.5 leading-relaxed">
                        {{ item.message }}
                      </p>
                      <span class="text-[10px] text-slate-400 font-mono mt-1 block">
                        {{ formatRelativeTime(item.created_at) }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Footer -->
                <div class="p-2.5 bg-slate-50 border-t border-slate-100 text-center">
                  <Link
                    href="/inbox"
                    @click="isNotificationsOpen = false"
                    class="text-xs font-semibold text-slate-700 hover:text-slate-900 transition block py-1"
                  >
                    View all in Review Inbox &rarr;
                  </Link>
                </div>
              </div>
            </Transition>
          </div>

          <!-- User Avatar with Interactive Auth & Settings Dropdown -->
          <div class="relative" ref="profileDropdownRef">
            <button
              @click="isProfileOpen = !isProfileOpen"
              type="button"
              class="relative block group focus:outline-none"
              aria-label="User Account Menu"
            >
              <img
                :src="user?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=250&auto=format&fit=crop&q=80'"
                :alt="user?.name || 'Alex Rivera'"
                class="w-9 h-9 rounded-full object-cover ring-2 ring-white shadow-sm group-hover:ring-[#9EE493] transition"
              />
              <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-[#10B981] ring-2 ring-white"></span>
            </button>

            <!-- Profile Dropdown Menu -->
            <Transition
              enter-active-class="transition duration-150 ease-out"
              enter-from-class="transform scale-95 opacity-0"
              enter-to-class="transform scale-100 opacity-100"
              leave-active-class="transition duration-100 ease-in"
              leave-from-class="transform scale-100 opacity-100"
              leave-to-class="transform scale-95 opacity-0"
            >
              <div
                v-if="isProfileOpen"
                class="absolute right-0 mt-2 w-72 bg-white rounded-3xl shadow-xl border border-slate-200/80 p-2 z-50 divide-y divide-slate-100 text-slate-800"
              >
                <!-- User Header Card -->
                <div class="p-3 flex items-center gap-3">
                  <img
                    :src="user?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=250&auto=format&fit=crop&q=80'"
                    :alt="user?.name || 'User'"
                    class="w-10 h-10 rounded-full object-cover ring-2 ring-[#9EE493]"
                  />
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5">
                      <p class="text-xs font-bold text-slate-900 truncate font-display">
                        {{ user?.name || 'Alex Rivera' }}
                      </p>
                      <span class="px-1.5 py-0.2 rounded-md text-[9px] font-bold uppercase tracking-wider bg-[#9EE493]/30 text-slate-900 border border-[#9EE493]/60">
                        {{ user?.role || 'Owner' }}
                      </span>
                    </div>
                    <p class="text-[11px] text-slate-400 truncate">
                      {{ user?.email || 'alex@synthex.internal' }}
                    </p>
                  </div>
                </div>

                <!-- Account Setting & Auth Related Options -->
                <div class="py-1.5 text-xs">
                  <Link
                    href="/settings"
                    @click="isProfileOpen = false"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-2xl text-slate-700 hover:text-slate-900 hover:bg-slate-50 font-medium transition"
                  >
                    <UserIcon class="w-4 h-4 text-slate-400" />
                    <span>Account Setting</span>
                  </Link>
                  <Link
                    href="/settings"
                    @click="isProfileOpen = false"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-2xl text-slate-700 hover:text-slate-900 hover:bg-slate-50 font-medium transition"
                  >
                    <Share2 class="w-4 h-4 text-slate-400" />
                    <span>Connected Social Accounts</span>
                  </Link>
                  <Link
                    href="/settings"
                    @click="isProfileOpen = false"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-2xl text-slate-700 hover:text-slate-900 hover:bg-slate-50 font-medium transition"
                  >
                    <Sparkles class="w-4 h-4 text-slate-400" />
                    <span>AI Provider & Model Vault</span>
                  </Link>
                  <Link
                    href="/settings"
                    @click="isProfileOpen = false"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-2xl text-slate-700 hover:text-slate-900 hover:bg-slate-50 font-medium transition"
                  >
                    <ShieldCheck class="w-4 h-4 text-slate-400" />
                    <span>Security & Metered Caps</span>
                  </Link>
                  <Link
                    href="/landing"
                    @click="isProfileOpen = false"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-2xl text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50/50 font-medium transition"
                  >
                    <Eye class="w-4 h-4 text-indigo-500" />
                    <span>Showcase & Live Simulator</span>
                  </Link>
                </div>

                <!-- Logout Option -->
                <div class="pt-1.5">
                  <button
                    @click="handleLogout"
                    type="button"
                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-2xl text-rose-600 hover:bg-rose-50 text-xs font-semibold transition"
                  >
                    <LogOut class="w-4 h-4 text-rose-500" />
                    <span>Logout</span>
                  </button>
                </div>
              </div>
            </Transition>
          </div>
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
