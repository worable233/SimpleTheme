<script setup lang="ts">
/**
 * StInput — 输入框（单行 / 多行 / 密码 / 成对）
 *
 * 用法：
 *   <StInput v-model="name" placeholder="昵称" />
 *   <StInput v-model="q" clearable @enter="search" />
 *   <StInput v-model="bio" type="textarea" :autosize="{ minRows: 2, maxRows: 6 }" show-count />
 *   <StInput v-model="pwd" type="password" show-password-on="click" />
 *   <StInput v-model="range" pair separator="~" :placeholder="['起', '止']" />
 *
 * 结构对齐 Naive UI Input（src/input）：
 *   外层 .st-input  →  .st-input__wrapper  →  [prefix] [input|textarea] [suffix]
 *   双层边框 .st-input__border（常态）+ .st-input__state-border（hover/focus/status）
 *   遮罩式 placeholder（textarea/autosize 需要跟随滚动），word count，password eye
 *
 * 受控：始终以 modelValue 为准；未绑定 v-model 时输入不会回显。
 */
import { Comment, Fragment, computed, isVNode, nextTick, onBeforeUnmount, onMounted, ref, useAttrs, useSlots, watch } from 'vue'
import StIcon from './StIcon.vue'
import StSpinner from './StSpinner.vue'
import type { StSize, StStatus } from '../types'

// inheritAttrs:false —— 默认透传会把属性落到根 div，导致 required / minlength /
// id / name / inputmode 这类**非声明 prop** 永远到不了内层原生控件：浏览器
// 原生校验静默失效，<label for> 也关联不上。
// 手动分流：class/style 留根元素（布局钩子），其余给内层控件。
defineOptions({ name: 'StInput', inheritAttrs: false })

type StInputType = 'text' | 'textarea' | 'password' | (string & {})

const props = withDefaults(
  defineProps<{
    /** 尺寸 */
    size?: StSize
    /** 控件类型 */
    type?: StInputType
    /** 占位文案；pair 模式下为 [左, 右] */
    placeholder?: string | [string, string]
    disabled?: boolean
    readonly?: boolean
    /** 有值时显示清除按钮 */
    clearable?: boolean
    /** 校验状态 */
    status?: StStatus
    /** 圆角胶囊（textarea 无效） */
    round?: boolean
    /** 等宽字体，适合 token / 代码类输入 */
    mono?: boolean
    /** textarea 行数 */
    rows?: number | string
    /** textarea 自动高度：true 或 { minRows, maxRows } */
    autosize?: boolean | { minRows?: number; maxRows?: number }
    /** 是否允许手动拖拽调整高度（textarea） */
    resizable?: boolean
    /** 显示字数统计 */
    showCount?: boolean
    /**
     * 显示 loading 图标。
     * 注意：Naive 用 `loading !== undefined` 判断，未传时保持 undefined。
     * Vue 会把未声明的 boolean 属性强制转为 false，因此这里显式声明
     * `boolean | undefined` 并在 withDefaults 里默认 undefined，
     * 以保留“未传”与“显式 false”的区别（前者不渲染 suffix）。
     */
    loading?: boolean | undefined
    /** 成对输入（如区间），modelValue 为 [左, 右] */
    pair?: boolean
    /** pair 中间的分隔符 */
    separator?: string
    /** 密码显示触发方式 */
    showPasswordOn?: 'mousedown' | 'click'
    maxlength?: number
    minlength?: number
    autocomplete?: string
    /** 无障碍名称；无可见 label 时必填 */
    ariaLabel?: string
  }>(),
  {
    size: 'medium',
    type: 'text',
    placeholder: '',
    clearable: false,
    rows: 3,
    autosize: false,
    resizable: true,
    showCount: false,
    pair: false,
    separator: '~',
    // 显式 undefined：保留 Naive `loading !== undefined` 的语义
    loading: undefined,
  },
)

const model = defineModel<string | number | undefined>({ default: '' })

const emit = defineEmits<{
  (e: 'enter', ev: KeyboardEvent): void
  (e: 'focus', ev: FocusEvent): void
  (e: 'blur', ev: FocusEvent): void
  (e: 'clear'): void
  (e: 'input', value: string | [string, string]): void
}>()

const slots = useSlots()
const attrs = useAttrs()

const inputEl = ref<HTMLInputElement | null>(null)
const textareaEl = ref<HTMLTextAreaElement | null>(null)
const textareaMirrorEl = ref<HTMLDivElement | null>(null)
const input2El = ref<HTMLInputElement | null>(null)

const focused = ref(false)
/** Naive 用 hoverRef 参与 clear 按钮显隐判断 */
const hovered = ref(false)
const passwordVisible = ref(false)

