<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue'
import type { AnnouncementSettings } from '@/types/wordpress'
import { StButton } from '@/ui'
import ModalCloseButton from '@/components/ModalCloseButton.vue'
import { useBodyScrollLock } from '@/composables/useBodyScrollLock'

defineProps<{
  announcement: AnnouncementSettings
}>()

const visible = ref(true)
const { lockBodyScroll, unlockBodyScroll } = useBodyScrollLock()

function close() {
  visible.value = false
}

function isSafeExternalUrl(value: string): boolean {
  try {
    const url = new URL(value)
    return url.protocol === 'http:' || url.protocol === 'https:'
  } catch {
    return false
  }
}

function handleButtonClick(button: { action?: 'close' | 'link'; url?: string }) {
  if (button.action === 'link' && button.url && isSafeExternalUrl(button.url)) {
    window.open(button.url, '_blank', 'noopener,noreferrer')
  }
  close()
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') close()
}

onMounted(() => {
  lockBodyScroll()
  document.addEventListener('keydown', onKeydown)
})

watch(visible, (isVisible) => {
  if (isVisible) lockBodyScroll()
  else unlockBodyScroll()
})

onUnmounted(() => {
  unlockBodyScroll()
  document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <Teleport to="body">
    <div v-if="visible" class="announcement-modal" @click.self="close">
      <div class="announcement-modal__panel">
        <div class="announcement-modal__header">
          <h2 class="announcement-modal__title">{{ announcement.pageTitle || '公告' }}</h2>
          <ModalCloseButton @click="close" />
        </div>
        <div
          class="announcement-modal__body"
          v-html="announcement.pageContent || ''"
        ></div>
        <div v-if="announcement.buttons?.length" class="announcement-modal__footer">
          <StButton
            v-for="(btn, i) in announcement.buttons"
            :key="i"
            type="primary"
            @click="handleButtonClick(btn)"
          >
            {{ btn.text }}
          </StButton>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.announcement-modal {
  position: fixed;
  inset: 0;
  z-index: 99999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background-color: rgb(0 0 0 / 0.5);
}

.announcement-modal__panel {
  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 560px;
  max-height: 80vh;
  border-radius: var(--radius-xl);
  background-color: var(--card);
  color: var(--foreground);
  box-shadow: 0 20px 60px rgb(0 0 0 / 0.3);
}

.announcement-modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px 0;
}

.announcement-modal__title {
  margin: 0;
  font-size: 18px;
}

.announcement-modal__body {
  overflow-y: auto;
  padding: 16px 24px;
  font-size: 13px;
  line-height: 1.7;
}

.announcement-modal__footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding: 0 24px 20px;
}
</style>
