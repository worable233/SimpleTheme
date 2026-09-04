import type { Component } from 'vue'
import { getRouterBase } from '@/lib/theme-config'

type SpecialPagePath = '/shuoshuo' | '/about' | '/archives' | '/links'
type SpecialPageModule = { default: Component }

const loaders: Record<SpecialPagePath, () => Promise<SpecialPageModule>> = {
  '/shuoshuo': () => import('@/views/ShuoshuoView.vue'),
  '/about': () => import('@/views/AboutView.vue'),
  '/archives': () => import('@/views/ArchivesView.vue'),
  '/links': () => import('@/views/LinksView.vue'),
}

const loadedPages: Partial<Record<SpecialPagePath, Component>> = {}
const loadingPages: Partial<Record<SpecialPagePath, Promise<Component>>> = {}

function normalizePath(path: string) {
  const base = getRouterBase()
  const withoutBase = base !== '/' && path.startsWith(base) ? path.slice(base.length - 1) : path
  return withoutBase.length > 1 ? withoutBase.replace(/\/+$/, '') : withoutBase
}

function asSpecialPagePath(path: string): SpecialPagePath | null {
  const normalizedPath = normalizePath(path)
  return normalizedPath in loaders ? (normalizedPath as SpecialPagePath) : null
}

export async function preloadSpecialPage(path: string) {
  const specialPath = asSpecialPagePath(path)
  if (!specialPath) return
  if (loadedPages[specialPath]) return

  const pending =
    loadingPages[specialPath] ||
    (loadingPages[specialPath] = loaders[specialPath]().then((module) => {
      loadedPages[specialPath] = module.default
      return module.default
    }))
  await pending
}

export function getPreloadedSpecialPage(path: string): Component | null {
  const specialPath = asSpecialPagePath(path)
  return specialPath ? (loadedPages[specialPath] ?? null) : null
}
