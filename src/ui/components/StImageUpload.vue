<script setup lang="ts">
/**
 * StImageUpload — 图片选择（WordPress 媒体库）
 *
 * 用法：
 *   <StImageUpload v-model="cover" alt="封面预览" aria-label="封面图" @clear="onRemove" />
 *
 * 媒体库是渐进增强：前台 / 未入队 wp-media 时只提示，不抛异常，
 * 因此这里对 window.wp 做能力检测而非直接调用。
 */
import { computed, ref, watch } from 'vue'
import StIcon from './StIcon.vue'
import StInput from './StInput.vue'

defineOptions({ name: 'StImageUpload' })

/**
 * wp.media 的最小局部声明：只覆盖本项目用到的能力，
 * 用断言取值而非 declare global，避免与业务代码里的全局声明冲突。
 */
interface WpMediaAttachment {
  url?: string
}
interface WpMediaSelection {
  first(): { toJSON(): WpMediaAttachment }
}
interface WpMediaFrame {
  on(event: 'select', handler: () => void): void
  open(): void
  state(): { get(key: 'selection'): WpMediaSelection }
}
type WpMediaFactory = (options: {
  title: string
  button: { text: string }
  multiple: boolean
}) => WpMediaFrame

const props = withDefaults(
  defineProps<{
    disabled?: boolean
    /** 空态文案 */
    placeholder?: string
    /** 预览框宽高比，如 '16 / 9'、'1 / 1' */
    aspect?: string
    /** 预览图替代文本；纯装饰时可省略 */
    alt?: string
    /**
     * 显示手填 URL 输入框。
     * 媒体库是渐进增强，某些前台 / 无 wp.media 的环境下选不了图，
     * 没有这条通道就彻底无法设置图片，所以默认为开。
     */
    urlInput?: boolean
    /** 手填输入框占位文案 */
    urlPlaceholder?: string
    /** 无障碍名称；无可见 label 时必填 */
    ariaLabel?: string
  }>(),
  {
    placeholder: '选择图片',
    aspect: '16 / 9',
    urlInput: true,
    urlPlaceholder: '或粘贴图片地址',
  },
)

const model = defineModel<string>({ default: '' })

const emit = defineEmits<{
  (e: 'change', url: string): void
  (e: 'clear'): void
}>()

/** 媒体库不可用时的降级提示，不用弹窗打断流程 */
const notice = ref('')

const changeLabel = computed(() => (props.ariaLabel ? `${props.ariaLabel}：更换` : '更换图片'))
const removeLabel = computed(() => (props.ariaLabel ? `${props.ariaLabel}：移除` : '移除图片'))
const pickLabel = computed(() => props.ariaLabel ?? props.placeholder)

/** 每次现取并检测：wp.media 可能在任何时刻才被 WP 入队 */
function getMediaFactory(): WpMediaFactory | undefined {
  const host = window as unknown as { wp?: { media?: WpMediaFactory } }
  return typeof host.wp?.media === 'function' ? host.wp.media : undefined
}

function update(url: string) {
  if (model.value === url) return
  model.value = url
  emit('change', url)
}

function pick() {
  if (props.disabled) return
  const media = getMediaFactory()
  if (!media) {
    notice.value = props.urlInput
      ? '当前环境未加载 WordPress 媒体库，请在下方填写图片地址'
      : '当前环境未加载 WordPress 媒体库'
    return
  }
  notice.value = ''
  const frame = media({
    title: '选择图片',
    button: { text: '使用此图片' },
    multiple: false,
  })
  frame.on('select', () => {
    const url = frame.state().get('selection').first().toJSON().url ?? ''
    if (url) update(url)
  })
  frame.open()
}

function clear() {
  if (props.disabled) return
  notice.value = ''
  update('')
  emit('clear')
}

/**
 * 手填 URL 用本地草稿：输入过程中不往上抛，
 * 失焦 / 回车才提交一次，避免每敲一个字符就写一次设置。
 */
const urlDraft = ref(model.value ?? '')
const urlAriaLabel = computed(() => (props.ariaLabel ? `${props.ariaLabel}：图片地址` : '图片地址'))

watch(model, (v) => {
  const next = v ?? ''
  if (next !== urlDraft.value) urlDraft.value = next
})

