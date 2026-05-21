import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import '@knadh/oat/oat.min.css'
import './styles/app.css'
import './styles/prose-enhancements.css'
// Boxicons 图标库（项目已全面迁移至 Boxicons）
import 'boxicons/css/boxicons.min.css'

const app = createApp(App)

app.use(router)
app.mount('#app')
