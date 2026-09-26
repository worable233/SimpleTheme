/**
 * 通用区块组件与登记。
 *
 * 绝大多数核心区块的服务端语义结构已经正确，只需保留标签 / 属性 / 内联样式，
 * 再补一个 `st-block-<name>` 主题钩子类。这里用一个通用实现 + 按名登记，
 * 避免为 30 多个区块各写一个几乎相同的文件。
 *
 * 需要重写 DOM 结构的区块（图标 / 数学 / 按钮 / 搜索 / 文件 / 列表型动态区块）
 * 在 `./components/*.vue` 里单独实现，并在 registry 中覆盖登记。
 */
import { defineComponent, h, type Component } from 'vue'
import { blockElProp, useBlock } from './helpers'
import { detectBlockName, registerBlock } from './renderer'

/** 生成一个「保留原标签」的区块组件。 */
function makeGeneric(): Component {
  return defineComponent({
    name: 'StGenericBlock',
    props: blockElProp,
    setup(props) {
      const name = detectBlockName(props.el)
      const { attrs, children } = useBlock(props, name ? `st-block-${name}` : undefined)
      return () => h((props.el as Element).tagName.toLowerCase(), attrs.value, children.value)
    },
  })
}

/**
 * 核心区块 → 走通用透传组件的清单。
 * 名字取 `wp-block-<name>` 的 `<name>`。
 */
export const GENERIC_BLOCK_NAMES: string[] = [
  'paragraph',
  'heading',
  'list',
  'list-item',
  'quote',
  'code',
  'preformatted',
  'verse',
  'table',
  'image',
  'gallery',
  'details',
  'pullquote',
  'separator',
  'spacer',
  'group',
  'columns',
  'column',
  'video',
  'calendar',
  'archives',
  'categories',
  'latest-posts',
  'page-list',
  'pages-list',
  'rss',
  'latest-comments',
  'social-links',
  'tag-cloud',
  'embed',
  'html',
  'shortcode',
  'read-more',
  'nextpage',
  'more',
  'footnotes',
  'pattern',
  'template-part',
  'query',
  'post-template',
  'post-title',
  'post-excerpt',
  'post-featured-image',
  'post-date',
  'post-terms',
  'post-content',
  'query-pagination',
  'query-pagination-previous',
  'query-pagination-next',
  'query-pagination-numbers',
  'query-no-results',
  'post-author',
  'post-comments-count',
  'avatar',
  'loginout',
  'comment-template',
  'comment-author-name',
  'comment-date',
  'comment-content',
  'comment-edit-link',
  'comment-reply-link',
  'post-navigation-link',
  'term-description',
  'term-name',
  'buttons',
  'audio',
  'accordion',
  'accordion-item',
  'accordion-heading',
  'accordion-panel',
  'cover',
  'media-text',
]

/** 登记通用区块。返回登记数量。 */
export function registerGenericBlocks(): number {
  for (const name of GENERIC_BLOCK_NAMES) {
    registerBlock(name, makeGeneric())
  }
  return GENERIC_BLOCK_NAMES.length
}
