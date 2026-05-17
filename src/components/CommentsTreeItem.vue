<script setup lang="ts">
import { computed, ref } from 'vue'
import { likeComment } from '@/lib/wordpress'
import { renderToHtml } from '@/lib/emoji'
import type { WordPressComment } from '@/types/wordpress'

defineOptions({
  name: 'CommentsTreeItem',
})

const props = defineProps<{
  item: WordPressComment
  depth?: number
}>()

const emit = defineEmits<{
  (e: 'reply', id: number): void
  (e: 'liked', payload: { id: number; likes: number }): void
  (e: 'like-error', message: string): void
}>()

const level = computed(() => props.depth || 0)
const liking = ref(false)
const liked = ref(localStorage.getItem(`simple_theme_comment_liked_${props.item.id}`) === '1')

const relativeTime = computed(() => {
  const now = Date.now()
  const then = new Date(props.item.date).getTime()
  const diff = Math.max(0, now - then)
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return '刚刚'
  if (mins < 60) return `${mins} 分钟前`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `${hours} 小时前`
  const days = Math.floor(hours / 24)
  if (days < 30) return `${days} 天前`
  const months = Math.floor(days / 30)
  if (months < 12) return `${months} 个月前`
  const years = Math.floor(months / 12)
  return `${years} 年前`
})

const avatarUrl = ref(props.item.avatar || '')
const avatarAttempts = ref(0)

function onAvatarError() {
  avatarAttempts.value++
  // Try commenter's website favicon as fallback
  if (avatarAttempts.value === 1 && props.item.authorUrl) {
    try {
      const domain = new URL(props.item.authorUrl).hostname
      avatarUrl.value = `https://www.google.com/s2/favicons?domain=${domain}&sz=64`
      return
    } catch {}
  }
  // Final fallback: show text avatar
  avatarUrl.value = ''
}

const goUrl = computed(() => {
  if (!props.item.authorUrl) return ''
  return '/go?url=' + encodeURIComponent(props.item.authorUrl)
})

const hasChildren = computed(() => !!(props.item.children && props.item.children.length > 0))

const renderedContent = computed(() => renderToHtml(props.item.content.rendered))

async function handleLike() {
  if (liked.value || liking.value) {
    return
  }

  liking.value = true
  try {
    const nextLikes = await likeComment(props.item.id)
    liked.value = true
    localStorage.setItem(`simple_theme_comment_liked_${props.item.id}`, '1')
    emit('liked', { id: props.item.id, likes: nextLikes })
  } catch (error) {
    const message = error instanceof Error ? error.message : '点赞失败，请稍后再试。'
    emit('like-error', message)
  } finally {
    liking.value = false
  }
}
</script>

<template>
  <div class="comments-item" :class="{ 'comments-item--nested': level > 0 }">
    <div class="comments-item__main">
      <div class="comments-item__avatar">
        <a
          v-if="props.item.authorUrl"
          :href="goUrl"
          rel="nofollow noopener noreferrer"
          class="comments-item__avatar-link"
        >
          <img
            v-if="avatarUrl"
            :src="avatarUrl"
            alt=""
            class="comments-item__avatar-img"
            loading="lazy"
            @error="onAvatarError"
          />
          <span v-else class="comments-item__avatar-fallback">{{ item.authorName.charAt(0) }}</span>
        </a>
        <template v-else>
          <img
            v-if="avatarUrl"
            :src="avatarUrl"
            alt=""
            class="comments-item__avatar-img"
            loading="lazy"
            @error="onAvatarError"
          />
          <span v-else class="comments-item__avatar-fallback">{{ item.authorName.charAt(0) }}</span>
        </template>
      </div>

      <div class="comments-item__body">
        <div class="comments-item__meta">
          <a
            v-if="item.authorUrl"
            :href="goUrl"
            rel="nofollow noopener noreferrer"
            class="comments-item__name"
          >
            {{ item.authorName || '匿名用户' }}
          </a>
          <strong v-else class="comments-item__name">
            {{ item.authorName || '匿名用户' }}
          </strong>
          <span class="comments-item__time">{{ relativeTime }}</span>
          <span class="comments-item__meta-info" v-if="item.metaInfo">
            {{ item.metaInfo.browser }} · {{ item.metaInfo.os }} · {{ item.metaInfo.location }}
          </span>
          <span v-if="item.status === 'hold'" class="comments-item__meta-info">· 待审核</span>
        </div>

        <div class="comments-item__text" v-html="renderedContent"></div>

        <div class="comments-item__actions">
          <button
            v-if="item.status !== 'hold'"
            class="comments-item__action"
            :class="{ 'comments-item__action--liked': liked }"
            type="button"
            :disabled="liked || liking"
            @click="handleLike"
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/>
            </svg>
            <span v-if="item.likes > 0">{{ item.likes }}</span>
            <span v-else>赞</span>
          </button>
          <button class="comments-item__action" type="button" @click="emit('reply', item.id)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="9 17 4 12 9 7"/><path d="M20 18v-2a4 4 0 0 0-4-4H4"/>
            </svg>
            回复
          </button>
        </div>
      </div>
    </div>

    <div v-if="hasChildren" class="comments-nested">
      <CommentsTreeItem
        v-for="child in item.children"
        :key="child.id"
        :item="child"
        :depth="level + 1"
        @reply="emit('reply', $event)"
        @liked="emit('liked', $event)"
        @like-error="emit('like-error', $event)"
      />
    </div>
  </div>
</template>

<style scoped>
</style>
