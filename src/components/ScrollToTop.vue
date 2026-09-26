<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import AppIcon from '@/components/AppIcon.vue'
import { StButton } from '@/ui'

const isVisible = ref(false)
let ticking = false

function onScroll() {
  if (!ticking) {
    ticking = true
    requestAnimationFrame(() => {
      isVisible.value = window.scrollY > 300
      ticking = false
    })
  }
}

function scrollToTop() {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

onMounted(() => {
  window.addEventListener('scroll', onScroll, { passive: true })
})

onUnmounted(() => {
  window.removeEventListener('scroll', onScroll)
})
</script>

<template>
  <Transition name="fab-fade">
    <StButton
      v-if="isVisible"
      class="scroll-to-top"
      circle
      size="large"
      aria-label="回到顶部"
      @click="scrollToTop"
    >
      <template #icon><AppIcon name="chevron-up" :size="20" /></template>
    </StButton>
  </Transition>
</template>

<style scoped>
/* 定位钩子：StButton 契约不允许工具类，位置由调用方 scoped CSS 负责 */
.scroll-to-top {
  position: fixed;
  right: var(--st-space-6);
  bottom: var(--st-space-6);
  z-index: 9998;
}

.fab-fade-enter-active,
.fab-fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}

.fab-fade-enter-from,
.fab-fade-leave-to {
  opacity: 0;
  transform: translateY(20px) scale(0.9);
}
</style>
