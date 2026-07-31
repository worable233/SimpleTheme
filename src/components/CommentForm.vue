<script setup lang="ts">
/**
 * CommentForm — 评论输入表单（含 contenteditable 编辑器和表情面板）
 */
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import EmojiPicker from '@/components/EmojiPicker.vue'
import ModalCloseButton from '@/components/ModalCloseButton.vue'
import { renderToHtml } from '@/lib/emoji'
import { fetchCaptcha } from '@/lib/api-comments'
import { showLoadingToast, dismissToast, showToast, showError } from '@/lib/toast'
import type { CommentFormSettings, CaptchaData, UserData } from '@/types/wordpress'

defineOptions({ name: 'CommentForm' })

const props = defineProps<{
  formSettings: CommentFormSettings
  currentUser: UserData | null
  loading?: boolean
  submitting?: boolean
  parentCommentId?: number
}>()

const emit = defineEmits<{
  (e: 'submit', payload: {
    name: string; email: string; url: string; content: string; cookies: boolean
    captchaPayload?: string
    isPrivate?: boolean; mailNotify?: boolean; useMarkdown?: boolean
  }): void
  (e: 'cancel-reply'): void
}>()

const content = defineModel<string>('content', { default: '' })
const authorName = defineModel<string>('name', { default: '' })
const authorEmail = defineModel<string>('email', { default: '' })
const authorUrl = defineModel<string>('url', { default: '' })
const cookiesConsent = defineModel<boolean>('cookies', { default: true })

const emojiOpen = ref(false)
const emojiLeaving = ref(false)
const emojiTab = ref<'bilibili' | 'tieba' | 'dinosaur' | 'kaomoji'>('bilibili')
const editorRef = ref<HTMLDivElement | null>(null)
const emojiPanelRef = ref<HTMLElement | null>(null)
const fileInputRef = ref<HTMLInputElement | null>(null)
const uploading = ref(false)

// Mobile bottom-sheet state
const mobileExpanded = ref(false)
const isMobile = ref(false)

function checkMobile() {
  isMobile.value = window.innerWidth <= 600
  if (!isMobile.value) mobileExpanded.value = false
}

function expandMobile() {
  if (isMobile.value) {
    if (props.currentUser || wizardCompleted.value) {
      mobileExpanded.value = true
    } else {
      openWizard()
    }
  }
}

function collapseMobile() {
  if (isMobile.value) {
    mobileExpanded.value = false
    emojiOpen.value = false
    editorRef.value?.blur()
  }
}

function onDocumentClick(e: MouseEvent) {
  if (!isMobile.value || !mobileExpanded.value) return
  const form = document.querySelector('.comments-form')
  if (form && !form.contains(e.target as Node)) {
    collapseMobile()
  }
}

// ── Wizard modal state ──
const wizardActive = ref(false)
const wizardCompleted = ref(false)
const wizardStep = ref<'name' | 'email' | 'url' | 'options'>('name')
const wizardDirection = ref<'forward' | 'backward'>('forward')
const wizardSteps = computed(() => {
  const steps: { key: 'name' | 'email' | 'url' | 'options'; title: string; desc: string }[] = [
    { key: 'name', title: '怎么称呼你？', desc: '输入你想显示的名称' },
  ]
  if (props.formSettings.showEmailField) {
    steps.push({ key: 'email', title: '留下联系方式', desc: '输入邮箱或 QQ 号' })
  }
  if (props.formSettings.showUrlField) {
    steps.push({ key: 'url', title: '你的网站', desc: '输入你的网站地址（可选）' })
  }
  steps.push({ key: 'options', title: '选项设置', desc: '设置发布选项并提交' })
  return steps
})
const currentStepIndex = computed(() => wizardSteps.value.findIndex(s => s.key === wizardStep.value))
const totalWizardSteps = computed(() => wizardSteps.value.length)
const progressPercent = computed(() => {
  if (totalWizardSteps.value <= 1) return 100
  return (currentStepIndex.value / (totalWizardSteps.value - 1)) * 100
})
const isStepValid = computed(() => {
  switch (wizardStep.value) {
    case 'name': return authorName.value.trim().length > 0
    case 'email': return props.formSettings.requireNameEmail ? authorEmail.value.trim().length > 0 : true
    case 'url': return true
    case 'options': return true
    default: return true
  }
})

// New form fields
const captchaData = ref<CaptchaData | null>(null)
const captchaPayload = ref('')
const isPrivate = ref(false)
const mailNotify = ref(false)
const useMarkdown = ref(false)

// ── CAPTCHA ──
onMounted(async () => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
  document.addEventListener('keydown', onWizardKeydown)
  document.addEventListener('click', onDocumentClick, true)
  if (props.formSettings.captchaEnabled && !props.currentUser) {
    await loadCaptcha()
  }
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', checkMobile)
  document.removeEventListener('keydown', onWizardKeydown)
  document.removeEventListener('click', onDocumentClick, true)
})

async function loadCaptcha() {
  try {
    captchaData.value = await fetchCaptcha()
  } catch {
    captchaData.value = { challenge: '' }
  }
}

function onCaptchaStateChange(e: Event) {
  const detail = (e as CustomEvent).detail
  if (detail?.payload) {
    captchaPayload.value = detail.payload
  }
}

