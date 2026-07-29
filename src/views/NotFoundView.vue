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
      <button
        class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border-none bg-primary px-5 py-2 text-sm leading-[1.4] font-medium whitespace-nowrap text-primary-foreground no-underline transition-[background,box-shadow] duration-150 hover:opacity-85 dark:shadow-[inset_0_1px_0_0_#fff3]"
        @click="goBack"
      >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M4 12L10 6M4 12L10 18M4 12H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        返回上一页
      </button>
    </template>
  </ErrorView>
</template>
