<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { toInternalPath } from '@/lib/theme-config'
import type { WordPressPost } from '@/types/wordpress'
import ErrorView from '@/components/ErrorView.vue'

defineProps<{
  termName: string
  termTaxonomy: string
  termPostsLoading: boolean
  errorMessage: string
  termPosts: WordPressPost[]
}>()

const formatDate = (dateString: string) =>
  new Intl.DateTimeFormat('zh-CN', {
    year: 'numeric', month: 'long', day: 'numeric',
  }).format(new Date(dateString))
</script>

<template>
  <section class="term-archive">
    <header class="section-header">
      <h1>
        <span class="section-header__title">{{ termName }}</span>
        <span class="section-header__subtitle">{{ termTaxonomy }}.</span>
      </h1>
    </header>

    <!-- Term loading -->
    <div v-if="termPostsLoading">
      <div style="padding: var(--space-4); background: var(--card); border-radius: var(--radius-large, 12px); border: 1px solid var(--border);">
        <div style="display: flex; flex-direction: column; gap: var(--space-2);">
          <div role="status" class="skeleton line"></div>
          <div role="status" class="skeleton line"></div>
          <div role="status" class="skeleton line" style="width: 50%;"></div>
        </div>
      </div>
    </div>

    <!-- Term error -->
    <ErrorView
      v-else-if="errorMessage"
      illustration="empty"
      title="归档内容加载失败"
      description="无法获取该归档下的内容，请稍后重试。"
    />

    <!-- Term empty -->
    <ErrorView
      v-else-if="termPosts.length === 0"
      illustration="searching"
      title="这里还没有内容"
      description="这个归档下还没有可展示的文章。"
    />

    <!-- Term posts -->
    <div v-else class="post-list post-list--two">
      <article v-for="post in termPosts" :key="post.id" style="overflow: hidden; padding: 0; background: var(--card); border-radius: var(--radius-large, 12px); border: 1px solid var(--border);">
        <div style="padding: var(--space-4);">
          <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: var(--space-2);">
            <span class="badge" data-variant="secondary">{{ post.categories?.[0] || '文章' }}</span>
            <time class="text-light" style="font-size: var(--text-8);" :datetime="post.date">{{ formatDate(post.date) }}</time>
          </div>
          <h3 style="margin: 0 0 var(--space-1); font-size: var(--text-5);" v-html="post.title.rendered"></h3>
          <div class="text-light" style="font-size: var(--text-7); line-height: 1.6; margin-bottom: var(--space-3);" v-html="post.excerpt?.rendered"></div>
          <div style="display: flex; align-items: center; gap: 0.5rem;">
            <RouterLink :to="toInternalPath(post.link)" class="button small">阅读全文</RouterLink>
            <a :href="post.link" class="button ghost small">原始链接</a>
          </div>
        </div>
      </article>
    </div>
  </section>
</template>
