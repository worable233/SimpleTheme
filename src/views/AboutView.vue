<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useHead } from '@unhead/vue'
import { useSiteShell } from '@/composables/useSiteShell'
import { fetchPage, getErrorMessage } from '@/lib/wordpress'
import { showError } from '@/lib/toast'
import { useContentEnhancer } from '@/composables/useContentEnhancer'
import type { WordPressPost } from '@/types/wordpress'
import ErrorView from '@/components/ErrorView.vue'

const { siteInfo } = useSiteShell()

useHead({ title: '关于' })

const aboutPage = ref<WordPressPost | null>(null)
const loading = ref(true)
const errorMessage = ref('')
const aboutContent = computed(() => aboutPage.value?.content?.rendered ?? null)
useContentEnhancer(aboutContent)

onMounted(async () => {
  try {
    aboutPage.value = await fetchPage('about')
  } catch (err) {
    errorMessage.value = getErrorMessage(err, '关于页面加载失败')
    showError(errorMessage.value)
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="about-page">
    <div v-if="loading" class="flex flex-col gap-3">
      <div class="rounded-large border border-border bg-card p-6">
        <div class="flex flex-col gap-2">
          <div role="status" class="skeleton line"></div>
          <div role="status" class="skeleton line"></div>
          <div role="status" class="skeleton line" style="width: 60%;"></div>
        </div>
      </div>
    </div>

    <template v-else-if="aboutPage">
      <div class="content-area">
        <article class="prose-content" v-html="aboutPage.content?.rendered"></article>
      </div>
    </template>

    <ErrorView
      v-else-if="errorMessage"
      illustration="warning"
      title="关于页面加载失败"
      :description="errorMessage"
    />

    <template v-else>
      <div class="content-area">
        <article class="prose-content">
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
</style>