// ── contenteditable 输入框 ──

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
      else if (type === 'dinosaur' && name) text += `#${name}#`
    }
  }
  return text
}

function saveCursorOffset(): number {
  const el = editorRef.value
  const sel = window.getSelection()
  if (!el || !sel || !sel.rangeCount) return 0
  const range = sel.getRangeAt(0)
  const container = range.startContainer
  if (container === el) {
    return rawOffsetUpToIndex(el, range.startOffset)
  }
  let pos = rawOffsetUpToNode(el, container)
  if (container.nodeType === Node.TEXT_NODE) {
    pos += range.startOffset
  }
  return pos
}

function rawOffsetUpToIndex(el: HTMLElement, index: number): number {
  let pos = 0
  for (let i = 0; i < index; i++) {
    const node = el.childNodes[i]
    if (!node) break
    if (node.nodeType === Node.TEXT_NODE) {
      pos += node.textContent?.length ?? 0
    } else if (node instanceof HTMLImageElement) {
      const name = node.dataset.name
      const mlen = name ? name.length + 4 : 0
      pos += mlen
    }
  }
  return pos
}

function rawOffsetUpToNode(el: HTMLElement, target: Node): number {
  let pos = 0
  for (const node of el.childNodes) {
    if (node === target) break
    if (node.nodeType === Node.TEXT_NODE) {
      pos += node.textContent?.length ?? 0
    } else if (node instanceof HTMLImageElement) {
      const name = node.dataset.name
      const mlen = name ? name.length + 4 : 0
      pos += mlen
    }
  }
  return pos
}

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
      const name = n.dataset.name
      const mlen = name ? name.length + 4 : 0
      if (pos + mlen >= target) {
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

function handleEditorInput() {
  const el = editorRef.value
  if (!el) return
  const raw = extractPlainText()
  content.value = raw
  const html = renderToHtml(raw)
  if (el.innerHTML !== html) {
    const cursor = saveCursorOffset()
    el.innerHTML = html
    requestAnimationFrame(() => restoreCursorOffset(cursor))
  }
}

function insertEmoji(text: string) {
  const el = editorRef.value
  if (!el) {
    content.value += text
    return
  }
  el.focus()
  const cursor = saveCursorOffset()
  const raw = extractPlainText()
  const before = raw.substring(0, cursor)
  const after = raw.substring(cursor)
  const newRaw = before + text + after
  content.value = newRaw
  el.innerHTML = renderToHtml(newRaw)
  const newCursor = cursor + text.length
  requestAnimationFrame(() => restoreCursorOffset(newCursor))
}

function toggleEmoji() {
  emojiOpen.value = !emojiOpen.value
}

function openFilePicker() {
  fileInputRef.value?.click()
}

async function uploadImage() {
  const input = fileInputRef.value
  if (!input || !input.files || !input.files[0]) return
  const file = input.files[0]
  if (file.size > 10 * 1024 * 1024) {
    showError('图片不能超过 10MB')
    input.value = ''
    return
  }

  uploading.value = true
  const toastEl = showLoadingToast('正在上传图片...', '图片上传中')

  try {
    const formData = new FormData()
    formData.append('file', file)

    const response = await fetch('https://api.xinyew.cn/api/360tc', {
      method: 'POST',
      body: formData,
    })

    const result = await response.json()

    if (result.errno === 0 && result.data?.url) {
      dismissToast(toastEl)
      showToast('图片上传成功', '成功', { variant: 'success' })
      useMarkdown.value = true
      insertEmoji(`![](${result.data.url})`)
    } else {
      dismissToast(toastEl)
      showError(result.error || '图片上传失败')
    }
  } catch (e) {
    dismissToast(toastEl)
    showError('图片上传失败: ' + (e instanceof Error ? e.message : '网络异常'))
  } finally {
    uploading.value = false
    input.value = ''
  }
}

function onEmojiBeforeLeave() {
  emojiLeaving.value = true
}

function onEmojiAfterLeave() {
  emojiLeaving.value = false
}

// ── Wizard modal functions ──

function openWizard() {
  wizardDirection.value = 'forward'
  wizardStep.value = 'name'
  emojiOpen.value = false
  wizardActive.value = true
}

function closeWizard() {
  wizardActive.value = false
}

function nextStep() {
  const idx = currentStepIndex.value
  if (idx < totalWizardSteps.value - 1) {
    wizardDirection.value = 'forward'
    wizardStep.value = wizardSteps.value[idx + 1]!.key
  }
}

function prevStep() {
  const idx = currentStepIndex.value
  if (idx > 0) {
    wizardDirection.value = 'backward'
    wizardStep.value = wizardSteps.value[idx - 1]!.key
  }
}

function finishWizard() {
  wizardCompleted.value = true
  wizardActive.value = false
  mobileExpanded.value = true
}

function onWizardKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape' && wizardActive.value) {
    closeWizard()
  }
}

function focusWizardInput() {
  nextTick(() => {
    const el = document.querySelector<HTMLInputElement>('.wizard-step .comments-form__input')
    el?.focus()
  })
}

watch(wizardStep, () => {
  focusWizardInput()
})

