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
  <section class="error-view">
    <div class="error-view__inner">
      <!-- 插画 -->
      <div class="error-view__illustration">
        <UndrawIllustration :name="illustration" width="320" height="240" class="error-view__img" />
      </div>

      <!-- 标题 -->
      <h1 class="error-view__title">{{ title }}</h1>

      <!-- 描述 -->
      <p v-if="description" class="error-view__desc">
        {{ description }}
      </p>

      <!-- 操作按钮 -->
      <div v-if="$slots.actions" class="error-view__actions">
        <slot name="actions" />
      </div>

      <!-- 额外信息 -->
      <div v-if="$slots.extra" class="error-view__extra">
        <slot name="extra" />
      </div>
    </div>
  </section>
</template>

<style scoped>
.error-view {
  display: flex;
  min-height: 100vh;
  align-items: center;
  justify-content: center;
  padding: 32px 16px;
}

.error-view__inner {
  display: flex;
  flex: 1;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  max-width: 440px;
  text-align: center;
}

.error-view__illustration {
  width: 100%;
  max-width: 320px;
  margin-bottom: 24px;
}

.error-view__img {
  width: 100%;
  height: auto;
}

.error-view__title {
  margin: 0 0 8px;
  font-size: 18px;
  font-weight: 625;
  line-height: 1.4;
  color: var(--foreground);
}

.error-view__desc {
  margin: 0 0 28px;
  font-size: 13px;
  line-height: 1.6;
  color: var(--secondary);
}

.error-view__actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 10px;
  margin-bottom: 32px;
}

.error-view__extra {
  width: 100%;
}
</style>
