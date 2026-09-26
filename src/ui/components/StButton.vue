<script setup lang="ts">
/**
 * StButton — 按钮
 *
 * 用法：
 *   <StButton type="primary" @click="save">保存</StButton>
 *   <StButton loading>提交中</StButton>
 *   <StButton quaternary circle aria-label="关闭"><template #icon>…</template></StButton>
 *
 * 约定：对外只暴露 props / slots / emits，不接受 class 透传做样式覆盖。
 * 需要改外观请用 type / size / secondary… 等语义 props。
 */
import { computed, onBeforeUnmount, ref, useSlots } from 'vue'
import StSpinner from './StSpinner.vue'
import type { StIntent, StSize } from '../types'

defineOptions({ name: 'StButton' })

const props = withDefaults(
  defineProps<{
    /** 语义色 */
    type?: StIntent
    /** 尺寸 */
    size?: StSize
    /** 降低视觉权重：淡化描边与文字 */
    secondary?: boolean
    /** 三级权重 */
    tertiary?: boolean
    /** 四级权重：无背景、无边框 */
    quaternary?: boolean
    /** 纯文字按钮（无内边距背景，仅悬停变色） */
    text?: boolean
    /** 透明背景 + 彩色描边 */
    ghost?: boolean
    /** 虚线描边 */
    dashed?: boolean
    /** 全圆角 */
    round?: boolean
    /** 正圆（仅图标） */
    circle?: boolean
    /** 撑满父容器宽度 */
    block?: boolean
    /** 加粗文字 */
    strong?: boolean
    disabled?: boolean
    /** 加载中：显示指示器并阻止点击 */
    loading?: boolean
    /** 渲染的标签，默认 button（可传 'a' 等） */
    tag?: string
    /** 原生 type 属性，仅 tag='button' 时生效 */
    attrType?: 'button' | 'submit' | 'reset'
    /** 无障碍名称；circle 模式必填 */
    ariaLabel?: string
  }>(),
  {
    type: 'default',
    size: 'medium',
    tag: 'button',
    attrType: 'button',
  },
)

const emit = defineEmits<{
  (e: 'click', ev: MouseEvent): void
}>()

const slots = useSlots()

const classes = computed(() => [
  'st-button',
  `st-button--${props.type}`,
  `st-button--${props.size}`,
  {
    'st-button--secondary': props.secondary,
    'st-button--tertiary': props.tertiary,
    'st-button--quaternary': props.quaternary,
    'st-button--text': props.text,
    'st-button--ghost': props.ghost,
    'st-button--dashed': props.dashed,
    'st-button--round': props.round,
    'st-button--circle': props.circle,
    'st-button--block': props.block,
    'st-button--strong': props.strong,
    'st-button--icon-only': !slots.default && (!!slots.icon || props.circle),
    'is-disabled': props.disabled,
    'is-loading': props.loading,
  },
])

const isNativeButton = computed(() => props.tag === 'button')
const blocked = computed(() => props.disabled || props.loading)

/** 是否播放波纹：对齐 Naive —— text / secondary / tertiary / quaternary
 *  的 rippleColor 是透明 #0000，等于没有波纹；其余类型才有。 */
const hasWave = computed(
  () => !props.text && !props.secondary && !props.tertiary && !props.quaternary,
)

/** 点击波纹：与 Naive 一致，点击后激活动画、约 1s 后复位以便再次触发。 */
const waveActive = ref(false)
let waveTimer: ReturnType<typeof setTimeout> | null = null

function playWave() {
  if (waveTimer !== null) {
    clearTimeout(waveTimer)
    waveActive.value = false
  }
  requestAnimationFrame(() => {
    waveActive.value = true
    waveTimer = setTimeout(() => {
      waveActive.value = false
      waveTimer = null
    }, 1000)
  })
}

function handleClick(ev: MouseEvent) {
  if (blocked.value) {
    ev.preventDefault()
    ev.stopPropagation()
    return
  }
  if (hasWave.value) playWave()
  emit('click', ev)
}

onBeforeUnmount(() => {
  if (waveTimer !== null) clearTimeout(waveTimer)
})
</script>

<template>
  <component
    :is="tag"
    :class="classes"
    :type="isNativeButton ? attrType : undefined"
    :disabled="isNativeButton ? blocked : undefined"
    :aria-disabled="!isNativeButton && blocked ? true : undefined"
    :aria-busy="loading ? true : undefined"
    :aria-label="ariaLabel"
    :tabindex="!isNativeButton && blocked ? -1 : undefined"
    @click="handleClick"
  >
    <span
      v-if="hasWave"
      class="st-button__wave"
      :class="{ 'is-active': waveActive }"
      aria-hidden="true"
    />
    <!-- 图标槽：对齐 Naive Button 的两层结构
         外层 width-expand（出现/消失时宽度展开或收起）
         内层 icon-switch（spinner ↔ 图标 绝对定位交叉切换，scale .75） -->
    <Transition name="st-icon-expand">
      <span v-if="slots.icon || loading" class="st-button__icon">
        <Transition name="st-icon">
          <span v-if="loading" key="loading" class="st-button__icon-slot">
            <StSpinner :size="size" />
          </span>
          <span v-else key="icon" class="st-button__icon-slot">
            <slot name="icon" />
          </span>
        </Transition>
      </span>
    </Transition>
    <span v-if="slots.default" class="st-button__content"><slot /></span>
  </component>