/** class/style 留在根 div：调用方仍能传布局类（如给外层定宽） */
const rootAttrs = computed(() => ({ class: attrs.class, style: attrs.style }))

/** 其余非声明属性一律下沉到内层原生控件 */
const inputAttrs = computed(() =>
  Object.fromEntries(Object.entries(attrs).filter(([k]) => k !== 'class' && k !== 'style')),
)

/** pair 模式下左/右值（pair 的数组值通过 unknown 通道存取，避免污染标量模型类型） */
const isPair = computed(() => props.pair)
const pairValue = computed<[string, string]>(() => {
  const v = model.value as unknown
  if (Array.isArray(v)) return [String(v[0] ?? ''), String(v[1] ?? '')]
  return ['', '']
})
const singleValue = computed<string | number>(() => {
  const v = model.value as unknown
  if (Array.isArray(v)) return ''
  return (v as string | number) ?? ''
})

/** Naive 结构：占位符拆成左右两份 */
const placeholderPair = computed<[string, string]>(() =>
  Array.isArray(props.placeholder)
    ? [props.placeholder[0] ?? '', props.placeholder[1] ?? '']
    : [props.placeholder, props.placeholder],
)

const hasValue = computed(() => {
  const v = model.value as unknown
  if (Array.isArray(v)) return v.some((x) => x !== '' && x != null)
  return v !== '' && v !== null && v !== undefined
})

/**
 * Naive showClearButton（Input.tsx:287-310）：
 *   disabled || readonly || !clearable || (!focus && !hover) → false
 *   有值时再要求 (hover || focus)。
 * 即清除按钮只在聚焦/悬停且非空时出现，而非只要有值就显示。
 */
const showClear = computed(() => {
  if (props.disabled || props.readonly || !props.clearable) return false
  if (!focused.value && !hovered.value) return false
  if (props.pair) return hasValue.value && (hovered.value || focused.value)
  return hasValue.value && (hovered.value || focused.value)
})
const showEye = computed(() => props.type === 'password' && !!props.showPasswordOn)

/**
 * 判断具名插槽是否真的有内容。
 * 直接移植 Naive 的 resolve-slot.ts：Vue 里“插槽函数存在”≠“有内容”——
 * 渲染函数父组件即使不传子节点，也可能留下一个只含注释节点的插槽函数。
 * Naive 用 ensureValidVNode 递归排除 Comment / 空 Fragment 来判断；
 * 不这么做就会渲染出 0 宽的空 suffix 容器（带 margin-left:4px，
 * 把输入区挤窄 4px）。
 */
function ensureValidVNode(vnodes: unknown): boolean {
  const arr = Array.isArray(vnodes) ? vnodes : [vnodes]
  return arr.some((child) => {
    if (child == null || child === false) return false
    if (!isVNode(child)) return true
    if (child.type === Comment) return false
    if (child.type === Fragment && !ensureValidVNode(child.children)) return false
    return true
  })
}

function slotHasContent(name: string): boolean {
  const fn = slots[name]
  if (!fn) return false
  return ensureValidVNode(fn())
}

/** Naive: children || clearable || showCount || showPasswordOn || loading !== undefined */
const showSuffix = computed(
  () =>
    !isPair.value &&
    (slotHasContent('suffix') ||
      props.clearable ||
      props.showCount ||
      showEye.value ||
      props.loading !== undefined),
)

/**
 * 占位遮罩显隐 —— 对齐 Naive `showPlaceholder`：
 *   非合字输入中 && 值为空（pair 时左值空） && 占位文案非空。
 * Naive 把原生 ::placeholder 设成透明，用这个遮罩层顶替，原因是
 * textarea 滚动 / autosize 时占位要能跟随。
 */
const showPlaceholder = computed<[boolean, boolean]>(() => {
  const p = placeholderPair.value
  if (isPair.value) {
    return [pairValue.value[0] === '', pairValue.value[1] === ''].map(
      (empty, i) => empty && !!p[i],
    ) as [boolean, boolean]
  }
  return [singleValue.value === '' && !!p[0], false]
})

/** 字数：按码点计数（Naive 的 len 实现），避免 emoji 被算成 2 */
const wordCount = computed(() => {
  const v = singleValue.value
  if (typeof v !== 'string') return 0
  return Array.from(v).length
})

const countText = computed(() =>
  props.maxlength === undefined ? String(wordCount.value) : `${wordCount.value} / ${props.maxlength}`,
)

