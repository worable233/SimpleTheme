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
 * 基于 reka-ui Select：键盘导航、typeahead、浮层焦点管理全部交给它，
 * 本组件只补外观与 clearable / filterable 这层扩展。触发器视觉对齐 StInput。
 *
 * filterable 对齐 Naive Select 的 filterable：面板顶部有搜索框，按 label
 * 做不区分大小写的包含匹配；过滤后为空时显示空态。匹配在当前 options 上
 * 进行，远程搜索（Naive 的 remote）由调用方自行换 options 实现。
 */
import { computed, nextTick, ref, watch } from 'vue'
import {
  SelectContent,
  SelectIcon,
  SelectItem,
  SelectItemIndicator,
  SelectItemText,
  SelectPortal,
  SelectRoot,
  SelectTrigger,
  SelectValue,
  SelectViewport,
} from 'reka-ui'
import StIcon from './StIcon.vue'
import type { StOption, StSize, StStatus } from '../types'

defineOptions({ name: 'StSelect' })

const props = withDefaults(
  defineProps<{
    options: StOption[]
    /** 尺寸 */
    size?: StSize
    placeholder?: string
    disabled?: boolean
    /** 有值时显示清除按钮 */
    clearable?: boolean
    /** 校验状态 */
    status?: StStatus
    /** 面板顶部提供搜索框，按 label 过滤选项 */
    filterable?: boolean
    /** filterable 搜索框的占位文案 */
    filterPlaceholder?: string
    /** 过滤后无匹配时的空态文案 */
    emptyText?: string
    /** 无障碍名称；无可见 label 时必填 */
    ariaLabel?: string
  }>(),
  {
    size: 'medium',
    placeholder: '请选择',
    filterable: false,
    filterPlaceholder: '搜索…',
    emptyText: '无匹配项',
  },
)

const model = defineModel<string | number | null>({ default: null })

const emit = defineEmits<{
  (e: 'change', value: string | number | null): void
  (e: 'search', keyword: string): void
}>()

const rootEl = ref<HTMLElement | null>(null)
/** filterable 搜索框；浮层被 Portal 到 body，不能用 rootEl 查，须用模板 ref */
const searchEl = ref<HTMLInputElement | null>(null)
/** filterable 搜索关键词 */
const keyword = ref('')

const hasValue = computed(
  () => model.value !== '' && model.value !== null && model.value !== undefined,
)

/** filterable 时按 label 做包含匹配；否则原样透传 */
const visibleOptions = computed(() => {
  if (!props.filterable) return props.options
  const q = keyword.value.trim().toLowerCase()
  if (!q) return props.options
  return props.options.filter((o) => o.label.toLowerCase().includes(q))
})

const showClear = computed(() => props.clearable && hasValue.value && !props.disabled)

const classes = computed(() => [
  'st-select',
  `st-select--${props.size}`,
  {
    'st-select--disabled': props.disabled,
    'st-select--clearable': showClear.value,
    [`st-select--${props.status}`]: !!props.status,
  },
])

/** 面板关闭时清空关键词并复位到已选项，下次打开是干净列表 */
watch(keyword, (v) => {
  if (props.filterable) emit('search', v)
})

function onOpenChange(open: boolean) {
  if (!open) {
    keyword.value = ''
    return
  }
  if (!props.filterable) return
  // reka 在浮层定位完成（isPositioned）后才把焦点移到列表项，且这一步是
  // 异步的；nextTick 会早于它执行导致焦点被抢走。这里等一帧再抢回来，
  // 之后键盘输入才会落到搜索框（input 上已 keydown.stop，不会触发 reka
  // 的 typeahead）。
  nextTick(() => {
    requestAnimationFrame(() => {
      searchEl.value?.focus()
    })
  })
}

/** reka-ui 的回调参数是宽泛的 AcceptableValue，这里收窄成组件契约里的值域 */
function handleChange(value: unknown) {
  const next = (value ?? null) as string | number | null
  model.value = next
  emit('change', next)
}

/**
 * 搜索框键盘策略：可打印字符只留给搜索框（否则会触发 reka 的 typeahead，
 * 键盘输入即选中第一个匹配项，和「先筛选再确认」冲突）；方向键 / Enter /
 * Escape / Tab 等导航键放行给 reka，由它把焦点移到列表项并完成选择。
 */
function onSearchKeydown(e: KeyboardEvent) {
  if (e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
    e.stopPropagation()
  }
}

function clear() {
  model.value = null
  emit('change', null)
  // 清除后把焦点还给触发器：面板不打开，键盘用户不会丢焦点
  rootEl.value?.querySelector<HTMLElement>('.st-select__trigger')?.focus()
}
</script>

