<script setup lang="ts">
/**
 * StSelect — 下拉选择
 *
 * 用法：
 *   <StSelect v-model="layout" :options="options" />
 *   <StSelect v-model="id" :options="options" clearable placeholder="全部" />
 *   <StSelect v-model="type" :options="options" status="error" aria-label="类型" />
 *   <StSelect v-model="q" :options="options" filterable placeholder="搜索…" />
 *
 * 视觉逐条移植自 Naive UI：
 *   src/_internal/selection/src/styles/index.cssr.ts  触发器（双层边框）
 *   src/_internal/selection/styles/{light,dark}.ts    各态取值
 *   src/_internal/clear/src/styles/index.cssr.ts      清除按钮 + icon-switch
 *   src/_internal/select-menu/src/styles/index.cssr.ts 菜单 / 选项 / 空态
 *   src/_internal/select-menu/styles/light.ts         选项态色 / 组头
 * Naive 的触发器是 `div[role=combobox]`（非 button），后缀里箭头与清除
 * 交叉切换；本组件结构与之对齐。reka-ui 继续负责键盘导航、typeahead、
 * 浮层定位与焦点管理。
 *
 * filterable 对齐 Naive：输入框在触发器内（打开时才出现），已选标签让位，
 * 占位显示当前选项名；选项按 label 做不区分大小写的包含匹配。远程搜索
 * （Naive 的 remote）由调用方自行换 options 实现。
 */
import { computed, nextTick, ref, watch } from 'vue'
import {
  SelectContent,
  SelectItem,
  SelectItemIndicator,
  SelectItemText,
  SelectPortal,
  SelectRoot,
  SelectTrigger,
  SelectViewport,
} from 'reka-ui'
import StIcon from './StIcon.vue'
import StSpinner from './StSpinner.vue'
import type { StOption, StSize, StStatus } from '../types'

defineOptions({ name: 'StSelect' })

const props = withDefaults(
  defineProps<{
    options: StOption[]
    /** 尺寸 */
    size?: StSize
    placeholder?: string
    disabled?: boolean
    /** 有值时显示清除按钮（悬停或展开时出现，对齐 Naive） */
    clearable?: boolean
    /** 校验状态 */
    status?: StStatus
    /** 触发器内提供输入框，按 label 过滤选项（Naive filterable） */
    filterable?: boolean
    /** filterable 展开时的占位覆盖；不传则用已选项名 / placeholder */
    filterPlaceholder?: string
    /** 过滤后无匹配时的空态文案 */
    emptyText?: string
    /** 无障碍名称；无可见 label 时必填 */
    ariaLabel?: string
    /** 显示下拉箭头（Naive showArrow，默认 true） */
    showArrow?: boolean
    /** 显示边框与状态边框（Naive bordered，默认 true） */
    bordered?: boolean
    /** 加载中：后缀箭头位置换成转圈（Naive loading） */
    loading?: boolean
  }>(),
  {
    size: 'medium',
    placeholder: '请选择',
    filterable: false,
    emptyText: '无匹配项',
    showArrow: true,
    bordered: true,
    loading: false,
  },
)

const model = defineModel<string | number | null>({ default: null })

const emit = defineEmits<{
  (e: 'change', value: string | number | null): void
  (e: 'search', keyword: string): void
}>()

const rootEl = ref<HTMLElement | null>(null)
/** filterable 输入框（打开时才渲染） */
const inputEl = ref<HTMLInputElement | null>(null)
/** filterable 搜索关键词 */
const keyword = ref('')
/** 受控展开态：onOpenChange 里同步 */
const openState = ref(false)
/** 悬停 / 聚焦：参与清除按钮与焦点环显隐（对齐 Naive 的 hoverRef / focused） */
const hovered = ref(false)
const focused = ref(false)

const hasValue = computed(
  () => model.value !== '' && model.value !== null && model.value !== undefined,
)

const selectedOption = computed(() => props.options.find((o) => o.value === model.value) ?? null)

const selectedLabel = computed(() => selectedOption.value?.label ?? '')

/** filterable 时按 label 做包含匹配；否则原样透传 */
const visibleOptions = computed(() => {
  if (!props.filterable) return props.options
  const q = keyword.value.trim().toLowerCase()
  if (!q) return props.options
  return props.options.filter((o) => o.label.toLowerCase().includes(q))
})

/** Naive mergedClearable：hover 或 active（展开）且有值才显示清除 */
const showClear = computed(
  () => props.clearable && !props.disabled && hasValue.value && (hovered.value || openState.value),
)

