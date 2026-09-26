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
    <div v-if="visible" class="announcement-capsule" @click="dismiss">
      <span v-if="announcement.icon" class="announcement-capsule__icon">{{ announcement.icon }}</span>
      <span class="announcement-capsule__text">{{ announcement.capsuleTitle }}</span>
    </div>
  </Teleport>
</template>

<style scoped>
.announcement-capsule {
  position: fixed;
  bottom: 24px;
  left: 50%;
  z-index: 99999;
  display: flex;
  align-items: center;
  gap: 8px;
  max-width: 90vw;
  padding: 10px 24px;
  border-radius: var(--radius-full);
  background-color: var(--card);
  color: var(--foreground);
  font-size: 14px;
  cursor: pointer;
  box-shadow: 0 4px 20px rgb(0 0 0 / 0.15);
  transform: translateX(-50%);
}

.announcement-capsule__icon {
  font-size: 18px;
}

.announcement-capsule__text {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
