<script setup lang="ts">
import { onMounted } from 'vue'
import ErrorView from '@/components/ErrorView.vue'
import { StButton, useToast } from '@/ui'

const toast = useToast()

function goBack() {
  if (window.history.length > 1) {
    window.history.back()
  } else {
    window.location.href = '/'
  }
}

onMounted(() => {
  // 404 提示：标题即正文，用 error 类型（停留更久、语义为告警）
  toast.error('这个地址没有匹配到站点内容，请检查链接是否正确。')
})
</script>

<template>
  <ErrorView
    illustration="lost"
    title="无法访问此页面"
    description="该链接可能已失效、被删除，或输入的地址有误。"
  >
    <template #actions>
      <StButton type="primary" round @click="goBack">
        <template #icon>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4 12L10 6M4 12L10 18M4 12H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </template>
        返回上一页
      </StButton>
    </template>
  </ErrorView>
</template>
