<script setup lang="ts">
/**
 * StModal — 模态对话框
 *
 * 用法：
 *   <StModal v-model:show="visible" title="编辑资料" size="medium" @close="onClosed">
 *     <template #action><StButton size="small">帮助</StButton></template>
 *     内容
 *     <template #footer>
 *       <StButton @click="visible = false">取消</StButton>
 *       <StButton type="primary" @click="save">保存</StButton>
 *     </template>
 *   </StModal>
 *
 * 焦点陷阱 / ESC / 滚动锁定 / aria-modal 由 reka-ui Dialog 提供，组件内不手写。
 * 关闭途径（关闭按钮 / ESC / 点击遮罩）统一受 closable 约束；
 * close 事件只在 reka 内部触发关闭时发出，父组件主动改 show 不会回环。
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

defineOptions({ name: 'StModal' })

type StModalSize = 'small' | 'medium' | 'large' | 'full'

const props = withDefaults(
  defineProps<{
    /** 标题；与 header 插槽二选一，插槽优先 */
    title?: string
    /** 副标题，仅在有标题时展示 */
    subtitle?: string
    /** 宽度档位；full 额外放宽最大高度 */
    size?: StModalSize
    /** false 时禁用全部关闭途径：关闭按钮 / ESC / 点击遮罩 */
    closable?: boolean
    /** 点击遮罩关闭，受 closable 约束 */
    maskClosable?: boolean
    /** ESC 关闭，受 closable 约束 */
    escClosable?: boolean
    /** 是否渲染右上角关闭按钮，受 closable 约束 */
    showClose?: boolean
  }>(),
  {
    size: 'medium',
    closable: true,
    maskClosable: true,
    escClosable: true,
    showClose: true,
  },
)

const show = defineModel<boolean>('show', { default: false })

const emit = defineEmits<{
  (e: 'close'): void
}>()

const slots = useSlots()

const canEscape = computed(() => props.closable && props.escClosable)
const canMaskClose = computed(() => props.closable && props.maskClosable)
const canShowClose = computed(() => props.closable && props.showClose)
const hasHeading = computed(() => !!slots.header || !!props.title)

/** 受控回写：reka 只在内部发起关闭时 emit update:open，这里就是 close 的唯一出口 */
function onOpenChange(value: boolean) {
  show.value = value
  if (!value) emit('close')
}

function onEscapeKeyDown(ev: KeyboardEvent) {
  if (!canEscape.value) ev.preventDefault()
}

// DismissableLayer 仅在 defaultPrevented 为 false 时 dismiss
function onPointerDownOutside(ev: Event) {
  if (!canMaskClose.value) ev.preventDefault()
}
</script>

<template>
  <DialogRoot :open="show" :modal="true" @update:open="onOpenChange">
    <DialogPortal to="body">
      <DialogOverlay class="st-modal__overlay" />

      <DialogContent
        class="st-modal__content"
        :class="`st-modal__content--${size}`"
        @escape-key-down="onEscapeKeyDown"
        @pointer-down-outside="onPointerDownOutside"
      >
        <!-- 无可见标题时也要给出可访问名称，否则 aria-labelledby 指向不存在的节点 -->
        <DialogTitle v-if="!hasHeading" class="st-modal__title st-modal__title--sr">
          对话框
        </DialogTitle>

        <header v-if="hasHeading || slots.action || canShowClose" class="st-modal__header">
          <div v-if="hasHeading" class="st-modal__heading">
            <DialogTitle class="st-modal__title">
              <slot name="header">{{ title }}</slot>
            </DialogTitle>
            <DialogDescription v-if="subtitle" class="st-modal__subtitle">
              {{ subtitle }}
            </DialogDescription>
          </div>

          <div v-if="slots.action" class="st-modal__action"><slot name="action" /></div>

          <DialogClose
            v-if="canShowClose"
            class="st-modal__close"
            :class="{ 'st-modal__close--plain': !canEscape }"
            aria-label="关闭"
          >
            <span class="st-modal__close-esc" aria-hidden="true">ESC</span>
            <StIcon class="st-modal__close-x" name="x" :size="16" />
          </DialogClose>
        </header>

        <div class="st-modal__body"><slot /></div>

        <footer v-if="slots.footer" class="st-modal__footer"><slot name="footer" /></footer>
      </DialogContent>
    </DialogPortal>
  </DialogRoot>
</template>

<style scoped>
.st-modal__overlay {
  position: fixed;
  inset: 0;
  z-index: var(--st-z-modal);
  background-color: var(--st-overlay-bg);
  /* 对齐 Naive Modal：遮罩 fade-in .25s easeOut（进出同值） */
  animation: st-modal-overlay-in var(--st-duration-modal) var(--ease-out);
}