const classes = computed(() => [
  'st-input',
  `st-input--${props.size}`,
  {
    'st-input--textarea': props.type === 'textarea',
    'st-input--pair': props.pair,
    'st-input--round': props.round && props.type !== 'textarea',
    'st-input--mono': props.mono,
    'st-input--disabled': props.disabled,
    'st-input--readonly': props.readonly,
    'st-input--focus': focused.value,
    'st-input--autosize': props.type === 'textarea' && !!props.autosize,
    // Naive: cNotM('autosize', 'width: 100%') 与 cM('resizable')
    'st-input--resizable': props.type === 'textarea' && !props.autosize && props.resizable,
    [`st-input--${props.status}`]: !!props.status,
  },
])

/** 当前应渲染的 input type（密码可见时切 text） */
const innerInputType = computed(() =>
  props.type === 'password' && passwordVisible.value ? 'text' : props.type,
)

function onFocus(ev: FocusEvent) {
  focused.value = true
  emit('focus', ev)
}

function onBlur(ev: FocusEvent) {
  focused.value = false
  emit('blur', ev)
}

function onEnter(ev: KeyboardEvent) {
  emit('enter', ev)
}

function emitInput(value: string | [string, string]) {
  emit('input', value)
}

function onSingleInput(ev: Event) {
  const val = (ev.target as HTMLInputElement).value
  model.value = val
  emitInput(val)
  syncMirror()
}

function setPair(next: [string, string]) {
  model.value = next as unknown as string
}

function onPairInput(index: 0 | 1, ev: Event) {
  const val = (ev.target as HTMLInputElement).value
  const next: [string, string] = [...pairValue.value]
  next[index] = val
  setPair(next)
  emitInput(next)
}

function clear() {
  if (props.pair) setPair(['', ''])
  else model.value = ''
  emit('clear')
  ;(props.type === 'textarea' ? textareaEl.value : inputEl.value)?.focus()
}

function togglePassword() {
  passwordVisible.value = !passwordVisible.value
}

function onEyeMousedown(ev: MouseEvent) {
  if (props.showPasswordOn === 'mousedown') {
    ev.preventDefault()
    togglePassword()
  }
}

function onEyeClick() {
  if (props.showPasswordOn === 'click') togglePassword()
}

// ==================== textarea autosize ====================
/** 把当前值写进镜像元素，CSS 再据镜像高度撑开 textarea（Naive 的做法）*/
function syncMirror() {
  if (props.type !== 'textarea' || !props.autosize) return
  const el = textareaMirrorEl.value
  if (el) el.textContent = `${singleValue.value}\r\n`
}

/** 依据 autosize 的 minRows/maxRows 计算镜像的 min/max 高度 */
function updateTextareaBounds() {
  const { autosize, type } = props
  if (type !== 'textarea' || !autosize) return
  const el = textareaMirrorEl.value
  const target = textareaEl.value
  if (!el || !target) return
  const cs = window.getComputedStyle(target)
  const pt = Number.parseFloat(cs.paddingTop) || 0
  const pb = Number.parseFloat(cs.paddingBottom) || 0
  const lh = Number.parseFloat(cs.lineHeight) || 0
  if (typeof autosize === 'boolean') {
    el.style.minHeight = ''
    el.style.maxHeight = ''
    return
  }
  if (autosize.minRows) {
    el.style.minHeight = `${pt + pb + lh * Math.max(autosize.minRows, 1)}px`
  }
  if (autosize.maxRows) {
    el.style.maxHeight = `${pt + pb + lh * autosize.maxRows}px`
  }
}

watch(singleValue, () => {
  if (props.type === 'textarea') {
    if (props.autosize) nextTick(syncMirror)
    else nextTick(autoGrowPlainTextarea)
  }
})

/** 非 autosize 也允许随内容增高（resizable 时靠 CSS；这里只保证初始高度正确）*/
function autoGrowPlainTextarea() {
  const el = textareaEl.value
  if (!el || props.autosize) return
  el.style.height = 'auto'
  el.style.height = `${el.scrollHeight}px`
}

function focus() {
  ;(props.type === 'textarea' ? textareaEl.value : inputEl.value)?.focus()
}

defineExpose({ focus, el: inputEl, textareaEl })

onMounted(() => {
  syncMirror()
  updateTextareaBounds()
  if (props.type === 'textarea' && props.autosize) {
    // 尺寸受字体/父容器影响，挂载后按实际 computed style 再算一次
    nextTick(updateTextareaBounds)
  }
})

onBeforeUnmount(() => {
  /* 预留：若将来加 ResizeObserver 在此断开 */
})
</script>

