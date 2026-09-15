<script setup lang="ts">
/**
 * StBreadcrumb — 面包屑导航
 *
 * 用法：
 *   <StBreadcrumb :items="[{ label: '首页', to: '/' }, { label: '文章' }]" />
 *   <StBreadcrumb :items="items" separator=">">
 *     <template #separator><StIcon name="chevron-right" :size="12" /></template>
 *   </StBreadcrumb>
 *
 * 最后一项始终视为当前页：即使给了 to / href 也不可点击，并标记 aria-current="page"。
 * 分隔符放在 <li> 内部且 aria-hidden，屏幕阅读器不会把它念成内容。
 */
import { RouterLink } from 'vue-router'

defineOptions({ name: 'StBreadcrumb' })

withDefaults(
  defineProps<{
    items: { label: string; to?: string; href?: string }[]
    separator?: string
  }>(),
  {
    separator: '/',
  },
)
</script>

<template>
  <nav class="st-breadcrumb" aria-label="面包屑">
    <ol class="st-breadcrumb__list">
      <li v-for="(item, index) in items" :key="index" class="st-breadcrumb__item">
        <span v-if="index > 0" class="st-breadcrumb__separator" aria-hidden="true">
          <slot name="separator">{{ separator }}</slot>
        </span>

        <RouterLink
          v-if="index < items.length - 1 && item.to"
          class="st-breadcrumb__link"
          :to="item.to"
        >
          {{ item.label }}
        </RouterLink>
        <a
          v-else-if="index < items.length - 1 && item.href"
          class="st-breadcrumb__link"
          :href="item.href"
        >
          {{ item.label }}
        </a>
        <span
          v-else
          class="st-breadcrumb__text"
          :aria-current="index === items.length - 1 ? 'page' : undefined"
        >
          {{ item.label }}
        </span>
      </li>
    </ol>
  </nav>
</template>

<style scoped>
.st-breadcrumb {
  box-sizing: border-box;
  color: var(--st-placeholder);
  font-size: var(--st-font-small);
}

.st-breadcrumb__list {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.st-breadcrumb__item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-width: 0;
}

.st-breadcrumb__separator {
  display: inline-flex;
  align-items: center;
  flex: none;
  color: var(--st-placeholder);
  user-select: none;
}

.st-breadcrumb__link {
  color: var(--st-placeholder);
  text-decoration: none;
  transition: color var(--transition-fast);
}

.st-breadcrumb__link:hover {
  color: var(--st-text);
}

.st-breadcrumb__link:focus-visible {
  outline: none;
  border-radius: var(--radius-xs);
  box-shadow: var(--st-focus-ring);
}

/* 当前页用正文色 + 中等字重区分，且不可点击 */
.st-breadcrumb__text {
  color: var(--st-text);
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

@media (prefers-reduced-motion: reduce) {
  .st-breadcrumb__link {
    transition: none;
  }
}
</style>
