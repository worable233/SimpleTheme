<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useHead } from '@unhead/vue'
import { useRoute, useRouter } from 'vue-router'
import UndrawIllustration from '@/components/UndrawIllustration.vue'
import { showToast, dismissToast } from '@/lib/toast'

const route = useRoute()
const router = useRouter()

const rawTargetUrl = computed(() => {
  const raw = typeof route.query.url === 'string' ? route.query.url : ''
  // Vue Router has already decoded query values. Decoding again changes valid
  // targets containing a literal percent-encoded character.
  return raw
})

const targetUrl = computed(() => {
  try {
    const url = new URL(rawTargetUrl.value)
    return url.protocol === 'http:' || url.protocol === 'https:' ? url.href : ''
  } catch {
    return ''
  }
})

const hasValidTarget = computed(() => !!targetUrl.value)

useHead({ title: computed(() => (hasValidTarget.value ? '即将前往外部网站' : '无效的链接')) })

const displayUrl = computed(() => {
  try {
    const url = new URL(targetUrl.value)
    return (
      url.hostname +
      (url.pathname.length > 1
        ? url.pathname.substring(0, 50) + (url.pathname.length > 50 ? '...' : '')
        : '')
    )
  } catch {
    return targetUrl.value
  }
})

const countdown = ref(5)
const isRedirecting = ref(false)
let timer: ReturnType<typeof setInterval> | null = null
let countdownToast: HTMLElement | undefined

function doRedirect() {
  if (!hasValidTarget.value || isRedirecting.value) return
  isRedirecting.value = true
  if (countdownToast) dismissToast(countdownToast)
  window.location.href = targetUrl.value
}

function goBack() {
  if (countdownToast) dismissToast(countdownToast)
  if (window.history.length > 1) {
    router.back()
  } else {
    router.push('/')
  }
}

onMounted(() => {
  if (!hasValidTarget.value) return

  countdownToast = showToast('将在 5 秒后自动前往目标网站', undefined, { duration: 5000 })

  timer = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) {
      if (timer) clearInterval(timer)
      doRedirect()
    } else if (countdownToast) {
      const msgEl = countdownToast.querySelector('.toast-message')
      if (msgEl) msgEl.textContent = `将在 ${countdown.value} 秒后自动前往目标网站`
    }
  }, 1000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
  if (countdownToast) dismissToast(countdownToast)
})

// ----- 按钮样式 -----
const goBtnBase =
  'min-w-[140px] flex-1 cursor-pointer rounded-[10px] px-6 py-3 text-center text-[0.9375rem] transition-all duration-200'
const goBtnPrimary =
  'border border-primary bg-primary text-primary-foreground hover:opacity-85 disabled:cursor-default disabled:opacity-50 disabled:hover:opacity-50'
const goBtnGhost = 'border border-border bg-transparent text-foreground hover:bg-accent'
</script>

<template>
  <section class="flex min-h-screen items-center justify-center px-4 py-8">
    <!-- Invalid or missing URL -->
    <div
      v-if="!hasValidTarget"
      class="flex w-full max-w-[440px] flex-1 flex-col items-center justify-center text-center"
    >
      <div class="mb-6 w-full max-w-[300px]">
        <UndrawIllustration name="access-denied" width="300" height="225" />
      </div>
      <h1 class="mb-2 text-[1.35rem] leading-[1.4] font-[650] text-foreground">无效的链接</h1>
      <p class="mb-5 text-sm leading-relaxed text-secondary">
        {{ rawTargetUrl ? '仅支持 HTTP 或 HTTPS 链接。' : '缺少前往地址。' }}
      </p>
      <div class="flex w-full flex-wrap justify-center gap-3">
        <button :class="[goBtnBase, goBtnGhost]" @click="goBack">返回首页</button>
      </div>
    </div>

    <!-- Valid redirect confirmation -->
    <div
      v-else
      class="flex w-full max-w-[440px] flex-1 flex-col items-center justify-center text-center"
    >
      <div class="mb-6 w-full max-w-[300px]">
        <UndrawIllustration name="navigator" width="300" height="225" />
      </div>

      <h1 class="mb-2 text-[1.35rem] leading-[1.4] font-[650] text-foreground">即将前往外部网站</h1>
      <p class="mb-5 text-sm leading-relaxed text-secondary">您即将访问以下链接：</p>

      <div
        class="mb-3 flex w-full items-center gap-2 rounded-xl border border-border bg-muted px-4 py-3 text-left text-sm break-all text-foreground"
      >
        <svg
          class="shrink-0 text-secondary"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          width="16"
          height="16"
        >
          <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
          <polyline points="15 3 21 3 21 9" />
          <line x1="10" y1="14" x2="21" y2="3" />
        </svg>
        <span :title="targetUrl">{{ displayUrl }}</span>
      </div>

      <div class="flex w-full flex-wrap justify-center gap-3">
        <button :class="[goBtnBase, goBtnPrimary]" :disabled="isRedirecting" @click="doRedirect">
          {{ isRedirecting ? '正在前往...' : '继续前往' }}
        </button>
        <button :class="[goBtnBase, goBtnGhost]" @click="goBack">返回首页</button>
      </div>

      <p class="mt-6 max-w-[380px] text-xs leading-relaxed text-secondary opacity-75">
        本站仅为用户提供信息参考，不对目标网站的内容、安全性、准确性及合法性作任何保证。请用户自行判断并承担相关风险。
      </p>
    </div>
  </section>
</template>
