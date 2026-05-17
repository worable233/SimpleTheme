<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import CommentsTreeItem from '@/components/CommentsTreeItem.vue'
import UndrawIllustration from '@/components/UndrawIllustration.vue'
import { useSiteShell } from '@/composables/useSiteShell'
import { createComment, fetchComments, getErrorMessage } from '@/lib/wordpress'
import { showError, showLoadingToast, showToast, dismissToast } from '@/lib/toast'
import { getThemeConfig } from '@/lib/theme-config'
import {
  bilibiliNames,
  biliImg,
  tiebaNames,
  tiebaImg,
  renderToHtml,
} from '@/lib/emoji'
import type { CommentFormSettings, WordPressComment } from '@/types/wordpress'

const props = defineProps<{
  postId: number
  enabled: boolean
  formSettings: CommentFormSettings
}>()

const comments = ref<WordPressComment[]>([])
const loading = ref(false)
const submitting = ref(false)

const commentsPanelRef = ref<HTMLElement | null>(null)
let observer: IntersectionObserver | null = null

const authorName = ref('')
const authorEmail = ref('')
const authorUrl = ref('')
const content = ref('')
const cookiesConsent = ref(false)
const parentCommentId = ref(0)

const CONSENT_KEY = 'simple_theme_cookies_consent'

const { siteInfo } = useSiteShell()
const currentUser = computed(() => {
  const cfg = getThemeConfig()
  return siteInfo.value?.currentUser ?? cfg.currentUser ?? null
})

const aiteUser = ref(false)

// 颜文字 (来自 Sakurairo)
const kaomojiList = [
  '(⌒▽⌒)', '（￣▽￣）', '(=・ω・=)', '(｀・ω・´)', '(〜￣△￣)〜',
  '(･∀･)', '(°∀°)ﾉ', '(￣3￣)', '╮(￣▽￣)╭', '(´_ゝ｀)',
  '←_←', '→_→', '(<_<)', '(>_>)', '(;¬_¬)',
  '("▔□▔)/', '(ﾟДﾟ≡ﾟдﾟ)!?', 'Σ(ﾟдﾟ;)', 'Σ(￣□￣||)', '(’；ω；‘)',
  '（/TДT)/', '(^・ω・^ )', '(｡･ω･｡)', '(●￣(ｴ)￣●)', 'ε=ε=(ノ≧∇≦)ノ',
  '(’･_･‘)', '(-_-#)', '（￣へ￣）', '(￣ε(#￣)Σ', "ヽ('Д')ﾉ",
  '（#-_-)┯━┯', '(╯°口°)╯(┴—┴', '←◡←', '( ♥д♥)', '_(:3」∠)_',
  'Σ>―(〃°ω°〃)♡→', '⁄(⁄ ⁄•⁄ω⁄•⁄ ⁄)⁄', '(╬ﾟдﾟ)▄︻┻┳═一', '･*･:≡(　ε:)',
  '(笑)', '(汗)', '(泣)', '(苦笑)',
]

const emojiOpen = ref(false)
const emojiTab = ref<'bilibili' | 'tieba' | 'kaomoji'>('bilibili')
const emojiPanelRef = ref<HTMLElement | null>(null)

// ── contenteditable 输入框 ──
const editorRef = ref<HTMLDivElement | null>(null)

/** 从 innerHTML 提取纯文本标记（img → {{name}} / ::name::） */
function extractPlainText(): string {
  const el = editorRef.value
  if (!el) return ''
  let text = ''
  for (const node of el.childNodes) {
    if (node.nodeType === Node.TEXT_NODE) {
      text += node.textContent
    } else if (node instanceof HTMLImageElement) {
      const type = node.dataset.type
      const name = node.dataset.name
      if (type === 'bili' && name) text += `{{${name}}}`
      else if (type === 'tieba' && name) text += `::${name}::`
    }
  }
  return text
}


