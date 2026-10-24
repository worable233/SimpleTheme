import { createApp } from 'vue'
import { createHead } from '@unhead/vue/client'
import App from './App.vue'
import router from './router'
import { getThemeConfig } from '@/lib/theme-config'
import { rememberStaticSnapshot } from '@/lib/static-snapshot'
import '@/styles/tailwind.css'
import './styles/app.css'
import './styles/transitions.css'
import './styles/prose.css'
import './styles/blocks.css'
// ALTCHA Proof-of-Work CAPTCHA Web Component
// ALTCHA 必须锁在 2.x：后端 inc/rest/comments.php 按 v2 协议校验
// （widget 本地求解 sha256(salt + number) → payload 随评论一起提交）。
// v3 换成了服务端校验（verifyUrl + 新的 PoW 算法），直接升级会让验证码永远失败。
import 'altcha'

// Register Service Worker for full caching (production only)
if ('serviceWorker' in navigator && !import.meta.env.DEV) {
  window.addEventListener('load', () => {
    const homeUrl = getThemeConfig().homeUrl
    const serviceWorkerUrl = new URL('sw.js', homeUrl)
    const scope = new URL('./', homeUrl).pathname

    navigator.serviceWorker.register(serviceWorkerUrl.href, { scope }).catch(() => {
      // SW registration failed — app works without it
    })
  })
}

const app = createApp(App)

// Preserve server-rendered static content before Vue wipes #app on mount.
// If the REST API is unreachable at runtime (e.g. web.archive.org playback,
// or a backend outage), views fall back to this static HTML instead of an
// error illustration — keeping content visible & archivable.
const staticEl = document.getElementById('st-static')
if (staticEl && staticEl.innerHTML.trim()) {
  window.__ST_STATIC_HTML__ = staticEl.innerHTML
  window.__ST_STATIC_PATH__ = window.location.pathname
  // 再存一份到本地：下次这份文档里没有存档时（各种缓存场景）仍能兜底
  rememberStaticSnapshot(window.location.pathname, staticEl.innerHTML)
}

// ALTCHA is a native web component, not a Vue component
// (build-time counterpart lives in vite.config.ts → @vitejs/plugin-vue compilerOptions)
app.config.compilerOptions.isCustomElement = (tag: string) => tag.startsWith('altcha-')

app.use(createHead())
app.use(router)
app.mount('#app')
