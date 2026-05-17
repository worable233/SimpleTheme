<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import UndrawIllustration from '@/components/UndrawIllustration.vue'
import { showToast, dismissToast } from '@/lib/toast'

const route = useRoute()
const router = useRouter()

const targetUrl = computed(() => {
  const raw = route.query.url as string | undefined
  if (!raw) return '/'
  try {
    return decodeURIComponent(raw)
  } catch {
    return raw
  }
})

const displayUrl = computed(() => {
  try {
    const url = new URL(targetUrl.value)
    return url.hostname + (url.pathname.length > 1 ? url.pathname.substring(0, 50) + (url.pathname.length > 50 ? '...' : '') : '')
  } catch {
    return targetUrl.value
  }
})

const countdown = ref(5)
const isRedirecting = ref(false)
let timer: ReturnType<typeof setInterval> | null = null
let countdownToast: HTMLElement | null = null

function doRedirect() {
  if (isRedirecting.value) return
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
  if (!route.query.url) return

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
</script>

<template>
  <section class="go-page">
    <!-- Invalid or missing URL -->
    <div v-if="!route.query.url" class="go-container">
      <div class="go-illustration">
        <UndrawIllustration name="access-denied" width="300" height="225" />
      </div>
      <h1 class="go-title">无效的链接</h1>
      <p class="go-desc">缺少前往地址。</p>
      <div class="go-actions">
        <button class="button go-btn" @click="goBack">返回首页</button>
      </div>
    </div>

    <!-- Valid redirect confirmation -->
    <div v-else class="go-container">
      <div class="go-illustration">
        <UndrawIllustration name="navigator" width="300" height="225" />
      </div>

      <h1 class="go-title">即将前往外部网站</h1>
      <p class="go-desc">您即将访问以下链接：</p>

      <div class="go-url-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
          <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
          <polyline points="15 3 21 3 21 9" />
          <line x1="10" y1="14" x2="21" y2="3" />
        </svg>
        <span :title="targetUrl">{{ displayUrl }}</span>
      </div>

      <div class="go-actions">
        <button class="button go-btn go-btn--primary" :disabled="isRedirecting" @click="doRedirect">
          {{ isRedirecting ? '正在前往...' : '继续前往' }}
        </button>
        <button class="button go-btn go-btn--ghost" @click="goBack">返回首页</button>
      </div>

      <p class="go-disclaimer">
        本站仅为用户提供信息参考，不对目标网站的内容、安全性、准确性及合法性作任何保证。请用户自行判断并承担相关风险。
      </p>
    </div>
  </section>
</template>

<style scoped>
.go-page {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 2rem 1rem;
}

.go-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex: 1;
  max-width: 440px;
  width: 100%;
  text-align: center;
}

.go-illustration {
  width: 100%;
  max-width: 300px;
  margin-bottom: 1.5rem;
}

.go-title {
  font-size: 1.35rem;
  font-weight: 650;
  color: var(--foreground);
  margin: 0 0 0.5rem;
  line-height: 1.4;
}

.go-desc {
  font-size: 0.875rem;
  color: var(--secondary);
  margin: 0 0 1.25rem;
  line-height: 1.6;
}

.go-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  justify-content: center;
  width: 100%;
}

.go-btn {
  flex: 1;
  min-width: 140px;
  padding: 0.75rem 1.5rem !important;
  font-size: 0.9375rem !important;
  border-radius: 10px !important;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: center;
}

.go-btn--primary {
  background: var(--primary);
  color: var(--primary-foreground);
  border: 1px solid var(--primary);
}

.go-btn--primary:hover:not(:disabled) {
  opacity: 0.85;
}

.go-btn--primary:disabled {
  opacity: 0.5;
  cursor: default;
}

.go-btn--ghost {
  background: transparent;
  color: var(--foreground);
  border: 1px solid var(--border);
}

.go-btn--ghost:hover {
  background: var(--accent);
}

.go-url-box {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.75rem 1rem;
  border-radius: 12px;
  background: var(--muted);
  color: var(--foreground);
  font-size: 0.875rem;
  word-break: break-all;
  text-align: left;
  margin-bottom: 0.75rem;
  border: 1px solid var(--border);
}

.go-url-box svg {
  flex-shrink: 0;
  color: var(--secondary);
}

.go-disclaimer {
  font-size: 0.75rem;
  color: var(--secondary);
  margin: 1.5rem 0 0;
  line-height: 1.6;
  opacity: 0.75;
  max-width: 380px;
}
</style>
