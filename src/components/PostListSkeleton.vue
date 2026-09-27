<script setup lang="ts">
/**
 * PostListSkeleton — 文章列表骨架屏
 *
 * 首页 / 归档页共用：与 `.post-card` 同构，支持按实测卡片高度与是否含特色图
 * 渲染，避免加载完成后列表高度跳动（高度由 useSkeletonSize 记忆）。
 */
defineOptions({ name: 'PostListSkeleton' })

withDefaults(
  defineProps<{
    /** 骨架卡片数量 */
    count?: number
    /** 卡片实测高度（px），来自 useSkeletonSize；缺省时用 CSS 默认值 */
    cardHeight?: number
    /** 是否渲染特色图占位 */
    showCover?: boolean
  }>(),
  {
    count: 3,
    showCover: true,
  },
)
</script>

<template>
  <div class="post-list">
    <div
      v-for="i in count"
      :key="i"
      class="post-card-skeleton"
      :style="cardHeight ? { height: cardHeight + 'px', minHeight: cardHeight + 'px' } : undefined"
    >
      <div v-if="showCover" class="post-card-skeleton__cover"></div>
      <div class="post-card-skeleton__meta"><span></span><span></span></div>
      <div class="post-card-skeleton__text">
        <div class="post-card-skeleton__title"></div>
        <div class="post-card-skeleton__excerpt"></div>
        <div class="post-card-skeleton__excerpt post-card-skeleton__excerpt--short"></div>
      </div>
    </div>
  </div>
</template>
