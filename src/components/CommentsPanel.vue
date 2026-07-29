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
import { createComment, fetchComments, getErrorMessage, pinComment, deleteComment, fetchUserPendingComments } from '@/lib/wordpress'
import { showError, showLoadingToast, showToast, dismissToast } from '@/lib/toast'
import { getThemeConfig } from '@/lib/theme-config'
import type { CommentFormSettings, WordPressComment } from '@/types/wordpress'

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
const currentUser = computed(() => {
  const cfg = getThemeConfig()
  return siteInfo.value?.currentUser ?? cfg.currentUser ?? null
})

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
  showToast(message, '评论通知', { variant: 'warning', duration: 3200 })
}

async function loadComments(page = 1) {
  if (!props.enabled || !props.postId) {
    comments.value = []
    return
  }
  if (page === 1) loading.value = true
  else loadingMore.value = true
  try {
    const result = await fetchComments(props.postId, undefined, page, PER_PAGE)
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
      // Merge new items, avoiding duplicates
      const existingIds = new Set(comments.value.map((c) => c.id))
      for (const item of result.items) {
        if (!existingIds.has(item.id)) {
          comments.value.push(item)
        }
      }
    }
  } catch (error) {
    showError(getErrorMessage(error, '评论加载失败，请稍后重试。'))
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
      showToast('请填写评论内容。', '提示', { variant: 'warning' })
      return
    }
  } else {
    if (!payload.name.trim() || !payload.content.trim()) {
      showToast('请填写必填项后再提交。', '提示', { variant: 'warning' })
      return
    }
    if (
      props.formSettings.requireNameEmail &&
      props.formSettings.showEmailField &&
      !payload.email.trim()
    ) {
      showToast('请填写邮箱。', '提示', { variant: 'warning' })
      return
    }
  }

  submitting.value = true
  const loadingToast = showLoadingToast('正在提交评论...', '发送中')

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
    })

    dismissToast(loadingToast)

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

    showToast(
      isApproved ? '评论已发布。' : '评论提交成功，等待审核。',
      '成功',
      { variant: 'success', duration: 3200 },
    )
  } catch (error) {
    dismissToast(loadingToast)
    showError(getErrorMessage(error, '评论提交失败，请稍后重试。'))
  } finally {
    submitting.value = false
  }
}

async function handleDeleteComment(commentId: number) {
  const toast = showLoadingToast('正在删除评论...', '删除中')
  try {
    await deleteComment(commentId)
    dismissToast(toast)

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
    showToast('评论已删除。', '成功', { variant: 'success', duration: 2000 })
  } catch {
    dismissToast(toast)
    showToast('删除失败，请稍后重试。', '错误', { variant: 'danger' })
  }
}

async function handlePinToggle(commentId: number, pin: boolean) {
  try {
    await pinComment(commentId, pin)
    // Reload comments to reflect pinning order
    void loadComments(1)
    showToast(pin ? '已置顶评论。' : '已取消置顶。', '成功', { variant: 'success', duration: 2000 })
  } catch {
    showToast('操作失败。', '错误', { variant: 'danger' })
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
      <span class="comments-header__count">{{ totalComments }}</span>
    </header>

    <!-- Disabled: comments closed -->
    <div v-if="!enabled" class="flex flex-1 flex-col items-center justify-center px-4 py-12 text-center">
      <div class="mb-5 w-full max-w-[220px]">
        <UndrawIllustration name="cancel" width="200" height="150" class="h-auto w-full" />
      </div>
      <h4 class="mb-1.5 text-lg leading-[1.4] font-[625] text-foreground">评论未开启</h4>
      <p class="text-sm leading-relaxed text-secondary">当前文章未开启评论。</p>
    </div>

    <!-- Disabled: registration only (anonymous users only) -->
    <div v-else-if="formSettings.registrationOnly && !currentUser" class="flex flex-1 flex-col items-center justify-center px-4 py-12 text-center">
      <div class="mb-5 w-full max-w-[220px]">
        <UndrawIllustration name="access-denied" width="200" height="150" class="h-auto w-full" />
      </div>
      <h4 class="mb-1.5 text-lg leading-[1.4] font-[625] text-foreground">仅注册用户可评论</h4>
      <p class="text-sm leading-relaxed text-secondary">站点设置为仅注册用户可评论，请先登录。</p>
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
    <div v-if="loading" class="comments-loading">
      <div class="skeleton skeleton--paragraph" role="status"></div>
      <div class="skeleton skeleton--paragraph skeleton--w-75" role="status"></div>
    </div>

    <!-- Empty -->
    <div v-else-if="enabled && comments.length === 0" class="flex flex-1 flex-col items-center justify-center px-4 py-12 text-center">
      <div class="mb-5 w-full max-w-[220px]">
        <UndrawIllustration name="chatting" width="200" height="150" class="h-auto w-full" />
      </div>
      <h4 class="mb-1.5 text-lg leading-[1.4] font-[625] text-foreground">还没有评论</h4>
      <p class="text-sm leading-relaxed text-secondary">还没有评论，来发第一条吧。</p>
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
    <div v-if="commentsLoaded && !loading && !allLoaded" class="flex justify-center py-4">
      <button
        class="cursor-pointer rounded-[20px] border border-border bg-transparent px-6 py-2 font-[inherit] text-[13px] text-primary transition-all duration-150 hover:border-primary hover:bg-muted disabled:cursor-not-allowed disabled:opacity-50"
        :disabled="loadingMore"
        @click="loadMore"
      >
        {{ loadingMore ? '加载中...' : '加载更多评论' }}
      </button>
    </div>

    <!-- End note -->
    <p v-if="commentsLoaded && allLoaded && comments.length > 0" class="end-note m-0 px-0 pt-6 pb-2 text-center text-[0.8125rem] text-secondary">
      {{ siteInfo.endNote || '好像就这么多' }}
    </p>
  </section>
</template>
