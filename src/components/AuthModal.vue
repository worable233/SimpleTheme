<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import {
  apiLogin,
  apiRegister,
  apiLostPassword,
  apiValidateResetKey,
  apiResetPassword,
} from '@/lib/api-auth'
import { useAuth } from '@/composables/useAuth'
import { useBodyScrollLock } from '@/composables/useBodyScrollLock'
import { getThemeConfig } from '@/lib/theme-config'
import ModalCloseButton from '@/components/ModalCloseButton.vue'

const emit = defineEmits<{ close: [] }>()

const { setLoggedIn } = useAuth()
const { lockBodyScroll, unlockBodyScroll } = useBodyScrollLock()

/** 从 REST 错误响应中提取可读消息 */
function apiErrorMessage(e: unknown): string | undefined {
  return (e as { response?: { data?: { message?: string } } })?.response?.data?.message
}

// 弹窗当前显示的标签页
type AuthTab = 'login' | 'register' | 'lostpassword' | 'resetpassword' | 'message'
const activeTab = ref<AuthTab>('login')
const tabTitle = computed(() => {
  const map: Record<AuthTab, string> = {
    login: '登录',
    register: '注册',
    lostpassword: '找回密码',
    resetpassword: '重置密码',
    message: '',
  }
  return map[activeTab.value]
})

const canRegister = computed(() => {
  return getThemeConfig()?.features?.registration !== false
})

// 表单数据
const log = ref('')
const pwd = ref('')
const rememberme = ref(true)
const regUser = ref('')
const regEmail = ref('')
const lostUser = ref('')
const resetPass1 = ref('')
const resetPass2 = ref('')

// 状态
const loading = ref(false)
const errorMsg = ref('')
const successMsg = ref('')

// 密码重置 — 从 URL 参数读取 key 和 login
const route = useRoute()
const resetKey = ref('')
const resetLogin = ref('')

onMounted(() => {
  // 检查 URL 中是否有重置密码参数
  if (route.query.action === 'resetpass' && route.query.key && route.query.login) {
    resetKey.value = route.query.key as string
    resetLogin.value = route.query.login as string
    activeTab.value = 'resetpassword'
    // 验证密钥
    validateResetKey()
    // 清除 URL 参数
    const url = new URL(window.location.href)
    url.searchParams.delete('action')
    url.searchParams.delete('key')
    url.searchParams.delete('login')
    window.history.replaceState({}, '', url.toString())
  }

  // 检查是否有 checkemail 状态
  if (route.query.checkemail) {
    if (route.query.checkemail === 'confirm') {
      activeTab.value = 'message'
      successMsg.value = '密码重置邮件已发送，请检查您的邮箱中的确认链接。'
    } else if (route.query.checkemail === 'registered') {
      activeTab.value = 'message'
      successMsg.value = '注册成功！请检查您的邮箱中的确认邮件，然后登录。'
    }
  }

  // 检查 loggedout 状态
  if (route.query.loggedout) {
    activeTab.value = 'message'
    successMsg.value = '您已成功退出登录。'
  }
})

// 验证重置密钥
async function validateResetKey() {
  loading.value = true
  errorMsg.value = ''
  try {
    const result = await apiValidateResetKey(resetKey.value, resetLogin.value)
    if (!result.success) {
      errorMsg.value = result.message || '重置链接无效或已过期'
      activeTab.value = 'lostpassword'
    }
  } catch (e) {
    errorMsg.value = apiErrorMessage(e) || '验证失败，请重新请求重置链接'
    activeTab.value = 'lostpassword'
  } finally {
    loading.value = false
  }
}

