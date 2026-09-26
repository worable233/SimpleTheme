/**
 * 区块登记表：区块名 → 组件。
 *
 * 策略：
 *   - 结构无需改写的区块 → `registerGenericBlocks()`（保留原标签/属性/样式，
 *     仅补 `st-block-<name>` 主题钩子类）；
 *   - 需要重绘结构的区块 → 在此用专用组件覆盖登记。
 *
 * 该函数幂等，可在任意入口安全调用。
 */
import { registerBlock } from './renderer'
import { registerGenericBlocks } from './generic'
import BlockIcon from './components/BlockIcon.vue'
import BlockMath from './components/BlockMath.vue'
import BlockButton from './components/BlockButton.vue'
import BlockSearch from './components/BlockSearch.vue'
import BlockFile from './components/BlockFile.vue'
import BlockLinkList from './components/BlockLinkList.vue'

/** 用同一组件渲染的列表型动态区块。 */
const LINK_LIST_BLOCKS = [
  'categories',
  'archives',
  'latest-posts',
  'page-list',
  'pages-list',
  'rss',
  'tag-cloud',
]

let done = false

/** 登记全部区块组件（幂等）。 */
export function registerBlocks(): void {
  if (done) return
  done = true

  // 通用透传（结构已正确）
  registerGenericBlocks()

  // 需要重绘结构的区块（覆盖登记）
  registerBlock('icon', BlockIcon)
  registerBlock('math', BlockMath)
  registerBlock('button', BlockButton)
  registerBlock('search', BlockSearch)
  registerBlock('file', BlockFile)
  for (const name of LINK_LIST_BLOCKS) registerBlock(name, BlockLinkList)
}
