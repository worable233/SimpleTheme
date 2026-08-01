<script setup lang="ts">
/**
 * StaticFallback — 渲染服务端预渲染的静态内容作为兜底。
 *
 * 仅在视图初始数据加载失败、且用户仍处于首屏原始 URL 时展示（由调用方判断）。
 * 内容来自 #st-static（cf-* 结构），这里补上与主题变量匹配的样式，
 * 并隐藏与 SPA 布局重复的 header/footer。
 */
defineOptions({ name: 'StaticFallback' })

defineProps<{ html: string }>()
</script>

<template>
  <section class="st-fallback">
    <p class="st-fallback__notice">实时内容加载失败，以下为静态存档版本。</p>
    <!-- eslint-disable-next-line vue/no-v-html -->
    <div class="st-fallback__body" v-html="html"></div>
  </section>
</template>

<!-- 非 scoped：v-html 注入的 cf-* 内容拿不到 scoped 属性，必须用全局选择器并加前缀限定 -->
<style>
.st-fallback {
  width: 100%;
  padding: 0.5rem 0 2rem;
}

.st-fallback__notice {
  margin: 0 0 1.5rem;
  padding: 0.625rem 0.875rem;
  border: 1px solid var(--border);
  border-radius: var(--radius-medium, 8px);
  background: var(--card);
  color: var(--secondary);
  font-size: 0.8125rem;
  line-height: 1.5;
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

.st-fallback .cf-pagination a {
  color: var(--primary, var(--foreground));
  text-decoration: none;
}

.st-fallback .cf-empty {
  color: var(--secondary);
  font-style: italic;
}
</style>