function clearForm() {
  content.value = ''
  if (editorRef.value) editorRef.value.innerHTML = ''
  captchaPayload.value = ''
  isPrivate.value = false
  mailNotify.value = false
  useMarkdown.value = true
  closeWizard()
  collapseMobile()
  if (props.formSettings.captchaEnabled && !props.currentUser) {
    void loadCaptcha()
  }
}

function handleSubmit() {
  const payload: {
    name: string; email: string; url: string; content: string; cookies: boolean
    captchaPayload?: string
    isPrivate?: boolean; mailNotify?: boolean; useMarkdown?: boolean
  } = {
    name: authorName.value,
    email: authorEmail.value,
    url: authorUrl.value,
    content: content.value,
    cookies: cookiesConsent.value,
    isPrivate: isPrivate.value,
    mailNotify: mailNotify.value,
    useMarkdown: useMarkdown.value,
  }
  if (props.formSettings.captchaEnabled && captchaData.value) {
    payload.captchaPayload = captchaPayload.value
  }
  emit('submit', payload)
}

defineExpose({ clearForm })
</script>

<template>

  <form
    class="comments-form"
    :class="{
      'comments-form--expanded': mobileExpanded,
      'comments-form--emoji-open': isMobile && (emojiOpen || emojiLeaving),
      'comments-form--mobile': isMobile
    }"
    @submit.prevent="handleSubmit"
  >
    <!-- Reply indicator -->
    <div v-if="parentCommentId" class="comments-replying">
      正在回复 <strong>#{{ parentCommentId }}</strong>
      <button type="button" class="comments-replying__cancel" @click="emit('cancel-reply')">
        取消
      </button>
    </div>

    <!-- Input row: textarea + collapsed actions (inline on mobile) -->
    <div class="comments-form__input-row">
      <div
        ref="editorRef"
        contenteditable
        class="comments-form__textarea"
        :class="{
          'comments-form__textarea--empty': !content,
          'comments-form__textarea--collapsed': isMobile && !mobileExpanded
        }"
        :data-placeholder="parentCommentId ? '写下回复...' : '写下评论...'"
        role="textbox"
        @input="handleEditorInput"
        @focus="expandMobile"
        @click="expandMobile"
      ></div>
    </div>

    <!-- Expandable section (above emoji panel on mobile) -->
    <div class="comments-form__expandable" :class="{ 'comments-form__expandable--open': !isMobile || mobileExpanded }">
      <div class="comments-form__expandable-inner">
      <div v-if="currentUser" class="comments-form__logged-in">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
          <circle cx="12" cy="7" r="4"/>
        </svg>
        <span>已登录为 <strong>{{ currentUser.displayName }}</strong></span>
      </div>
      <div v-else-if="!isMobile" class="comments-form__row">
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
          type="text"
          class="comments-form__input"
          placeholder="邮箱 * (支持 QQ 号)"
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

      <div class="comments-form__options">
        <label v-if="formSettings.showPrivateOption !== false" class="comments-form__option" title="评论仅博主和你不见">
          <input v-model="isPrivate" type="checkbox" />
          <span>悄悄话</span>
        </label>
        <label v-if="parentCommentId" class="comments-form__option" title="有回复时邮件通知你">
          <input v-model="mailNotify" type="checkbox" />
          <span>邮件提醒</span>
        </label>
        <label v-if="formSettings.showMarkdownOption !== false && !isMobile" class="comments-form__option" title="启用 Markdown 格式">
          <input v-model="useMarkdown" type="checkbox" />
          <span>Markdown</span>
        </label>
        <label v-if="formSettings.showCookiesOptIn && !currentUser" class="comments-form__option" title="记住信息">
          <input v-model="cookiesConsent" type="checkbox" />
          <span>记住信息</span>
        </label>
      </div>

      <div v-if="formSettings.captchaEnabled && !currentUser" class="comments-form__captcha">
        <altcha-widget
          :challenge="captchaData?.challenge || ''"
          style="--altcha-max-width: 100%"
          @statechange="onCaptchaStateChange"
        ></altcha-widget>
      </div>

      <div class="comments-form__footer">
        <div class="comments-form__footer-right">
          <!-- Desktop emoji panel (dropdown above toggle) -->
          <div v-if="!isMobile && emojiOpen" ref="emojiPanelRef" class="emoji-panel-wrapper emoji-panel-wrapper--dropdown">
            <EmojiPicker
              v-model:tab="emojiTab"
              @select="insertEmoji"
            />
          </div>
          <input ref="fileInputRef" type="file" accept="image/*" hidden @change="uploadImage" />
          <button v-if="formSettings.showImageUpload" type="button" class="emoji-toggle-btn image-upload-btn" :disabled="uploading" @click="openFilePicker" title="上传图片">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
              <circle cx="8.5" cy="8.5" r="1.5"/>
              <polyline points="21 15 16 10 5 21"/>
            </svg>
          </button>
          <button type="button" class="emoji-toggle-btn" @click="toggleEmoji" title="表情">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10" />
              <path d="M8 14s1.5 2 4 2 4-2 4-2" />
              <line x1="9" y1="9" x2="9.01" y2="9" />
              <line x1="15" y1="9" x2="15.01" y2="9" />
            </svg>
          </button>
          <button type="submit" class="comments-form__submit" :disabled="submitting || loading">
            {{ submitting ? '提交中...' : '发表评论' }}
          </button>
        </div>
      </div>
      </div>
    </div>

    <!-- Mobile emoji panel (below expandable section) -->
    <Transition name="emoji-slide" @before-leave="onEmojiBeforeLeave" @after-leave="onEmojiAfterLeave">
      <div v-if="isMobile && emojiOpen" class="emoji-panel-wrapper emoji-panel-wrapper--inline">
        <EmojiPicker
          v-model:tab="emojiTab"
          @select="insertEmoji"
        />
      </div>
    </Transition>
  </form>

  <!-- Mobile wizard modal (Teleported) -->
  <Teleport to="body">
    <Transition name="wizard">
      <div v-if="wizardActive" class="wizard-mask" @click="closeWizard">
        <div class="wizard-modal" @click.stop>
          <!-- Progress bar -->
          <div class="wizard-progress-bar">
            <div class="wizard-progress-bar__fill" :style="{ width: progressPercent + '%' }"></div>
          </div>

          <!-- Close button -->
          <ModalCloseButton class="wizard-close" @click="closeWizard" />

          <!-- Step content with directional slide -->
          <Transition
            :name="wizardDirection === 'forward' ? 'wizard-slide-fwd' : 'wizard-slide-bwd'"
            mode="out-in"
          >
            <div :key="wizardStep" class="wizard-step">
              <!-- Step 1: name -->
              <template v-if="wizardStep === 'name'">
                <div class="wizard-step__icon">
                  <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                  </svg>
                </div>
                <h3 class="wizard-step__title">怎么称呼你？</h3>
                <p class="wizard-step__desc">输入你想显示的名称</p>
                <div class="wizard-step__field">
                  <input
                    v-model="authorName"
                    type="text"
                    class="wizard-step__input"
                    placeholder="输入昵称"
                    maxlength="40"
                    @keydown.enter.prevent="isStepValid && nextStep()"
                  />
                </div>
              </template>

              <!-- Step 2: email -->
              <template v-else-if="wizardStep === 'email'">
                <div class="wizard-step__icon">
                  <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="4" width="20" height="16" rx="2"/>
                    <polyline points="2 4 12 13 22 4"/>
                  </svg>
                </div>
                <h3 class="wizard-step__title">留下联系方式</h3>
                <p class="wizard-step__desc">方便博主与你联系</p>
                <div class="wizard-step__field">
                  <input
                    v-model="authorEmail"
                    type="text"
                    class="wizard-step__input"
                    placeholder="输入邮箱或 QQ 号"
                    maxlength="80"
                    @keydown.enter.prevent="isStepValid && nextStep()"
                  />
                </div>
              </template>

              <!-- Step 3: url -->
              <template v-else-if="wizardStep === 'url'">
                <div class="wizard-step__icon">
                  <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                  </svg>
                </div>
                <h3 class="wizard-step__title">你的网站</h3>
                <p class="wizard-step__desc">可选，点击头像时会用到</p>
                <div class="wizard-step__field">
                  <input
                    v-model="authorUrl"
                    type="url"
                    class="wizard-step__input"
                    placeholder="输入你的网站地址"
                    maxlength="120"
                    @keydown.enter.prevent="nextStep()"
                  />
                </div>
              </template>

              <!-- Step 4: options + finish -->
              <template v-else>
                <div class="wizard-step__icon">
                  <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                  </svg>
                </div>
                <h3 class="wizard-step__title">选项设置</h3>
                <p class="wizard-step__desc">配置你的发布偏好</p>
                <div class="wizard-step__options">
                  <label v-if="formSettings.showPrivateOption !== false" class="wizard-step__option">
                    <input v-model="isPrivate" type="checkbox" class="wizard-step__checkbox" />
                    <span class="wizard-step__option-text">
                      <span class="wizard-step__option-label">悄悄话</span>
                      <span class="wizard-step__option-hint">仅博主和你可见</span>
                    </span>
                  </label>
                  <label v-if="parentCommentId" class="wizard-step__option">
                    <input v-model="mailNotify" type="checkbox" class="wizard-step__checkbox" />
                    <span class="wizard-step__option-text">
                      <span class="wizard-step__option-label">邮件提醒</span>
                      <span class="wizard-step__option-hint">有回复时通知你</span>
                    </span>
                  </label>
                  <label v-if="formSettings.showMarkdownOption !== false" class="wizard-step__option">
                    <input v-model="useMarkdown" type="checkbox" class="wizard-step__checkbox" />
                    <span class="wizard-step__option-text">
                      <span class="wizard-step__option-label">Markdown</span>
                      <span class="wizard-step__option-hint">使用 Markdown 格式</span>
                    </span>
                  </label>
                  <label v-if="formSettings.showCookiesOptIn && !currentUser" class="wizard-step__option">
                    <input v-model="cookiesConsent" type="checkbox" class="wizard-step__checkbox" />
                    <span class="wizard-step__option-text">
                      <span class="wizard-step__option-label">记住信息</span>
                      <span class="wizard-step__option-hint">保存昵称、邮箱等信息</span>
                    </span>
                  </label>
                </div>
                <div v-if="formSettings.captchaEnabled && !currentUser" class="wizard-step__captcha">
                  <altcha-widget
                    :challenge="captchaData?.challenge || ''"
                    style="--altcha-max-width: 100%"
                    @statechange="onCaptchaStateChange"
                  ></altcha-widget>
                </div>
              </template>
            </div>
          </Transition>

          <!-- Navigation -->
          <div class="wizard-nav">
            <button
              v-if="currentStepIndex > 0"
              type="button"
              class="wizard-nav__btn wizard-nav__btn--prev"
              @click="prevStep"
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
              </svg>
              上一步
            </button>
            <button
              v-if="currentStepIndex < totalWizardSteps - 1"
              type="button"
              class="wizard-nav__btn wizard-nav__btn--next"
              :disabled="!isStepValid"
              @click="nextStep"
            >
              下一步
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
              </svg>
            </button>
            <button
              v-else
              type="button"
              class="wizard-nav__btn wizard-nav__btn--submit"
              @click="finishWizard"
            >
              完成
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
/* ── Desktop base styles ── */

