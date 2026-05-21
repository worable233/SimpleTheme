<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

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
      class="button ghost"
      @click="scrollToTop"
      aria-label="回到顶部"
      style="position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9998; width: 3rem; height: 3rem; padding: 0; border-radius: 50%; display: flex; align-items: center; justify-content: center;"
    >
      <i class="bx bx-chevron-up" style="font-size: 20px;"></i>
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
