<script setup lang="ts">
import { MessageCircle, Repeat2, Heart, Bookmark, BarChart2 } from 'lucide-vue-next';

interface Props {
  authorName?: string;
  authorHandle?: string;
  authorAvatarUrl?: string;
  content: string;
  mediaUrl?: string;
}

withDefaults(defineProps<Props>(), {
  authorName: 'Alex Rivera',
  authorHandle: '@alexrivera_tech',
  authorAvatarUrl: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=128&q=80',
});
</script>

<template>
  <div class="w-full bg-white border border-slate-200/80 rounded-3xl p-5 shadow-[0_4px_20px_rgba(0,0,0,0.03)] text-slate-900 font-sans">
    <!-- Platform Header Pill -->
    <div class="pb-3 mb-3 flex items-center justify-between border-b border-slate-100">
      <span class="text-[11px] font-semibold text-slate-900 flex items-center gap-1.5">
        <span class="w-2 h-2 rounded-full bg-slate-900"></span>
        X (Twitter) Feed Preview
      </span>
      <span class="text-[10px] text-slate-400 font-mono">Max 280 Chars • Verified</span>
    </div>

    <div class="flex gap-3">
      <img
        :src="authorAvatarUrl"
        alt="Avatar"
        class="w-10 h-10 rounded-full border border-slate-200 object-cover shrink-0"
      />
      <div class="flex-1 min-w-0">
        <!-- Name & Handle -->
        <div class="flex items-center gap-1.5 text-xs">
          <span class="font-bold text-slate-900 truncate">{{ authorName }}</span>
          <span class="text-slate-400 truncate">{{ authorHandle }}</span>
          <span class="text-slate-400">· 1m</span>
        </div>

        <!-- Tweet Text -->
        <div class="mt-1 text-xs text-slate-800 whitespace-pre-line leading-relaxed">
          {{ content }}
        </div>

        <!-- Attachment -->
        <div v-if="mediaUrl" class="mt-2.5 rounded-2xl overflow-hidden border border-slate-100">
          <img :src="mediaUrl" alt="Tweet Media" class="w-full object-cover max-h-[260px]" />
        </div>

        <!-- Interactions -->
        <div class="mt-3.5 flex items-center justify-between text-slate-400 text-xs">
          <button class="flex items-center gap-1 hover:text-sky-500 transition">
            <MessageCircle class="w-3.5 h-3.5" /> 18
          </button>
          <button class="flex items-center gap-1 hover:text-emerald-500 transition">
            <Repeat2 class="w-3.5 h-3.5" /> 42
          </button>
          <button class="flex items-center gap-1 hover:text-rose-500 transition">
            <Heart class="w-3.5 h-3.5" /> 185
          </button>
          <button class="flex items-center gap-1 hover:text-slate-800 transition">
            <BarChart2 class="w-3.5 h-3.5" /> 8.4K
          </button>
          <button class="hover:text-slate-800 transition">
            <Bookmark class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
