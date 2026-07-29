/**
 * 内联插画 SVG 表 — 构建时通过 Vite 的 import.meta.glob 将 SVG 文件导入为字符串
 *
 * 插画由 bin/copy-illustrations.mjs 同步到 src/assets/illustrations/（内联）
 * 和 public/illustrations/（静态产物，供 PHP 端点读取）。
 * 文件名（不含 .svg 扩展名）即为插画名。
 */
const svgModules = import.meta.glob<string>('@/assets/illustrations/*.svg', {
  query: '?raw',
  import: 'default',
  eager: true,
})

/**
 * key = 文件名（不含 .svg），value = SVG 内容
 */
const illustrationCache = new Map<string, string>()

for (const [filePath, svgContent] of Object.entries(svgModules)) {
  // filePath 如 /src/assets/illustrations/blank-canvas.svg
  const name = filePath.split('/').pop()?.replace(/\.svg$/i, '')
  if (name && svgContent) {
    illustrationCache.set(name, svgContent)
  }
}

export function getIllustration(name: string): string | undefined {
  return illustrationCache.get(name)
}

export function hasIllustration(name: string): boolean {
  return illustrationCache.has(name)
}

export function getAllIllustrationNames(): string[] {
  return Array.from(illustrationCache.keys())
}
