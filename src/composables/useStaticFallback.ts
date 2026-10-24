import { computed } from 'vue'
import { useRoute } from 'vue-router'

/**
 * useStaticFallback — 运行时数据不可用时的静态内容兜底。
 *
 * 服务端已在 #st-static 里输出了完整的静态 HTML（文章列表 / 正文 / 归档等），
 * main.ts 在 Vue 挂载前把它连同当时的 pathname 一起保存。当某个视图的初始数据
 * 加载失败时（典型场景：web.archive.org 回放、后端宕机、离线），若用户仍停留在
 * 服务端渲染那份内容的原始 URL 上，就用这份静态 HTML 兜底，而不是显示报错插画。
 *
 * 通过 pathname 比对确保只在「首屏原始 URL」生效；任何前端路由跳转后 pathname
 * 不再匹配，兜底自动失效，避免展示过期内容。
 */
export function useStaticFallback() {
  const route = useRoute()

  const staticFallbackHtml = computed(() => {
    if (typeof window === 'undefined') return ''
    const html = window.__ST_STATIC_HTML__
    const path = window.__ST_STATIC_PATH__
    if (!html || path == null) return ''
    // 读取 route.fullPath 让该 computed 在前端路由跳转后重新求值
    // （location.pathname 随 vue-router 同步更新）
    const stillOnBootUrl = route.fullPath !== undefined && window.location.pathname === path
    return stillOnBootUrl ? html : ''
  })

  return { staticFallbackHtml }
}
