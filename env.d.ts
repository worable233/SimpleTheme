/// <reference types="vite/client" />

import type { DefineComponent } from 'vue'
import type { SimpleThemeConfig } from './src/types/wordpress'

declare module '*.vue' {
  const component: DefineComponent<Record<string, never>, Record<string, never>, unknown>
  export default component
}

declare module '*.js' {
  const value: unknown
  export default value
}

declare module '*.svg?raw' {
  const content: string
  export default content
}

declare global {
  interface Window {
    SimpleThemeConfig?: SimpleThemeConfig
    Prism: typeof import('prismjs')
    /** Server-rendered static content (from #st-static), preserved before Vue mount. */
    __ST_STATIC_HTML__?: string
    /** location.pathname at boot — the URL the static content was rendered for. */
    __ST_STATIC_PATH__?: string
  }
  /** Prism syntax highlighter — loaded as a regular <script> by WordPress. */
  const Prism: typeof import('prismjs')
}

export {}
