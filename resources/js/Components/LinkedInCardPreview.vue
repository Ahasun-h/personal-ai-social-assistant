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
  authorName: 'Alex Mercer (Owner)',
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
  <div class="w-full bg-[#1F2937] border border-white/10 rounded-xl overflow-hidden shadow-xl text-gray-100 font-sans">
    <!-- Header -->
    <div class="p-3.5 flex items-start gap-3">
      <img
        :src="authorAvatarUrl"
        alt="Author"
        class="w-11 h-11 rounded-full border border-white/10 object-cover"
      />
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-1.5">
          <span class="font-semibold text-xs text-gray-100 hover:text-indigo-400 cursor-pointer truncate">
            {{ authorName }}
          </span>
          <span class="text-[11px] text-gray-400">• 1st</span>
        </div>
        <p class="text-[11px] text-gray-400 truncate">{{ authorHeadline }}</p>
        <div class="flex items-center gap-1 text-[10px] text-gray-500 mt-0.5">
          <span>Just now</span>
          <span>•</span>
          <Globe class="w-2.5 h-2.5 text-gray-500" />
        </div>
      </div>
    </div>

    <!-- Body -->
    <div class="px-3.5 pb-3 text-xs text-gray-200 whitespace-pre-line leading-relaxed">
      {{ displayedContent }}
      <button
        v-if="needsTruncation && !isExpanded"
        @click="isExpanded = true"
        class="text-gray-400 hover:text-indigo-400 font-semibold ml-1 focus:outline-none"
      >
        see more
      </button>
    </div>

    <!-- Attachment Preview -->
    <div v-if="mediaUrl" class="w-full bg-[#111827] border-y border-white/10 relative overflow-hidden">
      <img
        :src="mediaUrl"
        alt="Post Attachment"
        class="w-full object-cover max-h-[320px]"
      />
    </div>

    <!-- Reactions -->
    <div class="px-3.5 py-2 border-b border-white/10 flex items-center justify-between text-[11px] text-gray-400">
      <div class="flex items-center gap-1.5">
        <span class="flex items-center justify-center w-3.5 h-3.5 rounded-full bg-[#0A66C2]">
          <ThumbsUp class="w-2 h-2 text-white" />
        </span>
        <span>Jane and 38 others</span>
      </div>
      <div class="flex gap-2">
        <span>12 comments</span>
      </div>
    </div>

    <!-- Actions -->
    <div class="px-1 py-1 flex items-center justify-around text-gray-400 text-xs font-semibold">
      <button class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg hover:bg-white/5 transition">
        <ThumbsUp class="w-3.5 h-3.5" /> Like
      </button>
      <button class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg hover:bg-white/5 transition">
        <MessageSquare class="w-3.5 h-3.5" /> Comment
      </button>
      <button class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg hover:bg-white/5 transition">
        <Repeat2 class="w-3.5 h-3.5" /> Repost
      </button>
      <button class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg hover:bg-white/5 transition">
        <Send class="w-3.5 h-3.5" /> Send
      </button>
    </div>
  </div>
</template>
