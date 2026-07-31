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

// ALTCHA is a native web component, not a Vue component
// (build-time counterpart lives in vite.config.ts → @vitejs/plugin-vue compilerOptions)
app.config.compilerOptions.isCustomElement = (tag: string) => tag.startsWith('altcha-')

app.use(createHead())
app.use(router)
app.mount('#app')
