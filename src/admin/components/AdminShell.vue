<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import AdminSidebar from './AdminSidebar.vue'
import AdminTopbar from './AdminTopbar.vue'
import type { AdminMenuItem } from '../shell-entry'

const menuItems = ref<AdminMenuItem[]>([])
const currentUrl = ref('')
const siteName = ref('WordPress')
const userName = ref('')
const userAvatar = ref('')

// Parse admin page URL to get current page
function getCurrentUrl(): string {
  return window.location.pathname + window.location.search
}

// Check if a menu item matches current URL
function matchCurrentUrl(item: AdminMenuItem): boolean {
  const url = currentUrl.value
  if (item.url === url) return true
  // Handle query-string based URLs
  if (item.slug && url.includes(item.slug)) return true
  return false
}

// Build menu items from #adminmenu DOM
function buildMenuFromDom(): AdminMenuItem[] {
  const adminmenu = document.getElementById('adminmenu')
  if (!adminmenu) return []

  const items: AdminMenuItem[] = []
  const topLis = adminmenu.querySelectorAll(':scope > li.menu-top')

  topLis.forEach((li, index) => {
    const link = li.querySelector(':scope > a')
    if (!link) return

    const href = link.getAttribute('href') || ''
    const titleEl = link.querySelector('.wp-menu-name')
    const title = titleEl?.textContent?.trim() || link.textContent?.trim() || ''
    const iconEl = link.querySelector('.wp-menu-image')
    const icon = getMenuItemIcon(iconEl)
    const id = `menu-${index}`

    const children: { title: string; url: string }[] = []
    const subUl = li.querySelector(':scope > .wp-submenu')
    if (subUl) {
      subUl.querySelectorAll(':scope li a').forEach((subLink) => {
        const subHref = subLink.getAttribute('href') || ''
        const subTitle = subLink.textContent?.trim() || ''
        children.push({ title: subTitle, url: subHref })
      })
    }

    items.push({
      id,
      title,
      slug: href.split('?')[1] || href,
      url: href,
      icon,
      current: false,
      children,
    })
  })

  return items
}

function getMenuItemIcon(iconEl: Element | null): string {
  if (!iconEl) return ''
  // 1. Inline SVG (WP 5.7+)
  const svg = iconEl.querySelector('svg')
  if (svg) return svg.outerHTML

  // 2. <img> tag
  const img = iconEl.querySelector('img')
  if (img) return img.getAttribute('src') || ''

  // 3. dashicons class on the icon element itself
  // NOTE: className is like "wp-menu-image dashicons-before dashicons-dashboard".
  // regex: match dashicons-xxx but EXCLUDE "before" and "no-icon" placeholders.
  const classes = iconEl.className
  const dashiconMatch = classes.match(/\bdashicons-(?!before\b|no-icon\b)([a-z0-9-]+)/)
  if (dashiconMatch) return `dashicons-${dashiconMatch[1]}`

  // 4. Inline style background-image
  const inlineStyle = iconEl.getAttribute('style')
  if (inlineStyle) {
    const match = inlineStyle.match(/url\(['"]?([^'")\s]+)['"]?\)/)
    if (match) return match[1] || ''
  }

  // 5. Computed style background-image (catches CSS-class-based icons)
  try {
    const computedBg = window.getComputedStyle(iconEl).backgroundImage
    if (computedBg && computedBg !== 'none') {
      const match = computedBg.match(/url\(['"]?([^'")\s]+)['"]?\)/)
      if (match) return match[1] || ''
    }
  } catch (_) { /* noop */ }

  return ''
}

// Navigate to a new URL
function navigate(url: string) {
  window.location.href = url
}

function handleClick(e: MouseEvent) {
  // Handle clicks on admin-shell area to close dropdowns
  const target = e.target as HTMLElement
  if (!target.closest('.admin-topbar__dropdown') && !target.closest('.admin-topbar__user-btn')) {
    // Close user dropdown via click outside
  }
}

onMounted(() => {
  currentUrl.value = getCurrentUrl()
  menuItems.value = buildMenuFromDom()

  // Get user info
  const userDisplay = document.querySelector('#wp-admin-bar-my-account .display-name')
  const avatarImg = document.querySelector('#wp-admin-bar-my-account .avatar')
  const siteNameEl = document.querySelector('#wp-admin-bar-site-name .ab-item')

  if (userDisplay) userName.value = userDisplay.textContent?.trim() || ''
  if (avatarImg) userAvatar.value = (avatarImg as HTMLImageElement).src || ''
  if (siteNameEl) siteName.value = siteNameEl.textContent?.trim() || ''

  // Mark current item
  menuItems.value.forEach((item) => {
    item.current = matchCurrentUrl(item)
  })

  document.addEventListener('click', handleClick)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClick)
})
</script>

<template>
  <div class="admin-shell">
    <AdminSidebar
      :menu-items="menuItems"
      :current-url="currentUrl"
      @navigate="navigate"
    />
    <AdminTopbar
      :site-name="siteName"
      :user-name="userName"
      :user-avatar="userAvatar"
    />
    <!-- Spacer for fixed elements -->
    <div class="admin-shell__spacer"></div>
  </div>
</template>

<style scoped>
.admin-shell {
  display: contents;
}

.admin-shell__spacer {
  display: none;
}
</style>
