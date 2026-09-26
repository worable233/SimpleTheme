<script setup lang="ts">
/**
 * StTooltip — 文字提示
 *
 * 用法：
 *   <StTooltip content="复制链接" placement="top">
 *     <StButton circle aria-label="复制"><template #icon>…</template></StButton>
 *   </StTooltip>
 *
 * 注意：default 插槽直接接收 reka 的触发器 props，因此必须是**单个可接收 ref 的根元素**
 * （元素或转发 attrs 的组件，如 StButton）；多根节点会被丢弃且提示无法定位。
 * 悬停/聚焦延时、aria-describedby 由 reka-ui Tooltip 处理，组件内不手写。
 */
import { computed } from 'vue'
import {
  TooltipContent,
  TooltipPortal,
  TooltipProvider,
  TooltipRoot,
  TooltipTrigger,
} from 'reka-ui'
import type { StPlacement } from '../types'

defineOptions({ name: 'StTooltip' })

type StSide = 'top' | 'right' | 'bottom' | 'left'
type StAlign = 'start' | 'center' | 'end'

const props = withDefaults(
  defineProps<{
    /** 提示文字 */
    content: string
    /** 相对触发器的方位 */
    placement?: StPlacement
    /** 禁用后不再弹出 */
    disabled?: boolean
    /** 悬停多久后弹出（ms） */
    delayDuration?: number
    /** 与触发器的距离（px）；侧栏等窄触发器可调大以推出容器 */
    sideOffset?: number
  }>(),
  {
    placement: 'top',
    disabled: false,
    delayDuration: 200,
    sideOffset: 8,
  },
)

/** StPlacement 是 "side-align" 的复合写法，reka 需要拆成两个 prop */
const side = computed(() => props.placement.split('-')[0] as StSide)
const align = computed(() => (props.placement.split('-')[1] ?? 'center') as StAlign)
</script>

<template>
  <TooltipProvider>
    <TooltipRoot :delay-duration="delayDuration" :disabled="disabled">
      <TooltipTrigger as-child>
        <slot />
      </TooltipTrigger>

      <TooltipPortal to="body">
        <TooltipContent
          class="st-tooltip"
          :side="side"
          :align="align"
          :side-offset="sideOffset"
          :collision-padding="8"
        >
          {{ content }}
        </TooltipContent>
      </TooltipPortal>
    </TooltipRoot>
  </TooltipProvider>
</template>

<style scoped>
/* 恢复到迁移前的深色毛玻璃提示外观（旧 .sidebar-global-tooltip /
 * .admin-sidebar__tooltip / .stat-tooltip）：黑底半透明 + backdrop 模糊 +
 * 白字，无边框无箭头，与浅色 Popover 面板区分开。
 * 浮层经 reka-ui Portal 渲染到 body，拿不到本组件的 data-v-* 作用域属性，
 * 因此选择器用 :global() 命名空间化（st- 前缀已足够唯一）。 */
:global(.st-tooltip) {
  z-index: var(--st-z-dropdown);
  max-width: 260px;
  padding: 5px 12px;
  border-radius: var(--radius-medium);
  background-color: var(--st-tooltip-bg);
  color: var(--st-tooltip-text);
  font-size: var(--st-font-medium);
  line-height: 1.4;
  white-space: nowrap;
  pointer-events: none;
  -webkit-backdrop-filter: blur(var(--st-tooltip-blur));
  backdrop-filter: blur(var(--st-tooltip-blur));
  overflow-wrap: anywhere;

  /* 进场起始位移，按 data-side 改写：始终从触发器一侧滑入（旧版侧栏
   * 提示即 translateX(-4px)，此处推广到四个方向）。 */
  --st-tooltip-shift-x: 0px;
  --st-tooltip-shift-y: 0px;
}

:global(.st-tooltip[data-side='right']) {
  --st-tooltip-shift-x: -4px;
}

:global(.st-tooltip[data-side='left']) {
  --st-tooltip-shift-x: 4px;
}

:global(.st-tooltip[data-side='bottom']) {
  --st-tooltip-shift-y: -4px;
}

:global(.st-tooltip[data-side='top']) {
  --st-tooltip-shift-y: 4px;
}

/* 进出场：0.12s 淡入 + 4px 位移，与旧 .sidebar-global-tooltip 同节奏
 * （进场 easeOut、退场 easeIn 且略快）。reka 的 Presence 监听 data-state
 * 由 open → closed 时 animation-name 的变化来延迟卸载，因此退场必须写
 * 在 [data-state='closed'] 上才生效。
 * 动画只作用于内容根：定位用的 transform 在 reka 外层 popper wrapper 上，
 * 二者互不打架。 */
:global(.st-tooltip[data-state='open']) {
  animation: st-tooltip-in 0.12s var(--ease-out);
}

:global(.st-tooltip[data-state='closed']) {
  animation: st-tooltip-out 0.1s var(--ease-in);
}

@keyframes st-tooltip-in {
  from {
    opacity: 0;
    transform: translate(var(--st-tooltip-shift-x), var(--st-tooltip-shift-y));
  }
}

@keyframes st-tooltip-out {
  to {
    opacity: 0;
    transform: translate(var(--st-tooltip-shift-x), var(--st-tooltip-shift-y));
  }
}

@media (prefers-reduced-motion: reduce) {
  :global(.st-tooltip[data-state='open']),
  :global(.st-tooltip[data-state='closed']) {
    animation: none;
  }
}
</style>
