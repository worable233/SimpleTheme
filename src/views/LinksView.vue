<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useHead } from '@unhead/vue'
import { fetchLinks, fetchPage, getErrorMessage } from '@/lib/wordpress'
import { showError } from '@/lib/toast'
import { useContentEnhancer } from '@/composables/useContentEnhancer'
import { useSiteShell } from '@/composables/useSiteShell'
import { withCache } from '@/lib/api-cache'
import CommentsPanel from '@/components/CommentsPanel.vue'
import LinkCard from '@/components/links/LinkCard.vue'
import type { WordPressLinkCategory, WordPressPost } from '@/types/wordpress'
import ErrorView from '@/components/ErrorView.vue'

const { siteInfo } = useSiteShell()

useHead({ title: '友人帐' })

const linkCategories = ref<WordPressLinkCategory[]>([])
const loading = ref(true)
const errorMessage = ref('')
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
    errorMessage.value = getErrorMessage(err, '友链加载失败')
    showError(errorMessage.value)
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
    <div v-if="loading" class="links-skeleton">
      <div v-for="c in 2" :key="'cat-' + c" class="sk-category">
        <div class="sk-category-header">
          <span role="status" class="skeleton" style="width: 80px; height: 22px; border-radius: 6px;"></span>
          <span role="status" class="skeleton" style="width: 100px; height: 16px; border-radius: 6px;"></span>
        </div>
        <div class="link-grid">
          <div v-for="i in 4" :key="'card-' + c + '-' + i" class="sk-link-card">
            <div class="link-card__inner">
              <span role="status" class="skeleton" style="width: 2.5rem; height: 2.5rem; border-radius: 50%; flex-shrink: 0;"></span>
              <span role="status" class="skeleton" style="flex: 1; height: 16px; border-radius: 6px;"></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 加载失败 -->
    <ErrorView
      v-else-if="errorMessage"
      illustration="warning"
      title="友链加载失败"
      :description="errorMessage"
    />

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
        <section v-if="linksPage" class="links-content prose-content">
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

.sk-link-card {
  animation: slideIn var(--anim-duration-enter) var(--anim-ease-enter) both;
}
.sk-category:nth-child(1) .sk-link-card:nth-child(1) { animation-delay: 0.04s; }
.sk-category:nth-child(1) .sk-link-card:nth-child(2) { animation-delay: 0.08s; }
.sk-category:nth-child(1) .sk-link-card:nth-child(3) { animation-delay: 0.12s; }
.sk-category:nth-child(1) .sk-link-card:nth-child(4) { animation-delay: 0.16s; }
.sk-category:nth-child(2) .sk-link-card:nth-child(1) { animation-delay: 0.14s; }
.sk-category:nth-child(2) .sk-link-card:nth-child(2) { animation-delay: 0.18s; }
.sk-category:nth-child(2) .sk-link-card:nth-child(3) { animation-delay: 0.22s; }
.sk-category:nth-child(2) .sk-link-card:nth-child(4) { animation-delay: 0.26s; }

.links-skeleton {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.sk-category {
  animation: slideIn var(--anim-duration-enter) var(--anim-ease-enter) both;
}
.sk-category:nth-child(1) { animation-delay: 0s; }
.sk-category:nth-child(2) { animation-delay: 0.1s; }

.sk-category-header {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
  margin-bottom: 1.25rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid var(--muted);
}

.sk-link-card {
  background: var(--card);
  border-radius: var(--radius-large, 8px);
  border: 1px solid var(--border, transparent);
  overflow: hidden;
}
.sk-link-card .link-card__inner {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem;
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
