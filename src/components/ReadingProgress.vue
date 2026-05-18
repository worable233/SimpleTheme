<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'

const progress = ref(0)
const displayProgress = ref(0)
const isVisible = ref(false)
const ticking = ref(false)

// RAF 节流的滚动处理：仅在浏览器绘制帧内更新，避免布局抖动
function updateProgress() {
  if (ticking.value) return
  ticking.value = true

  requestAnimationFrame(() => {
    const scrollTop = window.scrollY
    const docHeight = document.documentElement.scrollHeight - window.innerHeight
    const scrollPercent = scrollTop / docHeight
    progress.value = Math.round(scrollPercent * 100)
    
    // 当滚动超过 5% 时显示进度条
    isVisible.value = scrollPercent > 0.05

    ticking.value = false
  })
}

// 对 stroke-dashoffset 做防抖，减少重绘频率
let dashDebounce: ReturnType<typeof setTimeout> | null = null
watch(progress, (val) => {
  if (dashDebounce) clearTimeout(dashDebounce)
  dashDebounce = setTimeout(() => {
    displayProgress.value = val
  }, 50)
})

onMounted(() => {
  window.addEventListener('scroll', updateProgress, { passive: true })
  updateProgress()
})

onUnmounted(() => {
  window.removeEventListener('scroll', updateProgress)
  if (dashDebounce) clearTimeout(dashDebounce)
})

const strokeWidth = 3
const radius = 16
const circumference = 2 * Math.PI * radius
const strokeDashoffset = computed(() => {
  return circumference - (displayProgress.value / 100) * circumference
})
</script>

<template>
  <Transition name="progress-fade">
    <div v-if="isVisible" class="reading-progress" :aria-label="`阅读进度 ${progress}%`">
      <svg
        class="reading-progress__svg"
        width="40"
        height="40"
        viewBox="0 0 40 40"
        role="progressbar"
        :aria-valuenow="progress"
        aria-valuemin="0"
        aria-valuemax="100"
      >
        <!-- Background circle -->
        <circle
          class="reading-progress__background"
          cx="20"
          cy="20"
          :r="radius"
          :stroke-width="strokeWidth"
          fill="none"
        />
        <!-- Progress circle -->
        <circle
          class="reading-progress__circle"
          cx="20"
          cy="20"
          :r="radius"
          :stroke-width="strokeWidth"
          fill="none"
          :style="{ strokeDashoffset }"
          transform="rotate(-90 20 20)"
        />
        <!-- Percentage text -->
        <text
          v-if="progress < 100"
          x="20"
          y="20"
          text-anchor="middle"
          dominant-baseline="central"
          class="reading-progress__text"
        >
          {{ progress }}%
        </text>
      </svg>
    </div>
  </Transition>
</template>

<style scoped>
.reading-progress {
  position: fixed;
  top: 1rem;
  right: 1rem;
  z-index: 9999;
  cursor: pointer;
  transition: transform 0.2s ease;
}

.reading-progress:hover {
  transform: scale(1.1);
}

.reading-progress__svg {
  transform: rotate(-90deg);
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

.reading-progress__background {
  stroke: var(--border);
}

.reading-progress__circle {
  stroke: var(--primary);
  stroke-linecap: round;
  stroke-dasharray: v-bind('circumference');
  transition: stroke-dashoffset 0.3s ease;
}

.reading-progress__text {
  transform: rotate(90deg);
  transform-origin: center;
  font-size: 0.625rem;
  font-weight: 600;
  fill: var(--foreground);
  text-anchor: middle;
  dominant-baseline: central;
}

/* Progress fade transitions */
.progress-fade-enter-active,
.progress-fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}

.progress-fade-enter-from,
.progress-fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
