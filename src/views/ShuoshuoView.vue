<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useSiteShell } from '@/composables/useSiteShell'
import { fetchCollection, getErrorMessage } from '@/lib/wordpress'
import { toInternalPath, getThemeConfig } from '@/lib/theme-config'
import { showError } from '@/lib/toast'
import type { WordPressPost } from '@/types/wordpress'
import ErrorView from '@/components/ErrorView.vue'

const { siteInfo, ensureLoaded } = useSiteShell()

const pageSize = computed(() => siteInfo.value.collections?.shuoshuoPageSize ?? 12)
const sectionTitle = computed(() => siteInfo.value.collections?.shuoshuoTitle || '说说')
const sectionSubtitle = computed(() => siteInfo.value.collections?.shuoshuoSubtitle || 'Shuoshuo.')

const metaConfig = computed(() => getThemeConfig().features?.meta)

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
    await ensureLoaded()
    const response = await fetchCollection('shuoshuo', {
      limit: pageSize.value,
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
        <span class="section-header__title">{{ sectionTitle }}</span>
        <span class="section-header__subtitle">{{ sectionSubtitle }}</span>
      </h1>
    </header>

    <!-- Loading skeleton -->
    <div v-if="loading" class="post-list">
      <div v-for="i in Math.min(pageSize, 8)" :key="'sk-' + i" class="post-card-skeleton">
        <div class="post-card-skeleton__text">
          <div style="height: 1.125rem; width: 70%; margin-bottom: 0.5rem; border-radius: 4px; background: var(--muted); animation: pulse 1.5s ease-in-out infinite;"></div>
          <div style="height: 0.75rem; width: 100%; margin-bottom: 0.5rem; border-radius: 4px; background: var(--muted); animation: pulse 1.5s ease-in-out infinite;"></div>
          <div style="height: 0.75rem; width: 65%; margin-bottom: 0.75rem; border-radius: 4px; background: var(--muted); animation: pulse 1.5s ease-in-out infinite;"></div>
        </div>
        <div class="post-card-skeleton__cover"></div>
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

    <div v-else class="post-list">
      <article v-for="post in items" :key="post.id" class="post-card">
        <!-- Cover image — absolute positioned right overlay -->
        <img
          v-if="post.featuredImage"
          :src="post.featuredImage"
          :alt="post.title.rendered"
          class="post-card__cover"
          loading="lazy"
        />
        <!-- Meta badge -->
        <div class="post-card__meta">
          <span class="post-card__meta-item">说说</span>
          <time v-if="metaConfig?.showPublishDate" class="post-card__meta-item" :datetime="post.date">{{ formatDate(post.date) }}</time>
          <span v-if="metaConfig?.showCommentCount && post.commentCount !== undefined" class="post-card__meta-item">{{ post.commentCount }} 评论</span>
          <span v-if="metaConfig?.showViewCount && post.viewCount !== undefined" class="post-card__meta-item">{{ post.viewCount }} 热度</span>
        </div>
        <!-- Text content -->
        <div class="post-card__body">
          <h2 class="post-card__title">
            <RouterLink :to="toInternalPath(post.link)" v-html="post.title.rendered" />
          </h2>
          <div class="post-card__content" v-html="post.content?.rendered" />
        </div>
      </article>
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

.section-header {
  animation: slideIn var(--anim-duration-enter) var(--anim-ease-enter) both;
}

/* Staggered slide-in for cards */
.post-card {
  animation: slideIn var(--anim-duration-enter) var(--anim-ease-enter) both;
}
.post-card:nth-child(1) { animation-delay: 0.06s; }
.post-card:nth-child(2) { animation-delay: 0.11s; }
.post-card:nth-child(3) { animation-delay: 0.16s; }
.post-card:nth-child(4) { animation-delay: 0.21s; }
.post-card:nth-child(5) { animation-delay: 0.26s; }
.post-card:nth-child(6) { animation-delay: 0.31s; }
.post-card:nth-child(7) { animation-delay: 0.36s; }
.post-card:nth-child(8) { animation-delay: 0.41s; }



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

/* ============ Responsive ============ */
@media (max-width: 640px) {
  .shuoshuo-page {
    padding: 1rem;
  }
}

/* Responsive skeleton — stack vertically on mobile */
@media (max-width: 600px) {
  .post-card-skeleton {
    flex-direction: column;
    min-height: auto;
  }
  .post-card-skeleton__text {
    padding: 1rem;
    gap: 0.5rem;
  }
  .post-card-skeleton__cover {
    width: 100%;
    height: 140px;
    min-height: auto;
    flex-shrink: 0;
  }
}
</style>
