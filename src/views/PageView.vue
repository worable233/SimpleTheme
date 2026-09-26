<script setup lang="ts">
import { computed } from 'vue'
import { useContentEnhancer } from '@/composables/useContentEnhancer'
import type { WordPressPost } from '@/types/wordpress'
import { StTag } from '@/ui'

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
      <span class="page-view__badge">{{ primaryBadge }}</span>
      <h1 v-html="pageData.title.rendered"></h1>

      <div class="page-view__meta">
        <time :datetime="pageData.date">发布 {{ formatDate(pageData.date) }}</time>
        <span v-if="pageData.modified">修改 {{ formatDate(pageData.modified) }}</span>
        <StTag v-for="tag in pageTags" :key="tag" size="tiny" bordered>#{{ tag }}</StTag>
      </div>

      <p class="page-view__link-label">
        页面链接：
        <a :href="pageData.link">{{ pageData.link }}</a>
      </p>
    </header>

    <div class="single-post__body prose-content" v-html="pageData.content?.rendered"></div>
  </article>
</template>

<style scoped>
.page-view__badge {
  display: inline-block;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: var(--primary);
}

.page-view__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px;
  color: var(--muted-foreground);
}

.page-view__link-label {
  font-size: 13px;
  color: var(--muted-foreground);
}
</style>
