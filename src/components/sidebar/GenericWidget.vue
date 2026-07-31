<script setup lang="ts">
/**
 * GenericWidget — 渲染核心/区块/第三方 WordPress 小工具的 HTML。
 *
 * 后端已用主题风格的 before_widget/before_title 包裹好结构，
 * 这里只负责注入 HTML 并把内部链接点击转交给 vue-router（避免整页刷新）。
 */
import { useRouter } from 'vue-router'
import { isExternalUrl, toInternalPath } from '@/lib/theme-config'

defineProps<{
  html: string
}>()

const router = useRouter()

function handleClick(event: MouseEvent) {
  const target = event.target
  if (!(target instanceof HTMLElement)) return
  const anchor = target.closest('a')
  if (!anchor) return

  const href = anchor.getAttribute('href')
  if (
    !href || href.startsWith('#') || href.startsWith('mailto:') ||
    href.startsWith('tel:') || anchor.target === '_blank' ||
    anchor.hasAttribute('download') || isExternalUrl(href)
  ) return

  event.preventDefault()
  void router.push(toInternalPath(href))
}
</script>

<template>
  <!-- eslint-disable-next-line vue/no-v-html -->
  <div class="sidebar-widget" @click="handleClick" v-html="html"></div>
</template>
