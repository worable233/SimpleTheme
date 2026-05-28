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
  transform: translateX(-50%);
  z-index: 99999;
  background: var(--theme-card-light, #fff);
  color: var(--theme-fg-light, #333);
  padding: 10px 24px;
  border-radius: 100px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.15);
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 14px;
  max-width: 90vw;
}

.announcement-capsule__icon {
  font-size: 18px;
}

.announcement-capsule__text {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
