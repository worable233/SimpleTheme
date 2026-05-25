/**
 * Admin Shell Entry Point
 *
 * Mounts a lightweight Vue 3 app that renders the admin sidebar and topbar,
 * replacing WordPress's native #adminmenu and #wpadminbar.
 *
 * Menu data is parsed from the existing #adminmenu DOM structure,
 * so all plugin-added menu items work without custom REST endpoints.
 */

import { createApp } from 'vue'
import 'boxicons/css/boxicons.min.css'
import '@knadh/oat/oat.min.css'
import AdminShell from './components/AdminShell.vue'

export interface AdminMenuItem {
  id: string
  title: string
  slug: string
  url: string
  icon: string
  current: boolean
  children: { title: string; url: string }[]
}

// Create mount point
const container = document.createElement('div')
container.id = 'simple-theme-admin-shell'
container.style.display = 'contents'
document.body.appendChild(container)

const app = createApp(AdminShell)
app.mount('#simple-theme-admin-shell')