.comments-form {
  position: relative;
}

.comments-form__textarea {
  width: 100%;
  min-height: 80px;
  max-height: 400px;
  padding: 10px 12px 6px;
  font-size: 14px;
  line-height: 1.6;
  color: var(--foreground);
  background: var(--faint);
  border: 1px solid var(--border);
  border-radius: 4px;
  font-family: inherit;
  transition: border-color 0.2s;
  white-space: pre-wrap;
  overflow-wrap: break-word;
  -webkit-user-modify: read-write-plaintext-only;
}

.comments-form__textarea:focus {
  outline: none;
  border-color: var(--primary);
  background: var(--card);
}

.comments-form__textarea--empty::before {
  content: attr(data-placeholder);
  color: var(--secondary, #999);
  pointer-events: none;
}

.comments-form__logged-in {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 8px;
  font-size: 13px;
  color: var(--secondary);
}

.comments-form__row {
  display: flex;
  gap: 8px;
  margin-top: 8px;
}

.comments-form__input {
  flex: 1;
  padding: 7px 10px;
  font-size: 13px;
  color: var(--foreground);
  background: var(--faint);
  border: 1px solid var(--border);
  border-radius: 4px;
  font-family: inherit;
  transition: border-color 0.2s;
}

.comments-form__input:focus {
  outline: none;
  border-color: var(--primary);
  background: var(--card);
}

.comments-form__options {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 8px;
  font-size: 13px;
  color: var(--secondary);
}

.comments-form__option {
  display: flex;
  align-items: center;
  gap: 4px;
  cursor: pointer;
  margin: 0;
}

.comments-form__option input {
  margin: 0;
}

.comments-form__captcha {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 8px;
}

.comments-replying {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
  padding: 8px 12px;
  background: var(--accent);
  border-radius: var(--radius-medium, 6px);
  font-size: 13px;
  color: var(--secondary);
}

.comments-replying__cancel {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: var(--secondary);
  cursor: pointer;
  border: none;
  background: none;
  font-family: inherit;
  padding: 4px 8px;
  border-radius: 4px;
  transition: all 0.15s;
}

.comments-replying__cancel:hover {
  color: var(--danger);
  background: rgba(221, 36, 36, 0.08);
}

.comments-form__footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  margin-top: 8px;
}

