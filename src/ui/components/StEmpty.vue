<script setup lang="ts">
/**
 * StEmpty — 空状态
 *
 * 用法：
 *   <StEmpty description="还没有评论" />
 *   <StEmpty><StButton>去写第一篇</StButton></StEmpty>
 *   <StEmpty image-src="/empty.svg" description="没有搜索结果" />
 *
 * 占位图标用 'box'：令牌层图标表里没有 'mood-empty'，
 * 不凭空引用解析器会兜底成 'circle' 的名字。
 */
import { computed, useSlots } from 'vue'
import StIcon from './StIcon.vue'
import type { StSize } from '../types'

defineOptions({ name: 'StEmpty' })

const props = withDefaults(
  defineProps<{
    description?: string
    size?: StSize
    /** 自定义插画，优先于 icon 插槽 */
    imageSrc?: string
  }>(),
  {
    description: '暂无数据',
    size: 'medium',
  },
)

const slots = useSlots()

const iconSize = computed(() => ({ tiny: 24, small: 32, medium: 40, large: 48 })[props.size])

const classes = computed(() => ['st-empty', `st-empty--${props.size}`])
</script>

<template>
  <div :class="classes">
    <div v-if="imageSrc" class="st-empty__image">
      <!-- 插画纯装饰，语义由描述文字承担 -->
      <img :src="imageSrc" alt="" />
    </div>
    <div v-else class="st-empty__icon">
      <slot name="icon"><StIcon name="box" :size="iconSize" /></slot>
    </div>

    <div class="st-empty__description">
      <slot name="description">{{ description }}</slot>
    </div>

    <div v-if="slots.default" class="st-empty__action"><slot /></div>
  </div>
</template>

<style scoped>
.st-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  box-sizing: border-box;
  padding: 32px 16px;
  color: var(--muted-foreground);
  text-align: center;
}

/* ==================== 尺寸 ==================== */
.st-empty--tiny {
  padding: 16px 8px;
}

.st-empty--small {
  padding: 24px 12px;
}

.st-empty--large {
  padding: 48px 24px;
}

/* ==================== 内容 ==================== */
.st-empty__image img {
  display: block;
  max-width: 100%;
  max-height: 160px;
}

.st-empty__icon {
  display: inline-flex;
  color: var(--muted-foreground);
  opacity: 0.6;
}

.st-empty__description {
  margin-top: 12px;
  font-size: var(--st-font-medium);
  line-height: 1.5;
}

.st-empty--tiny .st-empty__description {
  font-size: var(--st-font-tiny);
}

.st-empty--small .st-empty__description {
  font-size: var(--st-font-small);
}

.st-empty--large .st-empty__description {
  font-size: var(--st-font-large);
}

.st-empty__action {
  margin-top: 16px;
}
</style>
