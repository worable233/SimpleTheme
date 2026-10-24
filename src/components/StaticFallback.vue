<script setup lang="ts">
/**
 * StaticFallback — 渲染服务端预渲染的静态内容作为兜底。
 *
 * 仅在视图初始数据加载失败、且用户仍处于首屏原始 URL 时展示（由调用方判断）。
 * 内容来自 #st-static（cf-* 结构），这里补上与主题变量匹配的样式，
 * 并隐藏与 SPA 布局重复的 header/footer。
 */
import { onMounted, ref } from 'vue'

defineOptions({ name: 'StaticFallback' })

defineProps<{ html: string }>()

const root = ref<HTMLElement | null>(null)

/**
 * 存档里的图片沿用服务端输出的 loading="lazy"，但它们是刚注入的新节点，
 * 在兜底页面里常常停在空白、用户以为「图片没缓存」。兜底只在出故障时出现，
 * 直接全部改成 eager，让它们立刻加载（命中 SW 的 uploads 缓存）。
 */
onMounted(() => {
  const images = root.value?.querySelectorAll<HTMLImageElement>('img') ?? []
  images.forEach((img) => {
    img.loading = 'eager'
    img.decoding = 'async'
    const lazySrc = img.getAttribute('data-src') || img.getAttribute('data-lazy-src')
    if (lazySrc && !img.getAttribute('src')) img.src = lazySrc
  })
})
</script>

<template>
  <section class="st-fallback">
    <!-- 提示由全局 <ConnectionBanner /> 承担，这里只负责内容本身 -->
    <!-- eslint-disable-next-line vue/no-v-html -->
    <!-- 存档用的是服务端 HTML：列表区已改成与 Vue 相同的 .post-card 结构，
         正文区由 PHP 输出 .prose-content，所以这里不需要再套排版类。 -->
    <div ref="root" class="st-fallback__body" v-html="html"></div>
  </section>
</template>

<!-- 非 scoped：v-html 注入的 cf-* 内容拿不到 scoped 属性，必须用全局选择器并加前缀限定 -->
<style>
.st-fallback {
  width: 100%;
  /* 与 .single-post__body 一致的内边距，避免在窄视口下文字贴到屏幕边缘 */
  padding: 0.5rem 25px 2rem;
}

/* 单篇的封面/页头自己带 25px 内边距，外层再来一层会让封面缩进、不再通栏 */
.st-fallback:has(.single-post) {
  padding: 0 0 2rem;
}

/* 与 SPA 布局重复的部分隐藏 */
.st-fallback .cf-header,
.st-fallback .cf-footer {
  display: none;
}

.st-fallback .cf-page-title {
  margin: 0 0 1.5rem;
  font-size: 1.5rem;
  font-weight: 650;
  color: var(--foreground);
}

