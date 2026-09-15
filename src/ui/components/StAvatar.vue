<script setup lang="ts">
/**
 * StAvatar — 头像
 *
 * 用法：
 *   <StAvatar src="/a.png" alt="作者" />
 *   <StAvatar :size="48" fallback-text="Worable" />
 *   <StAvatar><StIcon name="user" /></StAvatar>
 *
 * 默认插槽优先级最高（自定义内容完全接管）；图片加载失败才回退到
 * 首字符或图标，避免出现空白方块。
 */
import { computed, ref, watch } from 'vue'
import StIcon from './StIcon.vue'
import type { StSize } from '../types'

defineOptions({ name: 'StAvatar' })

const props = withDefaults(
  defineProps<{
    src?: string
    alt?: string
    /** 尺寸档位或像素值 */
    size?: StSize | number
    /** 圆形（默认）或圆角方形 */
    round?: boolean
    /** 无图 / 加载失败时的文字回退，取首字符 */
    fallbackText?: string
  }>(),
  {
    size: 'medium',
    round: true,
  },
)

const loadFailed = ref(false)

// src 变化后必须重置失败态，否则新图会被上一次的错误永久挡住
watch(
  () => props.src,
  () => {
    loadFailed.value = false
  },
)

/** 档位映射沿用视觉尺寸，不复用 --st-height-*（头像高度与控件高度解耦） */
const px = computed(() =>
  typeof props.size === 'number'
    ? props.size
    : { tiny: 24, small: 28, medium: 36, large: 44 }[props.size],
)

const rootStyle = computed(() => ({
  width: `${px.value}px`,
  height: `${px.value}px`,
  fontSize: `${Math.max(10, Math.round(px.value * 0.4))}px`,
}))

const showImage = computed(() => !!props.src && !loadFailed.value)
const initial = computed(() => props.fallbackText?.trim().charAt(0) ?? '')

const classes = computed(() => ['st-avatar', { 'st-avatar--round': props.round }])
</script>

<template>
  <span :class="classes" :style="rootStyle">
    <slot>
      <img
        v-if="showImage"
        class="st-avatar__img"
        :src="src"
        :alt="alt"
        @error="loadFailed = true"
      />
      <span v-else-if="initial" class="st-avatar__text">{{ initial }}</span>
      <StIcon v-else name="user" :size="Math.round(px * 0.6)" />
    </slot>
  </span>
</template>

<style scoped>
.st-avatar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: none;
  box-sizing: border-box;
  overflow: hidden;
  border-radius: var(--radius-medium);
  background-color: var(--muted);
  color: var(--muted-foreground);
  line-height: 1;
  user-select: none;
}

.st-avatar--round {
  border-radius: var(--radius-full);
}

.st-avatar__img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.st-avatar__text {
  font-weight: 500;
  text-transform: uppercase;
}
</style>
