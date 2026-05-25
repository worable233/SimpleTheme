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
      <p class="cookie-consent__text">{{ message }}</p>
      <button class="cookie-consent__btn" @click="accept">知道了</button>
    </div>
  </Teleport>
</template>

<style scoped>
.cookie-consent {
  position: fixed;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 99998;
  background: var(--theme-card-light, #fff);
  color: var(--theme-fg-light, #333);
  padding: 12px 20px;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.15);
  display: flex;
  align-items: center;
  gap: 16px;
  max-width: 480px;
  font-size: 13px;
}

.cookie-consent__text {
  margin: 0;
  flex: 1;
  line-height: 1.5;
}

.cookie-consent__btn {
  flex-shrink: 0;
  padding: 6px 16px;
  border-radius: 8px;
  border: none;
  background: var(--primary, #333);
  color: #fff;
  cursor: pointer;
  font-size: 13px;
  white-space: nowrap;
}
</style>