<template>
  <div
    v-bind="rootAttrs"
    :class="classes"
    @mouseenter="hovered = true"
    @mouseleave="hovered = false"
  >
    <div class="st-input__wrapper">
      <span v-if="slotHasContent('prefix')" class="st-input__prefix"><slot name="prefix" /></span>

      <!-- textarea -->
      <template v-if="type === 'textarea'">
        <div class="st-input__textarea">
          <textarea
            ref="textareaEl"
            v-bind="inputAttrs"
            class="st-input__textarea-el"
            :value="singleValue"
            :placeholder="placeholderPair[0]"
            :disabled="disabled"
            :readonly="readonly"
            :rows="Number(rows)"
            :maxlength="maxlength"
            :minlength="minlength"
            :aria-label="ariaLabel"
            :aria-invalid="status === 'error' ? true : undefined"
            @input="onSingleInput"
            @focus="onFocus"
            @blur="onBlur"
            @keydown.enter="onEnter"
          />
          <!-- 占位遮罩：原生 ::placeholder 已被设为透明，由它顶替（textarea 可随滚动） -->
          <div v-if="showPlaceholder[0]" class="st-input__placeholder">
            <span>{{ placeholderPair[0] }}</span>
          </div>
          <!-- autosize 镜像：不可见，靠它把 textarea 撑高 -->
          <div v-if="autosize" ref="textareaMirrorEl" class="st-input__textarea-mirror" />
        </div>
      </template>

      <!-- 单行 input -->
      <template v-else-if="!pair">
        <div class="st-input__input">
          <input
            ref="inputEl"
            v-bind="inputAttrs"
            class="st-input__input-el"
            :type="innerInputType"
            :value="singleValue"
            :placeholder="placeholderPair[0]"
            :disabled="disabled"
            :readonly="readonly"
            :maxlength="maxlength"
            :minlength="minlength"
            :autocomplete="autocomplete"
            :aria-label="ariaLabel"
            :aria-invalid="status === 'error' ? true : undefined"
            @input="onSingleInput"
            @focus="onFocus"
            @blur="onBlur"
            @keydown.enter="onEnter"
          />
          <div v-if="showPlaceholder[0]" class="st-input__placeholder">
            <span>{{ placeholderPair[0] }}</span>
          </div>
          <!-- autosize 的单行镜像（Naive 亦有，只是内容为空时写 &nbsp;） -->
          <div v-if="autosize && type !== 'textarea'" class="st-input__input-mirror">&nbsp;</div>
        </div>
      </template>

      <!-- pair 左值 -->
      <template v-else>
        <div class="st-input__input">
          <input
            ref="inputEl"
            v-bind="inputAttrs"
            class="st-input__input-el"
            :type="type"
            :value="pairValue[0]"
            :placeholder="placeholderPair[0]"
            :disabled="disabled"
            :readonly="readonly"
            :maxlength="maxlength"
            :aria-label="ariaLabel"
            @input="onPairInput(0, $event)"
            @focus="onFocus"
            @blur="onBlur"
          />
          <div v-if="showPlaceholder[0]" class="st-input__placeholder">
            <span>{{ placeholderPair[0] }}</span>
          </div>
        </div>
      </template>

      <!-- suffix（非 pair：clear / loading / count / eye / 自定义）-->
      <div v-if="showSuffix" class="st-input__suffix">
        <!-- base-clear：固定 1em×1em，清除图标与占位图标做交叉切换（Naive 结构）-->
        <div v-if="clearable || slots['clear-icon-placeholder']" class="st-input__clear-wrap">
          <button
            v-if="showClear"
            type="button"
            class="st-input__clear"
            aria-label="清除"
            tabindex="-1"
            @mousedown.prevent
            @click="clear"
          >
            <slot name="clear-icon">
              <StIcon name="x" :size="16" />
            </slot>
          </button>
          <span v-else class="st-input__clear-placeholder">
            <slot name="clear-icon-placeholder" />
          </span>
        </div>
        <slot name="suffix" />
        <StSpinner v-if="loading" :size="size" class="st-input__loading" />
        <span v-if="showCount && type !== 'textarea'" class="st-input__word-count">
          {{ countText }}
        </span>
        <span
          v-if="showEye"
          class="st-input__eye"
          role="button"
          tabindex="-1"
          :aria-label="passwordVisible ? '隐藏密码' : '显示密码'"
          @mousedown="onEyeMousedown"
          @click="onEyeClick"
        >
          <slot :name="passwordVisible ? 'password-visible-icon' : 'password-invisible-icon'">
            <!-- Naive Input.tsx:1374-1387：可见时用 EyeIcon（睁眼），
                 不可见时用 EyeOffIcon（划线眼）。 -->
            <StIcon :name="passwordVisible ? 'eye' : 'eye-off'" :size="16" />
          </slot>
        </span>
      </div>
    </div>

    <!-- pair 分隔符与右值 -->
    <template v-if="pair">
      <span class="st-input__separator">
        <slot name="separator">{{ separator }}</slot>
      </span>
      <div class="st-input__wrapper">
        <div class="st-input__input">
          <input
            ref="input2El"
            v-bind="inputAttrs"
            class="st-input__input-el"
            :type="type"
            :value="pairValue[1]"
            :placeholder="placeholderPair[1]"
            :disabled="disabled"
            :readonly="readonly"
            :maxlength="maxlength"
            @input="onPairInput(1, $event)"
            @focus="onFocus"
            @blur="onBlur"
          />
          <div v-if="showPlaceholder[1]" class="st-input__placeholder">
            <span>{{ placeholderPair[1] }}</span>
          </div>
        </div>
        <div v-if="slotHasContent('suffix') || clearable" class="st-input__suffix">
          <div v-if="clearable" class="st-input__clear-wrap">
            <button
              v-if="showClear"
              type="button"
              class="st-input__clear"
              aria-label="清除"
              tabindex="-1"
              @mousedown.prevent
              @click="clear"
            >
              <slot name="clear-icon">
                <StIcon name="x" :size="16" />
              </slot>
            </button>
            <span v-else class="st-input__clear-placeholder">
              <slot name="clear-icon-placeholder" />
            </span>
          </div>
          <slot name="suffix" />
        </div>
      </div>
    </template>

    <!-- textarea 的字数统计（Naive 放在右下角）-->
    <span v-if="showCount && type === 'textarea'" class="st-input__word-count st-input__word-count--textarea">
      {{ countText }}
    </span>

    <!-- 双层边框 -->
    <div class="st-input__border" />
    <div class="st-input__state-border" />
  </div>
