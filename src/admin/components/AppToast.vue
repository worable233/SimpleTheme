<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'

const props = defineProps<{
  message: string
  type?: 'success' | 'error' | 'warning'
  visible: boolean
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

const show = ref(false)
const toastEl = ref<HTMLDivElement | null>(null)

watch(() => props.visible, async (val) => {
  if (val) {
    show.value = true
    await nextTick()
    if (toastEl.value) {
      const duration = props.type === 'error' ? 6000 : 4000
      toastEl.value.style.setProperty('--toast-duration', `${duration}ms`)
    }
    setTimeout(() => {
      show.value = false
      emit('close')
    }, (props.type === 'error' ? 6000 : 4000) + 300)
  } else {
    show.value = false
  }
})

function close() {
  show.value = false
  emit('close')
}
</script>

<template>
  <Teleport to="body">
    <div class="app-toast-container">
      <div
        v-if="show"
        ref="toastEl"
        class="app-toast"
        :class="[`app-toast--${type || 'success'}`]"
        :data-variant="type || 'success'"
      >
        <span class="app-toast-icon">
          <svg v-if="(type || 'success') === 'success'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 12l2 2 4-4"/></svg>
          <svg v-else-if="type === 'error'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>
          <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3L2 21h20L12 3z"/><line x1="12" y1="10" x2="12" y2="14"/><line x1="12" y1="17" x2="12" y2="17"/></svg>
        </span>
        <div class="app-toast-content">
          <h6 class="app-toast-title">
            {{ type === 'error' ? '错误' : type === 'warning' ? '提示' : '成功' }}
          </h6>
          <div class="app-toast-message">{{ message }}</div>
        </div>
        <button class="app-toast-close" aria-label="关闭" @click="close">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <div class="app-toast-progress">
          <div class="app-toast-progress-bar"></div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.app-toast-container {
  position: fixed;
  top: 24px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 999999;
  display: flex;
  flex-direction: column;
  align-items: center;
  pointer-events: none;
}

.app-toast {
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: flex-start;
  gap: 12px;
  min-width: 22rem;
  max-width: 28rem;
  padding: 14px 16px;
  background: var(--xh-card);
  color: var(--xh-text);
  border: 1px solid var(--xh-border);
  border-radius: var(--xh-radius);
  box-shadow: var(--xh-shadow-lg);
  pointer-events: auto;
  cursor: default;
}

.app-toast--enter {
  animation: toast-enter 0.35s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}
.app-toast--exit {
  animation: toast-exit 0.25s ease forwards;
  pointer-events: none;
}

@keyframes toast-enter {
  from {
    opacity: 0;
    transform: translateY(16px) scale(0.96);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
@keyframes toast-exit {
  from {
    opacity: 1;
    transform: scale(1);
  }
  to {
    opacity: 0;
    transform: scale(0.92);
  }
}

.app-toast-icon {
  flex-shrink: 0;
  width: 20px;
  height: 20px;
  margin-top: 2px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.app-toast-icon svg {
  width: 20px;
  height: 20px;
  display: block;
}

.app-toast--success .app-toast-icon { color: var(--xh-success); }
.app-toast--error .app-toast-icon { color: var(--xh-error); }
.app-toast--warning .app-toast-icon { color: var(--xh-warning); }

.app-toast-content {
  flex: 1;
  min-width: 0;
}

.app-toast-title {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
  line-height: 1.4;
  color: var(--xh-text);
}

.app-toast--success .app-toast-title { color: var(--xh-success); }
.app-toast--error .app-toast-title { color: var(--xh-error); }
.app-toast--warning .app-toast-title { color: var(--xh-warning); }

.app-toast-message {
  font-size: 13px;
  line-height: 1.45;
  color: var(--xh-text-secondary);
  margin-top: 2px;
}

.app-toast-close {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  margin-top: 1px;
  margin-right: -4px;
  border: none;
  border-radius: var(--xh-radius-xs);
  background: transparent;
  color: var(--xh-text-secondary);
  cursor: pointer;
  padding: 0;
  transition: background 0.15s ease, color 0.15s ease;
}

.app-toast-close:hover {
  background: var(--xh-primary-light);
  color: var(--xh-text);
}

.app-toast-close svg {
  width: 14px;
  height: 14px;
  display: block;
}

.app-toast-progress {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 3px;
  pointer-events: none;
}

.app-toast--success .app-toast-progress {
  background: color-mix(in srgb, var(--xh-success) 15%, transparent);
}
.app-toast--error .app-toast-progress {
  background: color-mix(in srgb, var(--xh-error) 15%, transparent);
}
.app-toast--warning .app-toast-progress {
  background: color-mix(in srgb, var(--xh-warning) 15%, transparent);
}

.app-toast-progress-bar {
  height: 100%;
  border-radius: 0 2px 0 0;
  animation: toast-progress var(--toast-duration, 4s) linear forwards;
}

.app-toast--error .app-toast-progress-bar { background: var(--xh-error); }
.app-toast--success .app-toast-progress-bar { background: var(--xh-success); }
.app-toast--warning .app-toast-progress-bar { background: var(--xh-warning); }

@keyframes toast-progress {
  from { width: 100%; }
  to { width: 0%; }
}
</style>
