<script setup lang="ts">
import { ref } from 'vue';
import SynthexCreativeStudio from '@/Components/SynthexCreativeStudio.vue';

interface MediaAsset {
  id: string;
  filename: string;
  path: string;
  mime_type?: string;
  width?: number;
  height?: number;
  is_ai_generated?: boolean;
}

interface Props {
  isOpen: boolean;
  asset: MediaAsset | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'saved', newAsset: any): void;
}>();

const handleSave = async (blob: Blob, filename: string) => {
  if (!blob || blob.size === 0) {
    emit('saved', { filename });
    return;
  }

  const formData = new FormData();
  formData.append('file', blob, filename);
  formData.append('alt_text', `Edited in Synthex Creative Studio: ${filename}`);

  try {
    const res = await fetch('/api/v1/media/upload', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
      },
      body: formData,
    });
    if (res.ok) {
      const data = await res.json();
      emit('saved', data.data);
    } else {
      emit('saved', { filename });
    }
  } catch (e) {
    console.error('Save failed:', e);
    emit('saved', { filename });
  }
};
</script>

<template>
  <SynthexCreativeStudio
    v-if="isOpen && asset"
    :asset="asset"
    @save="handleSave"
    @close="emit('close')"
  />
</template>
