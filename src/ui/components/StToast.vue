<script setup lang="ts">
/**
 * StToast — 全局提示渲染层
 *
 * 用法（应用根组件挂一次即可）：
 *   <StToast placement="top" :max="5" />
 *
 * 数据来自模块级单例 useToast()，任意调用点 toast.success(...) 都会推到这里。
 * 容器本身 pointer-events: none，只有单条提示可交互，避免遮挡页面点击。
 */
import { computed, watch } from 'vue'
import StIcon from './StIcon.vue'
import StSpinner from './StSpinner.vue'
import { useToast, type StToastType } from '../composables/useToast'

defineOptions({ name: 'StToast' })

type StToastPlacement = 'top' | 'top-right' | 'bottom-right'

const props = withDefaults(
  defineProps<{
    /** 堆叠位置 */
    placement?: StToastPlacement
    /** 同屏最多显示条数，超出时优先丢弃最旧的 */
    max?: number
  }>(),
  {
    placement: 'top',
    max: 5,
  },
)

const { toasts, remove } = useToast()

const ICONS: Record<Exclude<StToastType, 'loading'>, string> = {
  success: 'circle-check',
  error: 'circle-x',
  warning: 'alert-triangle',
  info: 'info-circle',
}

/** 数量上限是渲染层的契约，队列本身不感知 max；溢出时丢掉最旧的（并清掉其计时器） */
watch(
  [() => toasts.value.length, () => props.max],
  () => {
    const excess = toasts.value.length - props.max
    if (excess <= 0) return
    toasts.value.slice(0, excess).forEach((item) => remove(item.id))
  },
  { immediate: true },
)

/** 最新的提示贴近视口边缘，旧提示被推开 */
const items = computed(() =>
  props.placement === 'bottom-right' ? toasts.value : [...toasts.value].reverse(),
)

function iconName(type: StToastType) {
  return type === 'loading' ? 'info-circle' : ICONS[type]
}
</script>

<template>
  <Teleport to="body">
    <TransitionGroup tag="div" class="st-toast" :class="`st-toast--${placement}`" name="st-toast">
      <div
        v-for="item in items"
        :key="item.id"
        class="st-toast__item"
        :class="`st-toast__item--${item.type}`"
        :role="item.type === 'error' ? 'alert' : 'status'"
      >
        <span class="st-toast__icon">
          <StSpinner v-if="item.type === 'loading'" :size="18" />
          <StIcon v-else :name="iconName(item.type)" :size="18" />
        </span>

        <div class="st-toast__content">
          <h6 class="st-toast__title">{{ item.title }}</h6>
          <p v-if="item.message" class="st-toast__message">{{ item.message }}</p>
        </div>

        <button type="button" class="st-toast__close" aria-label="关闭" @click="remove(item.id)">
          <StIcon name="x" :size="14" />
        </button>
      </div>
    </TransitionGroup>
  </Teleport>
</template>

<style scoped>
.st-toast {
  position: fixed;
  z-index: var(--st-z-toast);
  display: flex;
  flex-direction: column;
  gap: 10px;

  /* 容器不接收点击，只有单条提示可交互 */
  pointer-events: none;
}

.st-toast--top {
  top: 24px;
  left: 50%;
  align-items: center;
  translate: -50% 0;
}

.st-toast--top-right {
  top: 24px;
  right: 24px;
  align-items: flex-end;
}

.st-toast--bottom-right {
  bottom: 24px;
  right: 24px;
  align-items: flex-end;
}

.st-toast__item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  box-sizing: border-box;
  width: max-content;
  min-width: min(20rem, calc(100vw - 32px));
  max-width: min(26rem, calc(100vw - 32px));
  /* 高度折叠过渡需要裁掉溢出内容（见下方 enter/leave 动效） */
  max-height: 20rem;
  margin-top: 0;
  margin-bottom: 0;
  overflow: hidden;
  padding: 12px 14px;
  border: var(--st-popover-border);
  border-radius: var(--radius-large);
  background-color: var(--st-popover-bg);
  color: var(--st-text);
  box-shadow: var(--st-popover-shadow);
  font-size: var(--st-font-medium);
  cursor: default;
  pointer-events: auto;
}

