<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { fetchCollection, getErrorMessage } from '@/lib/wordpress'
import { toInternalPath } from '@/lib/theme-config'
import { showError } from '@/lib/toast'
import type { WordPressPost } from '@/types/wordpress'
import ErrorView from '@/components/ErrorView.vue'

const items = ref<WordPressPost[]>([])
const loading = ref(true)
const errorMessage = ref('')

const formatDate = (dateString: string) =>
  new Intl.DateTimeFormat('zh-CN', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(new Date(dateString))

async function loadShuoshuo() {
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await fetchCollection('shuoshuo', {
      limit: 12,
    })
    items.value = response.items
  } catch (error) {
    errorMessage.value = getErrorMessage(error, '说说内容加载失败，请稍后重试。')
    showError(errorMessage.value)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void loadShuoshuo()
})
</script>

<template>
  <div class="shuoshuo-page">
    <header class="section-header">
      <h1>
        <span class="section-header__title">说说</span>
        <span class="section-header__subtitle">Shuoshuo.</span>
      </h1>
    </header>

    <!-- Loading skeleton -->
    <div v-if="loading" class="content-area">
      <div class="shuoshuo-list">
        <div v-for="item in 4" :key="'sk-' + item" class="shuoshuo-card-skeleton">
          <div class="skeleton-body">
            <div class="skeleton-line w-30"></div>
            <div class="skeleton-line w-70"></div>
            <div class="skeleton-line w-100"></div>
            <div class="skeleton-line w-50"></div>
          </div>
        </div>
      </div>
    </div>

    <ErrorView
      v-else-if="errorMessage"
      illustration="warning"
      title="说说加载失败"
      :description="errorMessage"
    />

    <ErrorView
      v-else-if="items.length === 0"
      illustration="chatting"
      title="还没有说说"
      description="还没有发布说说内容，稍后回来看看吧。"
    />

    <div v-else class="content-area">
      <div class="shuoshuo-list">
        <article v-for="post in items" :key="post.id" class="post-card post-card--stack">
          <!-- Meta badge — top-right overlay -->
          <div :class="['post-card__meta', { 'post-card__meta--bare': !post.featuredImage }]">
            <span class="post-card__meta-item">说说</span>
            <time class="post-card__meta-item" :datetime="post.date">{{ formatDate(post.date) }}</time>
          </div>

          <!-- Cover image — full width, inline order -->
          <div v-if="post.featuredImage" class="post-card__cover-wrap">
            <img
              :src="post.featuredImage"
              :alt="post.title.rendered"
              class="post-card__cover"
              loading="lazy"
            />
          </div>

          <!-- Text content -->
          <div class="post-card__text">
            <h2 class="post-card__title">
              <RouterLink :to="toInternalPath(post.link)" v-html="post.title.rendered" />
            </h2>
            <div class="post-card__content" v-html="post.content?.rendered" />
          </div>
        </article>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ============ Animation ============ */
.shuoshuo-page {
  --anim-ease-enter: cubic-bezier(0.16, 1, 0.3, 1);
  --anim-ease-hover: cubic-bezier(0.34, 1.56, 0.64, 1);
  --anim-duration-enter: 0.5s;
  --anim-duration-hover: 0.35s;
  padding: 25px;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(-24px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.content-area .section-header,
.content-area .shuoshuo-list {
  animation: none;
  opacity: 1;
  transform: none;
}

.section-header {
  animation: slideIn var(--anim-duration-enter) var(--anim-ease-enter) both;
}

.shuoshuo-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* Post card — vertical stack variant */
.post-card--stack {
  flex-direction: column;
  min-height: auto;
  animation: slideIn var(--anim-duration-enter) var(--anim-ease-enter) both;
}

.post-card--stack .post-card__cover-wrap {
  width: 100%;
  min-height: auto;
  order: -1; /* show cover above text, below meta which is absolute */
}

.post-card--stack .post-card__text {
  justify-content: flex-start;
}

.post-card--stack:nth-child(1) { animation-delay: 0.06s; }
.post-card--stack:nth-child(2) { animation-delay: 0.11s; }
.post-card--stack:nth-child(3) { animation-delay: 0.16s; }
.post-card--stack:nth-child(4) { animation-delay: 0.21s; }

/* Full content within text area */
.post-card__content {
  font-size: 0.9375rem;
  line-height: 1.8;
  color: var(--foreground);
  margin-top: 0.75rem;
}

.post-card__content :deep(p) {
  margin: 0.5em 0;
}

.post-card__content :deep(img) {
  max-width: 100%;
  height: auto;
  border-radius: var(--radius-medium, 8px);
  margin: 0.75em 0;
}

/* ============ Loading Skeleton ============ */
.shuoshuo-card-skeleton {
  padding: 1.25rem;
  background: var(--card);
  border-radius: var(--radius-large, 12px);
  border: 1px solid var(--border);
}

.skeleton-body {
  display: flex;
  flex-direction: column;
  gap: 0.625rem;
}

.skeleton-line {
  height: 0.75rem;
  border-radius: var(--radius-small, 4px);
  background: var(--muted);
  animation: pulse 1.5s ease-in-out infinite;
}

.w-30 { width: 30%; }
.w-50 { width: 50%; }
.w-70 { width: 70%; }
.w-100 { width: 100%; }

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.4; }
}

/* ============ Responsive ============ */
@media (max-width: 640px) {
  .shuoshuo-page {
    padding: 1rem;
  }

  .post-card--stack .post-card__cover-wrap {
    max-height: 180px;
  }
}
</style>