.st-fallback .cf-posts {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.st-fallback .cf-post {
  padding-bottom: 1.25rem;
  border-bottom: 1px solid var(--border);
}

.st-fallback .cf-post:last-child {
  border-bottom: 0;
}

.st-fallback .cf-post__title {
  margin: 0 0 0.375rem;
  font-size: 1.125rem;
  font-weight: 600;
  line-height: 1.4;
}

.st-fallback .cf-post__title a,
.st-fallback .cf-single__body a {
  color: var(--primary, var(--foreground));
  text-decoration: none;
}

.st-fallback .cf-post__title a:hover,
.st-fallback .cf-single__body a:hover {
  text-decoration: underline;
}

.st-fallback .cf-post__meta,
.st-fallback .cf-single__meta {
  margin-bottom: 0.5rem;
  font-size: 0.8125rem;
  color: var(--secondary);
}

.st-fallback .cf-post__meta a,
.st-fallback .cf-single__meta a {
  color: var(--secondary);
  text-decoration: none;
}

.st-fallback .cf-post__excerpt {
  font-size: 0.9375rem;
  line-height: 1.7;
  color: var(--foreground);
}

.st-fallback .cf-single__title {
  margin: 0 0 0.75rem;
  font-size: 1.75rem;
  font-weight: 700;
  line-height: 1.3;
  color: var(--foreground);
}

.st-fallback .cf-single__body {
  font-size: 1rem;
  line-height: 1.8;
  color: var(--foreground);
}

.st-fallback .cf-single__body img {
  max-width: 100%;
  height: auto;
}

.st-fallback .cf-single__body pre {
  padding: 1em;
  overflow-x: auto;
  border-radius: var(--radius-medium, 8px);
  background: var(--muted);
  font-size: 0.875em;
}

.st-fallback .cf-term-desc {
  margin-bottom: 1.25rem;
  color: var(--secondary);
}

.st-fallback .cf-pagination {
  display: flex;
  gap: 1rem;
  margin-top: 1.5rem;
  font-size: 0.9375rem;
}

/* 分类筛选条：存档里的筛选链接要长得和 Vue 的 StButton(small/round) 一样。
   StButton 的样式是 scoped 的，注入的 HTML 拿不到，只能在这里补一份。 */
.st-fallback .home-content__filters {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
}

.st-fallback .home-content__filters a {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.75rem;
  border: 1px solid var(--border);
  border-radius: var(--radius-full, 9999px);
  background-color: transparent;
  color: var(--secondary);
  font-size: 0.75rem;
  line-height: 1.6;
  text-decoration: none;
  transition: color 0.15s ease, border-color 0.15s ease;
}

.st-fallback .home-content__filters a:hover {
  color: var(--foreground);
  border-color: var(--muted-foreground);
}

.st-fallback .home-content__filters a:first-child {
  border-color: transparent;
  background-color: var(--foreground);
  color: var(--background);
}

/* ==================== 单篇：对齐 ContentView 的 scoped 样式 ====================
   这些规则在 Vue 里是写在 ContentView.vue 的 <style scoped> 中的，
   v-html 注入的服务端 HTML 拿不到 scoped 属性，只能在这里补一份。 */
.st-fallback .single-post__cover-meta,
.st-fallback .single-post__header-meta {
  flex-wrap: wrap;
  gap: 0.375rem 1rem;
  font-size: 0.8125rem;
}

.st-fallback .single-post__cover-meta {
  color: rgba(255 255 255 / 0.88);
  text-shadow: 0 1px 6px rgba(0 0 0 / 0.45);
}

.st-fallback .single-post__header-meta {
  color: var(--muted-foreground, #888);
}

.st-fallback .meta-item {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  white-space: nowrap;
}

.st-fallback .meta-item svg {
  width: 1rem;
  height: 1rem;
  flex-shrink: 0;
}

.st-fallback .meta-item a {
  color: inherit;
  text-decoration: none;
}

.st-fallback .meta-item a:hover {
  color: var(--primary);
}

/* 标签胶囊：StTag 同样是 scoped 样式，这里按它的 small + bordered 复刻 */
.st-fallback .single-post__tag-link {
  display: inline-flex;
  text-decoration: none;
  color: var(--secondary);
}

.st-fallback .st-tag {
  display: inline-flex;
  align-items: center;
  box-sizing: border-box;
  height: var(--st-height-small, 28px);
  padding: 0 8px;
  border: 1px solid var(--border);
  border-radius: var(--radius-small, 4px);
  background-color: var(--faint, var(--muted));
  color: var(--muted-foreground, var(--secondary));
  font-size: var(--st-font-small, 13px);
  line-height: 1;
  white-space: nowrap;
}

.st-fallback .single-post__tag-link:hover .st-tag {
  color: var(--primary);
  border-color: color-mix(in srgb, var(--primary) 35%, var(--border));
}

.st-fallback .cf-pagination a {
  color: var(--primary, var(--foreground));
  text-decoration: none;
}

.st-fallback .cf-empty {
  color: var(--secondary);
  font-style: italic;
}
</style>
