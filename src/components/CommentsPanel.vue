<script setup lang="ts">
/**
 * CommentsPanel — 评论区容器
 * 负责状态管理、API 调用、评论区列表渲染
 */
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import CommentForm from '@/components/CommentForm.vue'
import CommentsTreeItem from '@/components/CommentsTreeItem.vue'
import UndrawIllustration from '@/components/UndrawIllustration.vue'
import { useSiteShell } from '@/composables/useSiteShell'
import { useAuth } from '@/composables/useAuth'
import { createComment, fetchComments, getErrorMessage, pinComment, deleteComment, fetchUserPendingComments } from '@/lib/wordpress'
import type { CommentFormSettings, WordPressComment } from '@/types/wordpress'
import { getThemeConfig } from '@/lib/theme-config'
import { StButton, StSkeleton, StTag, useToast } from '@/ui'

const toast = useToast()

const props = defineProps<{
  postId: number
  enabled: boolean
  formSettings: CommentFormSettings
}>()

const comments = ref<WordPressComment[]>([])
const loading = ref(false)
const loadingMore = ref(false)
const submitting = ref(false)
const commentsLoaded = ref(false)
const commentsPanelRef = ref<HTMLElement | null>(null)
let observer: IntersectionObserver | null = null

const authorName = ref('')
const authorEmail = ref('')
const authorUrl = ref('')
const content = ref('')
const cookiesConsent = ref(false)
const parentCommentId = ref(0)
const formRef = ref<InstanceType<typeof CommentForm> | null>(null)

const CONSENT_KEY = 'simple_theme_cookies_consent'

const { siteInfo } = useSiteShell()
const { auth } = useAuth()
const currentUser = computed(() =>
  auth.user
    ? { displayName: auth.user.displayName, email: auth.user.email, url: auth.user.url }
    : null,
)

// Pagination state
const currentPage = ref(1)
const totalPages = ref(1)
const totalComments = ref(0)
const allLoaded = ref(false)
const PER_PAGE = 50

function startLazyObserver() {
  if (observer) {
    observer.disconnect()
    observer = null
  }
  const el = commentsPanelRef.value
  if (!el || !props.enabled || !props.postId) return
  observer = new IntersectionObserver(
    (entries) => {
      if (entries[0]?.isIntersecting) {
        void loadComments()
        observer?.disconnect()
        observer = null
      }
    },
    { rootMargin: '300px 0px' },
  )
  observer.observe(el)
}

watch(cookiesConsent, (newVal) => {
  if (!newVal) {
    localStorage.removeItem(CONSENT_KEY)
    localStorage.removeItem('simple_theme_comment_name')
    localStorage.removeItem('simple_theme_comment_email')
    localStorage.removeItem('simple_theme_comment_url')
  }
})

onMounted(() => {
  const consent = localStorage.getItem(CONSENT_KEY)
  cookiesConsent.value = consent === '1'
  if (cookiesConsent.value) {
    authorName.value = localStorage.getItem('simple_theme_comment_name') || ''
    authorEmail.value = localStorage.getItem('simple_theme_comment_email') || ''
    authorUrl.value = localStorage.getItem('simple_theme_comment_url') || ''
  }
  startLazyObserver()
})

onBeforeUnmount(() => {
  if (observer) {
    observer.disconnect()
    observer = null
  }
})

function applyLike(items: WordPressComment[], id: number, likes: number): boolean {
  for (const item of items) {
    if (item.id === id) {
      item.likes = likes
      return true
    }
    if (item.children && item.children.length > 0 && applyLike(item.children, id, likes)) {
      return true
    }
  }
  return false
}

function findComment(items: WordPressComment[], id: number): WordPressComment | undefined {
  for (const item of items) {
    if (item.id === id) return item
    if (item.children && item.children.length > 0) {
      const found = findComment(item.children, id)
      if (found) return found
    }
  }
  return undefined
}

function handleLiked(payload: { id: number; likes: number }) {
  applyLike(comments.value, payload.id, payload.likes)
}

function handleLikeError(message: string) {
  toast.warning('评论通知', message)
}

