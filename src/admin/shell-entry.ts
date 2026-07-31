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
import './styles/admin-tailwind.css'
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

// Find #wpwrap — insert Teleport targets and mount point before it
const wpwrap = document.getElementById('wpwrap')

// Sidebar Teleport target (first in body, before wpwrap)
const sidebarTarget = document.createElement('div')
sidebarTarget.id = 'simple-theme-admin-sidebar'
document.body.insertBefore(sidebarTarget, wpwrap)

// Topbar Teleport target (second, after sidebar, before wpwrap)
const topbarTarget = document.createElement('div')
topbarTarget.id = 'simple-theme-admin-topbar'
document.body.insertBefore(topbarTarget, wpwrap)

// Vue mount point (third, after topbar, before wpwrap)
const container = document.createElement('div')
container.id = 'simple-theme-admin-shell'
container.style.display = 'contents'
document.body.insertBefore(container, wpwrap)

const app = createApp(AdminShell)
app.mount('#simple-theme-admin-shell')