function commitUrl() {
  const next = urlDraft.value.trim()
  if (next === (model.value ?? '')) return
  notice.value = ''
  update(next)
}
</script>

<template>
  <div class="st-image-upload">
    <div v-if="model" class="st-image-upload__figure" :style="{ aspectRatio: aspect }">
      <img class="st-image-upload__img" :src="model" :alt="alt ?? ''" />
    </div>

    <button
      v-else
      type="button"
      class="st-image-upload__placeholder"
      :style="{ aspectRatio: aspect }"
      :disabled="disabled"
      :aria-label="pickLabel"
      @click="pick"
    >
      <StIcon name="cloud-upload" :size="26" />
      <span class="st-image-upload__hint">{{ placeholder }}</span>
    </button>

    <div v-if="model" class="st-image-upload__actions">
      <button
        type="button"
        class="st-image-upload__action"
        :disabled="disabled"
        :aria-label="changeLabel"
        @click="pick"
      >
        <StIcon name="refresh" :size="14" />
        <span>更换</span>
      </button>
      <button
        type="button"
        class="st-image-upload__action"
        :disabled="disabled"
        :aria-label="removeLabel"
        @click="clear"
      >
        <StIcon name="trash" :size="14" />
        <span>移除</span>
      </button>
    </div>

    <p v-if="notice" class="st-image-upload__notice" role="status">{{ notice }}</p>

    <StInput
      v-if="urlInput"
      v-model="urlDraft"
      size="small"
      :disabled="disabled"
      :placeholder="urlPlaceholder"
      :aria-label="urlAriaLabel"
      @enter="commitUrl"
      @blur="commitUrl"
    />
  </div>
</template>

<style scoped>
.st-image-upload {
  display: flex;
  flex-direction: column;
  gap: 8px;
  box-sizing: border-box;
  width: 100%;
}

/* ==================== 预览 ==================== */
.st-image-upload__figure {
  overflow: hidden;
  border: 1px solid var(--st-border);
  border-radius: var(--radius-medium);
  background-color: var(--st-fill-active);
}

.st-image-upload__img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* ==================== 空态占位 ==================== */
.st-image-upload__placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  box-sizing: border-box;
  width: 100%;
  padding: 12px;
  border: 1px dashed var(--st-border);
  border-radius: var(--radius-medium);
  background-color: var(--st-fill);
  color: var(--st-placeholder);
  font-family: inherit;
  font-size: var(--st-font-small);
  cursor: pointer;
  transition:
    border-color var(--transition-fast),
    background-color var(--transition-fast),
    color var(--transition-fast);
}

.st-image-upload__placeholder:hover:not(:disabled) {
  border-color: var(--ring);
  background-color: var(--st-fill-hover);
  color: var(--st-text);
}

.st-image-upload__placeholder:focus-visible {
  outline: none;
  border-color: var(--ring);
  box-shadow: var(--st-focus-ring);
}

.st-image-upload__placeholder:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.st-image-upload__hint {
  line-height: 1.4;
}

/* ==================== 操作区 ==================== */
.st-image-upload__actions {
  display: flex;
  gap: 8px;
}

.st-image-upload__action {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  height: var(--st-height-small);
  padding: 0 10px;
  border: 1px solid var(--st-border);
  border-radius: var(--radius-small);
  background-color: var(--st-fill);
  color: var(--st-text);
  font-family: inherit;
  font-size: var(--st-font-small);
  cursor: pointer;
  transition:
    border-color var(--transition-fast),
    color var(--transition-fast);
}

.st-image-upload__action:hover:not(:disabled) {
  border-color: var(--st-border-hover);
}

/* 移除是破坏性操作：仅在悬停/聚焦时点亮危险色，避免误触 */
.st-image-upload__action:last-child:hover:not(:disabled) {
  border-color: var(--danger);
  color: var(--danger);
}

.st-image-upload__action:focus-visible {
  outline: none;
  box-shadow: var(--st-focus-ring);
}

.st-image-upload__action:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.st-image-upload__notice {
  margin: 0;
  color: var(--st-placeholder);
  font-size: var(--st-font-small);
  line-height: 1.5;
}

@media (prefers-reduced-motion: reduce) {
  .st-image-upload__placeholder,
  .st-image-upload__action {
    transition: none;
  }
}
</style>