</template>

<style scoped>
/* ==================== 基础 ==================== */
.st-button {
  --st-button-color: var(--st-text);
  --st-button-bg: var(--st-fill);
  --st-button-border: var(--st-border);
  /* 波纹色：默认取前景色，保证在浅/深两套主题下都与按钮底色有对比。
   * Naive 用品牌色（绿）；我们主题是中性色板，故用 foreground 作等价物。 */
  --st-button-ripple: var(--foreground);

  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  box-sizing: border-box;
  height: var(--st-height-medium);
  padding: var(--st-pad-medium);
  border: 1px solid var(--st-button-border);
  border-radius: var(--radius-medium);
  background-color: var(--st-button-bg);
  color: var(--st-button-color);
  font-family: inherit;
  font-size: var(--st-font-medium);
  font-weight: 400;
  line-height: 1;
  white-space: nowrap;
  cursor: pointer;
  user-select: none;
  text-decoration: none;
  transition:
    background-color var(--transition-fast),
    border-color var(--transition-fast),
    color var(--transition-fast),
    opacity var(--transition-fast);
}

/* ==================== 点击波纹（wave）====================
 * 严格对齐 Naive UI 的 NBaseWave（src/_internal/wave）：
 * 一个铺满按钮（inset:0, border-radius:inherit）的覆盖层，点击时让
 * box-shadow 从 0 向外扩散到 4.5px 并同步淡出 —— 是"贴边光环"，不是
 * 从鼠标点扩散的实心圆（后者是 Material ripple，二者不同）。
 * 参数取自 Naive：duration .6s、opacity .6。 */
.st-button__wave {
  position: absolute;
  inset: 0;
  z-index: 0;
  border-radius: inherit;
  pointer-events: none;
  opacity: 0;
}

.st-button__wave.is-active {
  animation:
    st-button-wave-spread 0.6s var(--ease-out),
    st-button-wave-opacity 0.6s var(--ease-out);
}

@keyframes st-button-wave-spread {
  from {
    /* 不用精确 5px：Chrome 在整数边界会闪烁（Naive 原注释） */
    box-shadow: 0 0 0.5px 0 var(--st-button-ripple);
  }
  to {
    box-shadow: 0 0 0.5px 4.5px var(--st-button-ripple);
  }
}

@keyframes st-button-wave-opacity {
  from {
    opacity: 0.6;
  }
  to {
    opacity: 0;
  }
}

.st-button:hover:not(.is-disabled):not(.is-loading) {
  border-color: var(--st-border-hover);
}

.st-button:focus-visible {
  outline: none;
  box-shadow: var(--st-focus-ring);
  border-color: var(--ring);
}

.st-button.is-disabled,
.st-button.is-loading {
  cursor: not-allowed;
  opacity: 0.5;
}

/* ==================== 尺寸 ==================== */
.st-button--tiny {
  height: var(--st-height-tiny);
  padding: var(--st-pad-tiny);
  font-size: var(--st-font-tiny);
  border-radius: var(--radius-small);
}

.st-button--small {
  height: var(--st-height-small);
  padding: var(--st-pad-small);
  font-size: var(--st-font-small);
  border-radius: var(--radius-small);
}

.st-button--large {
  height: var(--st-height-large);
  padding: var(--st-pad-large);
  font-size: var(--st-font-large);
}

/* ==================== 语义色 ==================== */
.st-button--primary {
  --st-button-color: var(--primary-foreground);
  --st-button-bg: var(--primary);
  --st-button-border: var(--primary);
}

.st-button--success {
  --st-button-color: var(--st-on-color);
  --st-button-bg: var(--success);
  --st-button-border: var(--success);
}

.st-button--warning {
  --st-button-color: var(--st-on-color);
  --st-button-bg: var(--warning);
  --st-button-border: var(--warning);
}

.st-button--error {
  --st-button-color: var(--st-on-color);
  --st-button-bg: var(--danger);
  --st-button-border: var(--danger);
}

/* 彩色按钮悬停：整体略降不透明度，避免为每种色再定义 hover 令牌 */
.st-button--primary:hover:not(.is-disabled):not(.is-loading),
.st-button--success:hover:not(.is-disabled):not(.is-loading),
.st-button--warning:hover:not(.is-disabled):not(.is-loading),
.st-button--error:hover:not(.is-disabled):not(.is-loading) {
  opacity: 0.85;
  border-color: transparent;
}

/* ==================== 权重变体 ==================== */
.st-button--secondary {
  opacity: 0.85;
}

.st-button--tertiary {
  --st-button-bg: transparent;
  --st-button-border: var(--st-border);
}

