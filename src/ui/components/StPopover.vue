<script setup lang="ts">
/**
 * StPopover — 气泡卡片
 *
 * 用法：
 *   <StPopover v-model:show="open" placement="bottom-start" width="240px">
 *     <template #trigger><StButton>更多</StButton></template>
 *     面板内容
 *   </StPopover>
 *
 * 点击外部关闭 / 焦点归还 / aria 关联由 reka-ui Popover 提供。
 * hover 触发共用一个受控 open，靠宽限延时避免指针从触发器移向面板时闪断。
 */
import { computed, onBeforeUnmount } from 'vue'
import { PopoverArrow, PopoverContent, PopoverPortal, PopoverRoot, PopoverTrigger } from 'reka-ui'
import type { StPlacement } from '../types'

defineOptions({ name: 'StPopover' })

type StSide = 'top' | 'right' | 'bottom' | 'left'
type StAlign = 'start' | 'center' | 'end'

/** 指针跨越触发器与面板间隙的宽限时间 */
const HOVER_GRACE = 120

const props = withDefaults(
  defineProps<{
    /** 相对触发器的方位 */
    placement?: StPlacement
    /** 触发方式；hover 适合预览类浮层 */
    trigger?: 'click' | 'hover'
    /** 禁用后不再打开 */
    disabled?: boolean
    /** 面板宽度，任意合法 CSS 长度；省略时由内容决定 */
    width?: string
    /** 是否显示箭头 */
    showArrow?: boolean
  }>(),
  {
    placement: 'bottom-start',
    trigger: 'click',
    disabled: false,
    showArrow: false,
  },
)

const show = defineModel<boolean>('show', { default: false })

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
  if (!hoverTriggered.value || props.disabled) return
  cancelClose()
  closeTimer = setTimeout(() => {
    closeTimer = undefined
    show.value = false
  }, HOVER_GRACE)
}

function onTriggerEnter() {
  if (!hoverTriggered.value || props.disabled) return
  cancelClose()
  show.value = true
}

function onTriggerLeave() {
  if (!hoverTriggered.value) return
  scheduleClose()
}

function onOpenChange(value: boolean) {
  if (props.disabled) return
  cancelClose()
  show.value = value
}

onBeforeUnmount(cancelClose)
</script>

<template>
  <PopoverRoot :open="show" :modal="false" @update:open="onOpenChange">
    <PopoverTrigger as-child>
      <span
        class="st-popover__anchor"
        :class="{ 'is-disabled': disabled }"
        @mouseenter="onTriggerEnter"
        @mouseleave="onTriggerLeave"
      >
        <slot name="trigger" />
      </span>
    </PopoverTrigger>

    <PopoverPortal to="body">
      <PopoverContent
        class="st-popover__content"
        :side="side"
        :align="align"
        :side-offset="8"
        :collision-padding="8"
        :style="width ? { width } : undefined"
        @mouseenter="cancelClose"
        @mouseleave="onTriggerLeave"
      >
        <slot />
        <PopoverArrow v-if="showArrow" class="st-popover__arrow" :width="10" :height="5" />
      </PopoverContent>
    </PopoverPortal>
  </PopoverRoot>
</template>

<style scoped>
.st-popover__anchor {
  display: inline-flex;
}

.st-popover__anchor.is-disabled {
  cursor: not-allowed;
}

.st-popover__content {
  z-index: var(--st-z-dropdown);
  box-sizing: border-box;
  min-width: 140px;
  max-width: min(360px, calc(100vw - 32px));
  padding: 12px;
  border: var(--st-popover-border);
  border-radius: var(--radius-medium);
  background-color: var(--st-popover-bg);
  color: var(--st-text);
  box-shadow: var(--st-popover-shadow);
  font-size: var(--st-font-medium);
  line-height: 1.6;
  outline: none;
}

/* reka 的箭头是 <span><svg>…，颜色靠 currentColor 传递 */
.st-popover__arrow {
  color: var(--st-popover-bg);
  fill: currentColor;
}

.st-popover__arrow svg {
  display: block;
}
</style>
