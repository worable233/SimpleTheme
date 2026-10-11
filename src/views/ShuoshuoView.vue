<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useHead } from '@unhead/vue'
import { RouterLink } from 'vue-router'
import { useSiteShell } from '@/composables/useSiteShell'
import { fetchCollection, getErrorMessage } from '@/lib/wordpress'
import { toInternalPath, getThemeConfig } from '@/lib/theme-config'
import { rememberPreviews } from '@/lib/content-preview'
import { useSkeletonSize } from '@/composables/useSkeletonSize'
import type { WordPressPost } from '@/types/wordpress'
import ErrorView from '@/components/ErrorView.vue'
import { StSkeleton, useToast } from '@/ui'

const { siteInfo, ensureLoaded } = useSiteShell()
const toast = useToast()

const pageSize = computed(() => siteInfo.value.collections?.shuoshuoPageSize ?? 12)
// 说说页标题/副标题为固定页面文案，不开放后台配置。
const sectionTitle = '最近说说'
const sectionSubtitle = 'Shuoshuo.'

useHead({ title: sectionTitle })

const metaConfig = computed(() => getThemeConfig().features?.meta)

const items = ref<WordPressPost[]>([])
const loading = ref(true)
const errorMessage = ref('')

// 骨架尺寸记忆：复用上次真实卡片的高度/形态
const { size: cardSize, measure: measureCard } = useSkeletonSize('st_sk_shuoshuo_card')
const skeletonStyle = computed(() =>
  cardSize.value ? { '--sk-h': cardSize.value.h + 'px' } : undefined,
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
    toast.error(errorMessage.value)
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
          <StSkeleton width="70%" height="1.125rem" />
          <StSkeleton width="100%" height="0.75rem" />
          <StSkeleton width="65%" height="0.75rem" />
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
          <!-- 有标题才渲染标题；无标题的说说直接以正文为主体，避免空标题占位 -->
          <h2 v-if="post.title.rendered" class="shuoshuo-card__title">
            <RouterLink :to="toInternalPath(post.link)"><span v-html="post.title.rendered" /></RouterLink>
          </h2>
          <!-- 正文：默认折叠为预览，悬停卡片展开全文 -->
          <div v-if="post.content?.rendered" class="shuoshuo-card__content" v-html="post.content.rendered" />
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

/* ============ List container ============ */
.shuoshuo-list {
  container-type: inline-size;
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
}

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
  display: flex;
  flex-direction: column;
  justify-content: center;
}

/* 无封面时正文占满整宽，避免右侧留下空档 */
.shuoshuo-card:not(:has(.shuoshuo-card__cover)) .shuoshuo-card__body {
  width: 100%;
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

/* ============ 正文预览（默认折叠 3 行，悬停展开全文） ============ */
/* 无标题时正文即卡片主体，故标题存在时才需要上边距 */
.shuoshuo-card__title + .shuoshuo-card__content {
  margin-top: 0.5rem;
}

.shuoshuo-card__content {
  position: relative;
  font-size: 0.9375rem;
  line-height: 1.8;
  color: var(--secondary);
  /* 预览高度 = 2 行；悬停时放开到全文 */
  max-height: 3.6em;
  overflow: hidden;
  transition:
    max-height 0.35s var(--anim-ease-expand),
    color 0.35s var(--anim-ease-expand);
}

/* 底部渐隐，暗示「还有更多」，悬停展开后消失 */
.shuoshuo-card__content::after {
  content: '';
  position: absolute;
  inset-inline: 0;
  bottom: 0;
  height: 1.8em;
  background: linear-gradient(to bottom, transparent, var(--card));
  pointer-events: none;
  transition: opacity 0.35s var(--anim-ease-expand);
}

/* 悬停展开：沿用旧版展开动画的节奏（0.35s ease-expand + 180ms 延迟），
 * 只是起点由「完全隐藏」改为「2 行预览」，保留底部渐隐作为默认态。 */
.shuoshuo-card:hover .shuoshuo-card__content {
  max-height: 1000px;
  color: var(--foreground);
  transition-delay: 180ms;
}

.shuoshuo-card:hover .shuoshuo-card__content::after {
  opacity: 0;
  transition-delay: 180ms;
}

/* 触摸设备没有 hover，折叠的正文将无法展开；直接展示全文。 */
@media (hover: none) {
  .shuoshuo-card__content {
    max-height: none;
    color: var(--foreground);
  }

  .shuoshuo-card__content::after {
    display: none;
  }
}

.shuoshuo-card__content :deep(p) {
  margin: 0.5em 0;
}

.shuoshuo-card__content :deep(p:first-child) {
  margin-top: 0;
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
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

/* ============ Skeleton ============ */
.shuoshuo-card-skeleton {
  display: flex;
  container-type: inline-size;
  background: var(--card);
  box-shadow: var(--card-highlight);
  border-radius: var(--radius-large, 12px);
  border: 1px solid var(--border);
  overflow: hidden;
  min-height: var(--sk-h, 130px);
}

.shuoshuo-card-skeleton__text {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 0.75rem;
  padding: 1.5rem 1.75rem;
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

  /* 骨架同步堆叠，与 .shuoshuo-card 保持同一形态 */
  .shuoshuo-card-skeleton {
    flex-direction: column;
    min-height: auto;
  }

  .shuoshuo-card-skeleton__text {
    padding: 1rem 1.25rem;
    gap: 0.5rem;
  }

  .shuoshuo-card-skeleton__cover {
    width: 100%;
    height: auto;
    aspect-ratio: 2.4;
    max-height: 256px;
    min-height: auto;
  }
}
</style>