// 登录
async function handleLogin() {
  if (!log.value || !pwd.value) {
    errorMsg.value = '请输入用户名和密码'
    return
  }
  loading.value = true
  errorMsg.value = ''
  successMsg.value = ''
  try {
    const result = await apiLogin(log.value, pwd.value, rememberme.value)
    if (result.success && result.user && result.rest_nonce) {
      setLoggedIn(
        {
          id: result.user.id,
          displayName: result.user.display_name,
          avatar: result.user.avatar,
          email: result.user.email,
          url: result.user.url,
        },
        result.rest_nonce,
        result.redirect_to || '/wp-admin/',
        result.logout_url,
      )
      emit('close')
    } else {
      errorMsg.value = result.message || '登录失败'
    }
  } catch (e) {
    errorMsg.value = apiErrorMessage(e) || '网络错误，请稍后重试'
  } finally {
    loading.value = false
  }
}

// 注册
async function handleRegister() {
  if (!regUser.value || !regEmail.value) {
    errorMsg.value = '请填写用户名和邮箱'
    return
  }
  loading.value = true
  errorMsg.value = ''
  successMsg.value = ''
  try {
    const result = await apiRegister(regUser.value, regEmail.value)
    if (result.success) {
      activeTab.value = 'message'
      successMsg.value = result.message || '注册成功！请检查您的邮箱。'
    } else {
      errorMsg.value = result.message || '注册失败'
    }
  } catch (e) {
    errorMsg.value = apiErrorMessage(e) || '网络错误，请稍后重试'
  } finally {
    loading.value = false
  }
}

// 找回密码
async function handleLostPassword() {
  if (!lostUser.value) {
    errorMsg.value = '请输入用户名或邮箱地址'
    return
  }
  loading.value = true
  errorMsg.value = ''
  successMsg.value = ''
  try {
    const result = await apiLostPassword(lostUser.value)
    if (result.success) {
      activeTab.value = 'message'
      successMsg.value = result.message || '密码重置邮件已发送，请检查您的邮箱。'
    } else {
      errorMsg.value = result.message || '请求失败'
    }
  } catch (e) {
    errorMsg.value = apiErrorMessage(e) || '网络错误，请稍后重试'
  } finally {
    loading.value = false
  }
}

// 重置密码
async function handleResetPassword() {
  if (!resetPass1.value || !resetPass2.value) {
    errorMsg.value = '请填写新密码'
    return
  }
  if (resetPass1.value !== resetPass2.value) {
    errorMsg.value = '两次输入的密码不一致'
    return
  }
  if (resetPass1.value.length < 6) {
    errorMsg.value = '密码长度至少为 6 个字符'
    return
  }
  loading.value = true
  errorMsg.value = ''
  successMsg.value = ''
  try {
    const result = await apiResetPassword(
      resetKey.value,
      resetLogin.value,
      resetPass1.value,
      resetPass2.value,
    )
    if (result.success) {
      activeTab.value = 'message'
      successMsg.value = result.message || '密码已重置，请使用新密码登录。'
    } else {
      errorMsg.value = result.message || '密码重置失败'
    }
  } catch (e) {
    errorMsg.value = apiErrorMessage(e) || '网络错误，请稍后重试'
  } finally {
    loading.value = false
  }
}

// 切换标签
function switchTo(tab: AuthTab) {
  activeTab.value = tab
  errorMsg.value = ''
  successMsg.value = ''
}

// ESC 关闭
function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') emit('close')
}

onMounted(() => {
  document.addEventListener('keydown', onKeydown)
  lockBodyScroll()
})

onUnmounted(() => {
  unlockBodyScroll()
  document.removeEventListener('keydown', onKeydown)
})

/* 表单共享 utilities（同模板内多处复用） */
const fieldLabel = 'mb-1.5 block text-[13px] font-medium text-foreground'
const fieldInput =
  'w-full rounded-medium border border-input bg-card px-3.5 py-2.5 text-sm leading-normal text-foreground transition-[border-color,box-shadow] duration-150 focus:border-primary focus:shadow-[0_0_0_3px_color-mix(in_srgb,var(--primary)_20%,transparent)] focus:outline-none'
const primaryBtn =
  'w-full cursor-pointer rounded-medium border-none bg-primary px-6 py-2.5 text-sm leading-normal font-medium text-primary-foreground transition-all duration-150 hover:enabled:-translate-y-px hover:enabled:opacity-90 disabled:cursor-not-allowed disabled:opacity-50'