/** 后缀三态：loading / clear / arrow（Naive Suffix + NBaseClear） */
const suffixState = computed<'loading' | 'clear' | 'arrow' | 'none'>(() => {
  if (props.loading) return 'loading'
  if (showClear.value) return 'clear'
  if (props.showArrow) return 'arrow'
  return 'none'
})

/** filterable 展开时隐藏已选标签，让位给输入框 */
const showLabel = computed(() => !(props.filterable && openState.value) && hasValue.value)

/** filterable 展开时占位沿用已选项名（Naive filterablePlaceholder） */
const effectivePlaceholder = computed(
  () => props.filterPlaceholder || selectedLabel.value || props.placeholder,
)

const showPlaceholder = computed(() => {
  if (props.filterable && openState.value) return !keyword.value
  return !hasValue.value
})

const classes = computed(() => [
  'st-select',
  `st-select--${props.size}`,
  {
    'st-select--disabled': props.disabled,
    'st-select--filterable': props.filterable,
    'st-select--bordered': props.bordered,
    'st-select--focus': focused.value,
    'st-select--open': openState.value,
    [`st-select--${props.status}`]: !!props.status,
  },
])

watch(keyword, (v) => {
  if (props.filterable) emit('search', v)
})

function onOpenChange(open: boolean) {
  openState.value = open
  if (!open) {
    keyword.value = ''
    return
  }
  if (!props.filterable) return
  // reka 在浮层定位完成（isPositioned）后才把焦点移到列表项，且这一步是
  // 异步的；nextTick 会早于它执行导致焦点被抢走。这里等一帧再抢回来，
  // 之后键盘输入才会落到输入框（input 上已 keydown.stop，不会触发 reka
  // 的 typeahead）。
  nextTick(() => {
    requestAnimationFrame(() => {
      inputEl.value?.focus()
    })
  })
}

/** reka-ui 的回调参数是宽泛的 AcceptableValue，这里收窄成组件契约里的值域 */
function handleChange(value: unknown) {
  const next = (value ?? null) as string | number | null
  model.value = next
  emit('change', next)
}

/** 当前展开面板内的可选项（跳过 disabled），供输入框方向键定位高亮 */
function menuItems(): HTMLElement[] {
  const content = document.querySelector('.st-select__content')
  if (!content) return []
  return [...content.querySelectorAll<HTMLElement>('.st-select__item:not([data-disabled])')]
}

/**
 * 把高亮从输入框移进列表（reka 的高亮 = 列表项获得焦点）。
 * 输入框保持焦点时方向键到不了列表，这里手动接管：定位到目标项后，
 * 后续按键由 reka 的 item / content keydown 处理。
 */
function moveHighlight(to: 'next' | 'prev' | 'first' | 'last') {
  const items = menuItems()
  if (!items.length) return
  const active = document.activeElement
  let i = items.indexOf(active as HTMLElement)
  if (to === 'first') i = 0
  else if (to === 'last') i = items.length - 1
  else if (i < 0) i = to === 'next' ? 0 : items.length - 1
  else i = (i + (to === 'next' ? 1 : -1) + items.length) % items.length
  items[i]?.focus({ preventScroll: true })
}

/**
 * 输入框键盘策略：
 * - 可打印字符只留给输入框（否则触发 reka 的 typeahead，输入即选中，与
 *   「先筛选再确认」冲突）；
 * - 方向键 / Home / End / Enter 由本组件转发到列表项（见 moveHighlight）；
 * - Escape / Tab 放行给 reka 关闭面板或移出焦点。
 */
function onInputKeydown(e: KeyboardEvent) {
  if (e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
    e.stopPropagation()
    return
  }
  const nav: Record<string, 'next' | 'prev' | 'first' | 'last'> = {
    ArrowDown: 'next',
    ArrowUp: 'prev',
    Home: 'first',
    End: 'last',
  }
  const dir = nav[e.key]
  if (dir) {
    e.preventDefault()
    e.stopPropagation()
    moveHighlight(dir)
    return
  }
  if (e.key === 'Enter') {
    // 焦点还在输入框说明尚未高亮：把 Enter 转交给第一项（筛选后的首选）
    const first = menuItems()[0]
    if (first && document.activeElement !== first) {
      e.preventDefault()
      e.stopPropagation()
      first.focus({ preventScroll: true })
      first.dispatchEvent(
        new KeyboardEvent('keydown', { key: 'Enter', bubbles: true, cancelable: true }),
      )
    }
  }
}

