<script setup lang="ts">
import { ref } from 'vue'
import type { AnnouncementSettings } from '@/types/wordpress'

defineProps<{
  announcement: AnnouncementSettings
}>()

const STORAGE_KEY = 'announcement_capsule_dismissed'

const visible = ref(!localStorage.getItem(STORAGE_KEY))

function dismiss() {
  localStorage.setItem(STORAGE_KEY, '1')
  visible.value = false
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="visible"
      class="fixed bottom-6 left-1/2 z-[99999] flex max-w-[90vw] -translate-x-1/2 cursor-pointer items-center gap-2 rounded-full bg-card px-6 py-2.5 text-sm text-foreground shadow-[0_4px_20px_rgba(0,0,0,0.15)]"
      @click="dismiss"
    >
      <span v-if="announcement.icon" class="text-lg">{{ announcement.icon }}</span>
      <span class="overflow-hidden text-ellipsis whitespace-nowrap">{{
        announcement.capsuleTitle
      }}</span>
    </div>
  </Teleport>
</template>