.comments-form__footer-right {
  /* 表情弹窗（.comments-emoji absolute）的定位锚点 — 悬浮在按钮组上方右对齐 */
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.comments-form__submit {
  font-size: 13px;
  padding: 5px 16px;
  background: var(--primary);
  color: var(--primary-foreground);
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-family: inherit;
  font-weight: 500;
  transition: opacity 0.2s;
}

.comments-form__submit:hover {
  opacity: 0.85;
}

.comments-form__submit:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.emoji-toggle-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 8px;
  background: transparent;
  cursor: pointer;
  color: var(--secondary);
  transition: all 0.15s;
}

.emoji-toggle-btn svg {
  width: 20px;
  height: 20px;
}

.emoji-toggle-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.image-upload-btn svg {
  width: 18px;
  height: 18px;
}

.emoji-toggle-btn:hover {
  background: var(--muted);
  color: var(--foreground);
}

/* 桌面 dropdown 包装器保持 static，避免成为零尺寸锚点；
   面板实际锚到 .comments-form__footer-right */

/* ── Mobile layout (fixed bottom) ── */
.comments-form--mobile {
  position: fixed !important;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 500;
  background: color-mix(in srgb, var(--card) 65%, transparent);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-radius: 14px 14px 0 0;
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.12);
  padding: 0 0 calc(env(safe-area-inset-bottom, 0px) + 8px) 0;
  max-height: 50vh;
  overflow-y: auto;
  overflow-x: hidden;
  box-sizing: border-box;
}

/* ── Mobile bottom sheet (≤600px) ── */

.comments-form__expandable {
  /* Desktop: always fully visible */
  max-height: none;
  opacity: 1;
  transform: translateY(0);
  pointer-events: auto;
}

@media (min-width: 601px) {
  .comments-form__expandable {
    display: block;
    overflow: visible;
    opacity: 1 !important;
    transform: none !important;
    pointer-events: auto !important;
  }
}

