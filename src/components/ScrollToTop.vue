<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import AppIcon from '@/components/AppIcon.vue'

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
    <button
      v-if="isVisible"
      class="fixed right-6 bottom-6 z-[9998] flex h-12 w-12 cursor-pointer items-center justify-center rounded-full border border-border bg-card p-0 text-foreground shadow-medium transition-colors hover:bg-muted"
      @click="scrollToTop"
      aria-label="回到顶部"
    >
      <AppIcon name="chevron-up" :size="20" />
    </button>
  </Transition>
</template>

<style scoped>
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
