<script setup lang="ts">
/**
 * CategoryCard — 单个分类卡片（含最近文章列表）
 */
import type { RenderedText } from '@/types/wordpress'
import { RouterLink } from 'vue-router'
import { StTag } from '@/ui'
import { toInternalPath } from '@/lib/theme-config'

interface PostWithMeta {
  id: number
  link: string
  title: RenderedText | { rendered: string }
  date: string
  displayDate: string
}

defineProps<{
  name: string
  count: number
  posts: PostWithMeta[]
}>()

const emit = defineEmits<{
  (e: 'select', name: string): void
}>()
</script>

<template>
  <section class="category-card">
    <!--
      保留原生 <button>：内部 justify-between（左标题 + 右计数），
      StButton 会把内容塞进 .st-button__content。依据见 src/ui/README.md。
    -->
    <button
      type="button"
      class="category-card__head"
      :aria-label="`查看 ${name} 分类的 ${count} 篇文章`"
      aria-haspopup="dialog"
      @click="emit('select', name)"
    >
      <h3 class="category-card__title">{{ name }}</h3>
      <StTag round>{{ count }} 篇</StTag>
    </button>
    <div class="category-card__list">
      <div
        v-for="post in posts"
        :key="post.id"
        class="category-card__item"
      >
        <RouterLink
          :to="toInternalPath(post.link)"
          class="category-card__link"
          @click.stop
          >{{ (post.title as RenderedText).rendered }}</RouterLink
        >
        <span class="category-card__date">{{ post.displayDate }}</span>
      </div>
    </div>
  </section>
</template>

<style scoped>
.category-card {
  display: flex;
  flex-direction: column;
  padding: 1.2rem;
  border: 1.5px solid var(--border);
  border-radius: var(--radius-large);
  background-color: var(--card);
  backdrop-filter: blur(24px);
  box-shadow: 0 4px 24px 0 rgb(0 0 0 / 0.07);
  transition: transform var(--duration-enter) var(--ease-in-out),
    border-color var(--duration-enter) var(--ease-in-out),
    box-shadow var(--duration-enter) var(--ease-in-out);
}

.category-card:hover {
  transform: translateY(-2px);
  border-color: var(--primary);
  box-shadow: 0 4px 16px rgb(0 0 0 / 0.08);
}

.category-card:focus-within {
  border-color: var(--primary);
}

.category-card__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  margin-bottom: 12px;
  padding: 0;
  border: none;
  border-radius: var(--radius-small);
  background: transparent;
  text-align: left;
  cursor: pointer;
  appearance: none;
}

.category-card__head:focus-visible {
  outline: 2px solid var(--primary);
  outline-offset: 2px;
}

.category-card__title {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: var(--foreground);
}

.category-card__list {
  padding-top: 12px;
  border-top: 1px dashed var(--border);
}

.category-card__item {
  display: flex;
  justify-content: space-between;
  padding: 4px 0;
  font-size: 0.9rem;
}

.category-card__link {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: var(--foreground);
  text-decoration: none;
  transition: color 0.2s;
}

.category-card__link:hover {
  color: var(--primary);
}

[data-theme='dark'] .category-card__link {
  color: rgb(255 255 255 / 0.7);
}

.category-card__date {
  flex-shrink: 0;
  margin-left: 12px;
  font-size: 0.8rem;
  color: var(--secondary);
}
</style>