const formLinks = 'mt-4 flex justify-between text-[13px]'
const formLink =
  'text-primary no-underline transition-opacity duration-150 hover:underline hover:opacity-70'
const formClass = 'px-6 pt-5 pb-6 max-[480px]:p-4'
</script>

<template>
  <Teleport to="body">
    <div
      class="auth-modal__backdrop fixed inset-0 z-[10000] flex items-center justify-center bg-black/50 p-4"
      @click.self="$emit('close')"
    >
      <div
        class="auth-modal__container max-h-[90vh] w-full max-w-[420px] overflow-y-auto rounded-large bg-card shadow-large max-[480px]:max-w-full max-[480px]:rounded-medium"
        @click.stop
      >
        <!-- 标题 -->
        <div class="flex items-center justify-between px-6 pt-5 max-[480px]:px-4 max-[480px]:pt-4">
          <h3 class="m-0 text-xl font-[650] tracking-[-0.3px] text-foreground">{{ tabTitle }}</h3>
          <ModalCloseButton @click="$emit('close')" />
        </div>

        <!-- 错误 / 成功消息 -->
        <div
          v-if="errorMsg"
          class="mx-6 mt-4 rounded-medium bg-danger/[0.08] px-3.5 py-2.5 text-[13px] leading-normal text-danger max-[480px]:mx-4 max-[480px]:mt-3"
        >
          {{ errorMsg }}
        </div>
        <div
          v-if="successMsg"
          class="mx-6 mt-4 rounded-medium bg-success/[0.08] px-3.5 py-2.5 text-[13px] leading-normal text-success max-[480px]:mx-4 max-[480px]:mt-3"
        >
          {{ successMsg }}
        </div>

        <!-- 加载中 -->
        <div
          v-if="loading && activeTab === 'resetpassword' && !errorMsg"
          class="px-6 py-10 text-center text-muted-foreground"
        >
          <div class="auth-modal__spinner"></div>
          <p>正在验证...</p>
        </div>

        <!-- ===== 消息页面（注册成功/发送邮件成功） ===== -->
        <div v-if="activeTab === 'message'" class="px-6 py-10 text-center">
          <div class="mb-4 text-primary">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              width="48"
              height="48"
            >
              <path d="M22 2L11 13" />
              <path d="M22 2L15 22L11 13L2 9L22 2Z" />
            </svg>
          </div>
          <p class="m-0 mb-6 text-sm leading-[1.6] text-foreground">{{ successMsg }}</p>
          <button :class="primaryBtn" @click="$emit('close')">知道了</button>
        </div>

        <!-- ===== 登录表单 ===== -->
        <form v-if="activeTab === 'login'" @submit.prevent="handleLogin" :class="formClass">
          <div class="mb-4">
            <label for="auth-log" :class="fieldLabel">用户名或邮箱</label>
            <input
              id="auth-log"
              v-model="log"
              type="text"
              autocomplete="username"
              placeholder="输入用户名或邮箱"
              required
              :class="fieldInput"
            />
          </div>
          <div class="mb-4">
            <label for="auth-pwd" :class="fieldLabel">密码</label>
            <input
              id="auth-pwd"
              v-model="pwd"
              type="password"
              autocomplete="current-password"
              placeholder="输入密码"
              required
              :class="fieldInput"
            />
          </div>
          <div class="mb-4">
            <label
              class="flex cursor-pointer items-center gap-2 text-[13px] font-normal text-foreground"
            >
              <input v-model="rememberme" type="checkbox" class="h-4 w-4 accent-primary" />
              <span>记住我</span>
            </label>
          </div>
          <button type="submit" :class="primaryBtn" :disabled="loading">
            {{ loading ? '登录中...' : '登录' }}
          </button>
          <div :class="formLinks">
            <a href="#" :class="formLink" @click.prevent="switchTo('lostpassword')">忘记密码？</a>
            <a v-if="canRegister" href="#" :class="formLink" @click.prevent="switchTo('register')"
              >注册账号</a
            >
          </div>
        </form>

        <!-- ===== 注册表单 ===== -->
        <form v-if="activeTab === 'register'" @submit.prevent="handleRegister" :class="formClass">
          <div class="mb-4">
            <label for="auth-reg-user" :class="fieldLabel">用户名</label>
            <input
              id="auth-reg-user"
              v-model="regUser"
              type="text"
              autocomplete="username"
              placeholder="输入用户名"
              required
              :class="fieldInput"
            />
          </div>
          <div class="mb-4">
            <label for="auth-reg-email" :class="fieldLabel">邮箱</label>
            <input
              id="auth-reg-email"
              v-model="regEmail"
              type="email"
              autocomplete="email"
              placeholder="输入邮箱"
              required
              :class="fieldInput"
            />
          </div>
          <button type="submit" :class="primaryBtn" :disabled="loading">
            {{ loading ? '注册中...' : '注册' }}
          </button>
          <div :class="formLinks">
            <span class="text-muted-foreground">已有账号？</span>
            <a href="#" :class="formLink" @click.prevent="switchTo('login')">登录</a>
          </div>
        </form>

        <!-- ===== 找回密码表单 ===== -->
        <form
          v-if="activeTab === 'lostpassword'"
          @submit.prevent="handleLostPassword"
          :class="formClass"
        >
          <p class="m-0 mb-4 text-[13px] leading-normal text-muted-foreground">
            输入您的用户名或邮箱地址，我们将向您发送重置密码的链接。
          </p>
          <div class="mb-4">
            <label for="auth-lost-user" :class="fieldLabel">用户名或邮箱</label>
            <input
              id="auth-lost-user"
              v-model="lostUser"
              type="text"
              autocomplete="username"
              placeholder="输入用户名或邮箱"
              required
              :class="fieldInput"
            />
          </div>
          <button type="submit" :class="primaryBtn" :disabled="loading">
            {{ loading ? '发送中...' : '发送重置邮件' }}
          </button>
          <div :class="formLinks">
            <a href="#" :class="formLink" @click.prevent="switchTo('login')">返回登录</a>
          </div>
        </form>

        <!-- ===== 重置密码表单 ===== -->
        <form
          v-if="activeTab === 'resetpassword'"
          @submit.prevent="handleResetPassword"
          :class="formClass"
        >
          <p class="m-0 mb-4 text-[13px] leading-normal text-muted-foreground">
            请设置您的新密码。
          </p>
          <div class="mb-4">
            <label for="auth-reset-pass1" :class="fieldLabel">新密码</label>
            <input
              id="auth-reset-pass1"
              v-model="resetPass1"
              type="password"
              autocomplete="new-password"
              placeholder="输入新密码"
              required
              minlength="6"
              :class="fieldInput"
            />
          </div>
          <div class="mb-4">
            <label for="auth-reset-pass2" :class="fieldLabel">确认新密码</label>
            <input
              id="auth-reset-pass2"
              v-model="resetPass2"
              type="password"
              autocomplete="new-password"
              placeholder="再次输入新密码"
              required
              minlength="6"
              :class="fieldInput"
            />
          </div>
          <button type="submit" :class="primaryBtn" :disabled="loading">
            {{ loading ? '重置中...' : '重置密码' }}
          </button>
          <div :class="formLinks">
            <a href="#" :class="formLink" @click.prevent="switchTo('login')">返回登录</a>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
/* Entry animations + spinner (keyframes stay in CSS — utility exception) */
.auth-modal__backdrop {
  animation: auth-fade-in 0.2s ease;
}

@keyframes auth-fade-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.auth-modal__container {
  animation: auth-slide-up 0.25s ease;
}

@keyframes auth-slide-up {
  from {
    transform: translateY(20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.auth-modal__spinner {
  width: 32px;
  height: 32px;
  border: 3px solid var(--border);
  border-top-color: var(--primary);
  border-radius: 50%;
  animation: auth-spin 0.7s linear infinite;
  margin: 0 auto 12px;
}

@keyframes auth-spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
