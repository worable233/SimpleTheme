/**
 * src/blocks — WordPress 核心区块组件化渲染层
 *
 * 详见 README.md。对外只暴露：
 *   - `BlockContent`：正文 HTML → Vue 组件的渲染入口；
 *   - `registerBlocks`：手动登记（一般无需调用，BlockContent 会自动登记）。
 */
export { default as BlockContent } from './BlockContent.vue'
export { registerBlocks } from './registry'
export { renderNode, renderChildren, attrsOf, registerBlock, hasBlock } from './renderer'
export { blockElProp, useBlock } from './helpers'
