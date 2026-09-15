<script setup lang="ts">
/**
 * StPagination — 分页器
 *
 * 用法：
 *   <StPagination v-model:page="page" :page-count="20" />
 *   <StPagination
 *     v-model:page="page"
 *     v-model:page-size="size"
 *     :page-count="20"
 *     :total="203"
 *     show-quick-jumper
 *     show-size-picker
 *   />
 *
 * 页码列表带省略号折叠：首页、尾页、当前页及其左右各 1 页常驻，其余折成 `…`，
 * 因此最多渲染 7 个数字（`count <= 7` 时全部展示）。
 *
 * 页码按钮用原生 <button> 而非 StButton：这一层需要 aria-current、固定方形
 * 尺寸与"当前页"高亮，StButton 的对外契约既不暴露 aria-current，也不允许
 * 外部覆盖尺寸；在容器组件里直接消费令牌渲染，比反向定制 StButton 更干净。
 */
import { computed, ref, useSlots, watch } from 'vue'
import StIcon from './StIcon.vue'
import StInput from './StInput.vue'
import type { StSize } from '../types'

defineOptions({ name: 'StPagination' })

const props = withDefaults(
  defineProps<{
    /** 总页数 */
    pageCount: number
    /** 总条数，仅用于展示与推算边界 */
    total?: number
    size?: StSize
    disabled?: boolean
    /** 显示"跳至 N 页"输入框 */
    showQuickJumper?: boolean
    /** 显示每页条数选择器 */
    showSizePicker?: boolean
    pageSizes?: number[]
  }>(),
  {
    total: undefined,
    size: 'medium',
    disabled: false,
    showQuickJumper: false,
    showSizePicker: false,
    pageSizes: () => [10, 20, 50],
  },
)

const page = defineModel<number>('page', { required: true })
// pageSize 既是 props 也是 v-model：由 defineModel 一并声明 prop 与 update:pageSize 事件，
// 因此不再写进 defineProps，也不额外 defineEmits（否则会重复触发）
const pageSize = defineModel<number>('pageSize', { default: 10 })

const slots = useSlots()

const ICON_SIZE: Record<StSize, number> = { tiny: 12, small: 13, medium: 14, large: 15 }

/** 折叠标记，与页码数字区分开 */
const ELLIPSIS = 'ellipsis'
type PageCell = number | typeof ELLIPSIS

const count = computed(() => Math.max(1, Math.floor(props.pageCount) || 1))

const current = computed(() => clamp(page.value))

function clamp(value: number): number {
  if (!Number.isFinite(value)) return 1
  return Math.min(Math.max(Math.floor(value), 1), count.value)
}

const pages = computed<PageCell[]>(() => {
  const total = count.value
  if (total <= 7) return Array.from({ length: total }, (_, index) => index + 1)

  // 首页 / 尾页 / 当前页与左右邻页常驻，中间的空洞才折成省略号
  const anchors = [1, total, current.value - 1, current.value, current.value + 1]
    .filter((value) => value >= 1 && value <= total)
    .sort((a, b) => a - b)

  const cells: PageCell[] = []
  let previous = 0
  for (const value of anchors) {
    if (value === previous) continue
    if (value - previous > 1) cells.push(ELLIPSIS)
    cells.push(value)
    previous = value
  }
  return cells
})

const prevDisabled = computed(() => props.disabled || current.value <= 1)
const nextDisabled = computed(() => props.disabled || current.value >= count.value)

function goTo(target: number) {
  if (props.disabled) return
  const next = clamp(target)
  if (next === current.value) return
  page.value = next
}

// pageCount 收缩（筛选、删除）后把页码拉回范围内，否则会停在空白页
watch(count, (total) => {
  if (page.value > total) page.value = total
})

/* ---------- 快速跳转 ---------- */
const jumpValue = ref<string | number | undefined>('')

function commitJump() {
  const parsed = Number(jumpValue.value)
  jumpValue.value = ''
  if (!Number.isFinite(parsed) || parsed === 0) return
  goTo(parsed)
}

/* ---------- 每页条数 ---------- */
function changePageSize(event: Event) {
  const next = Number((event.target as HTMLSelectElement).value)
  if (!Number.isFinite(next) || next <= 0) return
  pageSize.value = next
}
</script>

