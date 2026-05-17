import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import '@knadh/oat/oat.min.css'
import './styles/app.css'
import './styles/prose-enhancements.css'
// Boxicons 图标库由 WordPress 后端根据设置决定是否加载
// 如果需要在 Vite 开发环境使用，请在 .env 中设置 VITE_ENABLE_BOXICONS=true
if (import.meta.env.VITE_ENABLE_BOXICONS === 'true') {
  import('boxicons/css/boxicons.min.css')
}

const app = createApp(App)

app.use(router)
app.mount('#app')
