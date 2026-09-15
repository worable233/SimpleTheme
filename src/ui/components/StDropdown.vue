<script setup lang="ts">
/**
 * StDropdown — 下拉菜单
 *
 * 用法：
 *   <StDropdown :options="items" placement="bottom-end" @select="onSelect">
 *     <template #trigger><StButton>操作</StButton></template>
 *   </StDropdown>
 *
 * 上下移动 / 回车选中 / ESC 关闭 / 焦点归还由 reka-ui DropdownMenu 提供，
 * 这里只负责选项渲染与令牌配色。
 */
import { computed, onBeforeUnmount, ref } from 'vue'
import {
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuPortal,
  DropdownMenuRoot,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from 'reka-ui'
import type { StPlacement } from '../types'

defineOptions({ name: 'StDropdown' })

type StSide = 'top' | 'right' | 'bottom' | 'left'
type StAlign = 'start' | 'center' | 'end'

interface StDropdownOption {
  label: string
  value: string | number
  disabled?: boolean
  /** 危险操作：使用 --danger 配色 */
  danger?: boolean
  /** 在本项上方插入分隔线（首项忽略） */
  divided?: boolean
}

/** 指针跨越触发器与菜单间隙的宽限时间 */
const HOVER_GRACE = 120

const props = withDefaults(
  defineProps<{
    options: StDropdownOption[]
    /** 相对触发器的方位 */
    placement?: StPlacement
    /** 触发方式；菜单默认点击展开，hover 仅用于轻量操作集 */
    trigger?: 'click' | 'hover'
  }>(),
  {
    placement: 'bottom-end',
    trigger: 'click',
  },
)

const emit = defineEmits<{
  (e: 'select', value: string | number): void
}>()

const open = ref(false)

const side = computed(() => props.placement.split('-')[0] as StSide)
const align = computed(() => (props.placement.split('-')[1] ?? 'center') as StAlign)
const hoverTriggered = computed(() => props.trigger === 'hover')

let closeTimer: ReturnType<typeof setTimeout> | undefined

function cancelClose() {
  if (closeTimer === undefined) return
  clearTimeout(closeTimer)
  closeTimer = undefined
}

function scheduleClose() {
  if (!hoverTriggered.value) return
  cancelClose()
  closeTimer = setTimeout(() => {
    closeTimer = undefined
    open.value = false
  }, HOVER_GRACE)
}

function onTriggerEnter() {
  if (!hoverTriggered.value) return
  cancelClose()
  open.value = true
}

function onOpenChange(value: boolean) {
  cancelClose()
  open.value = value
}

function onSelect(value: string | number) {
  emit('select', value)
}

onBeforeUnmount(cancelClose)
</script>

<template>
  <DropdownMenuRoot :open="open" :modal="false" @update:open="onOpenChange">
    <DropdownMenuTrigger as-child>
      <span class="st-dropdown__anchor" @mouseenter="onTriggerEnter" @mouseleave="scheduleClose">
        <slot name="trigger" />
      </span>
    </DropdownMenuTrigger>

    <DropdownMenuPortal to="body">
      <DropdownMenuContent
        class="st-dropdown__menu"
        :side="side"
        :align="align"
        :side-offset="6"
        :collision-padding="8"
        :loop="true"
        @mouseenter="cancelClose"
        @mouseleave="scheduleClose"
      >
        <template v-for="(option, index) in options" :key="`${index}-${option.value}`">
          <DropdownMenuSeparator v-if="option.divided && index > 0" class="st-dropdown__divider" />
          <DropdownMenuItem
            class="st-dropdown__item"
            :class="{ 'st-dropdown__item--danger': option.danger }"
            :disabled="option.disabled"
            :text-value="option.label"
            @select="onSelect(option.value)"
          >
            {{ option.label }}
          </DropdownMenuItem>
        </template>
      </DropdownMenuContent>
    </DropdownMenuPortal>
  </DropdownMenuRoot>
</template>

<style scoped>
.st-dropdown__anchor {
  display: inline-flex;
}

.st-dropdown__menu {
  z-index: var(--st-z-dropdown);
  box-sizing: border-box;
  min-width: 160px;
  max-width: min(320px, calc(100vw - 32px));
  max-height: 320px;
  padding: 4px;
  overflow-y: auto;
  border: var(--st-popover-border);
  border-radius: var(--radius-medium);
  background-color: var(--st-popover-bg);
  color: var(--st-text);
  box-shadow: var(--st-popover-shadow);
  outline: none;
}

.st-dropdown__item {
  display: flex;
  align-items: center;
  box-sizing: border-box;
  height: var(--st-height-medium);
  padding: 0 10px;
  border-radius: var(--radius-small);
  color: var(--st-text);
  font-size: var(--st-font-medium);
  line-height: 1;
  cursor: pointer;
  user-select: none;
  outline: none;
  transition: background-color var(--transition-fast);
}

/* reka 高亮 = 鼠标悬停或键盘焦点 */
.st-dropdown__item[data-highlighted] {
  background-color: var(--st-fill-hover);
}

.st-dropdown__item[data-disabled] {
  color: var(--st-text-disabled);
  cursor: not-allowed;
}

.st-dropdown__item--danger {
  color: var(--danger);
}

.st-dropdown__item--danger[data-highlighted] {
  background-color: color-mix(in srgb, var(--danger) 12%, transparent);
}

.st-dropdown__divider {
  height: 1px;
  margin: 4px -4px;
  background-color: var(--st-border);
}

@media (prefers-reduced-motion: reduce) {
  .st-dropdown__item {
    transition: none;
  }
}
</style>
