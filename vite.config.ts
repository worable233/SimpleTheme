import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// eslint-disable-next-line n/no-process-env
const WP_URL = process.env.WP_URL || 'http://localhost'

export default defineConfig({
  plugins: [vue(), vueDevTools()],
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
      },
      output: {
        manualChunks(id: string) {
          if (id.includes('@fancyapps/ui')) return 'fancyapps-ui'
          if (id.includes('prismjs')) return 'prismjs'
        },
      },
    },
    minify: 'esbuild',
    // esbuild 内置于 Vite，不需要额外依赖
  },
})
