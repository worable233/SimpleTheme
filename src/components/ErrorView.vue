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
  <section class="ev-root">
    <div class="ev-container">
      <!-- 插画 -->
      <div class="ev-illustration">
        <UndrawIllustration
          :name="illustration"
          width="320"
          height="240"
          class="ev-svg"
        />
      </div>

      <!-- 标题 -->
      <h1 class="ev-title">{{ title }}</h1>

      <!-- 描述 -->
      <p v-if="description" class="ev-desc">{{ description }}</p>

      <!-- 操作按钮 -->
      <div v-if="$slots.actions" class="ev-actions">
        <slot name="actions" />
      </div>

      <!-- 额外信息 -->
      <div v-if="$slots.extra" class="ev-extra">
        <slot name="extra" />
      </div>
    </div>
  </section>
</template>

<style scoped>
.ev-root {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 2rem 1rem;
}

.ev-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex: 1;
  max-width: 440px;
  width: 100%;
  text-align: center;
}

/* ---- Illustration ---- */
.ev-illustration {
  width: 100%;
  max-width: 320px;
  margin-bottom: 1.5rem;
}

.ev-svg {
  width: 100%;
  height: auto;
}

/* ---- Text ---- */
.ev-title {
  font-size: 1.25rem;
  font-weight: 625;
  color: var(--foreground);
  margin: 0 0 0.5rem;
  line-height: 1.4;
}

.ev-desc {
  font-size: 0.875rem;
  color: var(--secondary);
  margin: 0 0 1.75rem;
  line-height: 1.6;
}

/* ---- Actions ---- */
.ev-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.625rem;
  justify-content: center;
  margin-bottom: 2rem;
}

/* ---- Extra ---- */
.ev-extra {
  width: 100%;
}
</style>
