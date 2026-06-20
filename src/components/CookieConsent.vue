<script setup lang="ts">
import { ref, onMounted } from 'vue'

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
    <div v-if="visible" class="cookie-consent">
      <i class="bx bxs-cookie cookie-consent__icon"></i>
      <p class="cookie-consent__text">{{ message }}</p>
      <button class="cookie-consent__btn" @click="accept">知道了</button>
    </div>
  </Teleport>
</template>

<style scoped>
.cookie-consent {
  position: fixed;
  bottom: 28px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 99998;
  display: inline-flex;
  align-items: center;
  gap: 12px;
  max-width: 520px;
  width: auto;
  padding: 10px 18px 10px 14px;
  background: var(--card, #fff);
  border: 1px solid var(--border, #e2e2e2);
  border-radius: var(--radius-full, 9999px);
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
  font-size: 13px;
  line-height: 1.5;
  color: var(--foreground, #333);
  animation: cookie-in 0.4s cubic-bezier(0.16, 1, 0.3, 1);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
}

@keyframes cookie-in {
  from {
    opacity: 0;
    transform: translateX(-50%) translateY(16px) scale(0.96);
  }
  to {
    opacity: 1;
    transform: translateX(-50%) translateY(0) scale(1);
  }
}

.cookie-consent__icon {
  flex-shrink: 0;
  font-size: 22px;
  color: var(--muted-foreground, #999);
  margin-top: -1px;
}

.cookie-consent__text {
  margin: 0;
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.cookie-consent__btn {
  flex-shrink: 0;
  padding: 5px 16px;
  border: none;
  border-radius: var(--radius-full, 9999px);
  background: var(--primary, #333);
  color: var(--primary-foreground, #fff);
  font-size: 12px;
  font-weight: 500;
  font-family: inherit;
  cursor: pointer;
  white-space: nowrap;
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.cookie-consent__btn:hover {
  opacity: 0.85;
  transform: scale(1.03);
}

.cookie-consent__btn:active {
  transform: scale(0.97);
}

@media (max-width: 600px) {
  .cookie-consent {
    width: calc(100% - 32px);
    padding: 12px 16px;
    border-radius: var(--radius-large, 12px);
    flex-wrap: wrap;
    gap: 10px;
  }
  .cookie-consent__text {
    white-space: normal;
  }
}
</style>
