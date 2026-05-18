<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { fetchLinks, fetchPage } from '@/lib/wordpress'
import { showError } from '@/lib/toast'
import { useContentEnhancer } from '@/composables/useContentEnhancer'
import { useSiteShell } from '@/composables/useSiteShell'
import { withCache } from '@/lib/api-cache'
import CommentsPanel from '@/components/CommentsPanel.vue'
import LinkCard from '@/components/links/LinkCard.vue'
import type { WordPressLinkCategory, WordPressPost } from '@/types/wordpress'
import ErrorView from '@/components/ErrorView.vue'

const { siteInfo } = useSiteShell()

const linkCategories = ref<WordPressLinkCategory[]>([])
const loading = ref(true)
const linksPage = ref<WordPressPost | null>(null)
const linksPageContent = computed(() => linksPage.value?.content?.rendered ?? null)
useContentEnhancer(linksPageContent)

onMounted(async () => {
  try {
    const [data, page] = await Promise.all([
      withCache(fetchLinks, 'links')(),
      withCache(fetchPage, 'page:links')('links').catch(() => null),
    ])
    linkCategories.value = data
    linksPage.value = page
  } catch (err) {
    showError(err instanceof Error ? err.message : '友链加载失败')
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="links-page">
    <!-- 页面标题 -->
    <header class="section-header">
      <h1>
        <span class="section-header__title">友人帐</span>
        <span class="section-header__subtitle">Links.</span>
      </h1>
    </header>

    <!-- 加载骨架 -->
    <div v-if="loading" class="links-loading">
      <div v-for="i in 3" :key="i" class="link-card-skeleton">
        <div class="skeleton-avatar"></div>
        <div class="skeleton-body">
          <div class="skeleton-line w-50"></div>
          <div class="skeleton-line w-70"></div>
          <div class="skeleton-line w-40"></div>
        </div>
      </div>
    </div>

    <!-- 友链列表 -->
    <template v-else-if="linkCategories.length > 0">
      <div class="content-area">
        <section
          v-for="category in linkCategories"
          :key="category.id"
          class="link-category"
        >
          <!-- 分类标题 -->
          <header class="category-header">
            <h2 class="category-name">{{ category.name }}</h2>
            <span v-if="category.description" class="category-desc">
              / {{ category.description }}
            </span>
          </header>

          <!-- 链接卡片网格 -->
          <div class="link-grid">
            <LinkCard
              v-for="link in category.links"
              :key="link.id"
              :link="link"
            />
          </div>
        </section>

        <!-- 友链页面内容 -->
        <section v-if="linksPage" class="links-content oat-prose">
          <div v-html="linksPage.content?.rendered"></div>
        </section>

        <!-- 评论区 -->
        <div v-if="linksPage" class="comments-section">
          <CommentsPanel
            :post-id="linksPage.id"
            :enabled="'open' === (linksPage.comment_status || 'closed')"
            :form-settings="siteInfo.comments!"
          />
        </div>
      </div>
    </template>

    <!-- 空状态 -->
    <ErrorView
      v-else
      illustration="add-friends"
      title="还没有友链"
      description="还没有友链，稍后回来看看吧。"
    />
  </div>
</template>

<style scoped>
/* ============ Page Layout ============ */
.links-page {
  --anim-ease-enter: cubic-bezier(0.16, 1, 0.3, 1);
  --anim-ease-hover: cubic-bezier(0.34, 1.56, 0.64, 1);
  --anim-duration-enter: 0.5s;
  --anim-duration-hover: 0.35s;
  padding: 25px;
}

@keyframes slideIn {
  from { opacity: 0; transform: translateX(-24px); }
  to { opacity: 1; transform: translateX(0); }
}

.content-area .section-header,
.content-area .link-category,
.content-area .link-card,
.content-area .comments-section {
  animation: none;
  opacity: 1;
  transform: none;
}

.section-header {
  animation: slideIn var(--anim-duration-enter) var(--anim-ease-enter) both;
}

.link-card-skeleton {
  animation: slideIn var(--anim-duration-enter) var(--anim-ease-enter) both;
}
.link-card-skeleton:nth-child(1) { animation-delay: 0.06s; }
.link-card-skeleton:nth-child(2) { animation-delay: 0.11s; }
.link-card-skeleton:nth-child(3) { animation-delay: 0.16s; }

/* ============ Loading Skeleton ============ */
.links-loading {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.link-card-skeleton {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: var(--card);
  border-radius: var(--radius-large, 8px);
  border: 1px solid var(--border, transparent);
}

.skeleton-avatar {
  width: 3rem;
  height: 3rem;
  border-radius: 50%;
  background: var(--muted);
  animation: pulse 1.5s ease-in-out infinite;
  flex-shrink: 0;
}

.skeleton-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.skeleton-line {
  height: 0.75rem;
  border-radius: var(--radius-small, 4px);
  background: var(--muted);
  animation: pulse 1.5s ease-in-out infinite;
}

.w-50 { width: 50%; }
.w-70 { width: 70%; }
.w-40 { width: 40%; }

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.4; }
}

/* ============ Category Section ============ */
.link-category {
  margin-bottom: 2.5rem;
}

.category-header {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
  margin-bottom: 1.25rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid var(--muted);
}

.category-name {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--foreground);
  margin: 0;
}

.category-desc {
  font-size: 0.875rem;
  color: var(--secondary);
  font-weight: 400;
}

/* ============ Link Grid ============ */
.link-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 0.75rem;
}

/* ============ Links Page Content ============ */
.links-content {
  margin-top: 3rem;
}

/* ============ Comments Section ============ */
.comments-section {
  margin-top: 2.5rem;
}

/* ============ Responsive ============ */
@media (max-width: 640px) {
  .link-grid {
    grid-template-columns: 1fr;
  }
}
</style>
