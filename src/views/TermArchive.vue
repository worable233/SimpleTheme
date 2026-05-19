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
      </h1>
    </header>

    <!-- Term loading -->
    <div v-if="termPostsLoading" class="post-list">
      <div v-for="i in 3" :key="'sk-term-' + i" class="post-card-skeleton">
        <div class="post-card-skeleton__text">
          <div class="skeleton-line w-70"></div>
          <div class="skeleton-line w-50"></div>
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
        <!-- Text content: left side -->
        <div class="post-card__text">
          <h2 class="post-card__title">
            <router-link
              :to="toInternalPath(post.link)"
              v-html="post.title.rendered"
            ></router-link>
          </h2>
          <p v-if="post.excerpt?.rendered" class="post-card__excerpt" v-html="post.excerpt.rendered"></p>
        </div>

        <!-- Cover: right side -->
        <div v-if="post.featuredImage" class="post-card__cover-wrap">
          <router-link
            :to="toInternalPath(post.link)"
            :aria-label="post.title.rendered"
            class="post-card__cover-link"
          >
            <img :src="post.featuredImage" alt="" loading="lazy" class="post-card__cover" />
          </router-link>
        </div>

        <!-- Meta badge — top-right overlay -->
        <div :class="['post-card__meta', { 'post-card__meta--bare': !post.featuredImage }]">
          <time :datetime="post.date" class="post-card__meta-item">{{ formatDate(post.date) }}</time>
          <span v-if="post.readingTime" class="post-card__meta-item">{{ post.readingTime }} 分钟</span>
          <span v-if="post.viewCount !== undefined" class="post-card__meta-item">{{ post.viewCount }} 热度</span>
        </div>
      </article>
    </div>
  </section>
</template>
