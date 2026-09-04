<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useHead } from '@unhead/vue'
import { RouterLink } from 'vue-router'
import { useSiteShell } from '@/composables/useSiteShell'
import { fetchCollection, getErrorMessage } from '@/lib/wordpress'
import { toInternalPath, getThemeConfig } from '@/lib/theme-config'
import { rememberPreviews } from '@/lib/content-preview'
import { useSkeletonSize } from '@/composables/useSkeletonSize'
import { showError } from '@/lib/toast'
import type { WordPressPost } from '@/types/wordpress'
import ErrorView from '@/components/ErrorView.vue'

const { siteInfo, ensureLoaded } = useSiteShell()

const pageSize = computed(() => siteInfo.value.collections?.shuoshuoPageSize ?? 12)
const sectionTitle = computed(() => siteInfo.value.collections?.shuoshuoTitle || '说说')
const sectionSubtitle = computed(() => siteInfo.value.collections?.shuoshuoSubtitle || 'Shuoshuo.')

useHead({ title: sectionTitle })

const metaConfig = computed(() => getThemeConfig().features?.meta)

const items = ref<WordPressPost[]>([])
const loading = ref(true)
const errorMessage = ref('')

// 骨架尺寸记忆：复用上次真实卡片的高度/形态
const { size: cardSize, measure: measureCard } = useSkeletonSize('st_sk_shuoshuo_card')
const skeletonStyle = computed(() =>
  cardSize.value ? { height: cardSize.value.h + 'px', minHeight: cardSize.value.h + 'px' } : undefined,
)

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
    // 预览缓存 + 骨架尺寸测量
    rememberPreviews(response.items)
    void measureCard('.shuoshuo-card', '.shuoshuo-card__cover')
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
    <div v-if="loading" class="shuoshuo-list">
      <div v-for="i in Math.min(pageSize, 8)" :key="'sk-' + i" class="shuoshuo-card-skeleton" :style="skeletonStyle">
        <div class="shuoshuo-card-skeleton__text">
          <div class="sk-line" style="height: 1.125rem; width: 70%;"></div>
          <div class="sk-line" style="height: 0.75rem; width: 100%;"></div>
          <div class="sk-line" style="height: 0.75rem; width: 65%;"></div>
        </div>
        <div v-if="cardSize?.cover !== false" class="shuoshuo-card-skeleton__cover"></div>
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

    <div v-else class="shuoshuo-list">
      <article v-for="post in items" :key="post.id" class="shuoshuo-card">
        <!-- Cover image — absolute positioned right overlay -->
        <img
          v-if="post.featuredImage"
          :src="post.featuredImage"
          :alt="post.title.rendered"
          class="shuoshuo-card__cover"
          loading="lazy"
        />
        <!-- Meta badge -->
        <div class="shuoshuo-card__meta">
          <span class="shuoshuo-card__meta-item">说说</span>
          <time v-if="metaConfig?.showPublishDate" class="shuoshuo-card__meta-item" :datetime="post.date">{{ formatDate(post.date) }}</time>
          <span v-if="metaConfig?.showCommentCount && post.commentCount !== undefined" class="shuoshuo-card__meta-item">{{ post.commentCount }} 评论</span>
          <span v-if="metaConfig?.showViewCount && post.viewCount !== undefined" class="shuoshuo-card__meta-item">{{ post.viewCount }} 热度</span>
        </div>
        <!-- Text content -->
        <div class="shuoshuo-card__body">
          <h2 class="shuoshuo-card__title">
            <RouterLink :to="toInternalPath(post.link)"><span v-html="post.title.rendered" /></RouterLink>
          </h2>
          <!-- Smooth expand content on hover -->
          <div class="shuoshuo-card__expand">
            <div class="shuoshuo-card__content" v-html="post.content?.rendered" />
          </div>
        </div>
      </article>
    </div>
  </div>
</template>

<style scoped>
/* ============ Animation ============ */
.shuoshuo-page {
  --anim-ease-expand: cubic-bezier(0.4, 0, 0.2, 1);
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

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.4; }
}

.section-header {
  animation: slideIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
}

/* ============ List container ============ */
.shuoshuo-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* ============ Card ============ */
.shuoshuo-card {
  position: relative;
  container-type: inline-size;
  background: var(--card);
  box-shadow: var(--card-highlight);
  border-radius: var(--radius-large, 12px);
  border: 1px solid var(--border);
  overflow: hidden;
  transition: all 0.35s var(--anim-ease-expand);
  animation: slideIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.shuoshuo-card:nth-child(1) { animation-delay: 0.06s; }
.shuoshuo-card:nth-child(2) { animation-delay: 0.11s; }
.shuoshuo-card:nth-child(3) { animation-delay: 0.16s; }
.shuoshuo-card:nth-child(4) { animation-delay: 0.21s; }
.shuoshuo-card:nth-child(5) { animation-delay: 0.26s; }
.shuoshuo-card:nth-child(6) { animation-delay: 0.31s; }
.shuoshuo-card:nth-child(7) { animation-delay: 0.36s; }
.shuoshuo-card:nth-child(8) { animation-delay: 0.41s; }

/* Stretched link: make the entire card clickable via the title link's pseudo-element */
.shuoshuo-card__title a::after {
  content: '';
  position: absolute;
  inset: 0;
  z-index: 1;
}

/* ---- Hover: card lift + border glow ---- */
.shuoshuo-card:hover {
  border-color: var(--primary);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08), var(--card-highlight);
  transform: translateY(-2px);
}

