<script setup lang="ts">
/**
 * StDrawer — 侧滑抽屉
 *
 * 用法：
 *   <StDrawer v-model:show="visible" title="筛选" placement="right" width="360px">
 *     <template #footer><StButton block type="primary">应用</StButton></template>
 *     内容
 *   </StDrawer>
 *
 * 与 StModal 同源，都基于 reka-ui Dialog：
 * 焦点陷阱 / ESC / 滚动锁定 / 返回焦点由 reka 负责，这里只换容器形态与动效方向。
 */
import { computed, useSlots } from 'vue'
import {
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogOverlay,
  DialogPortal,
  DialogRoot,
  DialogTitle,
} from 'reka-ui'
import StIcon from './StIcon.vue'

defineOptions({ name: 'StDrawer' })

type StDrawerPlacement = 'right' | 'left'

const props = withDefaults(
  defineProps<{
    /** 标题；与 header 插槽二选一，插槽优先 */
    title?: string
    /** 副标题，仅在有标题时展示 */
    subtitle?: string
    /** 滑出方向 */
    placement?: StDrawerPlacement
    /** 面板宽度，任意合法 CSS 长度 */
    width?: string
    /** false 时禁用全部关闭途径：关闭按钮 / ESC / 点击遮罩 */
    closable?: boolean
    /** 是否渲染角落的关闭按钮，受 closable 约束 */
    showClose?: boolean
  }>(),
  {
    placement: 'right',
    width: '320px',
    closable: true,
    showClose: true,
  },
)

const show = defineModel<boolean>('show', { default: false })

const emit = defineEmits<{
  (e: 'close'): void
}>()

const slots = useSlots()

const canShowClose = computed(() => props.closable && props.showClose)
const hasHeading = computed(() => !!slots.header || !!props.title)

/** 受控回写：reka 只在内部发起关闭时 emit update:open */
function onOpenChange(value: boolean) {
  show.value = value
  if (!value) emit('close')
}

function onEscapeKeyDown(ev: KeyboardEvent) {
  if (!props.closable) ev.preventDefault()
}

// DismissableLayer 仅在 defaultPrevented 为 false 时 dismiss
function onPointerDownOutside(ev: Event) {
  if (!props.closable) ev.preventDefault()
}
</script>

<template>
  <DialogRoot :open="show" :modal="true" @update:open="onOpenChange">
    <DialogPortal to="body">
      <DialogOverlay class="st-drawer__overlay" />

      <DialogContent
        class="st-drawer__content"
        :class="`st-drawer__content--${placement}`"
        :style="{ width }"
        @escape-key-down="onEscapeKeyDown"
        @pointer-down-outside="onPointerDownOutside"
      >
        <!-- 无可见标题时也要给出可访问名称，否则 aria-labelledby 指向不存在的节点 -->
        <DialogTitle v-if="!hasHeading" class="st-drawer__title st-drawer__title--sr">
          抽屉
        </DialogTitle>

        <header v-if="hasHeading || canShowClose" class="st-drawer__header">
          <div v-if="hasHeading" class="st-drawer__heading">
            <DialogTitle class="st-drawer__title">
              <slot name="header">{{ title }}</slot>
            </DialogTitle>
            <DialogDescription v-if="subtitle" class="st-drawer__subtitle">
              {{ subtitle }}
            </DialogDescription>
          </div>

          <DialogClose v-if="canShowClose" class="st-drawer__close" aria-label="关闭">
            <span class="st-drawer__close-esc" aria-hidden="true">ESC</span>
            <StIcon class="st-drawer__close-x" name="x" :size="16" />
          </DialogClose>
        </header>

        <div class="st-drawer__body"><slot /></div>

        <footer v-if="slots.footer" class="st-drawer__footer"><slot name="footer" /></footer>
      </DialogContent>
    </DialogPortal>
  </DialogRoot>
</template>

<style scoped>
.st-drawer__overlay {
  position: fixed;
  inset: 0;
  z-index: var(--st-z-modal);
  background-color: var(--st-overlay-bg);
  animation: st-drawer-fade 0.2s var(--ease-out-quart);
}

