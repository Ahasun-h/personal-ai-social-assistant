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
  authorName: 'Alex Mercer',
  authorHandle: '@alexmercer',
  authorAvatarUrl: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=128&q=80',
});
</script>

<template>
  <div class="w-full bg-[#111827] border border-white/10 rounded-xl p-3.5 shadow-xl text-gray-100 font-sans">
    <div class="flex gap-3">
      <img
        :src="authorAvatarUrl"
        alt="Avatar"
        class="w-10 h-10 rounded-full border border-white/10 object-cover"
      />
      <div class="flex-1 min-w-0">
        <!-- Name & Handle -->
        <div class="flex items-center gap-1.5 text-xs">
          <span class="font-bold text-gray-100 truncate">{{ authorName }}</span>
          <span class="text-gray-400 truncate">{{ authorHandle }}</span>
          <span class="text-gray-500">· 1m</span>
        </div>

        <!-- Tweet Text -->
        <div class="mt-1 text-xs text-gray-200 whitespace-pre-line leading-relaxed">
          {{ content }}
        </div>

        <!-- Attachment -->
        <div v-if="mediaUrl" class="mt-2.5 rounded-lg overflow-hidden border border-white/10">
          <img :src="mediaUrl" alt="Tweet Media" class="w-full object-cover max-h-[260px]" />
        </div>

        <!-- Interactions -->
        <div class="mt-3 flex items-center justify-between text-gray-400 text-xs">
          <button class="flex items-center gap-1 hover:text-cyan-400 transition">
            <MessageCircle class="w-3.5 h-3.5" /> 18
          </button>
          <button class="flex items-center gap-1 hover:text-emerald-400 transition">
            <Repeat2 class="w-3.5 h-3.5" /> 42
          </button>
          <button class="flex items-center gap-1 hover:text-rose-400 transition">
            <Heart class="w-3.5 h-3.5" /> 185
          </button>
          <button class="flex items-center gap-1 hover:text-indigo-400 transition">
            <BarChart2 class="w-3.5 h-3.5" /> 8.4K
          </button>
          <button class="hover:text-indigo-400 transition">
            <Bookmark class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
