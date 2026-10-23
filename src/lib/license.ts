/**
 * 文章许可协议 —— 后台「页脚与备案 → 文章许可」取值到展示文案的映射。
 *
 * 与 inc/core/helpers.php 的 simple_theme_get_article_license() 保持一致：
 * 新增协议时两处都要改（PHP 侧供静态直出 HTML 使用）。
 */

export interface ArticleLicenseInfo {
  /** 后台存储的原始取值，如 'cc-by-nc-sa-40' */
  key: string
  /** 展示名，如 'CC BY-NC-SA 4.0' */
  label: string
  /** 协议原文地址；空字符串表示无公开协议页面（ARR） */
  url: string
  /** 文章页底部的完整说明句 */
  statement: string
}

const LICENSES: Record<string, { label: string; url: string }> = {
  'cc-by-40': { label: 'CC BY 4.0', url: 'https://creativecommons.org/licenses/by/4.0/' },
  'cc-by-sa-40': {
    label: 'CC BY-SA 4.0',
    url: 'https://creativecommons.org/licenses/by-sa/4.0/',
  },
  'cc-by-nc-40': { label: 'CC BY-NC 4.0', url: 'https://creativecommons.org/licenses/by-nc/4.0/' },
  'cc-by-nc-sa-40': {
    label: 'CC BY-NC-SA 4.0',
    url: 'https://creativecommons.org/licenses/by-nc-sa/4.0/',
  },
  'cc-by-nd-40': { label: 'CC BY-ND 4.0', url: 'https://creativecommons.org/licenses/by-nd/4.0/' },
  'cc-by-nc-nd-40': {
    label: 'CC BY-NC-ND 4.0',
    url: 'https://creativecommons.org/licenses/by-nc-nd/4.0/',
  },
  'cc0-10': { label: 'CC0 1.0', url: 'https://creativecommons.org/publicdomain/zero/1.0/' },
  mit: { label: 'MIT', url: 'https://opensource.org/license/mit' },
  arr: { label: 'All Rights Reserved', url: '' },
}

/** 'none' / 空值 / 未知取值返回 null（不渲染许可声明） */
export function resolveArticleLicense(key?: string | null): ArticleLicenseInfo | null {
  if (!key || key === 'none') return null
  const meta = LICENSES[key]
  if (!meta) return null
  return {
    key,
    label: meta.label,
    url: meta.url,
    statement: meta.url
      ? `本文采用 ${meta.label} 许可协议发布，转载请注明出处。`
      : '本文版权归作者所有，未经许可请勿转载。',
  }
}
