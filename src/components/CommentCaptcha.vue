<script setup lang="ts">
/**
 * CommentCaptcha — 评论人机验证浮层
 *
 * 自己画 UI（不用 altcha 的 web component，它那套样式和主题完全不同），
 * 求解用 worker 里的 sha256 暴力枚举，协议与服务端 `simple_theme_verify_altcha`
 * 对齐：payload = base64(JSON{challenge, number, salt, signature})。
 *
 * 交互：点「发表评论」且还没验证过 → 在按钮上方弹出本浮层 → 自动求解
 * （进度条）→ 通过后回调父组件继续提交。
 */
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import AppIcon from '@/components/AppIcon.vue'
import { StButton, StProgress } from '@/ui'
import type { CaptchaData } from '@/types/wordpress'
import CaptchaWorker from '@/workers/captcha.worker?worker'
import type { SolveResponse } from '@/workers/captcha.worker'

defineOptions({ name: 'CommentCaptcha' })

const props = defineProps<{
  show: boolean
  challenge: CaptchaData | null
}>()

const emit = defineEmits<{
  (e: 'update:show', value: boolean): void
  (e: 'verified', payload: string): void
  /** 挑战缺失/过期，需要父组件重新拉一次 */
  (e: 'refresh'): void
}>()

type Phase = 'idle' | 'solving' | 'done' | 'error'

const phase = ref<Phase>('idle')
const progress = ref(0)
const panelRef = ref<HTMLElement | null>(null)
let worker: Worker | null = null
let doneTimer = 0

const title = computed(() => {
  switch (phase.value) {
    case 'solving': return '正在验证'
    case 'done': return '验证通过'
    case 'error': return '验证失败'
    default: return '人机验证'
  }
})

const desc = computed(() => {
  switch (phase.value) {
    case 'solving': return '马上就好，通过后自动发表'
    case 'done': return '正在为你发表评论…'
    case 'error': return '挑战可能已过期，请重试'
    default: return '发表前需要完成一次校验，用于防止刷屏'
  }
})

const titleIcon = computed(() => {
  switch (phase.value) {
    case 'solving': return 'shield'
    case 'done': return 'check'
    case 'error': return 'x'
    default: return 'shield'
  }
})

function stopWorker(): void {
  if (worker) {
    worker.terminate()
    worker = null
  }
  if (doneTimer) {
    clearTimeout(doneTimer)
    doneTimer = 0
  }
}

function encodePayload(challenge: CaptchaData, number: number): string {
  return btoa(
    JSON.stringify({
      algorithm: challenge.algorithm,
      challenge: challenge.challenge,
      number,
      salt: challenge.salt,
      signature: challenge.signature,
    }),
  )
}

function start(): void {
  const challenge = props.challenge
  if (!challenge) {
    phase.value = 'error'
    emit('refresh')
    return
  }

  stopWorker()
  phase.value = 'solving'
  progress.value = 0

  try {
    const instance = new CaptchaWorker()
    worker = instance
    instance.onmessage = (event: MessageEvent<SolveResponse>) => {
      const data = event.data
      if (data.type === 'progress') {
        // 别让进度条卡在 99% 不动，留出最后一步的观感
        progress.value = Math.min(95, Math.round(data.value * 100))
      } else if (data.type === 'found') {
        progress.value = 100
        phase.value = 'done'
        const payload = encodePayload(challenge, data.number)
        stopWorker()
        doneTimer = window.setTimeout(() => emit('verified', payload), 260)
      } else {
        phase.value = 'error'
        stopWorker()
        emit('refresh')
      }
    }
    instance.onerror = () => {
      phase.value = 'error'
      stopWorker()
    }
    instance.postMessage({
      salt: challenge.salt,
      challenge: challenge.challenge,
      max: challenge.maxnumber,
    })
  } catch {
    phase.value = 'error'
    stopWorker()
  }
}

function close(): void {
  if (phase.value === 'solving') return
  emit('update:show', false)
}

function onPointerDown(event: PointerEvent): void {
  if (!props.show) return
  const target = event.target as Node | null
  if (target && panelRef.value && !panelRef.value.contains(target)) close()
}

function onKeydown(event: KeyboardEvent): void {
  if (props.show && event.key === 'Escape') close()
}

watch(
  () => props.show,
  (open) => {
    if (open) {
      // 直接进 solving：idle 只在没有挑战时短暂存在，避免标题先闪一帧默认文案
      phase.value = 'solving'
      progress.value = 0
      document.addEventListener('pointerdown', onPointerDown, true)
      document.addEventListener('keydown', onKeydown)
      nextTick(() => {
        // 等浮层落位再开始算，避免求解和入场动画抢帧
        window.setTimeout(start, 60)
      })
    } else {
      stopWorker()
      phase.value = 'idle'
      document.removeEventListener('pointerdown', onPointerDown, true)
      document.removeEventListener('keydown', onKeydown)
    }
  },
  { immediate: true },
)