<template>
  <nav
    class="st-pagination"
    :class="[`st-pagination--${size}`, { 'is-disabled': disabled }]"
    role="navigation"
    aria-label="分页"
  >
    <span v-if="slots.default" class="st-pagination__prefix"><slot /></span>

    <span v-if="total !== undefined" class="st-pagination__total">共 {{ total }} 条</span>

    <button
      type="button"
      class="st-pagination__item st-pagination__item--nav"
      aria-label="上一页"
      :disabled="prevDisabled"
      @click="goTo(current - 1)"
    >
      <StIcon name="chevron-left" :size="ICON_SIZE[size]" />
    </button>

    <template v-for="(cell, index) in pages" :key="cell === ELLIPSIS ? `gap-${index}` : cell">
      <span v-if="cell === ELLIPSIS" class="st-pagination__gap" aria-hidden="true">…</span>
      <button
        v-else
        type="button"
        class="st-pagination__item"
        :class="{ 'is-active': cell === current }"
        :disabled="disabled"
        :aria-label="`第 ${cell} 页`"
        :aria-current="cell === current ? 'page' : undefined"
        @click="goTo(cell)"
      >
        {{ cell }}
      </button>
    </template>

    <button
      type="button"
      class="st-pagination__item st-pagination__item--nav"
      aria-label="下一页"
      :disabled="nextDisabled"
      @click="goTo(current + 1)"
    >
      <StIcon name="chevron-right" :size="ICON_SIZE[size]" />
    </button>

    <span v-if="showSizePicker" class="st-pagination__sizer">
      <span class="st-pagination__label">每页</span>
      <span class="st-pagination__select">
        <select
          class="st-pagination__select-el"
          :value="pageSize"
          :disabled="disabled"
          aria-label="每页条数"
          @change="changePageSize"
        >
          <option v-for="option in pageSizes" :key="option" :value="option">{{ option }}</option>
        </select>
        <StIcon class="st-pagination__select-arrow" name="chevron-down" :size="12" />
      </span>
      <span class="st-pagination__label">条</span>
    </span>

    <span v-if="showQuickJumper" class="st-pagination__jumper">
      <span class="st-pagination__label">跳至</span>
      <span class="st-pagination__jumper-field">
        <StInput
          v-model="jumpValue"
          :size="size"
          type="number"
          aria-label="跳转页码"
          :disabled="disabled"
          @enter="commitJump"
          @blur="commitJump"
        />
      </span>
      <span class="st-pagination__label">页</span>
    </span>
  </nav>
</template>

<style scoped>
.st-pagination {
  --st-pagination-item: var(--st-height-medium);
  --st-pagination-font: var(--st-font-medium);

  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px;
  box-sizing: border-box;
  color: var(--st-text);
  font-size: var(--st-pagination-font);
}

.st-pagination.is-disabled {
  cursor: not-allowed;
}

/* ==================== 尺寸 ==================== */
.st-pagination--tiny {
  --st-pagination-item: var(--st-height-tiny);
  --st-pagination-font: var(--st-font-tiny);
}

.st-pagination--small {
  --st-pagination-item: var(--st-height-small);
  --st-pagination-font: var(--st-font-small);
}

.st-pagination--large {
  --st-pagination-item: var(--st-height-large);
  --st-pagination-font: var(--st-font-large);
}

/* ==================== 页码按钮 ==================== */
.st-pagination__item {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  box-sizing: border-box;
  min-width: var(--st-pagination-item);
  height: var(--st-pagination-item);
  padding: 0 4px;
  border: 1px solid transparent;
  border-radius: var(--radius-small);
  background-color: transparent;
  color: var(--st-text);
  font-family: inherit;
  font-size: inherit;
  line-height: 1;
  font-variant-numeric: tabular-nums;
  cursor: pointer;
  transition:
    background-color var(--transition-fast),
    border-color var(--transition-fast),
    color var(--transition-fast);
}

.st-pagination__item:hover:not(:disabled):not(.is-active) {
  background-color: var(--st-fill-hover);
}

.st-pagination__item:focus-visible {
  outline: none;
  box-shadow: var(--st-focus-ring);
}

.st-pagination__item:disabled {
  color: var(--st-text-disabled);
  cursor: not-allowed;
}

.st-pagination__item.is-active {
  border-color: var(--primary);
  background-color: var(--primary);
  color: var(--primary-foreground);
  font-weight: 600;
}

.st-pagination__item--nav {
  padding: 0;
}

/* 上一页/下一页在边界时禁用，但不要跟着变灰到看不出是按钮 */
.st-pagination__item--nav:disabled {
  color: var(--st-text-disabled);
  opacity: 0.6;
}

.st-pagination__gap {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: calc(var(--st-pagination-item) / 2);
  height: var(--st-pagination-item);
  color: var(--st-placeholder);
  user-select: none;
}

/* ==================== 附加区域 ==================== */
.st-pagination__prefix,
.st-pagination__total,
.st-pagination__sizer,
.st-pagination__jumper {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: var(--st-placeholder);
}

.st-pagination__prefix,
.st-pagination__total {
  margin-right: 4px;
}

.st-pagination__sizer,
.st-pagination__jumper {
  margin-left: 8px;
}

.st-pagination__label {
  white-space: nowrap;
}

.st-pagination__select {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.st-pagination__select-el {
  box-sizing: border-box;
  height: var(--st-pagination-item);
  padding: 0 24px 0 8px;
  border: 1px solid var(--st-border);
  border-radius: var(--radius-small);
  background-color: var(--st-fill);
  color: var(--st-text);
  font-family: inherit;
  font-size: inherit;
  cursor: pointer;
  appearance: none;
  transition: border-color var(--transition-fast);
}

.st-pagination__select-el:hover:not(:disabled) {
  border-color: var(--st-border-hover);
}

.st-pagination__select-el:focus-visible {
  outline: none;
  border-color: var(--ring);
  box-shadow: var(--st-focus-ring);
}

.st-pagination__select-el:disabled {
  color: var(--st-text-disabled);
  cursor: not-allowed;
}

.st-pagination__select-arrow {
  position: absolute;
  right: 6px;
  color: var(--st-placeholder);
  pointer-events: none;
}

.st-pagination__jumper-field {
  display: inline-block;
  width: 56px;
}

@media (prefers-reduced-motion: reduce) {
  .st-pagination__item,
  .st-pagination__select-el {
    transition: none;
  }
}
</style>