</template>

<style scoped>
/* ==========================================================================
 * StInput 样式 —— 逐条移植自 Naive UI
 *   src/input/src/styles/input.cssr.ts   （结构与过渡）
 *   src/input/styles/light.ts            （各态取值）
 *   src/input/styles/_common.ts          （padding / clearSize）
 *   src/_internal/clear/src/styles/index.cssr.ts （清除按钮）
 * 每条规则上方标注 Naive 出处行号，便于回查。
 * ========================================================================== */

/* ---- 根：input.cssr.ts:39-53 ----
 * 注意 Naive 把 --n-padding-vertical 定义在根上（由 --n-height 与
 * --n-font-size 推导），textarea 的内边距复用它。 */
.st-input {
  max-width: 100%;
  cursor: text;
  line-height: 1.5;
  z-index: auto;
  outline: none;
  box-sizing: border-box;
  position: relative;
  display: inline-flex;
  border-radius: var(--st-input-radius);
  background-color: var(--st-input-fill);
  font-size: var(--st-input-font-size);
  font-weight: 400;
  /* input.cssr.ts:50 —— 只过渡背景色 */
  transition: background-color 0.3s var(--ease-in-out);

  /* input.cssr.ts:53 —— 垂直内边距公式 */
  --st-input-padding-vertical: calc(
    (var(--st-input-height) - 1.5 * var(--st-input-font-size)) / 2
  );

  /* 尺寸相关变量（默认 medium），由下方 cM(size) 覆盖 */
  --st-input-height: var(--st-height-medium);
  --st-input-font-size: var(--st-font-medium);
  --st-input-radius: var(--radius-medium);
  --st-input-padding-left: 12px;
  --st-input-padding-right: 12px;
  --st-input-icon-size: 16px;
  /* light.ts: lineHeightTextarea = lineHeight = 1.6（common 的 lineHeight） */
  --st-input-line-height-textarea: 1.6;
  /* input.cssr.ts:117-120 —— cNotM('autosize', 'width: 100%') */
  width: 100%;
  /* clearSize: _common.ts */
  --st-input-clear-size: 16px;
}

/* ---- input.cssr.ts:56-60 ---- */
.st-input__input,
.st-input__textarea {
  overflow: hidden;
  flex-grow: 1;
  position: relative;
}

/* ---- input.cssr.ts:61-75 ---- */
.st-input__input-el,
.st-input__textarea-el,
.st-input__input-mirror,
.st-input__textarea-mirror,
.st-input__placeholder {
  box-sizing: border-box;
  font-size: inherit;
  line-height: 1.5;
  font-family: inherit;
  border: none;
  outline: none;
  background-color: transparent;
  text-align: inherit;
  transition:
    -webkit-text-fill-color 0.3s var(--ease-in-out),
    caret-color 0.3s var(--ease-in-out),
    color 0.3s var(--ease-in-out),
    text-decoration-color 0.3s var(--ease-in-out);
}

/* ---- input.cssr.ts:76-98 ---- */
.st-input__input-el,
.st-input__textarea-el {
  -webkit-appearance: none;
  scrollbar-width: none;
  width: 100%;
  min-width: 0;
  /* light.ts: textDecorationColor = textColor2 */
  text-decoration-color: var(--st-text);
  color: var(--st-text);
  /* light.ts: caretColor = primaryColor */
  caret-color: var(--primary);
  background-color: transparent;
}