/* reka 在关闭时把 data-state 改为 closed；Presence 会等动画结束再卸载。 */
.st-modal__overlay[data-state='closed'] {
  animation: st-modal-overlay-out var(--st-duration-modal) var(--ease-out);
}

/* 对齐 Naive Modal：fade-in-scale-up .25s，enterScale .5
 * 进场 easeOut、退场 easeIn（Naive fadeInScaleUpTransition 的取值） */
.st-modal__content {
  position: fixed;
  top: 50%;
  left: 50%;
  z-index: var(--st-z-modal);
  display: flex;
  flex-direction: column;
  box-sizing: border-box;
  max-height: calc(100vh - 64px);
  translate: -50% -50%;
  border: var(--st-popover-border);
  border-radius: var(--radius-large);
  background-color: var(--st-popover-bg);
  color: var(--st-text);
  box-shadow: var(--st-popover-shadow);
  outline: none;
  animation: st-modal-content-in var(--st-duration-modal) var(--ease-out);
}

/* 退场用 easeIn：内容向中心缩放时先慢后快，收得干脆 */
.st-modal__content[data-state='closed'] {
  animation: st-modal-content-out var(--st-duration-modal) var(--ease-in);
}

/* ==================== 尺寸 ==================== */
.st-modal__content--small {
  width: min(400px, calc(100vw - 32px));
}

.st-modal__content--medium {
  width: min(520px, calc(100vw - 32px));
}

.st-modal__content--large {
  width: min(720px, calc(100vw - 32px));
}

.st-modal__content--full {
  width: calc(100vw - 32px);
  max-height: calc(100vh - 32px);
}

/* ==================== 分区 ==================== */
.st-modal__header {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  flex: none;
  padding: 16px 20px 0;
}

.st-modal__heading {
  flex: 1 1 auto;
  min-width: 0;
}

.st-modal__title {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  line-height: 1.4;
  color: var(--foreground);
  overflow-wrap: anywhere;
}

/* 仅供读屏的兜底标题 */
.st-modal__title--sr {
  position: absolute;
  width: 1px;
  height: 1px;
  margin: -1px;
  padding: 0;
  overflow: hidden;
  clip-path: inset(50%);
  white-space: nowrap;
}

.st-modal__subtitle {
  margin: 4px 0 0;
  font-size: var(--st-font-small);
  line-height: 1.5;
  color: var(--muted-foreground);
}

.st-modal__action {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: none;
}

.st-modal__body {
  flex: 1 1 auto;
  min-height: 0;
  padding: 16px 20px;
  overflow-y: auto;
  font-size: var(--st-font-medium);
  line-height: 1.6;
}

.st-modal__footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  flex: none;
  padding: 4px 20px 16px;
}

/* ==================== 关闭按钮 ==================== */
.st-modal__close {
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

.st-modal__close:hover {
  border-color: var(--st-border-hover);
  background-color: var(--st-fill-hover);
}

.st-modal__close:focus-visible {
  outline: none;
  border-color: var(--ring);
  box-shadow: var(--st-focus-ring);
}

.st-modal__close-x {
  display: none;
}

/* 不可用 ESC 关闭时退化为常规 × 圆钮 */
.st-modal__close--plain {
  min-width: 0;
  width: 1.75rem;
  padding: 0;
  border-color: transparent;
  border-radius: var(--radius-full);
  background-color: transparent;
  color: var(--muted-foreground);
}

.st-modal__close--plain .st-modal__close-esc {
  display: none;
}

.st-modal__close--plain .st-modal__close-x {
  display: block;
}

/* 触屏设备没有 ESC 键，显示 × 更直观 */
@media (hover: none), (pointer: coarse) {
  .st-modal__close {
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

  .st-modal__close-esc {
    display: none;
  }

  .st-modal__close-x {
    display: block;
  }
}

@keyframes st-modal-content-in {
  from {
    opacity: 0;
    scale: 0.5;
  }
}

@keyframes st-modal-content-out {
  to {
    opacity: 0;
    scale: 0.5;
  }
}

@keyframes st-modal-overlay-in {
  from {
    opacity: 0;
  }
}

@keyframes st-modal-overlay-out {
  to {
    opacity: 0;
  }
}

@media (prefers-reduced-motion: reduce) {
  .st-modal__content,
  .st-modal__overlay,
  .st-modal__content[data-state='closed'],
  .st-modal__overlay[data-state='closed'] {
    animation: none;
  }

  .st-modal__close {
    transition: none;
  }
}
</style>
