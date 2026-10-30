<script setup lang="ts">
import { ref, computed } from 'vue';
import { Globe, ThumbsUp, MessageSquare, Repeat2, Send } from 'lucide-vue-next';

interface Props {
  authorName?: string;
  authorHeadline?: string;
  authorAvatarUrl?: string;
  content: string;
  mediaUrl?: string;
}

const props = withDefaults(defineProps<Props>(), {
  authorName: 'Alex Rivera',
  authorHeadline: 'Founder & AI Systems Architect',
  authorAvatarUrl: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=128&q=80',
});

const isExpanded = ref(false);
const FOLD_LIMIT = 210;
const needsTruncation = computed(() => props.content.length > FOLD_LIMIT);

const displayedContent = computed(() => {
  if (!needsTruncation.value || isExpanded.value) return props.content;
  return props.content.slice(0, FOLD_LIMIT) + '...';
});
</script>

<template>
  <div class="w-full bg-white border border-slate-200/80 rounded-3xl overflow-hidden shadow-[0_4px_20px_rgba(0,0,0,0.03)] text-slate-900 font-sans">
    <!-- Platform Header Pill -->
    <div class="px-4 pt-3 pb-1 flex items-center justify-between border-b border-slate-100">
      <span class="text-[11px] font-semibold text-[#0A66C2] flex items-center gap-1.5">
        <span class="w-2 h-2 rounded-full bg-[#0A66C2]"></span>
        LinkedIn Feed Preview
      </span>
      <span class="text-[10px] text-slate-400 font-mono">Algorithm: Quality Tier 1</span>
    </div>

    <!-- Author Header -->
    <div class="p-4 flex items-start gap-3">
      <img
        :src="authorAvatarUrl"
        alt="Author"
        class="w-11 h-11 rounded-full border border-slate-200 object-cover"
      />
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-1.5">
          <span class="font-bold text-xs text-slate-900 hover:text-[#0A66C2] cursor-pointer truncate">
            {{ authorName }}
          </span>
          <span class="text-[11px] text-slate-400">• 1st</span>
        </div>
        <p class="text-[11px] text-slate-500 truncate">{{ authorHeadline }}</p>
        <div class="flex items-center gap-1 text-[10px] text-slate-400 mt-0.5">
          <span>Just now</span>
          <span>•</span>
          <Globe class="w-2.5 h-2.5 text-slate-400" />
        </div>
      </div>
    </div>

    <!-- Body -->
    <div class="px-4 pb-4 text-xs text-slate-800 whitespace-pre-line leading-relaxed">
      {{ displayedContent }}
      <button
        v-if="needsTruncation && !isExpanded"
        @click="isExpanded = true"
        class="text-slate-500 hover:text-[#0A66C2] font-semibold ml-1 focus:outline-none"
      >
        see more
      </button>
    </div>

    <!-- Attachment Media Preview -->
    <div v-if="mediaUrl" class="border-y border-slate-100 bg-slate-50">
      <img :src="mediaUrl" alt="Post Asset" class="w-full object-cover max-h-[320px]" />
    </div>

    <!-- Social Engagements Footer -->
    <div class="px-4 py-2 flex items-center justify-between text-[11px] text-slate-400 border-b border-slate-100">
      <span class="flex items-center gap-1">
        <span class="w-4 h-4 rounded-full bg-[#0A66C2] text-white flex items-center justify-center text-[9px]">👍</span>
        <span>428 reactions</span>
      </span>
      <span>34 comments • 12 reposts</span>
    </div>

    <!-- Action Bar -->
    <div class="px-3 py-2 flex items-center justify-between text-xs font-semibold text-slate-500">
      <button class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl hover:bg-slate-50 text-slate-600 transition">
        <ThumbsUp class="w-4 h-4" />
        <span>Like</span>
      </button>
      <button class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl hover:bg-slate-50 text-slate-600 transition">
        <MessageSquare class="w-4 h-4" />
        <span>Comment</span>
      </button>
      <button class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl hover:bg-slate-50 text-slate-600 transition">
        <Repeat2 class="w-4 h-4" />
        <span>Repost</span>
      </button>
      <button class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl hover:bg-slate-50 text-slate-600 transition">
        <Send class="w-4 h-4" />
        <span>Send</span>
      </button>
    </div>
  </div>
</template>
