<script setup lang="ts">
/**
 * ErrorView — 统一的错误页面组件
 *
 * Props:
 *   illustration  — unDraw 插画名（如 "lost", "empty", "warning"）
 *   title         — 主标题
 *   description   — 描述文字
 *
 * Slots:
 *   actions       — 操作按钮（返回首页、重试等）
 *   extra         — 额外内容（详细信息等）
 */
import UndrawIllustration from '@/components/UndrawIllustration.vue'

defineOptions({ name: 'ErrorView' })

withDefaults(
  defineProps<{
    illustration?: string
    title: string
    description?: string
  }>(),
  {
    illustration: 'warning',
    description: '',
  },
)
</script>

<template>
  <section class="flex min-h-screen items-center justify-center px-4 py-8">
    <div class="flex w-full max-w-[440px] flex-1 flex-col items-center justify-center text-center">
      <!-- 插画 -->
      <div class="mb-6 w-full max-w-[320px]">
        <UndrawIllustration :name="illustration" width="320" height="240" class="h-auto w-full" />
      </div>

      <!-- 标题 -->
      <h1 class="m-0 mb-2 text-xl leading-[1.4] font-[625] text-foreground">{{ title }}</h1>

      <!-- 描述 -->
      <p v-if="description" class="m-0 mb-7 text-sm leading-[1.6] text-secondary">
        {{ description }}
      </p>

      <!-- 操作按钮 -->
      <div v-if="$slots.actions" class="mb-8 flex flex-wrap justify-center gap-2.5">
        <slot name="actions" />
      </div>

      <!-- 额外信息 -->
      <div v-if="$slots.extra" class="w-full">
        <slot name="extra" />
      </div>
    </div>
  </section>
</template>
