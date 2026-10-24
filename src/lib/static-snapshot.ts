/**
 * static-snapshot — 把最近一次「服务端预渲染的静态 HTML」留在本地。
 *
 * 为什么需要额外存一份：静态兜底原本只依赖 document 里的 #st-static，
 * 但只要这次的文档不是我们直出的那份（例如从各种缓存里拿到一份不含存档的
 * 外壳、或浏览器缓存了别的响应），#st-static 就是空的，兜底立刻失效 ——
 * 用户看到的就是「首页加载失败」+ 一条声称"已切换到本地存档"却什么都没有的提示。
 *
 * 这里在每次成功拿到存档时按路径存一份到 localStorage，后续同一路径再次访问
 * （哪怕是离线）就还能拿到上次的内容。
 */
const SNAPSHOT_KEY = 'st_static_snapshot_v1'
/** 太短的多半是残缺内容，不值得存 */
const MIN_LENGTH = 200

interface StaticSnapshot {
  path: string
  html: string
  savedAt: number
}

function read(): StaticSnapshot | null {
  try {
    const raw = localStorage.getItem(SNAPSHOT_KEY)
    if (!raw) return null
    const parsed = JSON.parse(raw) as Partial<StaticSnapshot>
    if (typeof parsed?.path !== 'string' || typeof parsed.html !== 'string') return null
    return { path: parsed.path, html: parsed.html, savedAt: parsed.savedAt ?? 0 }
  } catch {
    return null
  }
}

/** 启动时调用：把本次直出的静态存档记下来（页面路由变化不影响这份快照） */
export function rememberStaticSnapshot(path: string, html: string) {
  if (!html || html.length < MIN_LENGTH) return
  try {
    localStorage.setItem(
      SNAPSHOT_KEY,
      JSON.stringify({ path, html, savedAt: Date.now() } satisfies StaticSnapshot),
    )
  } catch {
    // 隐私模式 / 配额满：兜底有 document 里的 #st-static 就够了
  }
}

/** 取该路径上一次保存的存档；路径不同就不给，避免把别的页面内容当成本页 */
export function readStaticSnapshot(path: string): string {
  const snapshot = read()
  return snapshot && snapshot.path === path ? snapshot.html : ''
}
