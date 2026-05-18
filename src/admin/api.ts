import type { SimpleThemeConfig } from '@/types/wordpress'

export interface AdminSettings {
  [key: string]: unknown
}

declare global {
  interface Window {
    SimpleThemeConfig?: SimpleThemeConfig
  }
}

const config = window.SimpleThemeConfig
const API_URL = config?.routes?.settings || '/wp-json/simple-theme/v1/settings'
const REST_NONCE = config?.restNonce || ''

function getHeaders(): Record<string, string> {
  const headers: Record<string, string> = {
    'X-Requested-With': 'XMLHttpRequest',
  }
  if (REST_NONCE) {
    headers['X-WP-Nonce'] = REST_NONCE
  }
  return headers
}

export async function fetchSettings(): Promise<{ settings: AdminSettings; defaults: AdminSettings }> {
  const res = await fetch(API_URL, {
    credentials: 'same-origin',
    headers: getHeaders(),
  })
  if (!res.ok) throw new Error(`加载设置失败: ${res.status}`)
  return res.json()
}

export async function saveSettings(settings: AdminSettings): Promise<AdminSettings> {
  const res = await fetch(API_URL, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      ...getHeaders(),
    },
    body: JSON.stringify(settings),
  })
  if (!res.ok) throw new Error(`保存设置失败: ${res.status}`)
  return res.json()
}
