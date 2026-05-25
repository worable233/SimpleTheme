<script setup lang="ts">
import { ref, watch } from 'vue'

const props = defineProps<{
  message: string
  type?: 'success' | 'error' | 'warning'
  visible: boolean
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

const show = ref(false)

watch(() => props.visible, (val) => {
  if (val) {
    show.value = true
    setTimeout(() => {
      show.value = false
      emit('close')
    }, 3000)
  }
})
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="xh-toast-popup"
      :class="[`xh-toast-popup--${type || 'success'}`, { 'xh-toast-popup--enter': show }]"
    >
      <span class="xh-toast-popup__icon">
        <template v-if="(type || 'success') === 'success'">✓</template>
        <template v-else-if="type === 'error'">✕</template>
        <template v-else>!</template>
      </span>
      <span class="xh-toast-popup__text">{{ message }}</span>
    </div>
  </Teleport>
</template>

<style scoped>
.xh-toast-popup {
  position: fixed;
  top: 24px;
  left: 50%;
  transform: translateX(-50%) translateY(-12px);
  z-index: 999999;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  border-radius: var(--xh-radius-sm);
  font-size: 14px;
  font-weight: 500;
  box-shadow: var(--xh-shadow-lg);
  opacity: 0;
  transition: all 0.3s ease;
  pointer-events: none;
}
.xh-toast-popup--enter {
  opacity: 1;
  transform: translateX(-50%) translateY(0);
}
.xh-toast-popup--success {
  background: var(--xh-success-bg);
  color: var(--xh-success);
  border: 1px solid var(--xh-success);
}
.xh-toast-popup--error {
  background: var(--xh-error-bg);
  color: var(--xh-error);
  border: 1px solid var(--xh-error);
}
.xh-toast-popup--warning {
  background: var(--xh-warning-bg);
  color: var(--xh-warning);
  border: 1px solid var(--xh-warning);
}
.xh-toast-popup__icon {
  font-size: 16px;
  font-weight: 700;
  flex-shrink: 0;
}
</style>