@media (max-width: 600px) {
  .comments-form {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 500;
    background: var(--card);
    border-radius: 14px 14px 0 0;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.12);
    padding: 0 0 calc(env(safe-area-inset-bottom, 0px) + 8px) 0;
    max-height: 50vh;
    overflow-y: auto;
    overflow-x: hidden;
    box-sizing: border-box;
  }

  .comments-form--expanded {
    max-height: 85vh;
  }

  .comments-form--emoji-open {
    height: auto !important;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    padding: 0;
    max-height: 85vh;
  }

  .comments-form--emoji-open .emoji-panel-wrapper--inline {
    height: 45vh;
    max-height: 300px;
    flex-shrink: 0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    margin: 0;
  }

  .comments-form--emoji-open .comments-form__input-row {
    flex-shrink: 0;
  }

  .comments-form--emoji-open .comments-form__expandable {
    flex-shrink: 0;
    min-height: 0;
  }

  .comments-form--emoji-open > * {
    margin: 0;
  }

  /* ── Handle bar ── */
	  /* ── Input row: textarea + buttons in flex row ── */
  .comments-form__input-row {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px 0;
    background: color-mix(in srgb, var(--card) 65%, transparent);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
  }

  /* ── Collapsed textarea: compact pill ── */
  .comments-form__textarea--collapsed {
    min-height: 36px;
    max-height: 36px;
    height: 36px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    padding: 4px 10px;
    flex: 1;
    border-radius: 18px;
    margin-bottom: 0;
    background: var(--faint);
    line-height: 26px;
    font-size: 14px;
  }

  /* ── Expanded textarea: normal multi-line ── */
  .comments-form--expanded .comments-form__input-row {
    display: block;
    background: none;
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
  }
  .comments-form--expanded .comments-form__textarea {
    min-height: 80px;
    max-height: 200px;
    white-space: pre-wrap;
    padding: 10px 12px 6px;
    margin: 6px 0 0;
    width: auto;
    flex: none;
  }

  /* ── Emoji toggle button ── */

  	  /* ── Expandable section (mobile QQ-style) ── */
	  .comments-form__expandable {
	    display: grid;
	    grid-template-rows: 0fr;
	    overflow: hidden;
	    opacity: 0;
	    transform: translateY(8px);
	    transition: grid-template-rows 0.4s cubic-bezier(0.22, 1, 0.36, 1),
	                opacity 0.25s ease,
	                transform 0.25s ease;
	    pointer-events: none;
	  }

	  .comments-form__expandable--open {
	    grid-template-rows: 1fr;
	    opacity: 1;
	    transform: translateY(0);
	    pointer-events: auto;
	  }

	  .comments-form__expandable-inner {
	    min-height: 0;
	    padding: 8px 14px 4px;
	    display: flex;
	    flex-direction: column;
	    gap: 10px;
	  }

	  /* ── Stack inputs vertically ── */
	  .comments-form__expandable-inner > .comments-form__row {
	    display: flex;
	    flex-direction: column;
	    gap: 8px;
	    flex: none;
	    margin: 0;
	  }

	  .comments-form__expandable-inner .comments-form__input {
	    width: 100%;
	    box-sizing: border-box;
	    padding: 12px 14px;
	    font-size: 15px;
	    border-radius: 10px;
	    border: 1.5px solid var(--border);
	    background: var(--faint);
	    transition: all 0.2s;
	  }
	  .comments-form__expandable-inner .comments-form__input:focus {
	    border-color: var(--primary);
	    background: var(--card);
	    box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 15%, transparent);
	  }

	  /* ── Logged-in badge ── */
	  .comments-form__expandable-inner > .comments-form__logged-in {
	    margin: 0;
	    padding: 2px 0;
	  }

	  /* ── Options as chip-style toggles ── */
	  .comments-form__expandable-inner > .comments-form__options {
	    display: flex;
	    flex-wrap: wrap;
	    gap: 8px;
	    flex: none;
	    margin: 0;
	  }

	  .comments-form__expandable-inner .comments-form__option {
	      position: relative;
	      display: inline-flex;
	      align-items: center;
	      gap: 4px;
	      padding: 4px 10px;
	      border-radius: 8px;
	      font-size: 13px;
	      background: var(--muted);
	      cursor: pointer;
	      transition: all 0.15s;
	      user-select: none;
	      margin: 0;
	      min-height: 28px;
	    }

	  .comments-form__expandable-inner .comments-form__option:has(input:checked) {
	      background: color-mix(in srgb, var(--primary) 15%, transparent);
	      color: var(--primary);
	    }

	  .comments-form__expandable-inner .comments-form__option input[type="checkbox"] {
	      display: none;
	    }

	  .comments-form__expandable-inner .comments-form__option i {
	      font-size: 15px;
	    }

	  .comments-form__expandable-inner .comments-form__option span {
	      font-size: 12px;
	    }

	  /* ── Captcha ── */
	  .comments-form__expandable-inner > .comments-form__captcha {
	    display: flex;
	    align-items: center;
	    gap: 8px;
	    flex: none;
	    margin: 0;
	  }

	  .comments-form__expandable-inner .comments-form__captcha-question {
	    font-size: 14px;
	    font-weight: 600;
	    padding: 8px 12px;
	    background: var(--muted);
	    border-radius: 8px;
	    white-space: nowrap;
	    flex-shrink: 0;
	  }

	  .comments-form__expandable-inner .comments-form__captcha-input {
	    max-width: 100px;
	  }

	  /* ── Footer toolbar ── */
	  .comments-form__expandable-inner > .comments-form__footer {
	    display: flex;
	    align-items: center;
	    justify-content: space-between;
	    margin: 2px 0 0;
	    flex: none;
	    padding: 0;
	  }

	  .comments-form__expandable-inner .comments-form__footer-right {
	    display: flex;
	    align-items: center;
	    gap: 6px;
	    width: 100%;
	    justify-content: flex-end;
	  }

	  .comments-form__expandable-inner .emoji-toggle-btn {
	    width: 38px;
	    height: 38px;
	    border-radius: 10px;
	  }

	  .comments-form__expandable-inner .emoji-toggle-btn svg {
	    width: 22px;
	    height: 22px;
	  }

	  .comments-form__expandable-inner .comments-form__submit {
	    padding: 9px 22px;
	    font-size: 14px;
	    font-weight: 600;
	    border-radius: 20px;
	    min-height: 38px;
	  }

		  /* ── Mobile emoji panel (inline inside form, full width) ── */
		  .emoji-panel-wrapper--inline {
		    padding: 0;
		    flex-shrink: 0;
		  }

		  /* Reply indicator spacing */
		  .comments-replying {
		    margin: 4px 12px 8px;
		  }
		}