.st-input__input-el::-webkit-scrollbar,
.st-input__input-el::-webkit-scrollbar-track-piece,
.st-input__input-el::-webkit-scrollbar-thumb,
.st-input__textarea-el::-webkit-scrollbar,
.st-input__textarea-el::-webkit-scrollbar-track-piece,
.st-input__textarea-el::-webkit-scrollbar-thumb {
  width: 0;
  height: 0;
  display: none;
}

/* input.cssr.ts:91-94 —— 原生占位设为全透明，改用下方遮罩层 */
.st-input__input-el::placeholder,
.st-input__textarea-el::placeholder {
  color: transparent;
  -webkit-text-fill-color: transparent !important;
}

/* ---- input.cssr.ts:102-116 —— 占位遮罩 ---- */
.st-input__placeholder {
  pointer-events: none;
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  overflow: hidden;
  color: var(--st-placeholder);
}

.st-input__placeholder span {
  width: 100%;
  display: inline-block;
}

/* input.cssr.ts:117-119 —— textarea 的占位可溢出（跟随滚动） */
.st-input--textarea .st-input__placeholder {
  overflow: visible;
}

/* input.cssr.ts:161-163 —— 非 textarea 的占位不换行 */
.st-input:not(.st-input--textarea) .st-input__placeholder {
  white-space: nowrap;
}

/* ---- input.cssr.ts:99-101 —— round 用 height/2，不是 9999px ---- */
.st-input--round:not(.st-input--textarea) {
  border-radius: calc(var(--st-input-height) / 2);
}

/* ==================== wrapper：input.cssr.ts:130-137 ==================== */
.st-input__wrapper {
  overflow: hidden;
  display: inline-flex;
  flex-grow: 1;
  position: relative;
  padding-left: var(--st-input-padding-left);
  padding-right: var(--st-input-padding-right);
}

/* ---- input.cssr.ts:138-147 —— 单行镜像 ---- */
.st-input__input-mirror {
  padding: 0;
  height: var(--st-input-height);
  line-height: var(--st-input-height);
  overflow: hidden;
  visibility: hidden;
  position: static;
  white-space: pre;
  pointer-events: none;
}

/* ---- input.cssr.ts:148-160 —— 单行输入 ---- */
.st-input__input-el {
  padding: 0;
  height: var(--st-input-height);
  line-height: var(--st-input-height);
}

.st-input__input-el[type='password']::-ms-reveal {
  display: none;
}

/* input.cssr.ts:154-159 —— 输入框后紧跟的占位需垂直居中 */
.st-input__input-el + .st-input__placeholder {
  display: flex;
  align-items: center;
}

/* ---- input.cssr.ts:164-169 —— 眼睛 ---- */
.st-input__eye {
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.3s var(--ease-in-out);
}

/* ==================== textarea：input.cssr.ts:171-208 ==================== */
.st-input--textarea {
  width: 100%;
  /* 根需纵向排列以容下右下角字数 */
  flex-direction: column;
}

.st-input--textarea .st-input__wrapper {
  align-items: stretch;
}

/* input.cssr.ts:172-176 —— 字数绝对定位在右下 */
.st-input--textarea .st-input__word-count {
  position: absolute;
  right: var(--st-input-padding-right);
  bottom: var(--st-input-padding-vertical);
}

/* input.cssr.ts:177-182 —— resizable：可纵向拖动 */
.st-input--resizable .st-input__wrapper {
  resize: vertical;
  min-height: var(--st-input-height);
}

/* input.cssr.ts:183-198 —— textarea / 镜像 / 占位共用几何 */
.st-input__textarea-el,
.st-input__textarea-mirror,
.st-input--textarea .st-input__placeholder {
  height: 100%;
  padding-left: 0;
  padding-right: 0;
  padding-top: var(--st-input-padding-vertical);
  padding-bottom: var(--st-input-padding-vertical);
  word-break: break-word;
  display: inline-block;
  vertical-align: bottom;
  box-sizing: border-box;
  line-height: var(--st-input-line-height-textarea);
  margin: 0;
  resize: none;
  white-space: pre-wrap;
  scroll-padding-block-end: var(--st-input-padding-vertical);
}

/* input.cssr.ts:199-207 */
.st-input__textarea-mirror {
  width: 100%;
  pointer-events: none;
  overflow: hidden;
  visibility: hidden;
  position: static;
  white-space: pre-wrap;
  overflow-wrap: break-word;
}

/* input.cssr.ts:121-128 —— 仅 autosize 绝对定位（由镜像撑高） */
.st-input--autosize .st-input__textarea-el,
.st-input--autosize .st-input__input-el {
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
}