async function loadComments(page = 1) {
  if (!props.enabled || !props.postId) {
    comments.value = []
    return
  }
  if (page === 1) loading.value = true
  else loadingMore.value = true
  try {
    const order = getThemeConfig().features?.commentOrder === 'desc' ? 'desc' : 'asc'
    const result = await fetchComments(props.postId, undefined, page, PER_PAGE, order)
    totalComments.value = result.total
    totalPages.value = result.totalPages
    currentPage.value = result.page
    allLoaded.value = currentPage.value >= totalPages.value
    if (page === 1) {
      comments.value = result.items
      // Also fetch user's pending comments and merge
      try {
        const pending = await fetchUserPendingComments(props.postId)
        if (pending.length > 0) {
          const existingIds = new Set(comments.value.map((c) => c.id))
          for (const item of pending) {
            if (!existingIds.has(item.id)) {
              if (item.parent > 0) {
                const parent = findComment(comments.value, item.parent)
                if (parent) {
                  parent.children.push(item)
                } else {
                  comments.value.push(item)
                }
              } else {
                comments.value.push(item)
              }
            }
          }
        }
      } catch { /* ignore */ }
    } else {
      // Merge new items, avoiding duplicates; 孤儿页的回复尝试挂回已加载的父级
      const existingIds = new Set<number>()
      const collectIds = (list: WordPressComment[]) => {
        for (const c of list) {
          existingIds.add(c.id)
          if (c.children.length) collectIds(c.children)
        }
      }
      collectIds(comments.value)
      for (const item of result.items) {
        if (existingIds.has(item.id)) continue
        const parent = item.parent > 0 ? findComment(comments.value, item.parent) : undefined
        if (parent) {
          parent.children.push(item)
        } else {
          comments.value.push(item)
        }
      }
    }
  } catch (error) {
    toast.error(getErrorMessage(error, '评论加载失败，请稍后重试。'))
  } finally {
    loading.value = false
    loadingMore.value = false
    commentsLoaded.value = true
  }
}

function loadMore() {
  if (currentPage.value < totalPages.value && !loadingMore.value) {
    void loadComments(currentPage.value + 1)
  }
}

function useReply(id: number) {
  parentCommentId.value = id
}

async function handleFormSubmit(payload: {
  name: string
  email: string
  url: string
  content: string
  cookies: boolean
  captchaPayload?: string
  isPrivate?: boolean
  mailNotify?: boolean
  useMarkdown?: boolean
}) {
  // Logged-in users: use currentUser info, skip name/email validation
  if (currentUser.value) {
    payload.name = currentUser.value.displayName
    payload.email = currentUser.value.email ?? ''
    payload.url = currentUser.value.url ?? ''
    if (!payload.content.trim()) {
      toast.warning('提示', '请填写评论内容。')
      return
    }
  } else {
    if (!payload.name.trim() || !payload.content.trim()) {
      toast.warning('提示', '请填写必填项后再提交。')
      return
    }
    if (
      props.formSettings.requireNameEmail &&
      props.formSettings.showEmailField &&
      !payload.email.trim()
    ) {
      toast.warning('提示', '请填写邮箱。')
      return
    }
  }

  submitting.value = true
  const loadingToast = toast.loading('发送中', '正在提交评论...')

  try {
    const newComment = await createComment({
      post: props.postId,
      parent: parentCommentId.value || undefined,
      author_name: payload.name.trim(),
      author_email: props.formSettings.showEmailField ? payload.email.trim() : '',
      author_url: props.formSettings.showUrlField ? payload.url.trim() : '',
      content: payload.content.trim(),
      client_id: '',
      captchaPayload: payload.captchaPayload,
      isPrivate: payload.isPrivate,
      mailNotify: payload.mailNotify,
      useMarkdown: payload.useMarkdown,
      // 未展示同意选项时默认同意，后端据此调 wp_set_comment_cookies
      cookiesConsent: props.formSettings.showCookiesOptIn ? payload.cookies : true,
    })

    toast.remove(loadingToast)

    if (payload.cookies) {
      localStorage.setItem(CONSENT_KEY, '1')
      localStorage.setItem('simple_theme_comment_name', payload.name)
      localStorage.setItem('simple_theme_comment_email', payload.email)
      localStorage.setItem('simple_theme_comment_url', payload.url)
    } else {
      localStorage.removeItem(CONSENT_KEY)
      localStorage.removeItem('simple_theme_visitor_id')
      localStorage.removeItem('simple_theme_comment_name')
      localStorage.removeItem('simple_theme_comment_email')
      localStorage.removeItem('simple_theme_comment_url')
    }

    content.value = ''
    parentCommentId.value = 0
    formRef.value?.clearForm()

    // createComment already returns a fully mapped WordPressComment via mapWPComment
    const isApproved = newComment.status === 'approved'
    newComment.children = []

    if (newComment.parent > 0) {
      const parent = findComment(comments.value, newComment.parent)
      if (parent) {
        parent.children.push(newComment)
      } else {
        comments.value = [...comments.value, newComment]
      }
    } else {
      comments.value = [newComment, ...comments.value]
    }

    toast.success('成功', isApproved ? '评论已发布。' : '评论提交成功，等待审核。')
  } catch (error) {
    toast.remove(loadingToast)
    toast.error(getErrorMessage(error, '评论提交失败，请稍后重试。'))
  } finally {
    submitting.value = false
  }
}

async function handleDeleteComment(commentId: number) {
  const toastId = toast.loading('删除中', '正在删除评论...')
  try {
    await deleteComment(commentId)
    toast.remove(toastId)

    // Remove from tree
    function removeItem(items: WordPressComment[]): boolean {
      const idx = items.findIndex((c) => c.id === commentId)
      if (idx !== -1) {
        items.splice(idx, 1)
        return true
      }
      for (const item of items) {
        if (item.children.length && removeItem(item.children)) return true
      }
      return false
    }
    removeItem(comments.value)
    toast.success('成功', '评论已删除。')
  } catch {
    toast.remove(toastId)
    toast.error('错误', '删除失败，请稍后重试。')
  }
}

