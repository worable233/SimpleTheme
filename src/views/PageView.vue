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
      <span class="inline-block text-xs font-semibold tracking-[0.05em] text-primary uppercase">{{ primaryBadge }}</span>
      <h1 v-html="pageData.title.rendered"></h1>

      <div class="flex flex-wrap items-center gap-3 text-muted-foreground">
        <time :datetime="pageData.date">发布 {{ formatDate(pageData.date) }}</time>
        <span v-if="pageData.modified">修改 {{ formatDate(pageData.modified) }}</span>
        <span
          v-for="tag in pageTags"
          :key="tag"
          class="inline-block rounded border border-border px-2 py-0.5 text-xs"
          >#{{ tag }}</span
        >
      </div>

      <p class="text-sm text-muted-foreground">
        页面链接：
        <a :href="pageData.link">{{ pageData.link }}</a>
      </p>
    </header>

    <div class="single-post__body prose-content" v-html="pageData.content?.rendered"></div>
  </article>
</template>