<template>
  <div ref="rootEl" :class="classes">
    <SelectRoot
      :model-value="model"
      :disabled="disabled"
      @update:model-value="handleChange"
      @update:open="onOpenChange"
    >
      <SelectTrigger
        class="st-select__trigger"
        :disabled="disabled"
        :aria-label="ariaLabel"
        :aria-invalid="status === 'error' ? true : undefined"
      >
        <SelectValue class="st-select__value" :placeholder="placeholder" />
        <SelectIcon class="st-select__icon">
          <StIcon name="chevron-down" :size="14" />
        </SelectIcon>
      </SelectTrigger>

      <!-- Teleport 到 body：脱离后台卡片的 overflow/层叠上下文 -->
      <SelectPortal to="body">
        <SelectContent
          class="st-select__content st-transition-fade"
          :class="`st-select__content--${size}`"
          position="popper"
          side="bottom"
          align="start"
          :side-offset="4"
          @keydown.stop
        >
          <div v-if="filterable" class="st-select__search-wrap">
            <StIcon name="search" :size="14" class="st-select__search-icon" />
            <input
              ref="searchEl"
              v-model="keyword"
              class="st-select__search"
              type="text"
              :placeholder="filterPlaceholder"
              :aria-label="filterPlaceholder"
              @keydown="onSearchKeydown"
            />
          </div>
          <SelectViewport class="st-select__viewport">
            <SelectItem
              v-for="option in visibleOptions"
              :key="option.value"
              class="st-select__item"
              :class="{ 'is-selected': option.value === model }"
              :value="option.value"
              :disabled="option.disabled"
            >
              <SelectItemIndicator class="st-select__indicator">
                <StIcon name="check" :size="14" />
              </SelectItemIndicator>
              <SelectItemText>{{ option.label }}</SelectItemText>
            </SelectItem>
            <div v-if="filterable && visibleOptions.length === 0" class="st-select__empty">
              {{ emptyText }}
            </div>
          </SelectViewport>
        </SelectContent>
      </SelectPortal>
    </SelectRoot>

    <!-- 作为触发器兄弟节点而非子节点：button 不能嵌套 button，
         且点击它天然不会打开面板 -->
    <button
      v-if="showClear"
      type="button"
      class="st-select__clear"
      aria-label="清除"
      tabindex="-1"
      @mousedown.prevent
      @click="clear"
    >
      <StIcon name="x" :size="14" />
    </button>
  </div>
</template>

<style scoped>
.st-select {
  --st-select-height: var(--st-height-medium);
  --st-select-font: var(--st-font-medium);
  --st-select-pad: 10px;

  position: relative;
  display: inline-flex;
  align-items: center;
  box-sizing: border-box;
  width: 100%;
  font-size: var(--st-select-font);
}

/* ==================== 尺寸 ==================== */
.st-select--tiny {
  --st-select-height: var(--st-height-tiny);
  --st-select-font: var(--st-font-tiny);
  --st-select-pad: 7px;
}

.st-select--small {
  --st-select-height: var(--st-height-small);
  --st-select-font: var(--st-font-small);
  --st-select-pad: 9px;
}

.st-select--large {
  --st-select-height: var(--st-height-large);
  --st-select-font: var(--st-font-large);
  --st-select-pad: 12px;
}

/* ==================== 触发器 ==================== */
.st-select__trigger {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  box-sizing: border-box;
  width: 100%;
  height: var(--st-select-height);
  padding: 0 var(--st-select-pad);
  padding-right: 26px;
  border: 1px solid var(--st-select-border-color);
  border-radius: var(--radius-medium);
  background-color: var(--st-select-fill);
  color: var(--st-text);
  font-family: inherit;
  font-size: inherit;
  line-height: 1;
  text-align: left;
  cursor: pointer;
  transition:
    border-color var(--transition-fast),
    background-color var(--transition-fast),
    box-shadow var(--transition-fast);
}

.st-select--tiny .st-select__trigger,
.st-select--small .st-select__trigger {
  border-radius: var(--radius-small);
}

.st-select__trigger:hover:not(:disabled) {
  border-color: var(--st-select-border-hover);
}

.st-select__trigger:focus-visible,
.st-select__trigger[data-state='open'] {
  outline: none;
  border-color: var(--st-select-border-focus);
  box-shadow: var(--st-select-shadow-focus);
}

/* 展开态：Naive --active，底色转 tinted、边框转 primary、柔光 */
.st-select__trigger[data-state='open'] {
  background-color: var(--st-select-fill-active);
  border-color: var(--st-select-border-active);
  box-shadow: var(--st-select-shadow-active);
}

.st-select__trigger:disabled,
.st-select--disabled .st-select__trigger {
  cursor: not-allowed;
  background-color: var(--st-select-fill-disabled);
}