watch(
  () => props.challenge,
  () => {
    // 换了新挑战（比如上一次过期）→ 若浮层正开着就直接重算
    if (props.show && phase.value === 'error') start()
  },
)

onBeforeUnmount(() => {
  stopWorker()
  document.removeEventListener('pointerdown', onPointerDown, true)
  document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <Transition name="captcha-pop">
    <div
      v-if="show"
      ref="panelRef"
      class="comment-captcha"
      :class="`comment-captcha--${phase}`"
      role="dialog"
      aria-live="polite"
      :aria-label="title"
    >
      <div class="comment-captcha__head">
        <span class="comment-captcha__badge" aria-hidden="true">
          <AppIcon :name="titleIcon" :size="16" :stroke="2" />
        </span>
        <div class="comment-captcha__text">
          <p class="comment-captcha__title">{{ title }}</p>
          <p class="comment-captcha__desc">{{ desc }}</p>
        </div>
        <button
          v-if="phase !== 'solving'"
          type="button"
          class="comment-captcha__close"
          aria-label="关闭"
          @click="close"
        >
          <AppIcon name="x" :size="14" />
        </button>
      </div>

      <StProgress
        v-if="phase === 'solving'"
        class="comment-captcha__bar"
        :percentage="progress"
        :height="4"
        :show-indicator="false"
        processing
      />

      <div v-else-if="phase === 'error'" class="comment-captcha__actions">
        <StButton size="small" type="primary" @click="start">重新验证</StButton>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.comment-captcha {
  position: absolute;
  right: 0;
  bottom: calc(100% + 10px);
  z-index: var(--st-z-dropdown);
  box-sizing: border-box;
  width: min(296px, calc(100vw - 32px));
  padding: 12px 14px;
  border: var(--st-popover-border);
  border-radius: var(--radius-medium);
  background: var(--st-popover-bg);
  box-shadow: var(--st-popover-shadow);
  color: var(--st-text);
  transform-origin: bottom right;
}

/* 小三角，指向下方的按钮 */
.comment-captcha::after {
  content: '';
  position: absolute;
  right: 22px;
  bottom: -5px;
  width: 8px;
  height: 8px;
  border-right: var(--st-popover-border);
  border-bottom: var(--st-popover-border);
  background: var(--st-popover-bg);
  transform: rotate(45deg);
}

.comment-captcha__head {
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.comment-captcha__badge {
  display: inline-flex;
  flex: none;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  /* 不用 --primary：它是后台配置的品牌色（App.vue 内联到 :root），
     深色模式下常常是深色，压在这个深色底上几乎看不见 */
  color: var(--st-text);
  background: var(--st-fill-active);
  transition:
    color 0.2s var(--ease-in-out),
    background 0.2s var(--ease-in-out);
}

.comment-captcha--done .comment-captcha__badge {
  color: var(--success);
  background: color-mix(in srgb, var(--success) 14%, transparent);
}

.comment-captcha--error .comment-captcha__badge {
  color: var(--danger);
  background: color-mix(in srgb, var(--danger) 14%, transparent);
}

.comment-captcha__text {
  flex: 1;
  min-width: 0;
}

.comment-captcha__title {
  margin: 0;
  font-size: var(--st-font-small);
  font-weight: 600;
  line-height: 1.5;
}

.comment-captcha__desc {
  margin: 2px 0 0;
  font-size: 12px;
  line-height: 1.5;
  color: var(--muted-foreground);
}

.comment-captcha__close {
  flex: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  padding: 0;
  border: none;
  border-radius: 50%;
  color: var(--muted-foreground);
  background: transparent;
  cursor: pointer;
  transition:
    color 0.2s var(--ease-in-out),
    background 0.2s var(--ease-in-out);
}

.comment-captcha__close:hover {
  color: var(--st-text);
  background: var(--st-fill-active);
}

.comment-captcha__bar {
  margin-top: 10px;
}

/* 进度条默认用 --primary（后台配置的品牌色），深色模式下在深底上几乎看不见 */
.comment-captcha__bar :deep(.st-progress__fill) {
  background-color: var(--st-text);
}

.comment-captcha__actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 10px;
}

/* 入场：从按钮上方弹出 */
.captcha-pop-enter-active,
.captcha-pop-leave-active {
  transition:
    opacity 0.18s var(--ease-in-out),
    transform 0.18s var(--ease-in-out);
}

.captcha-pop-enter-from,
.captcha-pop-leave-to {
  opacity: 0;
  transform: translateY(6px) scale(0.96);
}
</style>