/** 保存光标在纯文本中的字符偏移 */
function saveCursorOffset(): number {
  const el = editorRef.value
  const sel = window.getSelection()
  if (!el || !sel || !sel.rangeCount) return 0
  const range = sel.getRangeAt(0)
  const container = range.startContainer
  // 如果光标在 el 自身的子节点之间（startContainer === el）
  if (container === el) {
    return rawOffsetUpToIndex(el, range.startOffset)
  }
  // 光标在某个子节点内部
  let pos = rawOffsetUpToNode(el, container)
  if (container.nodeType === Node.TEXT_NODE) {
    pos += range.startOffset
  }
  return pos
}

/** 计算 el 前 n 个子节点的纯文本偏移（img 按标记长度算） */
function rawOffsetUpToIndex(el: HTMLElement, index: number): number {
  let pos = 0
  for (let i = 0; i < index; i++) {
    const node = el.childNodes[i]
    if (!node) break
    if (node.nodeType === Node.TEXT_NODE) {
      pos += node.textContent?.length ?? 0
    } else if (node instanceof HTMLImageElement) {
      const type = node.dataset.type
      const name = node.dataset.name
      if (type === 'bili' && name) pos += name.length + 4
      else if (type === 'tieba' && name) pos += name.length + 4
    }
  }
  return pos
}

/** 计算 el 中 target 节点之前所有兄弟节点的纯文本偏移 */
function rawOffsetUpToNode(el: HTMLElement, target: Node): number {
  let pos = 0
  for (const node of el.childNodes) {
    if (node === target) break
    if (node.nodeType === Node.TEXT_NODE) {
      pos += node.textContent?.length ?? 0
    } else if (node instanceof HTMLImageElement) {
      const type = node.dataset.type
      const name = node.dataset.name
      if (type === 'bili' && name) pos += name.length + 4
      else if (type === 'tieba' && name) pos += name.length + 4
    }
  }
  return pos
}

/** 恢复光标到指定字符偏移 */
function restoreCursorOffset(target: number) {
  const el = editorRef.value
  const sel = window.getSelection()
  if (!el || !sel) return
  let pos = 0
  const walker = document.createTreeWalker(el, NodeFilter.SHOW_ALL, null)
  while (walker.nextNode()) {
    const n = walker.currentNode
    if (n.nodeType === Node.TEXT_NODE) {
      const len = n.textContent?.length ?? 0
      if (pos + len >= target) {
        const range = document.createRange()
        range.setStart(n, Math.min(target - pos, len))
        range.collapse(true)
        sel.removeAllRanges()
        sel.addRange(range)
        return
      }
      pos += len
    } else if (n instanceof HTMLImageElement) {
      const type = n.dataset.type
      const name = n.dataset.name
      let mlen = 0
      if (type === 'bili' && name) mlen = name.length + 4 // {{name}}
      else if (type === 'tieba' && name) mlen = name.length + 4 // ::name::
      if (pos + mlen >= target) {
        // 光标放在 img 后面
        const range = document.createRange()
        range.setStartAfter(n)
        range.collapse(true)
        sel.removeAllRanges()
        sel.addRange(range)
        return
      }
      pos += mlen
    }
  }
}

/** contenteditable input 事件处理：提取 → 只在 HTML 变更时渲染 → 恢复光标 */
function handleEditorInput() {
  const el = editorRef.value
  if (!el) return
  const raw = extractPlainText()
  content.value = raw
  const html = renderToHtml(raw)
  // 仅在渲染结果与当前 DOM 不同时替换，避免普通文本输入重置光标
  if (el.innerHTML !== html) {
    const cursor = saveCursorOffset()
    el.innerHTML = html
    requestAnimationFrame(() => restoreCursorOffset(cursor))
  }
}

function insertEmoji(text: string) {
  // text = {{name}} 或 ::name:: 或 颜文字
  const el = editorRef.value
  if (!el) {
    content.value += text
    return
  }
  // 确保编辑器有焦点，否则 saveCursorOffset 会返回 0
  el.focus()
  const cursor = saveCursorOffset()
  const raw = extractPlainText()
  const before = raw.substring(0, cursor)
  const after = raw.substring(cursor)
  const newRaw = before + text + after
  content.value = newRaw
  el.innerHTML = renderToHtml(newRaw)
  // 光标放在插入文本（标记）的后面
  const newCursor = cursor + text.length
  requestAnimationFrame(() => restoreCursorOffset(newCursor))
}