.st-toast__icon {
  display: inline-flex;
  flex: none;
  margin-top: 1px;
}

.st-toast__item--success .st-toast__icon {
  color: var(--success);
}

.st-toast__item--error .st-toast__icon {
  color: var(--danger);
}

.st-toast__item--warning .st-toast__icon {
  color: var(--warning);
}

.st-toast__item--info .st-toast__icon {
  color: var(--primary);
}

.st-toast__item--loading .st-toast__icon {
  color: var(--primary);
}

.st-toast__content {
  flex: 1 1 auto;
  min-width: 0;
}

.st-toast__title {
  margin: 0;
  font-size: var(--st-font-medium);
  font-weight: 600;
  line-height: 1.4;
  color: var(--foreground);
  overflow-wrap: anywhere;
}

.st-toast__message {
  margin: 2px 0 0;
  font-size: var(--st-font-small);
  line-height: 1.5;
  color: var(--muted-foreground);
  overflow-wrap: anywhere;
}

.st-toast__close {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: none;
  width: 22px;
  height: 22px;
  margin: -2px -4px 0 0;
  padding: 0;
  border: none;
  border-radius: var(--radius-full);
  background-color: transparent;
  color: var(--muted-foreground);
  cursor: pointer;
  transition: color var(--transition-fast);
}

.st-toast__close:hover {
  color: var(--foreground);
}

.st-toast__close:focus-visible {
  outline: none;
  box-shadow: var(--st-focus-ring);
}

/* ==================== 进场 / 退场 ====================
 * 逐值对齐 Naive UI 的 fadeInHeightExpandTransition
 * （src/_styles/transitions/fade-in-height-expand.cssr.ts）：
 *   duration:  .3s（进出同一个 duration）
 *   几何属性（max-height / margin / padding）: cubic-bezier(.4,0,.2,1) easeInOut
 *   opacity:   进入用 easeIn、退出用 easeOut
 * 目的：新增/移除时条目在高度上展开/折叠，让同屏其余条目平滑推开。 */
.st-toast-enter-active {
  transition:
    max-height 0.3s var(--ease-in-out),
    opacity 0.3s var(--ease-in),
    margin-top 0.3s var(--ease-in-out),
    margin-bottom 0.3s var(--ease-in-out),
    padding-top 0.3s var(--ease-in-out),
    padding-bottom 0.3s var(--ease-in-out);
}

.st-toast-leave-active {
  transition:
    max-height 0.3s var(--ease-in-out),
    opacity 0.3s var(--ease-out),
    margin-top 0.3s var(--ease-in-out),
    margin-bottom 0.3s var(--ease-in-out),
    padding-top 0.3s var(--ease-in-out),
    padding-bottom 0.3s var(--ease-in-out);
}

/* 位移过渡：让被推开的其它条目一起动（Naive 用同样的几何曲线） */
.st-toast-move {
  transition:
    translate 0.3s var(--ease-in-out),
    margin-top 0.3s var(--ease-in-out);
}

.st-toast-enter-from,
.st-toast-leave-to {
  opacity: 0;
  translate: 0 -10px;
  max-height: 0;
  margin-top: 0;
  margin-bottom: 0;
  padding-top: 0;
  padding-bottom: 0;
}

.st-toast--bottom-right .st-toast-enter-from,
.st-toast--bottom-right .st-toast-leave-to {
  translate: 0 10px;
}

.st-toast-enter-to,
.st-toast-leave-from {
  max-height: 20rem;
}

/* 容器自身不折叠，靠 gap 保持间距 */
.st-toast-leave-active {
  /* 退场期间不让出 DOM 位置，避免同屏其余条目二次位移 */
  pointer-events: none;
}

@media (prefers-reduced-motion: reduce) {
  .st-toast-enter-active,
  .st-toast-leave-active,
  .st-toast-move,
  .st-toast__close {
    transition: none;
  }

  .st-toast-enter-from,
  .st-toast-leave-to {
    max-height: none;
  }
}
</style>
