<script setup lang="ts">
/**
 * ConnectionBanner — 连接层问题的统一提示条（样式对齐 Cookie 横条）。
 *
 * 只在「人机验证过期 / 断网 / 服务器无响应」时出现，替代此前逐条弹出的
 * 「Request failed with status code 403」。用户点按钮完成一次刷新/重试即可。
 */
import { computed } from 'vue'
import AppIcon from '@/components/AppIcon.vue'
import { StButton } from '@/ui'
import { CONNECTION_COPY, connectionIssue, resetConnection } from '@/lib/connectivity'

const issue = computed(() => connectionIssue.value)
const copy = computed(() => (issue.value ? CONNECTION_COPY[issue.value] : null))
const iconName = computed(() => {
  if (issue.value === 'challenge') return 'shield'
  if (issue.value === 'offline') return 'wifi'
  return 'warning'
})

function retry() {
  resetConnection()
  window.location.reload()
}
</script>

<template>
  <Teleport to="body">
    <Transition name="conn" appear>
      <div v-if="copy" class="conn-banner" role="status" aria-live="polite">
        <AppIcon :name="iconName" :size="20" class="conn-banner__icon" />
        <p class="conn-banner__message">{{ copy.message }}</p>
        <StButton type="primary" round size="small" @click="retry">{{ copy.action }}</StButton>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.conn-banner {
  position: fixed;
  bottom: 28px;
  left: 50%;
  z-index: 99999;
  display: inline-flex;
  align-items: center;
  gap: 12px;
  width: auto;
  max-width: min(calc(100vw - 32px), 44rem);
  padding: 10px 18px 10px 14px;
  border: 1px solid var(--border);
  border-radius: var(--radius-full);
  background-color: var(--card);
  color: var(--foreground);
  font-size: 13px;
  line-height: 1.5;
  box-shadow: 0 4px 24px rgb(0 0 0 / 0.1);
  backdrop-filter: blur(40px);
  transform: translateX(-50%);
}

.conn-banner__icon {
  flex-shrink: 0;
  margin-top: -1px;
  color: var(--muted-foreground);
}

.conn-banner__message {
  flex: 1;
  min-width: 0;
  margin: 0;
}

@media (max-width: 37.5rem) {
  .conn-banner {
    width: calc(100% - 32px);
    flex-wrap: wrap;
    gap: 10px;
    padding: 12px 16px;
    border-radius: var(--radius-large);
  }
}

.conn-enter-active,
.conn-leave-active {
  transition:
    opacity 0.38s cubic-bezier(0.22, 1, 0.36, 1),
    translate 0.48s cubic-bezier(0.16, 1, 0.3, 1);
}

.conn-enter-from,
.conn-leave-to {
  opacity: 0;
  translate: 0 18px;
}

@media (prefers-reduced-motion: reduce) {
  .conn-enter-active,
  .conn-leave-active {
    transition-duration: 0.01ms;
  }
}
</style>
