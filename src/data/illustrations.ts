/**
 * 内联插画 SVG 表 — 构建时通过 Vite 的 import.meta.glob 将 SVG 文件导入为字符串
 *
 * 所有插画 SVG 文件必须放在 public/illustrations/ 目录下。
 * 文件名（不含 .svg 扩展名）即为插画名。
 */
const svgModules = import.meta.glob<string>('/public/illustrations/*.svg', {
  query: '?raw',
  import: 'default',
  eager: true,
})

/**
 * key = 文件名（不含 .svg），value = SVG 内容
 */
const illustrationCache = new Map<string, string>()

for (const [filePath, svgContent] of Object.entries(svgModules)) {
  // filePath 如 /public/illustrations/blank-canvas.svg
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
