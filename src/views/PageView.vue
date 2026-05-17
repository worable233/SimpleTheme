<script setup lang="ts">
import { computed } from 'vue'
import { useContentEnhancer } from '@/composables/useContentEnhancer'
import type { WordPressPost } from '@/types/wordpress'

const props = defineProps<{
  pageData: WordPressPost
}>()

const pageContent = computed(() => props.pageData.content?.rendered ?? null)
useContentEnhancer(pageContent)

const formatDate = (dateString: string) =>
  new Intl.DateTimeFormat('zh-CN', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(new Date(dateString))

const primaryBadge = computed(() => props.pageData.categories?.[0] || '页面')

const pageTags = computed(() => {
  const page = props.pageData as WordPressPost & { _embedded?: Record<string, unknown> }
  const terms = (page._embedded?.['wp:term'] as Array<Array<{ taxonomy?: string; name?: string }>> | undefined) || []
  for (const group of terms) {
    const tags = group
      .filter((term) => term?.taxonomy === 'post_tag' && typeof term.name === 'string')
      .map((term) => term.name as string)
    if (tags.length > 0) {
      return tags
    }
  }
  return [] as string[]
})
</script>

<template>
  <article class="single-post">
    <header class="single-post__header">
      <span style="display: inline-block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-primary);">{{ primaryBadge }}</span>
      <h1 v-html="pageData.title.rendered"></h1>

      <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; color: var(--text-muted, #666);">
        <time :datetime="pageData.date">发布 {{ formatDate(pageData.date) }}</time>
        <span v-if="pageData.modified">修改 {{ formatDate(pageData.modified) }}</span>
        <span v-for="tag in pageTags" :key="tag" style="display: inline-block; font-size: 0.75rem; padding: 0.125rem 0.5rem; border: 1px solid var(--border-color, #ddd); border-radius: 0.25rem;">#{{ tag }}</span>
      </div>

      <p style="color: var(--text-muted, #666); font-size: 0.875rem;">
        页面链接：
        <a :href="pageData.link">{{ pageData.link }}</a>
      </p>
    </header>

    <div class="single-post__body oat-prose" v-html="pageData.content?.rendered"></div>
  </article>
</template>