function clear() {
  model.value = null
  emit('change', null)
  keyword.value = ''
  // 清除后把焦点还给触发器：面板不打开，键盘用户不会丢焦点
  rootEl.value?.querySelector<HTMLElement>('.st-select__trigger')?.focus()
}
</script>

<template>
  <div ref="rootEl" :class="classes" @mouseenter="hovered = true" @mouseleave="hovered = false">
    <SelectRoot
      :open="openState"
      :model-value="model"
      :disabled="disabled"
      @update:open="onOpenChange"
      @update:model-value="handleChange"
    >
      <!-- Naive 触发器是 div[role=combobox]，故用 as="div" 而非默认 button -->
      <SelectTrigger
        as="div"
        class="st-select__trigger"
        :tabindex="disabled ? undefined : 0"
        :aria-label="ariaLabel"
        :aria-disabled="disabled || undefined"
        :aria-invalid="status === 'error' ? true : undefined"
        @focusin="focused = true"
        @focusout="focused = false"
      >
        <input
          v-if="filterable && openState"
          ref="inputEl"
          v-model="keyword"
          class="st-select__input"
          type="text"
          :readonly="disabled"
          :disabled="disabled"
          :aria-label="ariaLabel || placeholder"
          autocomplete="off"
          spellcheck="false"
          @keydown="onInputKeydown"
          @pointerdown.stop
          @mousedown.stop
          @click.stop
        />
        <span v-if="showLabel" class="st-select__value">{{ selectedLabel }}</span>
        <span v-else-if="showPlaceholder" class="st-select__placeholder">
          {{ effectivePlaceholder }}
        </span>

        <!-- 后缀：箭头 ⇄ 清除交叉切换（Naive NBaseClear + icon-switch） -->
        <span class="st-select__suffix">
          <Transition name="st-icon-switch">
            <span v-if="suffixState === 'loading'" key="loading" class="st-select__loading">
              <StSpinner :size="16" />
            </span>
            <button
              v-else-if="suffixState === 'clear'"
              key="clear"
              type="button"
              class="st-select__clear"
              aria-label="清除"
              tabindex="-1"
              @pointerdown.stop
              @mousedown.prevent.stop
              @click.stop="clear"
            >
              <StIcon name="x-circle" :size="16" />
            </button>
            <span v-else-if="suffixState === 'arrow'" key="arrow" class="st-select__arrow">
              <StIcon name="chevron-down" :size="16" />
            </span>
          </Transition>
        </span>
      </SelectTrigger>

      <!-- Teleport 到 body：脱离后台卡片的 overflow/层叠上下文 -->
      <SelectPortal to="body">
        <SelectContent
          class="st-select__content st-transition-fade-scale"
          :class="`st-select__content--${size}`"
          position="popper"
          side="bottom"
          align="start"
          :side-offset="4"
        >
          <SelectViewport class="st-select__viewport">
            <SelectItem
              v-for="option in visibleOptions"
              :key="option.value"
              class="st-select__item"
              :value="option.value"
              :disabled="option.disabled"
              :text-value="option.label"
            >
              <SelectItemText class="st-select__item-label">
                {{ option.label }}
              </SelectItemText>
              <SelectItemIndicator class="st-select__indicator">
                <StIcon name="check" :size="16" />
              </SelectItemIndicator>
            </SelectItem>
            <div v-if="visibleOptions.length === 0" class="st-select__empty">
              {{ emptyText }}
            </div>
          </SelectViewport>
        </SelectContent>
      </SelectPortal>
    </SelectRoot>

    <!-- 双层边框：常态 border + hover/focus/open 的 state-border（Naive 结构） -->
    <div v-if="bordered" class="st-select__border" />
    <div v-if="bordered" class="st-select__state-border" />
  </div>
</template>

<style scoped>
/* ===================================================================
 * StSelect 样式 —— 逐条移植自 Naive UI
 *   src/_internal/selection/src/styles/index.cssr.ts    （触发器 / 边框 / 状态）
 *   src/_internal/selection/styles/_common.ts           （paddingSingle / clearSize / arrowSize）
 *   src/_internal/clear/src/styles/index.cssr.ts         （清除按钮）
 *   src/_internal/select-menu/src/styles/index.cssr.ts   （菜单 / 选项 / 空态）
 *   src/_internal/select-menu/styles/_common.ts          （menu padding / option padding）
 *   src/_internal/select-menu/styles/light.ts            （选项态色）
 * 每条规则上方标注 Naive 出处，便于回查。
 * =================================================================== */