.emoji-slide-enter-active {
  transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}
.emoji-slide-leave-active {
  transition: all 0.2s ease;
}
.emoji-slide-enter-from {
  opacity: 0;
  transform: translateY(20px);
}
.emoji-slide-leave-to {
  opacity: 0;
  transform: translateY(20px);
}

/* ── Wizard Modal (modern, mobile-first) ── */

.wizard-mask {
  position: fixed;
  inset: 0;
  z-index: 10000;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.45);
  backdrop-filter: blur(6px);
  padding: 16px;
}

@media (max-width: 500px) {
  .wizard-mask {
    align-items: flex-end;
    padding: 0;
    backdrop-filter: blur(4px);
  }
}

.wizard-modal {
  position: relative;
  width: 100%;
  max-width: 380px;
  background: var(--card);
  border-radius: 20px;
  box-shadow:
    0 20px 60px rgba(0, 0, 0, 0.15),
    inset 0 1px 0 0 rgba(255, 255, 255, 0.2);
  overflow: hidden;
  animation: wizard-card-enter 0.45s cubic-bezier(0.16, 1, 0.3, 1);
}

@media (max-width: 500px) {
  .wizard-modal {
    max-width: 100%;
    border-radius: 20px 20px 0 0;
    animation: wizard-sheet-enter 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    padding-bottom: env(safe-area-inset-bottom, 12px);
  }
}

