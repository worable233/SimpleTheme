<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import HomeView from '@/views/HomeView.vue'
import ContentView from '@/views/ContentView.vue'
import { getPreviewPayload } from '@/lib/preview'

/**
 * 根路径的视图分发。
 *
 * 开启「固定链接」时首页就是 "/"，其余内容都在独立路径上。但朴素固定
 * 链接（WordPress 默认的 ?p=1 / ?cat=1 / ?m=20261015）把内容塞进了查询
 * 串，路径始终是 "/"，直接渲染首页会导致「文章点不进去」。这里按查询
 * 参数把这类地址交给 ContentView 解析。
 */
const route = useRoute()

/** 表示「这里有一篇内容/一个归档」的查询参数（朴素固定链接） */
const CONTENT_QUERY_KEYS = [
  'p',
  'page_id',
  'attachment_id',
  'preview_id',
  'cat',
  'category_name',
  'tag',
  'tag_id',
  'm',
  'year',
  'monthnum',
]

const isPreviewRequest = computed(() => Boolean(getPreviewPayload()))

const hasContentQuery = computed(() =>
  CONTENT_QUERY_KEYS.some((key) => route.query[key] !== undefined),
)
</script>

<template>
  <ContentView v-if="isPreviewRequest || hasContentQuery" />
  <HomeView v-else />
</template>
