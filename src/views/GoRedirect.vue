<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useHead } from '@unhead/vue'
import { useRoute, useRouter } from 'vue-router'
import UndrawIllustration from '@/components/UndrawIllustration.vue'
import AppIcon from '@/components/AppIcon.vue'
import { useSiteShell } from '@/composables/useSiteShell'
import { StButton, useToast } from '@/ui'

const route = useRoute()
const router = useRouter()
const { siteInfo, ensureLoaded } = useSiteShell()
const toast = useToast()

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

const redirectEnabled = computed(() => siteInfo.value.externalRedirect?.enabled ?? true)
const redirectDelay = computed(() => {
  const delay = Number(siteInfo.value.externalRedirect?.delay ?? 5)
  return Number.isFinite(delay) ? Math.min(30, Math.max(1, Math.round(delay))) : 5
})
const redirectTarget = computed<'_self' | '_blank' | '_parent' | '_top'>(() => {
  const target = siteInfo.value.externalRedirect?.target
  return target === '_blank' || target === '_parent' || target === '_top' ? target : '_self'
})
const countdown = ref(5)
const isRedirecting = ref(false)
let timer: ReturnType<typeof setInterval> | null = null
let countdownToastId: number | undefined

function doRedirect() {
  if (!hasValidTarget.value || isRedirecting.value) return
  isRedirecting.value = true
  if (countdownToastId !== undefined) toast.remove(countdownToastId)
  if (redirectTarget.value === '_self') {
    window.location.href = targetUrl.value
    return
  }

  const opened = window.open(targetUrl.value, redirectTarget.value, 'noopener,noreferrer')
  if (!opened) {
    window.location.href = targetUrl.value
  }
}

function goBack() {
  if (countdownToastId !== undefined) toast.remove(countdownToastId)
  if (window.history.length > 1) {
    router.back()
  } else {
    router.push('/')
  }
}

onMounted(async () => {
  await ensureLoaded()
  if (!hasValidTarget.value || !redirectEnabled.value) return

  countdown.value = redirectDelay.value

  countdownToastId = toast.show({
    type: 'info',
    title: `将在 ${countdown.value} 秒后自动前往目标网站`,
    duration: redirectDelay.value * 1000,
  })

  timer = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) {
      if (timer) clearInterval(timer)
      doRedirect()
    } else if (countdownToastId !== undefined) {
      toast.update(countdownToastId, {
        title: `将在 ${countdown.value} 秒后自动前往目标网站`,
      })
    }
  }, 1000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
  if (countdownToastId !== undefined) toast.remove(countdownToastId)
})
</script>

<template>
  <section class="go-redirect">
    <!-- Invalid or missing URL -->
    <div v-if="!hasValidTarget" class="go-redirect__inner">
      <div class="go-redirect__illustration">
        <UndrawIllustration name="access-denied" width="300" height="225" />
      </div>
      <h1 class="go-redirect__title">无效的链接</h1>
      <p class="go-redirect__desc">
        {{ rawTargetUrl ? '仅支持 HTTP 或 HTTPS 链接。' : '缺少前往地址。' }}
      </p>
      <div class="go-redirect__actions">
        <StButton tertiary size="large" @click="goBack">返回首页</StButton>
      </div>
    </div>

    <!-- Valid redirect confirmation -->
    <div v-else class="go-redirect__inner">
      <div class="go-redirect__illustration">
        <UndrawIllustration name="navigator" width="300" height="225" />
      </div>

      <h1 class="go-redirect__title">即将前往外部网站</h1>
      <p class="go-redirect__desc">您即将访问以下链接：</p>

      <div class="go-redirect__url">
        <AppIcon class="go-redirect__url-icon" name="external-link" :size="16" />
        <span :title="targetUrl">{{ displayUrl }}</span>
      </div>

      <div class="go-redirect__actions">
        <StButton type="primary" size="large" :disabled="isRedirecting" @click="doRedirect">
          {{ isRedirecting ? '正在前往...' : '继续前往' }}
        </StButton>
        <StButton tertiary size="large" @click="goBack">返回首页</StButton>
      </div>

      <p class="go-redirect__disclaimer">
        本站仅为用户提供信息参考，不对目标网站的内容、安全性、准确性及合法性作任何保证。请用户自行判断并承担相关风险。
      </p>
    </div>
  </section>
</template>

<style scoped>
.go-redirect {
  display: flex;
  min-height: 100vh;
  align-items: center;
  justify-content: center;
  padding: 32px 16px;
}

.go-redirect__inner {
  display: flex;
  flex: 1;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  max-width: 440px;
  text-align: center;
}

.go-redirect__illustration {
  width: 100%;
  max-width: 300px;
  margin-bottom: 24px;
}

.go-redirect__title {
  margin: 0 0 8px;
  font-size: 1.35rem;
  font-weight: 650;
  line-height: 1.4;
  color: var(--foreground);
}

.go-redirect__desc {
  margin: 0 0 20px;
  font-size: 13px;
  line-height: 1.625;
  color: var(--secondary);
}

.go-redirect__url {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  margin-bottom: 12px;
  padding: 12px 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius-xl);
  background-color: var(--muted);
  font-size: 13px;
  text-align: left;
  word-break: break-all;
  color: var(--foreground);
}

.go-redirect__url-icon {
  flex-shrink: 0;
  color: var(--secondary);
}

.go-redirect__actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 12px;
  width: 100%;
}

.go-redirect__disclaimer {
  margin-top: 24px;
  max-width: 380px;
  font-size: 12px;
  line-height: 1.625;
  color: var(--secondary);
  opacity: 0.75;
}
</style>
