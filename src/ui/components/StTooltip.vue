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
  TooltipArrow,
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
  }>(),
  {
    placement: 'top',
    disabled: false,
    delayDuration: 200,
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
          :side-offset="8"
          :collision-padding="8"
        >
          {{ content }}
          <TooltipArrow class="st-tooltip__arrow" :width="10" :height="5" />
        </TooltipContent>
      </TooltipPortal>
    </TooltipRoot>
  </TooltipProvider>
</template>

<style scoped>
.st-tooltip {
  z-index: var(--st-z-dropdown);
  max-width: 260px;
  padding: 6px 10px;
  border: var(--st-popover-border);
  border-radius: var(--radius-medium);
  background-color: var(--st-popover-bg);
  color: var(--st-text);
  box-shadow: var(--st-popover-shadow);
  font-size: var(--st-font-small);
  line-height: 1.5;
  overflow-wrap: anywhere;
}

/* reka 的箭头是 <span><svg>…，颜色靠 currentColor 传递 */
.st-tooltip__arrow {
  color: var(--st-popover-bg);
  fill: currentColor;
}

.st-tooltip__arrow svg {
  display: block;
}
</style>