function toggleEmoji() {
  emojiOpen.value = !emojiOpen.value
}

// 点击外部关闭表情面板
function startLazyObserver() {
  // 断开旧观察器
  if (observer) {
    observer.disconnect()
    observer = null
  }

  const el = commentsPanelRef.value
  if (!el || !props.enabled || !props.postId) return

  // 评论区距视口底部 300px 时触发加载
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
    localStorage.removeItem('simple_theme_visitor_id')
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
  } else {
    authorName.value = ''
    authorEmail.value = ''
    authorUrl.value = ''
  }
  startLazyObserver()

  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement
    const emojiPanel = emojiPanelRef.value
    const isInsidePanel = emojiPanel && emojiPanel.contains(target)
    const isToggleButton = target.closest('.emoji-toggle-btn')

    if (!isInsidePanel && !isToggleButton && emojiOpen.value) {
      emojiOpen.value = false
    }
  })
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

async function loadComments() {
  if (!props.enabled || !props.postId) {
    comments.value = []
    return
  }

  loading.value = true

  try {
    const storedId = localStorage.getItem('simple_theme_visitor_id')
    comments.value = await fetchComments(props.postId, storedId || undefined)
  } catch (error) {
    showError(getErrorMessage(error, '评论加载失败，请稍后重试。'))
  } finally {
    loading.value = false
  }
}

function useReply(id: number) {
  parentCommentId.value = id
}

async function submitComment() {
  if (!authorName.value.trim() || !content.value.trim()) {
    showToast('请填写必填项后再提交。', '提示', { variant: 'warning' })
    return
  }

  if (
    props.formSettings.requireNameEmail &&
    props.formSettings.showEmailField &&
    !authorEmail.value.trim()
  ) {
    showToast('请填写邮箱。', '提示', { variant: 'warning' })
    return
  }

  submitting.value = true
  const loadingToast = showLoadingToast('正在提交评论...', '发送中')

  try {
    const visitorId = crypto.randomUUID()
    const newComment = await createComment({
      post: props.postId,
      parent: parentCommentId.value || undefined,
      author_name: authorName.value.trim(),
      author_email: props.formSettings.showEmailField ? authorEmail.value.trim() : '',
      author_url: props.formSettings.showUrlField ? authorUrl.value.trim() : '',
      content: content.value.trim(),
      client_id: visitorId,
    })

    dismissToast(loadingToast)

    if (cookiesConsent.value) {
      localStorage.setItem(CONSENT_KEY, '1')
      localStorage.setItem('simple_theme_visitor_id', visitorId)
      localStorage.setItem('simple_theme_comment_name', authorName.value)
      localStorage.setItem('simple_theme_comment_email', authorEmail.value)
      localStorage.setItem('simple_theme_comment_url', authorUrl.value)
    } else {
      localStorage.removeItem(CONSENT_KEY)
      localStorage.removeItem('simple_theme_visitor_id')
      localStorage.removeItem('simple_theme_comment_name')
      localStorage.removeItem('simple_theme_comment_email')
      localStorage.removeItem('simple_theme_comment_url')
    }

    content.value = ''
    // 清空 contenteditable 编辑器
    if (editorRef.value) editorRef.value.innerHTML = ''
    parentCommentId.value = 0

    // 手动构造完整的 WordPressComment 对象，确保所有字段齐全
    const localComment: WordPressComment = {
      id: newComment.id,
      parent: newComment.parent,
      date: newComment.date || new Date().toISOString(),
      authorName: newComment.authorName || authorName.value.trim(),
      authorUrl: newComment.authorUrl || authorUrl.value.trim() || '',
      status: 'hold',
      avatar: newComment.avatar || '',
      content: newComment.content || { rendered: content.value.trim() },
      likes: newComment.likes ?? 0,
      metaInfo: newComment.metaInfo || { location: '', browser: '', os: '', ipMask: '' },
      children: [],
    }

    // 将新评论插入本地列表（不需重新拉取，避免 pending 状态看不到）
    if (localComment.parent > 0) {
      // 回复：找到父评论，追加到其 children
      const parent = findComment(comments.value, localComment.parent)
      if (parent) {
        parent.children.push(localComment)
      } else {
        // 如果父评论没加载到，就作为顶层评论追加
        comments.value = [...comments.value, localComment]
      }
    } else {
      // 顶层评论：插入到列表顶部（用新数组确保触发 Vue 响应式更新）
      comments.value = [localComment, ...comments.value]
    }

    showToast('评论提交成功，等待审核。', '成功', { variant: 'success', duration: 3200 })
  } catch (error) {
    dismissToast(loadingToast)
    showError(getErrorMessage(error, '评论提交失败，请稍后重试。'))
  } finally {
    submitting.value = false
  }
}

