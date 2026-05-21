<script setup lang="ts">
import { computed, ref } from 'vue'
import { likeComment, fetchCommentHistory } from '@/lib/wordpress'
import { renderToHtml } from '@/lib/emoji'
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
  (e: 'edit', commentId: number, content: string): void
  (e: 'pin-toggle', commentId: number, pin: boolean): void
}>()

const level = computed(() => props.depth || 0)
const liking = ref(false)
const liked = ref(localStorage.getItem(`simple_theme_comment_liked_${props.item.id}`) === '1')

// Editing state
const editing = ref(false)
const editContent = ref('')
const editingHistory = ref(false)
const history = ref<Array<{ content: string; time: string }>>([])

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

const renderedContent = computed(() => renderToHtml(props.item.content.rendered))

async function handleLike() {
  if (liked.value || liking.value) return
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

function startEdit() {
  if (!props.item.canEdit) return
  editing.value = true
  editContent.value = props.item.content.rendered.replace(/<p>|<\/p>/g, '')
}

function cancelEdit() {
  editing.value = false
  editContent.value = ''
}

function saveEdit() {
  if (!editContent.value.trim()) return
  emit('edit', props.item.id, editContent.value.trim())
  editing.value = false
}

async function showHistory() {
  if (editingHistory.value) {
    editingHistory.value = false
    return
  }
  try {
    history.value = await fetchCommentHistory(props.item.id)
    editingHistory.value = true
  } catch {
    history.value = []
    editingHistory.value = true
  }
}

function togglePin() {
  emit('pin-toggle', props.item.id, !props.item.isPinned)
}
</script>

<template>
  <div
    class="comments-item"
    :class="{
      'comments-item--nested': level > 0,
      'comments-item--pinned': item.isPinned,
    }"
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
          <span v-if="item.isPinned" class="comments-item__badge comments-item__badge--pinned">置顶</span>
          <span v-if="item.isPrivate" class="comments-item__badge comments-item__badge--private">私密</span>
          <span v-if="item.status === 'hold'" class="comments-item__badge comments-item__badge--pending">待审核</span>

          <span class="comments-item__time">{{ relativeTime }}</span>
          <span v-if="item.metaInfo?.browser || item.metaInfo?.os" class="comments-item__meta-info">
            {{ item.metaInfo.browser }} · {{ item.metaInfo.os }} · {{ item.metaInfo.location }}
          </span>
        </div>

        <!-- Edit mode -->
        <div v-if="editing" class="comments-item__edit">
          <textarea
            v-model="editContent"
            class="comments-item__edit-textarea"
            rows="3"
          ></textarea>
          <div class="comments-item__edit-actions">
            <button class="comments-item__edit-save" type="button" @click="saveEdit">保存</button>
            <button class="comments-item__edit-cancel" type="button" @click="cancelEdit">取消</button>
          </div>
        </div>

        <!-- Normal content -->
        <div v-else class="comments-item__text" v-html="renderedContent"></div>

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
          <button
            v-if="item.canEdit"
            class="comments-item__action"
            type="button"
            @click="startEdit"
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
            编辑
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

        <!-- Edit history -->
        <div v-if="editingHistory && history.length > 0" class="comments-item__history">
          <div class="comments-item__history-title">编辑历史</div>
          <div v-for="(entry, i) in history" :key="i" class="comments-item__history-entry">
            <div class="comments-item__history-time">{{ entry.time }}</div>
            <div class="comments-item__history-content">{{ entry.content }}</div>
          </div>
        </div>
        <div v-else-if="editingHistory && history.length === 0" class="comments-item__history">
          <div class="comments-item__history-title">暂无编辑历史</div>
        </div>
        <button
          v-if="item.canEdit"
          class="comments-item__action comments-item__action--small"
          type="button"
          @click="showHistory"
        >
          {{ editingHistory ? '隐藏历史' : '编辑历史' }}
        </button>
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
        @edit="(id: number, content: string) => emit('edit', id, content)"
        @pin-toggle="(id: number, pin: boolean) => emit('pin-toggle', id, pin)"
      />
    </div>
  </div>
</template>

<style scoped>
.comments-item--pinned {
  position: relative;
}
.comments-item--pinned::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: var(--primary, #4f46e5);
  border-radius: 2px 2px 0 0;
  opacity: 0.5;
}

.comments-item__badge {
  display: inline-flex;
  align-items: center;
  font-size: 10px;
  font-weight: 600;
  padding: 1px 6px;
  border-radius: 3px;
  margin-right: 4px;
  vertical-align: middle;
  line-height: 1.4;
}
.comments-item__badge--pinned {
  background: var(--primary, #4f46e5);
  color: #fff;
}
.comments-item__badge--private {
  background: #f59e0b;
  color: #fff;
}
.comments-item__badge--pending {
  background: var(--secondary, #999);
  color: #fff;
}

.comments-item__edit {
  margin: 8px 0;
}
.comments-item__edit-textarea {
  width: 100%;
  padding: 8px;
  font-size: 13px;
  border: 1px solid var(--border);
  border-radius: 4px;
  background: var(--faint);
  color: var(--foreground);
  font-family: inherit;
  resize: vertical;
}
.comments-item__edit-textarea:focus {
  border-color: var(--primary);
  outline: none;
}
.comments-item__edit-actions {
  display: flex;
  gap: 6px;
  margin-top: 6px;
}
.comments-item__edit-save,
.comments-item__edit-cancel {
  padding: 4px 12px;
  font-size: 12px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-family: inherit;
}
.comments-item__edit-save {
  background: var(--primary);
  color: var(--primary-foreground);
}
.comments-item__edit-cancel {
  background: var(--muted);
  color: var(--secondary);
}
.comments-item__edit-save:hover { opacity: 0.85; }
.comments-item__edit-cancel:hover { background: var(--border); }

.comments-item__history {
  margin-top: 8px;
  padding: 8px;
  background: var(--faint);
  border-radius: 4px;
  font-size: 12px;
}
.comments-item__history-title {
  font-weight: 600;
  margin-bottom: 4px;
  color: var(--secondary);
}
.comments-item__history-entry {
  margin-bottom: 4px;
  padding-bottom: 4px;
  border-bottom: 1px solid var(--border);
}
.comments-item__history-entry:last-child {
  border-bottom: none;
}
.comments-item__history-time {
  font-size: 11px;
  color: var(--secondary);
  margin-bottom: 2px;
}
.comments-item__history-content {
  color: var(--foreground);
  word-break: break-word;
}

.comments-item__action--small {
  font-size: 11px;
  padding: 2px 4px;
  margin-top: 4px;
}
</style>
