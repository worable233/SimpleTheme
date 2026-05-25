<script setup lang="ts">
/**
 * CategoryCard — 单个分类卡片（含最近文章列表）
 */
import type { RenderedText } from '@/types/wordpress'

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
  <section
    class="category-card"
    @click="emit('select', name)"
  >
    <div class="category-header">
      <h3 class="category-name">{{ name }}</h3>
      <span class="category-count">{{ count }} 篇</span>
    </div>
    <div class="category-posts">
      <div
        v-for="post in posts"
        :key="post.id"
        class="category-post-item"
      >
        <a :href="post.link" class="category-post-title">{{ (post.title as RenderedText).rendered }}</a>
        <span class="category-post-date">{{ post.displayDate }}</span>
      </div>
    </div>
  </section>
</template>

<style scoped>
.category-card {
  background: var(--card, rgba(255,255,255,0.7));
  border-radius: var(--radius-large, 8px);
  box-shadow: 0 4px 24px 0 rgba(0,0,0,0.07);
  padding: 1.2rem;
  display: flex;
  flex-direction: column;
  cursor: pointer;
  border: 1.5px solid var(--border, #e0e0e0);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.category-card:hover {
  transform: perspective(800px) translateY(-5px) rotateX(2deg);
  box-shadow: 0 10px 48px -4px rgba(0,0,0,0.13);
  border-color: var(--primary, #505050);
}

.category-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.8rem;
}

.category-name {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--foreground, #222);
  margin: 0;
}

.category-count {
  font-size: 0.9rem;
  background: var(--border, rgba(0,0,0,0.08));
  padding: 0.3rem 0.8rem;
  border-radius: var(--radius-full, 9999px);
  color: var(--foreground, #666);
  white-space: nowrap;
}

.category-posts {
  padding-top: 0.8rem;
  border-top: 1px dashed var(--border, #e0e0e0);
}

.category-post-item {
  display: flex;
  justify-content: space-between;
  font-size: 0.9rem;
  padding: 0.3rem 0;
}

.category-post-title {
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: var(--foreground, #555);
  text-decoration: none;
  transition: color 0.2s;
}

.category-post-title:hover {
  color: var(--primary, #505050);
}

.category-post-date {
  color: var(--foreground, #999);
  font-size: 0.8rem;
  margin-left: 0.8rem;
  flex-shrink: 0;
}

/* Dark mode */
:global(body.dark) .category-card {
  background: rgba(30,30,30,0.92);
  border-color: #333;
  box-shadow: inset 0 1px 0 0 #fff3;
}

:global(body.dark) .category-card:hover {
  border-color: var(--primary, #fff);
}

:global(body.dark) .category-name {
  color: rgba(255,255,255,0.9);
}

:global(body.dark) .category-count {
  background: rgba(255,255,255,0.1);
  color: rgba(255,255,255,0.7);
}

:global(body.dark) .category-posts {
  border-top-color: #333;
}

:global(body.dark) .category-post-title {
  color: rgba(255,255,255,0.7);
}

:global(body.dark) .category-post-title:hover {
  color: var(--primary, #fff);
}

:global(body.dark) .category-post-date {
  color: rgba(255,255,255,0.5);
}
</style>