.st-button--quaternary,
.st-button--text {
  --st-button-bg: transparent;
  --st-button-border: transparent;
}

.st-button--quaternary:hover:not(.is-disabled):not(.is-loading),
.st-button--text:hover:not(.is-disabled):not(.is-loading) {
  --st-button-bg: var(--st-fill-hover);
  border-color: transparent;
}

.st-button--text {
  padding-left: 4px;
  padding-right: 4px;
}

.st-button--ghost {
  --st-button-bg: transparent;
  --st-button-color: var(--st-button-border);
}

.st-button--ghost.st-button--default {
  --st-button-color: var(--st-text);
}

.st-button--ghost:hover:not(.is-disabled):not(.is-loading) {
  --st-button-bg: color-mix(in srgb, var(--st-button-border) 12%, transparent);
}

.st-button--dashed {
  border-style: dashed;
}

/* ==================== 形状 ==================== */
.st-button--round {
  border-radius: var(--radius-full);
}

.st-button--circle {
  border-radius: var(--radius-full);
  width: var(--st-height-medium);
  padding: 0;
}

.st-button--circle.st-button--tiny {
  width: var(--st-height-tiny);
}

.st-button--circle.st-button--small {
  width: var(--st-height-small);
}

.st-button--circle.st-button--large {
  width: var(--st-height-large);
}

.st-button--block {
  display: flex;
  width: 100%;
}

.st-button--strong {
  font-weight: 600;
}

/* 仅图标时收紧水平内边距，避免图标视觉偏移 */
.st-button--icon-only:not(.st-button--circle) {
  padding-left: 6px;
  padding-right: 6px;
}

/* ==================== 内容 ====================
 * 结构对齐 Naive Button：
 *   .st-button__icon          —— 外层，宽度可展开（fade-in-width-expand）
 *   .st-button__icon-slot     —— 内层，绝对定位占满 icon 区，交叉切换 */
.st-button__icon {
  position: relative;
  z-index: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: none;
  overflow: hidden;
}

.st-button__icon-slot {
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* 交叉切换：spinner ↔ 图标 叠放在同一格，进出各带 scale(.75) 与淡变。
 * 对齐 Naive iconSwitchTransition：`all .3s cubicBezierEaseInOut`，
 * transform-origin center；切换期间绝对定位，避免两节点同时存在撑大尺寸。 */
.st-icon-enter-active,
.st-icon-leave-active {
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  transform-origin: center;
  transition: all 0.3s var(--ease-in-out);
}

.st-icon-enter-from,
.st-icon-leave-to {
  opacity: 0;
  transform: translateY(-50%) scale(0.75);
}

.st-icon-enter-to,
.st-icon-leave-from {
  opacity: 1;
  transform: translateY(-50%) scale(1);
}

/* 外层宽度展开：图标槽出现时从 0 宽展开、消失时收起。
 * 对齐 Naive fadeInWidthExpandTransition：duration .2s、easeInOut，
 * 几何属性（max-width / margin）带 .1s 延迟，opacity 立即——
 * 进场时先淡入再展开，退场时先淡出再收拢，避免文字跳动。 */
.st-icon-expand-enter-active,
.st-icon-expand-leave-active {
  overflow: hidden;
  transition:
    max-width 0.2s var(--ease-in-out),
    margin-left 0.2s var(--ease-in-out),
    margin-right 0.2s var(--ease-in-out),
    opacity 0.2s var(--ease-in-out) 0.1s;
}

.st-icon-expand-leave-active {
  transition:
    max-width 0.2s var(--ease-in-out) 0.1s,
    margin-left 0.2s var(--ease-in-out) 0.1s,
    margin-right 0.2s var(--ease-in-out) 0.1s,
    opacity 0.2s var(--ease-in-out);
}

.st-icon-expand-enter-from,
.st-icon-expand-leave-to {
  max-width: 0;
  margin-left: 0;
  margin-right: 0;
  opacity: 0;
}

.st-icon-expand-enter-to,
.st-icon-expand-leave-from {
  max-width: 8rem;
  opacity: 1;
}

.st-button__content {
  display: inline-block;
  overflow: hidden;
  text-overflow: ellipsis;
  position: relative;
  z-index: 1;
}

/* 加载态：文案略降透明度，配合 spinner 表达"处理中" */
.st-button.is-loading .st-button__content {
  opacity: 0.4;
  transition: opacity var(--duration-enter) var(--ease-out);
}

@media (prefers-reduced-motion: reduce) {
  .st-button {
    transition: none;
  }

  .st-button.is-loading .st-button__content,
  .st-button__icon-slot,
  .st-icon-enter-active,
  .st-icon-leave-active,
  .st-icon-expand-enter-active,
  .st-icon-expand-leave-active {
    transition: none;
  }

  .st-icon-enter-from,
  .st-icon-leave-to,
  .st-icon-expand-enter-from,
  .st-icon-expand-leave-to {
    opacity: 1;
  }

  .st-button__wave.is-active {
    animation: none;
  }
}
</style>
