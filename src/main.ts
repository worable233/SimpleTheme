import { createApp } from 'vue'
import { createHead } from '@vueuse/head'
import App from './App.vue'
import router from './router'
import '@/styles/tailwind.css'
import './styles/app.css'
import './styles/prose.css'
// ALTCHA Proof-of-Work CAPTCHA Web Component
import 'altcha'

// Register Service Worker for full caching (production only)
if ('serviceWorker' in navigator && !import.meta.env.DEV) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {
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
}

// ALTCHA is a native web component, not a Vue component
// (build-time counterpart lives in vite.config.ts → @vitejs/plugin-vue compilerOptions)
app.config.compilerOptions.isCustomElement = (tag: string) => tag.startsWith('altcha-')

app.use(createHead())
app.use(router)
app.mount('#app')