/* 校验状态：边色表达，聚焦时不被 --ring 覆盖 */
.st-select--error .st-select__trigger {
  border-color: var(--danger);
}

.st-select--error .st-select__trigger:focus-visible,
.st-select--error .st-select__trigger[data-state='open'] {
  border-color: var(--danger);
  box-shadow: var(--st-select-shadow-active-error);
}

.st-select--error .st-select__trigger[data-state='open'] {
  background-color: var(--st-select-fill-active-error);
}

.st-select--warning .st-select__trigger {
  border-color: var(--warning);
}

.st-select--warning .st-select__trigger:focus-visible,
.st-select--warning .st-select__trigger[data-state='open'] {
  border-color: var(--warning);
  box-shadow: var(--st-select-shadow-active-warning);
}

.st-select--warning .st-select__trigger[data-state='open'] {
  background-color: var(--st-select-fill-active-warning);
}

.st-select--success .st-select__trigger {
  border-color: var(--success);
}

.st-select__value {
  flex: 1 1 auto;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* reka-ui 无值时给 SelectValue 加 data-placeholder */
.st-select__value[data-placeholder] {
  color: var(--st-placeholder);
}

.st-select__icon {
  position: absolute;
  right: var(--st-select-pad);
  display: inline-flex;
  align-items: center;
  color: var(--st-placeholder);
  pointer-events: none;
}

/* 有清除按钮时让出位置，图标与按钮不重叠 */
.st-select--clearable .st-select__trigger {
  padding-right: 48px;
}

.st-select--clearable .st-select__icon {
  right: calc(var(--st-select-pad) - 2px);
}

.st-select__clear {
  position: absolute;
  right: 24px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 16px;
  height: 16px;
  padding: 0;
  border: none;
  border-radius: var(--radius-full);
  background: transparent;
  color: var(--st-placeholder);
  cursor: pointer;
  transition: color var(--transition-fast);
}

.st-select__clear:hover {
  color: var(--st-text);
}

/* ==================== 下拉面板 ==================== */
/* 面板被 Teleport 到 body，拿不到 .st-select 上的私有变量，尺寸必须自带 */
:global(.st-select__content) {
  z-index: var(--st-z-dropdown);
  box-sizing: border-box;
  min-width: var(--reka-select-trigger-width);
  border: var(--st-popover-border);
  border-radius: var(--radius-medium);
  background-color: var(--st-popover-bg);
  box-shadow: var(--st-popover-shadow);
  color: var(--st-text);
}

:global(.st-select__content--tiny) {
  font-size: var(--st-font-tiny);
}

:global(.st-select__content--small) {
  font-size: var(--st-font-small);
}

:global(.st-select__content--medium) {
  font-size: var(--st-font-medium);
}

:global(.st-select__content--large) {
  font-size: var(--st-font-large);
}

/* 只做透明度动画：定位由 popper 用 transform 完成，动 transform 会打架。
 * 进场动画改由 transitions.css 的 .st-transition-fade 提供。 */

:global(.st-select__viewport) {
  padding: 4px;
  max-height: var(--reka-select-content-available-height, 320px);
  overflow-y: auto;
}

/* ==================== filterable 搜索框 ==================== */
:global(.st-select__search-wrap) {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 8px;
  border-bottom: 1px solid var(--border);
}

:global(.st-select__search-icon) {
  flex: none;
  color: var(--st-placeholder);
}

:global(.st-select__search) {
  flex: 1 1 auto;
  min-width: 0;
  padding: 0;
  border: none;
  background: transparent;
  color: var(--st-text);
  font-family: inherit;
  font-size: inherit;
  line-height: 1.4;
  outline: none;
}

:global(.st-select__search::placeholder) {
  color: var(--st-placeholder);
}

:global(.st-select__empty) {
  padding: 12px 8px;
  color: var(--st-placeholder);
  font-size: inherit;
  text-align: center;
}

.st-select__item {
  position: relative;
  display: flex;
  align-items: center;
  box-sizing: border-box;
  padding: 6px 8px 6px 26px;
  border-radius: var(--radius-small);
  color: var(--st-text);
  font-size: inherit;
  line-height: 1.4;
  cursor: pointer;
  user-select: none;
  outline: none;
}

.st-select__item[data-highlighted] {
  background-color: var(--st-fill-hover);
}

.st-select__item.is-selected {
  background-color: var(--accent);
  font-weight: 500;
}

.st-select__item[data-disabled] {
  color: var(--st-text-disabled);
  cursor: not-allowed;
}

.st-select__indicator {
  position: absolute;
  left: 8px;
  display: inline-flex;
  align-items: center;
  color: var(--primary);
}

@media (prefers-reduced-motion: reduce) {
  .st-select__trigger,
  .st-select__clear {
    transition: none;
  }
}
</style>