/* ---- base-selection（index.cssr.ts:38-51） ---- */
.st-select {
  position: relative;
  display: inline-block;
  box-sizing: border-box;
  width: 100%;
  max-width: 100%;
  vertical-align: bottom;
  font-size: var(--st-select-font-size);
  line-height: 1.5;
  border-radius: var(--st-select-radius);

  /* 尺寸相关变量（默认 medium），由下方 cM(size) 覆盖 */
  --st-select-height: var(--st-height-medium);
  --st-select-font-size: var(--st-font-medium);
  --st-select-radius: var(--radius-medium);
  --st-select-option-height: var(--st-height-medium);
  --st-select-menu-max-height: calc(var(--st-height-medium) * 7.6);
  /* selection/_common.ts: paddingSingle = '0 26px 0 12px' */
  --st-select-pad-left: 12px;
  --st-select-pad-right: 26px;
  /* selection/_common.ts: arrowSize / clearSize */
  --st-select-icon-size: 16px;
}

/* ---- 尺寸：height/fontSize 取 common/_common.ts；Naive select 的左右内边距不随尺寸变 ---- */
.st-select--tiny {
  --st-select-height: var(--st-height-tiny);
  --st-select-font-size: var(--st-font-tiny);
  --st-select-radius: var(--radius-small);
  --st-select-option-height: var(--st-height-tiny);
  --st-select-menu-max-height: calc(var(--st-height-tiny) * 7.6);
}

.st-select--small {
  --st-select-height: var(--st-height-small);
  --st-select-font-size: var(--st-font-small);
  --st-select-radius: var(--radius-small);
  --st-select-option-height: var(--st-height-small);
  --st-select-menu-max-height: calc(var(--st-height-small) * 7.6);
}

.st-select--large {
  --st-select-height: var(--st-height-large);
  --st-select-font-size: var(--st-font-large);
  --st-select-radius: var(--radius-medium);
  --st-select-option-height: var(--st-height-large);
  --st-select-menu-max-height: calc(var(--st-height-large) * 7.6);
}

/* ==================== 触发器 ====================
 * base-selection-label（index.cssr.ts:134-150）+ base-selection-input（:152-163） */
.st-select__trigger {
  display: inline-flex;
  align-items: center;
  box-sizing: border-box;
  position: relative;
  width: 100%;
  height: var(--st-select-height);
  padding: 0 var(--st-select-pad-right) 0 var(--st-select-pad-left);
  border-radius: inherit;
  background-color: var(--st-select-fill);
  color: var(--st-text);
  font-family: inherit;
  font-size: inherit;
  line-height: 1.5;
  text-align: left;
  cursor: pointer;
  outline: none;
  /* index.cssr.ts:144-147 —— background-color / color 过渡 */
  transition:
    background-color 0.3s var(--ease-in-out),
    color 0.3s var(--ease-in-out);
}

