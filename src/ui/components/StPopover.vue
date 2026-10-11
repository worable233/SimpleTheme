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
    /** 锚点撑满父级（网格/整块触发器的场景） */
    block?: boolean
    /** 面板是否自带 12px 内边距；为 false 时由内容自行控制贴边布局 */
    padded?: boolean
    /** 箭头填充色；默认与面板同色（--st-popover-bg），贴近不同底色时可覆写 */
    arrowColor?: string
    /**
     * 面板入场动画：
     *  - fade：淡入（对齐 Naive fade）
     *  - fade-scale：淡入 + 轻微放大（默认，浮层通用节奏）
     *  - slide：沿 --st-popover-shift-y 位移淡入
     *  - none：交给消费方自定义（如友链预览的分层错峰）
     */
    animation?: 'fade' | 'fade-scale' | 'slide' | 'none'
    /** slide 档位的位移距离，默认 8px（正值从下方升起） */
    shiftY?: string
    /** slide 档位的时长，默认 0.25s */
    duration?: string
    /** slide 档位的缓动，默认 cubic-bezier(0.55, 0, 0.8, 0.25) */
    easing?: string
  }>(),
  {
    placement: 'bottom-start',
    trigger: 'click',
    disabled: false,
    showArrow: false,
    block: false,
    padded: true,
    animation: 'fade-scale',
    shiftY: '8px',
    duration: '0.25s',
    easing: 'cubic-bezier(0.55, 0, 0.8, 0.25)',
  },
)

const contentStyle = computed<Record<string, string>>(() => {
  const style: Record<string, string> = {}
  if (props.width) style.width = props.width
  if (props.arrowColor) style['--st-popover-arrow-color'] = props.arrowColor
  if (props.animation === 'slide') {
    style['--st-popover-shift-y'] = props.shiftY
    style['--st-popover-duration'] = props.duration
    style['--st-popover-easing'] = props.easing
  }
  return style
})

const animationClass = computed(() => {
  switch (props.animation) {
    case 'fade':
      return 'st-transition-fade'
    case 'slide':
      return 'st-popover__content--slide'
    case 'none':
      return ''
    default:
      return 'st-transition-fade-scale'
  }
})

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
        :class="{ 'is-disabled': disabled, 'is-block': block }"
        @mouseenter="onTriggerEnter"
        @mouseleave="onTriggerLeave"
      >
        <slot name="trigger" />
      </span>
    </PopoverTrigger>

    <PopoverPortal to="body">
      <PopoverContent
        class="st-popover__content"
        :class="[animationClass, { 'st-popover__content--bare': !padded }]"
        :side="side"
        :align="align"
        :side-offset="8"
        :collision-padding="8"
        :style="contentStyle"
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

.st-popover__anchor.is-block {
  display: block;
  width: 100%;
}

.st-popover__anchor.is-disabled {
  cursor: not-allowed;
}

/* reka-ui 把内容 Portal 到 body，内容根拿不到本组件的 data-v-*，
 * scoped 选择器不命中；用 :global() 命名空间化（st- 前缀已足够唯一）。
 * 注意：锚点 .st-popover__anchor 留在组件模板内，继续保持 scoped。 */
:global(.st-popover__content) {
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

/* 裸面板：去掉内边距，由内容自行贴边排版（如友链预览的整宽描述条） */
:global(.st-popover__content--bare) {
  padding: 0;
}

/* slide 档位的关键帧与规则在 src/styles/transitions.css 中定义（全局层，
 * 避免 scoped @keyframes 改名与 :global() 引用失配）。 */

/* reka 的箭头是内联 <svg class="st-popover__arrow">，外壳 <span> 由
 * PopperArrow 负责绝对定位。用 currentColor 上色；颜色经
 * --st-popover-arrow-color 暴露，消费方可在贴近不同底色时覆写。 */
:global(.st-popover__arrow) {
  color: var(--st-popover-arrow-color, var(--st-popover-bg));
  fill: currentColor;
}
</style>
