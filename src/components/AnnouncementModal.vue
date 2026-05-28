<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import type { AnnouncementSettings } from '@/types/wordpress'

const props = defineProps<{
  announcement: AnnouncementSettings
}>()

const visible = ref(true)

function handleButtonClick(button: { action?: 'close' | 'link'; url?: string }) {
  if (button.action === 'link' && button.url) {
    window.open(button.url, '_blank')
  }
  visible.value = false
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') visible.value = false
}

onMounted(() => {
  document.body.style.overflow = 'hidden'
  document.addEventListener('keydown', onKeydown)
})

onUnmounted(() => {
  document.body.style.overflow = ''
  document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <Teleport to="body">
    <div v-if="visible" class="announcement-overlay" @click.self="visible = false">
      <div class="announcement-modal">
        <div class="announcement-modal__header">
          <h2>{{ announcement.pageTitle || '公告' }}</h2>
          <button class="announcement-modal__close" @click="visible = false">&times;</button>
        </div>
        <div class="announcement-modal__body" v-html="announcement.pageContent || ''"></div>
        <div v-if="announcement.buttons?.length" class="announcement-modal__footer">
          <button
            v-for="(btn, i) in announcement.buttons"
            :key="i"
            class="announcement-btn"
            @click="handleButtonClick(btn)"
          >
            {{ btn.text }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.announcement-overlay {
  position: fixed;
  inset: 0;
  z-index: 99999;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.announcement-modal {
  background: var(--theme-card-light, #fff);
  color: var(--theme-fg-light, #333);
  border-radius: 12px;
  max-width: 560px;
  width: 100%;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.announcement-modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px 0;
}

.announcement-modal__header h2 {
  margin: 0;
  font-size: 18px;
}

.announcement-modal__close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: inherit;
  padding: 4px;
  line-height: 1;
}

.announcement-modal__body {
  padding: 16px 24px;
  overflow-y: auto;
  font-size: 14px;
  line-height: 1.7;
}

.announcement-modal__footer {
  padding: 0 24px 20px;
  display: flex;
  gap: 8px;
  justify-content: flex-end;
}

.announcement-btn {
  padding: 8px 20px;
  border-radius: 8px;
  border: none;
  background: var(--primary, #333);
  color: #fff;
  cursor: pointer;
  font-size: 14px;
}
</style>
