<script setup lang="ts">
import { onMounted } from 'vue'
import { showToast } from '@/lib/toast'
import ErrorView from '@/components/ErrorView.vue'

function goBack() {
  if (window.history.length > 1) {
    window.history.back()
  } else {
    window.location.href = '/'
  }
}

onMounted(() => {
  showToast('这个地址没有匹配到站点内容，请检查链接是否正确。', '404', {
    variant: 'danger',
    placement: 'top-center',
    duration: 6000,
  })
})
</script>

<template>
  <ErrorView
    illustration="lost"
    title="无法访问此页面"
    description="该链接可能已失效、被删除，或输入的地址有误。"
  >
    <template #actions>
      <button class="ev-btn ev-btn--primary" @click="goBack">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M4 12L10 6M4 12L10 18M4 12H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        返回上一页
      </button>
    </template>
  </ErrorView>
</template>

<style scoped>
/* ---- Buttons (match ErrorView style) ---- */
.ev-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.5rem 1.25rem;
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 500;
  line-height: 1.4;
  text-decoration: none;
  cursor: pointer;
  transition: background 0.15s, box-shadow 0.15s;
  border: none;
  font-family: inherit;
  white-space: nowrap;
}

.ev-btn--primary {
  background: var(--primary);
  color: var(--primary-foreground);
}
.ev-btn--primary:hover {
  opacity: 0.85;
}
</style>