.st-drawer__content {
  position: fixed;
  top: 0;
  bottom: 0;
  z-index: var(--st-z-modal);
  display: flex;
  flex-direction: column;
  box-sizing: border-box;
  max-width: 100vw;
  background-color: var(--st-popover-bg);
  color: var(--st-text);
  box-shadow: var(--st-popover-shadow);
  outline: none;
}

/* 圆角与内阴影只出现在朝向页面的一侧，避免"悬浮卡片"感 */
.st-drawer__content--right {
  right: 0;
  border-left: var(--st-popover-border);
  animation: st-drawer-in-right 0.28s var(--ease-out-quart);
}

.st-drawer__content--left {
  left: 0;
  border-right: var(--st-popover-border);
  animation: st-drawer-in-left 0.28s var(--ease-out-quart);
}

/* ==================== 分区 ==================== */
.st-drawer__header {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  flex: none;
  padding: 16px 16px 0;
}

.st-drawer__heading {
  flex: 1 1 auto;
  min-width: 0;
}

.st-drawer__title {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  line-height: 1.4;
  color: var(--foreground);
  overflow-wrap: anywhere;
}

/* 仅供读屏的兜底标题 */
.st-drawer__title--sr {
  position: absolute;
  width: 1px;
  height: 1px;
  margin: -1px;
  padding: 0;
  overflow: hidden;
  clip-path: inset(50%);
  white-space: nowrap;
}

.st-drawer__subtitle {
  margin: 4px 0 0;
  font-size: var(--st-font-small);
  line-height: 1.5;
  color: var(--muted-foreground);
}

.st-drawer__body {
  flex: 1 1 auto;
  min-height: 0;
  padding: 16px;
  overflow-y: auto;
  font-size: var(--st-font-medium);
  line-height: 1.6;
}

.st-drawer__footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  flex: none;
  padding: 4px 16px 16px;
}

/* ==================== 关闭按钮 ==================== */
.st-drawer__close {
  display: flex;
  align-items: center;
  justify-content: center;
  flex: none;
  box-sizing: border-box;
  min-width: 2.5rem;
  height: 1.6rem;
  margin-top: 2px;
  padding: 0 6px;
  border: 1px solid var(--st-border);
  border-radius: var(--radius-small);
  background-color: var(--st-fill-active);
  color: var(--st-text);
  font-family: var(--font-code);
  font-size: 0.66rem;
  font-weight: 700;
  line-height: 1;
  cursor: pointer;
  user-select: none;
  transition:
    background-color var(--transition-fast),
    border-color var(--transition-fast),
    color var(--transition-fast);
}

.st-drawer__close:hover {
  border-color: var(--st-border-hover);
  background-color: var(--st-fill-hover);
}

.st-drawer__close:focus-visible {
  outline: none;
  border-color: var(--ring);
  box-shadow: var(--st-focus-ring);
}

.st-drawer__close-x {
  display: none;
}

/* 触屏设备没有 ESC 键，显示 × 更直观 */
@media (hover: none), (pointer: coarse) {
  .st-drawer__close {
    min-width: 0;
    width: 30px;
    height: 30px;
    margin-right: -4px;
    padding: 0;
    border-color: transparent;
    border-radius: var(--radius-full);
    background-color: transparent;
    color: var(--muted-foreground);
  }

  .st-drawer__close-esc {
    display: none;
  }

  .st-drawer__close-x {
    display: block;
  }
}

@keyframes st-drawer-in-right {
  from {
    opacity: 0.6;
    translate: 100% 0;
  }
}

@keyframes st-drawer-in-left {
  from {
    opacity: 0.6;
    translate: -100% 0;
  }
}

@keyframes st-drawer-fade {
  from {
    opacity: 0;
  }
}

@media (prefers-reduced-motion: reduce) {
  .st-drawer__content,
  .st-drawer__overlay {
    animation: none;
  }

  .st-drawer__close {
    transition: none;
  }
}
</style>
