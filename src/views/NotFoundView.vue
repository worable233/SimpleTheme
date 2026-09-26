<script setup lang="ts">
import { onMounted } from 'vue'
import ErrorView from '@/components/ErrorView.vue'
import AppIcon from '@/components/AppIcon.vue'
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
          <AppIcon name="arrow-left" :size="16" />
        </template>
        返回上一页
      </StButton>
    </template>
  </ErrorView>
</template>
