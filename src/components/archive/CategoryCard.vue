<script setup lang="ts">
/**
 * CategoryCard — 单个分类卡片（含最近文章列表）
 */
import type { RenderedText } from '@/types/wordpress'
import { RouterLink } from 'vue-router'
import { toInternalPath } from '@/lib/theme-config'

interface PostWithMeta {
  id: number
  link: string
  title: RenderedText | { rendered: string }
  date: string
  displayDate: string
}

defineProps<{
  name: string
  count: number
  posts: PostWithMeta[]
}>()

const emit = defineEmits<{
  (e: 'select', name: string): void
}>()
</script>

<template>
  <section
    class="flex flex-col rounded-large border-[1.5px] border-border bg-card p-[1.2rem] shadow-[0_4px_24px_0_rgba(0,0,0,0.07)] backdrop-blur-xl transition-all duration-[350ms] ease-[cubic-bezier(0.34,1.56,0.64,1)] hover:transform-[perspective(800px)_translateY(-5px)_rotateX(2deg)] hover:border-primary hover:shadow-[0_10px_48px_-4px_rgba(0,0,0,0.13)] focus-within:border-primary dark:shadow-[inset_0_1px_0_0_#fff3]"
  >
    <button
      type="button"
      class="mb-3 flex w-full cursor-pointer appearance-none items-center justify-between rounded-small bg-transparent p-0 text-left focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
      :aria-label="`查看 ${name} 分类的 ${count} 篇文章`"
      aria-haspopup="dialog"
      @click="emit('select', name)"
    >
      <h3 class="m-0 text-xl font-bold text-foreground">{{ name }}</h3>
      <span
        class="rounded-full bg-border px-3 py-1 text-[0.9rem] whitespace-nowrap text-foreground dark:bg-white/10 dark:text-white/70"
        >{{ count }} 篇</span
      >
    </button>
    <div class="border-t border-dashed border-border pt-3">
      <div
        v-for="post in posts"
        :key="post.id"
        class="flex justify-between py-1 text-[0.9rem]"
      >
        <RouterLink
          :to="toInternalPath(post.link)"
          class="flex-1 overflow-hidden text-ellipsis whitespace-nowrap text-foreground no-underline transition-colors duration-200 hover:text-primary dark:text-white/70"
          @click.stop
          >{{ (post.title as RenderedText).rendered }}</RouterLink
        >
        <span class="ml-3 shrink-0 text-[0.8rem] text-secondary">{{ post.displayDate }}</span>
      </div>
    </div>
  </section>
</template>