/* base-selection-input__content（index.cssr.ts:166-170） */
.st-select__value {
  flex: 1 1 auto;
  min-width: 0;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

/* base-selection-placeholder（index.cssr.ts:107-114） */
.st-select__placeholder {
  flex: 1 1 auto;
  min-width: 0;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  color: var(--st-placeholder);
  transition: color 0.3s var(--ease-in-out);
}

/* filterable 展开时输入框与占位共处一行：占位绝对定位叠在输入框上
   （Naive 用 input-mirror 量宽，这里直接定位到左右内边距之间）。 */
.st-select--filterable.st-select--open .st-select__placeholder {
  position: absolute;
  left: var(--st-select-pad-left);
  right: var(--st-select-pad-right);
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
}

/* base-selection-input（index.cssr.ts:152-165） */
.st-select__input {
  flex: 1 1 auto;
  min-width: 1px;
  width: 100%;
  padding: 0;
  border: none;
  outline: none;
  background-color: transparent;
  color: var(--st-text);
  font-family: inherit;
  font-size: inherit;
  line-height: inherit;
  /* light.ts: caretColor = primaryColor */
  caret-color: var(--primary);
}

/* ==================== 后缀：base-suffix + base-clear ====================
 * index.cssr.ts:74-86（右 10px 定位）
 * clear/index.cssr.ts:12-18（1em 定位盒 + icon-switch） */
.st-select__suffix {
  position: absolute;
  top: 50%;
  right: 10px;
  transform: translateY(-50%);
  height: 1em;
  width: 1em;
  pointer-events: none;
}

.st-select__arrow,
.st-select__clear,
.st-select__loading {
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  display: flex;
  align-items: center;
  justify-content: center;
}

/* base-suffix__arrow（index.cssr.ts:81-85） */
.st-select__arrow {
  font-size: var(--st-select-icon-size);
  color: var(--st-select-icon-color);
  transition: color 0.3s var(--ease-in-out);
}

/* base-loading（index.cssr.ts:53-55） */
.st-select__loading {
  color: var(--primary);
}

/* base-clear__clear（clear/index.cssr.ts:14-25） */
.st-select__clear {
  height: 1em;
  width: 1em;
  padding: 0;
  border: none;
  background: transparent;
  cursor: pointer;
  pointer-events: auto;
  font-size: var(--st-select-icon-size);
  color: var(--st-select-clear-color);
  transition: color 0.3s var(--ease-in-out);
}

.st-select__clear:hover {
  color: var(--st-select-clear-color-hover);
}

.st-select__clear:active {
  color: var(--st-select-clear-color-active);
}

/* ==================== 双层边框：index.cssr.ts:57-73,176-197 ==================== */
.st-select__border,
.st-select__state-border {
  box-sizing: border-box;
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  pointer-events: none;
  border-radius: inherit;
  border: 1px solid var(--st-select-border-color);
  transition:
    box-shadow 0.3s var(--ease-in-out),
    border-color 0.3s var(--ease-in-out);
}

.st-select__state-border {
  border-color: transparent;
  z-index: 1;
}

/* 非禁用交互：hover → 边框；focus → 边框 + 光环；open → primary 边框 + 光环 */
.st-select:not(.st-select--disabled):hover .st-select__state-border {
  border: 1px solid var(--st-select-border-hover);
}

.st-select:not(.st-select--disabled).st-select--focus .st-select__state-border {
  border: 1px solid var(--st-select-border-focus);
  box-shadow: var(--st-select-shadow-focus);
}

.st-select:not(.st-select--disabled).st-select--open .st-select__state-border {
  border: 1px solid var(--st-select-border-active);
  box-shadow: var(--st-select-shadow-active);
}

/* 展开态：label 底色转 active（Naive colorActive；浅色为透明、深色为 primary@10%） */
.st-select--open .st-select__trigger {
  background-color: var(--st-select-fill-active);
}

/* ==================== 禁用：index.cssr.ts:198-222 ==================== */
.st-select--disabled .st-select__trigger {
  cursor: not-allowed;
  background-color: var(--st-select-fill-disabled);
}

.st-select--disabled .st-select__value,
.st-select--disabled .st-select__input {
  color: var(--st-select-text-disabled);
  cursor: not-allowed;
}

.st-select--disabled .st-select__placeholder {
  color: var(--st-select-placeholder-disabled);
}

.st-select--disabled .st-select__arrow {
  color: var(--st-select-icon-color-disabled);
}

/* ==================== 校验状态：index.cssr.ts:260-284 ====================
 * 常态给 state-border 上状态色，focus/open 再加对应光环。 */
.st-select--warning .st-select__state-border {
  border: 1px solid var(--warning);
}

.st-select:not(.st-select--disabled).st-select--warning.st-select--focus .st-select__state-border,
.st-select:not(.st-select--disabled).st-select--warning.st-select--open .st-select__state-border {
  border-color: var(--warning);
  box-shadow: var(--st-select-shadow-focus-warning);
}

.st-select--error .st-select__state-border {
  border: 1px solid var(--danger);
}

.st-select:not(.st-select--disabled).st-select--error.st-select--focus .st-select__state-border,
.st-select:not(.st-select--disabled).st-select--error.st-select--open .st-select__state-border {
  border-color: var(--danger);
  box-shadow: var(--st-select-shadow-focus-error);
}

/* ==================== 菜单 ====================
 * 面板被 Teleport 到 body，拿不到 .st-select 上的私有变量，尺寸必须自带。
 * select/src/styles/index.cssr.ts:10-15（menu margin / box-shadow）
 * select-menu/src/styles/index.cssr.ts:7-16（背景 / 圆角 / 过渡） */
:global(.st-select__content) {
  z-index: var(--st-z-dropdown);
  box-sizing: border-box;
  min-width: var(--reka-select-trigger-width);
  border: var(--st-popover-border);
  border-radius: var(--st-select-radius);
  background-color: var(--st-popover-bg);
  box-shadow: var(--st-popover-shadow);
  color: var(--st-text);
  overflow: hidden;
}

/* 尺寸：选项高 / 字号 / 菜单最大高（select-menu/_common.ts: height = optionHeight * 7.6） */
:global(.st-select__content--tiny) {
  --st-select-option-height: var(--st-height-tiny);
  --st-select-menu-max-height: calc(var(--st-height-tiny) * 7.6);
  font-size: var(--st-font-tiny);
}

:global(.st-select__content--small) {
  --st-select-option-height: var(--st-height-small);
  --st-select-menu-max-height: calc(var(--st-height-small) * 7.6);
  font-size: var(--st-font-small);
}

:global(.st-select__content--medium) {
  --st-select-option-height: var(--st-height-medium);
  --st-select-menu-max-height: calc(var(--st-height-medium) * 7.6);
  font-size: var(--st-font-medium);
}

:global(.st-select__content--large) {
  --st-select-option-height: var(--st-height-large);
  --st-select-menu-max-height: calc(var(--st-height-large) * 7.6);
  font-size: var(--st-font-large);
}

/* base-select-menu-option-wrapper（select-menu/index.cssr.ts:43-46）+ padding 4px 0 */
:global(.st-select__viewport) {
  padding: 4px 0;
  max-height: var(--st-select-menu-max-height, 258px);
  overflow-y: auto;
  scrollbar-width: thin;
}

/* ==================== 选项：base-select-option ====================
 * 内容经 Portal 到 body，统一用 :global；选项高亮背景用 inset 的 ::before。 */
:global(.st-select__item) {
  position: relative;
  display: flex;
  align-items: center;
  box-sizing: border-box;
  min-height: var(--st-select-option-height);
  /* option-padding 0 12px；show-checkmark 追加 20px 右内边距（:92-94） */
  padding: 0 calc(12px + 20px) 0 12px;
  font-size: inherit;
  line-height: 1.5;
  cursor: pointer;
  outline: none;
  user-select: none;
  color: var(--st-text);
  opacity: 1;
  transition:
    color 0.3s var(--ease-in-out),
    opacity 0.3s var(--ease-in-out);
}

/* 选项高亮背景用 inset 的 ::before（:95-104），左右各内缩 4px */
:global(.st-select__item::before) {
  content: '';
  position: absolute;
  left: 4px;
  right: 4px;
  top: 0;
  bottom: 0;
  border-radius: var(--st-select-radius);
  background-color: transparent;
  transition: background-color 0.3s var(--ease-in-out);
}

/* pending（键盘高亮 / 悬停）：:111-115 */
:global(.st-select__item[data-highlighted]::before) {
  background-color: var(--st-select-option-hover);
}

/* selected：文字转 primary，背景保持透明（light.ts: optionColorActive = 透明）+ 勾 */
:global(.st-select__item[data-state='checked']) {
  color: var(--st-select-option-text-active);
}

:global(.st-select__item[data-state='checked'][data-highlighted]::before) {
  background-color: var(--st-select-option-hover);
}

/* disabled：:128-137 */
:global(.st-select__item[data-disabled]) {
  cursor: not-allowed;
  color: var(--st-select-option-text-disabled);
}

:global(.st-select__item[data-disabled][data-state='checked']) {
  opacity: var(--st-select-option-opacity-disabled);
}

:global(.st-select__item-label) {
  position: relative;
  z-index: 1;
  flex: 1 1 auto;
  min-width: 0;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

/* base-select-option__check（:138-149，含 fadeInScaleUp enterScale .5） */
:global(.st-select__indicator) {
  position: absolute;
  right: 8px;
  top: 50%;
  transform: translateY(-50%);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: var(--st-select-option-check);
  animation: st-select-check-in 0.2s var(--ease-out);
}

/* base-select-menu__empty（select-menu/index.cssr.ts:47-52） */
:global(.st-select__empty) {
  display: flex;
  flex: 1;
  justify-content: center;
  padding: 12px 32px;
  color: var(--st-placeholder);
  font-size: inherit;
}

@media (prefers-reduced-motion: reduce) {
  .st-select__trigger,
  .st-select__placeholder,
  .st-select__arrow,
  .st-select__clear,
  .st-select__border,
  .st-select__state-border {
    transition: none;
  }

  :global(.st-select__item),
  :global(.st-select__item::before),
  :global(.st-select__indicator) {
    transition: none;
    animation: none;
  }
}
</style>
