<script setup lang="ts">
import { computed, ref } from 'vue'
import { likeComment } from '@/lib/wordpress'
import { renderCommentContent } from '@/lib/emoji'
import type { WordPressComment } from '@/types/wordpress'

defineOptions({ name: 'CommentsTreeItem' })

const props = defineProps<{
  item: WordPressComment
  depth?: number
}>()

const emit = defineEmits<{
  (e: 'reply', id: number): void
  (e: 'liked', payload: { id: number; likes: number }): void
  (e: 'like-error', message: string): void
  (e: 'delete', commentId: number): void
  (e: 'pin-toggle', commentId: number, pin: boolean): void
}>()

const level = computed(() => props.depth || 0)

/* Badge base utilities (shared by pinned / private / pending variants) */
const badgeBase =
  'mr-1 inline-flex items-center rounded-[3px] px-1.5 py-px align-middle text-[10px] leading-[1.4] font-semibold text-white'
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

const avatarSources = ref<string[]>([])

function initAvatarSources() {
  const sources: string[] = []

  // ① Try QQ avatar first (if QQ number detected)
  if (props.item.qqAvatar) {
    sources.push(props.item.qqAvatar)
  }

  // ② Backend avatar
  if (props.item.avatar) {
    sources.push(props.item.avatar)
  }

  // ③ Website favicon
  if (props.item.authorUrl && isValidUrl(props.item.authorUrl)) {
    const domain = new URL(props.item.authorUrl).hostname
    sources.push(`https://${domain}/favicon.ico`)
  }

  // ④ Text avatar fallback
  sources.push('')

  avatarSources.value = sources
}

function isValidUrl(url: string): boolean {
  try { new URL(url); return true }
  catch { return false }
}

initAvatarSources()

const avatarIndex = ref(0)
const avatarUrl = computed(() => avatarSources.value[avatarIndex.value])

function onAvatarError() {
  avatarIndex.value++
}

const goUrl = computed(() => {
  if (!props.item.authorUrl) return ''
  return '/go?url=' + encodeURIComponent(props.item.authorUrl)
})

const hasChildren = computed(() => !!(props.item.children && props.item.children.length > 0))

const renderedContent = computed(() => renderCommentContent(props.item.content.rendered))

async function handleLike() {
  if (liking.value) return
  liking.value = true
  try {
    const nextLikes = await likeComment(props.item.id)
    liked.value = !liked.value
    localStorage.setItem(`simple_theme_comment_liked_${props.item.id}`, liked.value ? '1' : '0')
    emit('liked', { id: props.item.id, likes: nextLikes })
  } catch (error) {
    const message = error instanceof Error ? error.message : '点赞失败，请稍后再试。'
    emit('like-error', message)
  } finally {
    liking.value = false
  }
}

function togglePin() {
  emit('pin-toggle', props.item.id, !props.item.isPinned)
}
</script>

<template>
  <div
    class="comments-item"
    :class="
      item.isPinned &&
      'relative before:absolute before:inset-x-0 before:top-0 before:h-[2px] before:rounded-t-[2px] before:bg-primary before:opacity-50'
    "
  >
    <div class="comments-item__main">
      <div class="comments-item__avatar">
        <a
          v-if="item.authorUrl"
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

          <!-- Badges -->
          <span v-if="item.isPinned" :class="[badgeBase, 'bg-primary']">置顶</span>
          <span v-if="item.isPrivate" :class="[badgeBase, 'bg-[#f59e0b]']">私密</span>
          <span v-if="item.status === 'hold'" :class="[badgeBase, 'bg-secondary']">待审核</span>
        </div>

        <!-- Normal content -->
        <div class="comments-item__text" v-html="renderedContent"></div>

        <!-- Time + Device info -->
        <span class="comments-item__time">{{ relativeTime }}</span>
        <span v-if="item.metaInfo?.browser || item.metaInfo?.os || item.metaInfo?.location" class="comments-item__meta-info">
          {{ item.metaInfo.browser }}<template v-if="item.metaInfo.os || item.metaInfo.location"> · {{ item.metaInfo.os }}</template><template v-if="item.metaInfo.location"> · {{ item.metaInfo.location }}</template>
        </span>

        <div class="comments-item__actions">
          <button
            v-if="item.status !== 'hold'"
            class="comments-item__action"
            :class="{ 'comments-item__action--liked': liked }"
            type="button"
            :disabled="liking"
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
          <button
            v-if="item.status === 'hold'"
            class="comments-item__action comments-item__action--delete"
            type="button"
            @click="emit('delete', item.id)"
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
            </svg>
            删除
          </button>
          <button
            v-if="item.canPin"
            class="comments-item__action"
            type="button"
            @click="togglePin"
          >
            {{ item.isPinned ? '取消置顶' : '置顶' }}
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
        @delete="(id: number) => emit('delete', id)"
        @pin-toggle="(id: number, pin: boolean) => emit('pin-toggle', id, pin)"
      />
    </div>
  </div>
</template>