@keyframes wizard-card-enter {
  0% {
    opacity: 0;
    transform: scale(0.9) translateY(16px);
  }
  100% {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

@keyframes wizard-sheet-enter {
  0% {
    opacity: 0;
    transform: translateY(100%);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

/* ── Progress bar ── */

.wizard-progress-bar {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: var(--border);
  z-index: 1;
}

.wizard-progress-bar__fill {
  height: 100%;
  background: var(--primary);
  transition: width 0.4s cubic-bezier(0.22, 1, 0.36, 1);
  border-radius: 0 2px 2px 0;
}

/* ── Close button（外观由 ModalCloseButton 统一，此处仅定位） ── */

.wizard-close {
  position: absolute;
  top: 14px;
  right: 14px;
  z-index: 2;
}

/* ── Step content ── */

.wizard-step {
  padding: 28px 24px 20px;
  min-height: 180px;
  display: flex;
  flex-direction: column;
}

@media (max-width: 500px) {
  .wizard-step {
    padding: 24px 20px 16px;
    min-height: 160px;
    overflow-y: auto;
    max-height: 55vh;
  }
}

.wizard-step__icon {
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--muted);
  border-radius: 12px;
  margin-bottom: 14px;
  color: var(--primary);
}

.wizard-step__title {
  font-size: 1.3rem;
  font-weight: 650;
  color: var(--foreground);
  margin: 0 0 4px;
  letter-spacing: -0.02em;
}

.wizard-step__desc {
  font-size: 0.85rem;
  color: var(--secondary);
  margin: 0 0 22px;
  line-height: 1.5;
}

.wizard-step__field {
  /* Input wrapper */
}

.wizard-step__input {
  width: 100%;
  box-sizing: border-box;
  padding: 12px 14px;
  font-size: 15px;
  border: 1.5px solid var(--border);
  border-radius: 10px;
  background: var(--faint);
  color: var(--foreground);
  font-family: inherit;
  transition: all 0.2s;
  outline: none;
}

.wizard-step__input:focus {
  border-color: var(--primary);
  background: var(--card);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 15%, transparent);
}

.wizard-step__input::placeholder {
  color: var(--secondary);
  opacity: 0.6;
}

/* ── Options (step 4) ── */

.wizard-step__options {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 16px;
}

.wizard-step__option {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  background: var(--faint);
  border-radius: 10px;
  cursor: pointer;
  transition: background 0.15s;
  margin: 0;
}

.wizard-step__option:hover {
  background: var(--muted);
}

.wizard-step__checkbox {
  width: 17px;
  height: 17px;
  accent-color: var(--primary);
  flex-shrink: 0;
  margin: 0;
  cursor: pointer;
}

.wizard-step__option-text {
  display: flex;
  flex-direction: column;
  gap: 1px;
}

.wizard-step__option-label {
  font-size: 14px;
  font-weight: 500;
  color: var(--foreground);
}

.wizard-step__option-hint {
  font-size: 12px;
  color: var(--secondary);
  opacity: 0.75;
}

/* ── Captcha ── */

.wizard-step__captcha {
  display: flex;
  align-items: center;
  gap: 10px;
}

.wizard-step__captcha-question {
  font-size: 14px;
  font-weight: 600;
  color: var(--foreground);
  white-space: nowrap;
  padding: 8px 12px;
  background: var(--muted);
  border-radius: 8px;
  user-select: none;
  flex-shrink: 0;
}

.wizard-step__captcha-input {
  max-width: 110px;
}

/* ── Navigation ── */

.wizard-nav {
  display: flex;
  padding: 0 24px 20px;
  gap: 12px;
}

@media (max-width: 500px) {
  .wizard-nav {
    padding: 0 20px 16px;
    gap: 10px;
  }
}

.wizard-nav__btn {
  padding: 10px 18px;
  font-size: 14px;
  font-weight: 500;
  border-radius: 10px;
  border: none;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.15s;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

@media (max-width: 500px) {
  .wizard-nav__btn {
    padding: 12px 18px;
    min-height: 44px;
    font-size: 15px;
    flex: 1;
    justify-content: center;
  }
}

.wizard-nav__btn--prev {
  background: var(--muted);
  color: var(--secondary);
}

.wizard-nav__btn--prev:hover {
  background: var(--border);
  color: var(--foreground);
}

.wizard-nav__btn--next {
  background: var(--primary);
  color: var(--primary-foreground);
  margin-left: auto;
}

.wizard-nav__btn--next:hover {
  filter: brightness(1.1);
}

.wizard-nav__btn--next:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  filter: none;
}

.wizard-nav__btn--submit {
  background: var(--primary);
  color: var(--primary-foreground);
  padding: 10px 24px;
  margin-left: auto;
}

.wizard-nav__btn--submit:hover {
  filter: brightness(1.1);
}

.wizard-nav__btn--submit:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  filter: none;
}

@media (max-width: 500px) {
  .wizard-nav__btn--submit {
    flex: 1;
    justify-content: center;
    padding: 12px 24px;
    min-height: 44px;
    font-size: 15px;
  }
}

/* ── Dark-mode glass consistency ── */

body[data-theme='dark'] .wizard-modal {
  background: var(--card);
  box-shadow:
    0 20px 60px rgba(0, 0, 0, 0.4),
    inset 0 1px 0 0 rgba(255, 255, 255, 0.08);
}

body[data-theme='dark'] .wizard-modal .wizard-progress-bar {
  background: rgba(255, 255, 255, 0.08);
}

body[data-theme='dark'] .wizard-step__option {
  background: rgba(255, 255, 255, 0.04);
}

body[data-theme='dark'] .wizard-step__option:hover {
  background: rgba(255, 255, 255, 0.08);
}

body[data-theme='dark'] .wizard-step__input {
  background: rgba(255, 255, 255, 0.05);
}

body[data-theme='dark'] .wizard-step__input:focus {
  background: rgba(255, 255, 255, 0.08);
}

/* ── Dark mode: no border highlight on collapsed empty textarea ── */
body[data-theme='dark'] .comments-form__textarea--empty.comments-form__textarea--collapsed {
  border-color: transparent;
}
body[data-theme='dark'] .comments-form__textarea--empty.comments-form__textarea--collapsed:focus {
  border-color: transparent;
  background: var(--faint);
}

/* ── Dark mode: visible frosted glass on mobile ── */
body[data-theme='dark'] .comments-form--mobile {
  background: rgba(255, 255, 255, 0.06);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
}
body[data-theme='dark'] .comments-form--mobile .comments-form__input-row {
  background: rgba(255, 255, 255, 0.04);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}

/* ── Transitions ── */

/* Mask fade */
.wizard-enter-active {
  transition: opacity 0.25s ease;
}

.wizard-leave-active {
  transition: opacity 0.2s ease;
}

.wizard-enter-from,
.wizard-leave-to {
  opacity: 0;
}

/* Slide forward: new from right, old to left */
.wizard-slide-fwd-enter-active {
  transition: all 0.28s cubic-bezier(0.22, 1, 0.36, 1);
}

.wizard-slide-fwd-leave-active {
  transition: all 0.18s ease;
}

.wizard-slide-fwd-enter-from {
  opacity: 0;
  transform: translateX(35px);
}

.wizard-slide-fwd-leave-to {
  opacity: 0;
  transform: translateX(-20px);
}

/* Slide backward: new from left, old to right */
.wizard-slide-bwd-enter-active {
  transition: all 0.28s cubic-bezier(0.22, 1, 0.36, 1);
}

.wizard-slide-bwd-leave-active {
  transition: all 0.18s ease;
}

.wizard-slide-bwd-enter-from {
  opacity: 0;
  transform: translateX(-35px);
}

.wizard-slide-bwd-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

/* ── Dark mode ── */

body[data-theme='dark'] .wizard-mask {
  background: rgba(0, 0, 0, 0.7);
}

body[data-theme='dark'] .wizard-step__input:focus {
  box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.08);
}
</style>