async function handlePinToggle(commentId: number, pin: boolean) {
  try {
    await pinComment(commentId, pin)
    // Reload comments to reflect pinning order
    void loadComments(1)
    toast.success('成功', pin ? '已置顶评论。' : '已取消置顶。')
  } catch {
    toast.error('错误', '操作失败。')
  }
}

watch(
  () => [props.postId, props.enabled],
  () => {
    comments.value = []
    currentPage.value = 1
    totalPages.value = 1
    allLoaded.value = false
    commentsLoaded.value = false
    startLazyObserver()
  },
)
</script>

<template>
  <section ref="commentsPanelRef" class="comments-panel">
    <!-- Header -->
    <header v-if="enabled" class="comments-header">
      <h3 class="comments-header__title">评论区</h3>
      <StTag size="tiny" :bordered="false">{{ totalComments }}</StTag>
    </header>

    <!-- Disabled: comments closed -->
    <div v-if="!enabled" class="comments-empty">
      <div class="comments-empty__illustration">
        <UndrawIllustration name="cancel" width="200" height="150" class="comments-empty__img" />
      </div>
      <h4 class="comments-empty__title">评论未开启</h4>
      <p class="comments-empty__desc">当前文章未开启评论。</p>
    </div>

    <!-- Disabled: registration only (anonymous users only) -->
    <div v-else-if="formSettings.registrationOnly && !currentUser" class="comments-empty">
      <div class="comments-empty__illustration">
        <UndrawIllustration name="access-denied" width="200" height="150" class="comments-empty__img" />
      </div>
      <h4 class="comments-empty__title">仅注册用户可评论</h4>
      <p class="comments-empty__desc">站点设置为仅注册用户可评论，请先登录。</p>
    </div>

    <!-- Comment Form -->
    <CommentForm
      v-else
      ref="formRef"
      :form-settings="formSettings"
      :current-user="currentUser"
      :submitting="submitting"
      :loading="loading"
      :parent-comment-id="parentCommentId"
      v-model:content="content"
      v-model:name="authorName"
      v-model:email="authorEmail"
      v-model:url="authorUrl"
      v-model:cookies="cookiesConsent"
      @submit="handleFormSubmit"
      @cancel-reply="parentCommentId = 0"
    />

    <!-- Loading -->
    <div v-if="loading" class="comments-loading" role="status" aria-label="评论加载中">
      <StSkeleton width="100%" height="1rem" />
      <StSkeleton width="75%" height="1rem" />
    </div>

    <!-- Empty -->
    <div v-else-if="enabled && comments.length === 0" class="comments-empty">
      <div class="comments-empty__illustration">
        <UndrawIllustration name="chatting" width="200" height="150" class="comments-empty__img" />
      </div>
      <h4 class="comments-empty__title">还没有评论</h4>
      <p class="comments-empty__desc">还没有评论，来发第一条吧。</p>
    </div>

    <!-- Comments List -->
    <div v-else class="comments-list">
      <CommentsTreeItem
        v-for="item in comments"
        :key="item.id"
        :item="item"
        @reply="useReply"
        @liked="handleLiked"
        @like-error="handleLikeError"
        @delete="handleDeleteComment"
        @pin-toggle="handlePinToggle"
      />
    </div>

    <!-- Load More -->
    <div v-if="commentsLoaded && !loading && !allLoaded" class="comments-more">
      <StButton type="primary" ghost round :disabled="loadingMore" @click="loadMore">
        {{ loadingMore ? '加载中...' : '加载更多评论' }}
      </StButton>
    </div>

    <!-- End note -->
    <p v-if="commentsLoaded && allLoaded && comments.length > 0" class="end-note comments-end-note">
      {{ siteInfo.endNote || '好像就这么多' }}
    </p>
  </section>
</template>

<style scoped>
.comments-empty {
  display: flex;
  flex: 1;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 16px;
  text-align: center;
}

.comments-empty__illustration {
  width: 100%;
  max-width: 220px;
  margin-bottom: 20px;
}

.comments-empty__img {
  width: 100%;
  height: auto;
}

.comments-empty__title {
  margin: 0 0 6px;
  font-size: 18px;
  font-weight: 625;
  line-height: 1.4;
  color: var(--foreground);
}

.comments-empty__desc {
  font-size: 13px;
  line-height: 1.625;
  color: var(--secondary);
}

.comments-more {
  display: flex;
  justify-content: center;
  padding: 16px 0;
}

.comments-end-note {
  margin: 0;
  padding: 24px 0 8px;
  text-align: center;
  font-size: 0.8125rem;
  color: var(--secondary);
}
</style>