/* 非 autosize 的 textarea：高度由自身 rows / 拖动决定 */
.st-input--textarea:not(.st-input--autosize) .st-input__textarea {
  flex-grow: 1;
}

.st-input--textarea:not(.st-input--autosize) .st-input__textarea-el {
  height: auto;
}

/* ==================== pair：input.cssr.ts:209-226 ==================== */
.st-input--pair .st-input__input-el,
.st-input--pair .st-input__placeholder {
  text-align: center;
}

.st-input__separator {
  display: flex;
  align-items: center;
  transition: color 0.3s var(--ease-in-out);
  color: var(--st-text);
  white-space: nowrap;
}

.st-input__separator .st-icon {
  color: var(--st-placeholder);
}

/* ==================== 禁用：input.cssr.ts:227-257 ==================== */
.st-input--disabled {
  cursor: not-allowed;
  background-color: var(--st-input-fill-disabled);
}

.st-input--disabled .st-input__border {
  border: 1px solid var(--st-input-border-color);
}

.st-input--disabled .st-input__input-el,
.st-input--disabled .st-input__textarea-el {
  cursor: not-allowed;
  color: var(--st-text-disabled);
  text-decoration-color: var(--st-text-disabled);
}

.st-input--disabled .st-input__placeholder {
  color: var(--st-placeholder);
}

.st-input--disabled .st-input__separator {
  color: var(--st-text-disabled);
}

.st-input--disabled .st-input__separator .st-icon {
  color: var(--st-text-disabled);
}

.st-input--disabled .st-input__word-count {
  color: var(--st-text-disabled);
}

.st-input--disabled .st-input__prefix,
.st-input--disabled .st-input__suffix {
  color: var(--st-text-disabled);
}

.st-input--disabled .st-input__prefix .st-icon,
.st-input--disabled .st-input__suffix .st-icon {
  color: var(--st-text-disabled);
}

/* ==================== 非禁用交互：input.cssr.ts:258-279 ==================== */
.st-input:not(.st-input--disabled) .st-input__eye {
  color: var(--st-placeholder);
  cursor: pointer;
}

.st-input:not(.st-input--disabled) .st-input__eye:hover {
  color: var(--st-text);
}

.st-input:not(.st-input--disabled) .st-input__eye:active {
  color: var(--st-text-disabled);
}

/* input.cssr.ts:270-272 —— hover 改背景 + state-border */
.st-input:not(.st-input--disabled):hover {
  background-color: var(--st-input-fill);
}

.st-input:not(.st-input--disabled):hover .st-input__state-border {
  border: 1px solid var(--st-input-border-hover);
}

/* input.cssr.ts:273-278 —— focus 同时改背景与 state-border */
.st-input:not(.st-input--disabled).st-input--focus {
  background-color: var(--st-input-fill-focus);
}

.st-input:not(.st-input--disabled).st-input--focus .st-input__state-border {
  border: 1px solid var(--st-input-border-focus);
  box-shadow: var(--st-input-shadow-focus);
}

/* ==================== 双层边框：input.cssr.ts:280-297 ====================
 * Naive 用完整 border 简写（--n-border = `1px solid <color>`），
 * 两层都 border-radius: inherit；state 层常态透明。 */
.st-input__border,
.st-input__state-border {
  box-sizing: border-box;
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  pointer-events: none;
  border-radius: inherit;
  border: 1px solid var(--st-input-border-color);
  transition:
    box-shadow 0.3s var(--ease-in-out),
    border-color 0.3s var(--ease-in-out);
}

.st-input__state-border {
  border-color: transparent;
  z-index: 1;
}

/* ==================== prefix / suffix：input.cssr.ts:298-339 ==================== */
.st-input__prefix {
  margin-right: 4px;
}

.st-input__suffix {
  margin-left: 4px;
}

.st-input__prefix,
.st-input__suffix {
  transition: color 0.3s var(--ease-in-out);
  flex-wrap: nowrap;
  flex-shrink: 0;
  line-height: var(--st-input-height);
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  /* light.ts: suffixTextColor = textColor2（正文色，非占位色） */
  color: var(--st-text);
}

/* input.cssr.ts:313-317 —— loading 图标 */
.st-input__suffix .st-input__loading {
  font-size: var(--st-input-icon-size);
  margin: 0 2px;
  color: var(--primary);
}

/* input.cssr.ts:329-335 —— 直接子级图标统一 iconColor */
.st-input__prefix > .st-icon,
.st-input__suffix > .st-icon {
  transition: color 0.3s var(--ease-in-out);
  color: var(--st-placeholder);
  font-size: var(--st-input-icon-size);
}

