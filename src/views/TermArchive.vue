<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { toInternalPath, getThemeConfig } from '@/lib/theme-config'
import type { WordPressPost } from '@/types/wordpress'
import ErrorView from '@/components/ErrorView.vue'

defineProps<{
  termName: string
  termTaxonomy: string
  termPostsLoading: boolean
  errorMessage: string
  termPosts: WordPressPost[]
}>()

const metaConfig = computed(() => getThemeConfig().features?.meta)

const subtitleMap: Record<string, string> = {
  post_tag: '标签归档',
  category: '分类归档',
  date: '日期归档',
}

const formatDate = (dateString: string) =>
  new Intl.DateTimeFormat('zh-CN', {
    year: 'numeric', month: 'long', day: 'numeric',
  }).format(new Date(dateString))

const formatModifiedDate = (dateString?: string) => {
  if (!dateString) return ''
  return '更新: ' + formatDate(dateString)
}

const formatWordCount = (count?: number) => {
  if (!count) return ''
  return count >= 1000 ? `${(count / 1000).toFixed(1)}k 字` : `${count} 字`
}
</script>

<template>
  <section class="term-archive">
    <header class="section-header">
      <h1>
        <span class="section-header__title">{{ termTaxonomy === 'post_tag' ? '#' + termName : termName }}</span>
        <span class="section-header__subtitle">&nbsp;{{ subtitleMap[termTaxonomy] || '归档' }}</span>
      </h1>
    </header>

    <!-- Term loading -->
    <div v-if="termPostsLoading" class="post-list">
      <div v-for="i in 3" :key="'sk-term-' + i" class="post-card-skeleton">
        <div class="post-card-skeleton__text">
          <div class="skeleton-line w-[70%]"></div>
          <div class="skeleton-line w-1/2"></div>
        </div>
        <div class="post-card-skeleton__cover"></div>
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
    <div v-else class="post-list">
      <article v-for="post in termPosts" :key="post.id" class="post-card">
        <!-- Cover image — absolute positioned right overlay -->
        <img
          v-if="post.featuredImage"
          :src="post.featuredImage"
          alt=""
          loading="lazy"
          class="post-card__cover"
        />
        <!-- Meta badge — top-right overlay -->
        <div class="post-card__meta">
          <span v-if="metaConfig?.showCategory && post.categories?.[0]" class="post-card__meta-item">{{ post.categories[0] }}</span>
          <time v-if="metaConfig?.showPublishDate" :datetime="post.date" class="post-card__meta-item">{{ formatDate(post.date) }}</time>
          <time v-if="metaConfig?.showModifiedDate && post.modified" :datetime="post.modified" class="post-card__meta-item">{{ formatModifiedDate(post.modified) }}</time>
          <span v-if="metaConfig?.showCommentCount && post.commentCount !== undefined" class="post-card__meta-item">{{ post.commentCount }} 评论</span>
          <span v-if="metaConfig?.showViewCount && post.viewCount !== undefined" class="post-card__meta-item">{{ post.viewCount }} 热度</span>
          <span v-if="metaConfig?.showReadingTime && post.readingTime" class="post-card__meta-item">{{ post.readingTime }} 分钟</span>
          <span v-if="metaConfig?.showWordCount && post.wordCount" class="post-card__meta-item">{{ formatWordCount(post.wordCount) }}</span>
        </div>
        <!-- Body: title, excerpt -->
        <div class="post-card__body">
          <h2 class="post-card__title">
            <router-link :to="toInternalPath(post.link)">
              <span v-html="post.title.rendered"></span>
            </router-link>
          </h2>
          <p v-if="post.excerpt?.rendered" class="post-card__excerpt" v-html="post.excerpt.rendered"></p>
        </div>
      </article>
    </div>
  </section>
</template>