body[data-theme='dark'] .shuoshuo-card:hover {
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25), var(--card-highlight);
}

/* ---- Hover: cover image zoom ---- */
.shuoshuo-card__cover {
  position: absolute;
  inset-inline-end: 0;
  top: 0;
  width: calc(40% + 2em);
  height: 100%;
  object-fit: cover;
  object-position: center;
  display: block;
  opacity: 0.8;
  transition: opacity 0.35s var(--anim-ease-expand), transform 0.35s var(--anim-ease-expand);
  mask-image: linear-gradient(to right, transparent 0, #000 40px);
  -webkit-mask-image: linear-gradient(to right, transparent 0, #000 40px);
  pointer-events: none;
  z-index: 0;
}

.shuoshuo-card:hover .shuoshuo-card__cover {
  opacity: 1;
  transform: scale(1.04);
}

/* ============ Body / always visible ============ */
.shuoshuo-card__body {
  position: relative;
  padding: 1.5rem 1.75rem;
  width: 60%;
  min-height: 130px;
  z-index: 1;
}

.shuoshuo-card__title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 650;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.shuoshuo-card__title a {
  color: var(--foreground);
  text-decoration: none;
  transition: color 0.2s ease;
}

.shuoshuo-card__title a:hover {
  color: var(--primary);
}

/* ============ Expandable content (hidden by default, revealed on hover) ============ */
.shuoshuo-card__expand {
  max-height: 0;
  opacity: 0;
  overflow: hidden;
  transition:
    max-height 0.35s var(--anim-ease-expand),
    opacity 0.35s var(--anim-ease-expand),
    margin 0.35s var(--anim-ease-expand);
  transition-delay: 0ms;
  margin-top: 0;
}

.shuoshuo-card:hover .shuoshuo-card__expand {
  max-height: 600px;
  opacity: 1;
  transition-delay: 180ms;
  margin-top: 0.75rem;
}

/* Full content within expand area */
.shuoshuo-card__content {
  font-size: 0.9375rem;
  line-height: 1.8;
  color: var(--foreground);
}

.shuoshuo-card__content :deep(p) {
  margin: 0.5em 0;
}

.shuoshuo-card__content :deep(img) {
  max-width: 100%;
  height: auto;
  border-radius: var(--radius-medium, 8px);
  margin: 0.75em 0;
}

/* ============ Meta badge (top-right) ============ */
.shuoshuo-card__meta {
  position: absolute;
  top: 8px;
  right: 8px;
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 3px 8px;
  background: rgba(255, 255, 255, 0.5);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: none;
  border-radius: var(--radius-medium, 8px);
  font-size: 0.6875rem;
  color: var(--foreground);
  line-height: 1.6;
  pointer-events: none;
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.shuoshuo-card:not(:has(.shuoshuo-card__cover)) .shuoshuo-card__meta {
  background: none;
  backdrop-filter: none;
  -webkit-backdrop-filter: none;
  box-shadow: none;
  border: none;
}

.shuoshuo-card__meta-item {
  white-space: nowrap;
}

body[data-theme='dark'] .shuoshuo-card__meta {
  background: rgba(0, 0, 0, 0.35);
  box-shadow: inset 0 1px 0 0 #fff3, 0 0 10px rgba(0, 0, 0, 0.1);
}

/* ============ Skeleton ============ */
.shuoshuo-card-skeleton {
  display: flex;
  background: var(--card);
  box-shadow: var(--card-highlight);
  border-radius: var(--radius-large, 12px);
  border: 1px solid var(--border);
  overflow: hidden;
  min-height: 130px;
}

.shuoshuo-card-skeleton__text {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 0.75rem;
  padding: 1.5rem 1.75rem;
}

.sk-line {
  border-radius: 4px;
  background: var(--muted);
  animation: pulse 1.5s ease-in-out infinite;
}

.shuoshuo-card-skeleton__cover {
  width: calc(40% + 2em);
  min-height: 130px;
  flex-shrink: 0;
  background: var(--muted);
}

/* ============ Container query: narrow card ============ */
@container (max-width: 528px) {
  .shuoshuo-card__cover {
    position: revert;
    width: 100%;
    max-height: 256px;
    aspect-ratio: 2.4;
    mask-image: none;
    -webkit-mask-image: none;
    opacity: 1;
  }

  .shuoshuo-card__body {
    width: auto;
    padding: 1rem 1.25rem;
    min-height: auto;
  }

  .shuoshuo-card__title {
    font-size: 1rem;
  }
}

/* ============ Responsive ============ */
@media (max-width: 640px) {
  .shuoshuo-page {
    padding: 1rem;
  }
}

@media (max-width: 600px) {
  .shuoshuo-card__title {
    font-size: 0.9375rem;
    -webkit-line-clamp: 2;
  }
  .section-header__title {
    font-size: 24px;
  }
  .section-header__subtitle {
    font-size: 12px;
  }
}

@media (max-width: 600px) {
  .shuoshuo-card-skeleton {
    flex-direction: column;
    min-height: auto;
  }
  .shuoshuo-card-skeleton__text {
    padding: 1rem;
    gap: 0.5rem;
  }
  .shuoshuo-card-skeleton__cover {
    width: 100%;
    height: 140px;
    min-height: auto;
  }
}
</style>