/* ==================== 清除按钮：clear/index.cssr.ts ====================
 * 外层 1em×1em 定位容器；clear 与 placeholder 两态绝对居中叠放，
 * 由 iconSwitchTransition 做交叉切换（见下方 --clear 过渡）。 */
.st-input__clear-wrap {
  flex-shrink: 0;
  height: 1em;
  width: 1em;
  position: relative;
}

.st-input__clear,
.st-input__clear-placeholder {
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translateX(-50%) translateY(-50%);
  display: flex;
}

.st-input__clear {
  font-size: var(--st-input-clear-size);
  height: 1em;
  width: 1em;
  padding: 0;
  border: none;
  background: transparent;
  cursor: pointer;
  /* light.ts: clearColor = neutral(alpha4) */
  color: var(--st-placeholder);
  transition: color 0.3s var(--ease-in-out);
}

.st-input__clear:hover {
  color: var(--st-text);
}

.st-input__clear:active {
  color: var(--st-text-disabled);
}

/* ==================== 字数：input.cssr.ts:340-348 ==================== */
.st-input__word-count {
  pointer-events: none;
  line-height: 1.5;
  font-size: 0.85em;
  color: var(--st-placeholder);
  transition: color 0.3s var(--ease-in-out);
  margin-left: 4px;
  /* 注意是 font-variant 简写（Naive 原样） */
  font-variant: tabular-nums;
  white-space: nowrap;
}

/* ==================== 状态色：input.cssr.ts:349-382 ====================
 * warning/error 各自一套：常态边框、hover 边框、focus 背景+边框+光环、
 * caret 与 loading 颜色。 */
.st-input--warning .st-input__state-border {
  border: 1px solid var(--warning);
}

.st-input--warning .st-input__input-el,
.st-input--warning .st-input__textarea-el {
  caret-color: var(--warning);
}

.st-input--warning .st-input__loading {
  color: var(--warning);
}

.st-input:not(.st-input--disabled).st-input--warning:hover .st-input__state-border {
  border: 1px solid var(--warning);
}

.st-input:not(.st-input--disabled).st-input--warning.st-input--focus,
.st-input:not(.st-input--disabled).st-input--warning:focus {
  background-color: var(--st-input-fill-focus-warning);
}

.st-input:not(.st-input--disabled).st-input--warning.st-input--focus
  .st-input__state-border {
  border: 1px solid var(--warning);
  box-shadow: var(--st-input-shadow-focus-warning);
}

.st-input--error .st-input__state-border {
  border: 1px solid var(--danger);
}

.st-input--error .st-input__input-el,
.st-input--error .st-input__textarea-el {
  caret-color: var(--danger);
}

.st-input--error .st-input__loading {
  color: var(--danger);
}

.st-input:not(.st-input--disabled).st-input--error:hover .st-input__state-border {
  border: 1px solid var(--danger);
}

.st-input:not(.st-input--disabled).st-input--error.st-input--focus,
.st-input:not(.st-input--disabled).st-input--error:focus {
  background-color: var(--st-input-fill-focus-error);
}

.st-input:not(.st-input--disabled).st-input--error.st-input--focus
  .st-input__state-border {
  border: 1px solid var(--danger);
  box-shadow: var(--st-input-shadow-focus-error);
}

/* ==================== 尺寸：_common.ts + base 的 height/fontSize ==================== */
.st-input--tiny {
  --st-input-height: var(--st-height-tiny);
  --st-input-font-size: var(--st-font-tiny);
  --st-input-radius: var(--radius-small);
  --st-input-padding-left: 8px;
  --st-input-padding-right: 8px;
}

.st-input--small {
  --st-input-height: var(--st-height-small);
  --st-input-font-size: var(--st-font-small);
  --st-input-radius: var(--radius-small);
  --st-input-padding-left: 10px;
  --st-input-padding-right: 10px;
}

.st-input--large {
  --st-input-height: var(--st-height-large);
  --st-input-font-size: var(--st-font-large);
  --st-input-radius: var(--radius-medium);
  --st-input-padding-left: 14px;
  --st-input-padding-right: 14px;
}

.st-input--mono .st-input__input-el,
.st-input--mono .st-input__textarea-el {
  font-family: var(--font-code);
}

/* ==================== 只读 ==================== */
.st-input--readonly {
  background-color: var(--st-input-fill-disabled);
}

@media (prefers-reduced-motion: reduce) {
  .st-input,
  .st-input__input-el,
  .st-input__textarea-el,
  .st-input__input-mirror,
  .st-input__textarea-mirror,
  .st-input__placeholder,
  .st-input__eye,
  .st-input__clear,
  .st-input__word-count,
  .st-input__prefix,
  .st-input__suffix,
  .st-input__separator,
  .st-input__border,
  .st-input__state-border {
    transition: none;
  }
}
</style>
