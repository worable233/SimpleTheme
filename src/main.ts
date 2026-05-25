import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import '@knadh/oat/oat.min.css'
import './styles/app.css'
import './styles/prose-enhancements.css'
// Boxicons 图标库（项目已全面迁移至 Boxicons）
import 'boxicons/css/boxicons.min.css'
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
app.config.compilerOptions.isCustomElement = (tag: string) => tag.startsWith('altcha-')

app.use(router)
app.mount('#app')
