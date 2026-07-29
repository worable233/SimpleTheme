import { fileURLToPath, URL } from 'node:url'
import { defineConfig, type Plugin } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import tailwindcss from '@tailwindcss/vite'
import { VitePWA } from 'vite-plugin-pwa'

const WP_URL = process.env.WP_URL || 'http://localhost'

// SPA fallback: redirect unmatched paths to the app's base URL so
// client-side routes like /shuoshuo work on page refresh in dev mode.
// (Must be a plugin — `configureServer` is a plugin hook, not a server option.)
function spaFallback(): Plugin {
  return {
    name: 'simple-theme:spa-fallback',
    configureServer(server) {
      // Pre-middleware: rewrite before Vite's static file handler runs.
      server.middlewares.use((req, _res, next) => {
        const url = req.url ?? ''
        // Let Vite internals, API, and uploads pass through.
        if (
          url.startsWith('/@') ||
          url.startsWith('/wp-json') ||
          url.startsWith('/wp-content/uploads') ||
          url.startsWith('/node_modules') ||
          url.startsWith('/src/') ||
          url === '/favicon.ico'
        ) {
          return next()
        }
        // For any path that is not under the base, serve the SPA entry.
        if (!url.startsWith('/wp-content/themes/simple-theme/dist/')) {
          req.url = '/wp-content/themes/simple-theme/dist/index.html'
        }
        next()
      })
    },
  }
}

export default defineConfig(({ command }) => ({
  base: command === 'serve'
    ? '/wp-content/themes/simple-theme/dist/'
    : './',
  plugins: [
    vue({
      template: {
        compilerOptions: {
          // ALTCHA is a native web component, not a Vue component
          isCustomElement: (tag) => tag.startsWith('altcha-'),
        },
      },
    }),
    vueDevTools(),
    tailwindcss(),
    spaFallback(),
    VitePWA({
      strategies: 'generateSW',
      registerType: 'autoUpdate',
      workbox: {
        // Don't precache — cache only what the current UI actually loads
        globPatterns: [],
        navigateFallback: null,
        runtimeCaching: [
          {
            // Theme built assets (hashed URLs = naturally versioned)
            urlPattern: /\/wp-content\/themes\/simple-theme\/dist\//,
            handler: 'CacheFirst',
            options: {
              cacheName: 'st-assets',
              expiration: { maxEntries: 50, maxAgeSeconds: 30 * 24 * 60 * 60 },
            },
          },
          {
            // Theme REST API — NetworkFirst (fresh when online, cache when offline)
            urlPattern: /\/wp-json\/simple-theme\/v1\//,
            handler: 'NetworkFirst',
            options: {
              cacheName: 'st-api',
              expiration: { maxEntries: 50, maxAgeSeconds: 7 * 24 * 60 * 60 },
              networkTimeoutSeconds: 4,
            },
          },
          {
            // Uploaded images — CacheFirst
            urlPattern: /\/wp-content\/uploads\//,
            handler: 'CacheFirst',
            options: {
              cacheName: 'st-uploads',
              expiration: { maxEntries: 100, maxAgeSeconds: 30 * 24 * 60 * 60 },
            },
          },
          {
            // Emoji images — CacheFirst
            // Note: path varies; match both dev (/emojis/) and prod (/wp-content/.../emojis/)
            urlPattern: /\/(?:wp-content\/[^/]+\/)?emojis\//,
            handler: 'CacheFirst',
            options: {
              cacheName: 'st-emojis',
              expiration: { maxEntries: 100, maxAgeSeconds: 30 * 24 * 60 * 60 },
            },
          },
        ],
      },
    }),
  ],
  define: {
    '__BUILD_TIME__': JSON.stringify(new Date().toISOString()),
  },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    port: 5173,
    proxy: {
      '/wp-json': {
        target: WP_URL,
        changeOrigin: true,
      },
      '/wp-content/uploads': {
        target: WP_URL,
        changeOrigin: true,
      },
    },
  },
  build: {
    // 生成 manifest 交给 WordPress 读取实际产物文件名。
    outDir: 'dist',
    manifest: true,
    rollupOptions: {
      input: {
        frontend: 'src/main.ts',
        admin: 'src/admin/main.ts',
        'admin-shell': 'src/admin/shell-entry.ts',
      },
      output: {
        manualChunks(id: string) {
          if (id.includes('@fancyapps/ui')) return 'fancyapps-ui'
        },
      },
    },
    minify: 'esbuild',
    // esbuild 内置于 Vite，不需要额外依赖
  },
}))
