<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useSiteShell } from '@/composables/useSiteShell'
import { fetchPage, getErrorMessage } from '@/lib/wordpress'
import { showError } from '@/lib/toast'
import { useContentEnhancer } from '@/composables/useContentEnhancer'
import type { WordPressPost } from '@/types/wordpress'

const { siteInfo } = useSiteShell()

const aboutPage = ref<WordPressPost | null>(null)
const loading = ref(true)
const aboutContent = computed(() => aboutPage.value?.content?.rendered ?? null)
useContentEnhancer(aboutContent)

onMounted(async () => {
  try {
    aboutPage.value = await fetchPage('about')
  } catch (err) {
    showError(err instanceof Error ? err.message : '关于页面加载失败')
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="about-page">
    <div v-if="loading" style="display: flex; flex-direction: column; gap: var(--space-3);">
      <div style="padding: var(--space-6); background: var(--card); border-radius: var(--radius-large, 12px); border: 1px solid var(--border);">
        <div style="display: flex; flex-direction: column; gap: var(--space-2);">
          <div role="status" class="skeleton line"></div>
          <div role="status" class="skeleton line"></div>
          <div role="status" class="skeleton line" style="width: 60%;"></div>
        </div>
      </div>
    </div>

    <template v-else-if="aboutPage">
      <div class="content-area">
        <article class="oat-prose" v-html="aboutPage.content?.rendered"></article>
      </div>
    </template>

    <template v-else>
      <div class="content-area">
        <article class="oat-prose">
          <div class="about-page__fallback">
            <h2>{{ siteInfo.name ? `欢迎来到 ${siteInfo.name}` : '关于本站' }}</h2>
            <p>这是一个基于 WordPress 构建的博客网站。</p>
            <h3>关于本站</h3>
            <p>在这里分享技术文章、生活感悟和其他有趣的内容。</p>
            <h3>联系方式</h3>
            <p>如有任何问题或建议，欢迎通过以下方式联系：</p>
          </div>
        </article>
      </div>
    </template>
  </div>
</template>

<style scoped>
.about-page {
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
</style>
