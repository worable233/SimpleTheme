<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import type { AnnouncementSettings } from '@/types/wordpress'
import ModalCloseButton from '@/components/ModalCloseButton.vue'

defineProps<{
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
    <div
      v-if="visible"
      class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 p-5"
      @click.self="visible = false"
    >
      <div
        class="flex max-h-[80vh] w-full max-w-[560px] flex-col rounded-xl bg-card text-foreground shadow-[0_20px_60px_rgba(0,0,0,0.3)]"
      >
        <div class="flex items-center justify-between px-6 pt-5">
          <h2 class="m-0 text-lg">{{ announcement.pageTitle || '公告' }}</h2>
          <ModalCloseButton @click="visible = false" />
        </div>
        <div
          class="overflow-y-auto px-6 py-4 text-sm leading-[1.7]"
          v-html="announcement.pageContent || ''"
        ></div>
        <div v-if="announcement.buttons?.length" class="flex justify-end gap-2 px-6 pb-5">
          <button
            v-for="(btn, i) in announcement.buttons"
            :key="i"
            class="cursor-pointer rounded-lg border-none bg-primary px-5 py-2 text-sm text-primary-foreground"
            @click="handleButtonClick(btn)"
          >
            {{ btn.text }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
