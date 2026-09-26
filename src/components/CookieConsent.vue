<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AppIcon from '@/components/AppIcon.vue'
import { StButton } from '@/ui'

defineProps<{
  message: string
}>()

const visible = ref(false)

onMounted(() => {
  if (!localStorage.getItem('cookie_consent')) {
    visible.value = true
  }
})

function accept() {
  localStorage.setItem('cookie_consent', '1')
  visible.value = false
}
</script>

<template>
  <Teleport to="body">
    <Transition name="cookie" appear>
      <div v-if="visible" class="cookie-consent">
        <AppIcon name="cookie" filled :size="22" class="cookie-consent__icon" />
        <p class="cookie-consent__message">
          {{ message }}
        </p>
        <StButton type="primary" round size="small" @click="accept">知道了</StButton>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.cookie-consent {
  position: fixed;
  bottom: 28px;
  left: 50%;
  z-index: 99998;
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
  box-shadow: 0 4px 24px rgb(0 0 0 / 0.08);
  backdrop-filter: blur(40px);
  transform: translateX(-50%);
}

.cookie-consent__icon {
  flex-shrink: 0;
  margin-top: -1px;
  color: var(--muted-foreground);
}

.cookie-consent__message {
  flex: 1;
  min-width: 0;
  margin: 0;
}

@media (max-width: 37.5rem) {
  .cookie-consent {
    width: calc(100% - 32px);
    flex-wrap: wrap;
    gap: 10px;
    padding: 12px 16px;
    border-radius: var(--radius-large);
  }
}

/* Use a spring-like ease-out for both mounting and dismissal without overscaling. */
.cookie-enter-active,
.cookie-leave-active {
  transition:
    opacity 0.38s cubic-bezier(0.22, 1, 0.36, 1),
    translate 0.48s cubic-bezier(0.16, 1, 0.3, 1);
}

.cookie-enter-from,
.cookie-leave-to {
  opacity: 0;
  translate: 0 18px;
}

@media (prefers-reduced-motion: reduce) {
  .cookie-enter-active,
  .cookie-leave-active {
    transition-duration: 0.01ms;
  }
}
</style>