watch(
  () => [props.postId, props.enabled],
  () => {
    comments.value = []
    startLazyObserver()
  },
)
</script>

<template>
  <section ref="commentsPanelRef" class="comments-panel">
    <!-- Header -->
    <header v-if="enabled" class="comments-header">
      <h3 class="comments-header__title">评论区</h3>
      <span v-if="!loading" class="comments-header__count">{{ comments.length }}</span>
    </header>

    <!-- Disabled states -->
    <div v-if="!enabled" class="comments-empty">
      <div class="comments-empty__illustration">
        <UndrawIllustration name="cancel" width="200" height="150" class="comments-empty__svg" />
      </div>
      <h4 class="comments-empty__title">评论未开启</h4>
      <p class="comments-empty__desc">当前文章未开启评论。</p>
    </div>
    <div v-else-if="formSettings.registrationOnly" class="comments-empty">
      <div class="comments-empty__illustration">
        <UndrawIllustration
          name="access-denied"
          width="200"
          height="150"
          class="comments-empty__svg"
        />
      </div>
      <h4 class="comments-empty__title">仅注册用户可评论</h4>
      <p class="comments-empty__desc">站点设置为仅注册用户可评论，请先登录。</p>
    </div>

    <!-- Comment Form -->
    <form v-else class="comments-form" @submit.prevent="submitComment">
      <div v-if="parentCommentId" class="comments-replying">
        正在回复 <strong>#{{ parentCommentId }}</strong>
        <button type="button" class="comments-replying__cancel" @click="parentCommentId = 0">
          取消
        </button>
      </div>

      <div
        ref="editorRef"
        contenteditable
        class="comments-form__textarea"
        :class="{ 'comments-form__textarea--empty': !content }"
        :data-placeholder="parentCommentId ? '写下你的回复...' : '写下你的评论...'"
        role="textbox"
        @input="handleEditorInput"
      ></div>

      <div v-if="currentUser" class="comments-form__logged-in">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
          <circle cx="12" cy="7" r="4"/>
        </svg>
        <span>已登录为 <strong>{{ currentUser.displayName }}</strong></span>
      </div>
      <div v-else class="comments-form__row">
        <input
          v-model="authorName"
          type="text"
          class="comments-form__input"
          placeholder="昵称 *"
          maxlength="40"
          required
        />
        <input
          v-if="formSettings.showEmailField"
          v-model="authorEmail"
          type="email"
          class="comments-form__input"
          placeholder="邮箱 *"
          maxlength="80"
          :required="formSettings.requireNameEmail"
        />
        <input
          v-if="formSettings.showUrlField"
          v-model="authorUrl"
          type="url"
          class="comments-form__input"
          placeholder="网站"
          maxlength="120"
        />
      </div>

      <div class="comments-form__footer">
        <div class="comments-form__footer-left">
          <label v-if="formSettings.showCookiesOptIn" class="comments-form__remember">
            <input v-model="cookiesConsent" type="checkbox" />
            记住信息
          </label>
        </div>
        <div class="comments-form__footer-right">
          <!-- Emoji Panel (floating above footer) -->
          <div
            ref="emojiPanelRef"
            class="comments-emoji"
            :class="{ 'comments-emoji--open': emojiOpen }"
            @click.stop
          >
            <div class="comments-emoji__tabs">
              <button
                class="comments-emoji__tab"
                :class="{ 'comments-emoji__tab--active': emojiTab === 'bilibili' }"
                type="button"
                @click="emojiTab = 'bilibili'"
              >
                <img
                  class="comments-emoji__tab-icon"
                  src="https://s.nmxc.ltd/sakurairo_vision/@3.0/smilies/bilipng/emoji_keai.png"
                  alt="bilibili"
                />
                bilibili
              </button>
              <button
                class="comments-emoji__tab"
                :class="{ 'comments-emoji__tab--active': emojiTab === 'tieba' }"
                type="button"
                @click="emojiTab = 'tieba'"
              >
                <img
                  class="comments-emoji__tab-icon"
                  src="https://s.nmxc.ltd/sakurairo_vision/@3.0/smilies/tiebapng/icon_haha.png"
                  alt="tieba"
                />
                Tieba
              </button>
              <button
                class="comments-emoji__tab"
                :class="{ 'comments-emoji__tab--active': emojiTab === 'kaomoji' }"
                type="button"
                @click="emojiTab = 'kaomoji'"
              >
                (･∀･) 颜文字
              </button>
            </div>

            <!-- Bilibili -->
            <div v-if="emojiTab === 'bilibili'" class="comments-emoji__grid">
              <button
                v-for="name in bilibiliNames"
                :key="name"
                class="comments-emoji__item comments-emoji__item--img"
                type="button"
                @mousedown.prevent
                @click="insertEmoji('{{' + name + '}}')"
                :title="name"
              >
                <img :src="biliImg(name)" :alt="name" loading="lazy" />
              </button>
            </div>

            <!-- Tieba -->
            <div v-if="emojiTab === 'tieba'" class="comments-emoji__grid">
              <button
                v-for="name in tiebaNames"
                :key="name"
                class="comments-emoji__item comments-emoji__item--img"
                type="button"
                @mousedown.prevent
                @click="insertEmoji('::' + name + '::')"
                :title="name"
              >
                <img :src="tiebaImg(name)" :alt="name" loading="lazy" />
              </button>
            </div>

            <!-- 颜文字 -->
            <div v-if="emojiTab === 'kaomoji'" class="comments-emoji__grid">
              <button
                v-for="e in kaomojiList"
                :key="e"
                class="comments-emoji__item comments-emoji__item--kaomoji"
                type="button"
                @mousedown.prevent
                @click="insertEmoji(e)"
              >
                {{ e }}
              </button>
            </div>
          </div>
          <button type="button" class="emoji-toggle-btn" @click="toggleEmoji" title="表情">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10" />
              <path d="M8 14s1.5 2 4 2 4-2 4-2" />
              <line x1="9" y1="9" x2="9.01" y2="9" />
              <line x1="15" y1="9" x2="15.01" y2="9" />
            </svg>
          </button>
          <button type="submit" class="comments-form__submit" :disabled="submitting">
            {{ submitting ? '提交中...' : '发表评论' }}
          </button>
        </div>
      </div>
    </form>

    <!-- Loading -->
    <div v-if="loading" class="comments-loading">
      <div class="skeleton line" role="status"></div>
      <div class="skeleton line" role="status"></div>
    </div>

    <!-- Empty -->
    <div v-else-if="enabled && comments.length === 0" class="comments-empty">
      <div class="comments-empty__illustration">
        <UndrawIllustration name="chatting" width="200" height="150" class="comments-empty__svg" />
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
      />
    </div>
  </section>
</template>

<style scoped>
.comments-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex: 1;
  padding: 3rem 1rem;
  text-align: center;
}

.comments-empty__illustration {
  width: 100%;
  max-width: 220px;
  margin-bottom: 1.25rem;
}

.comments-empty__svg {
  width: 100%;
  height: auto;
}

.comments-empty__title {
  font-size: 1.125rem;
  font-weight: 625;
  color: var(--foreground);
  margin: 0 0 0.375rem;
  line-height: 1.4;
}

.comments-empty__desc {
  font-size: 0.875rem;
  color: var(--secondary);
  margin: 0;
  line-height: 1.6;
}
</style>
