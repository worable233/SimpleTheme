<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useHead } from '@unhead/vue'
import { useSiteShell } from '@/composables/useSiteShell'
import { fetchPage, getErrorMessage } from '@/lib/wordpress'
import { useContentEnhancer } from '@/composables/useContentEnhancer'
import type { WordPressPost } from '@/types/wordpress'
import ErrorView from '@/components/ErrorView.vue'
import { StCard, StStack, StSkeleton, useToast } from '@/ui'
import { BlockContent } from '@/blocks'

const { siteInfo } = useSiteShell()
const toast = useToast()

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
    toast.error(errorMessage.value)
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="about-page">
    <StCard v-if="loading">
      <StStack gap="2">
        <StSkeleton />
        <StSkeleton />
        <StSkeleton width="60%" />
      </StStack>
    </StCard>

    <template v-else-if="aboutPage">
      <div class="content-area">
        <BlockContent class="prose-content" :html="aboutPage.content?.rendered" />
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
  padding: 25px;
}
</style>
