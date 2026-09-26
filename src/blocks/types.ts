/**
 * src/blocks — WordPress 核心区块的 Vue 组件化渲染层
 *
 * 背景：详情/页面/关于等正文来自 `content.rendered`（纯 HTML，区块分隔注释已被
 * 服务端消费掉）。本层把这段 HTML 解析成 DOM，再递归映射为 Vue 组件：
 *   - 已登记的核心区块 → 用 src/ui 组件 + 主题令牌重绘；
 *   - 未登记/未知区块 → 原样透传（保留全部属性与内联样式）。
 *
 * 这样区块编辑器里设置的一切（对齐、长宽高、内联 style、预设色/字号类）都由
 * 「原样透传」与「属性转发」天然保留，同时常用区块统一到主题组件。
 *
 * 注意：服务端 `templates/parts/static-content.php` 仍输出语义化 HTML 供 SEO /
 * 无 JS 用户使用；本层只影响 SPA 挂载后的渲染，不改变爬虫看到的 HTML。
 */
import type { Component } from 'vue'

/** 区块组件的统一入参：原始 DOM 元素。 */
export interface BlockProps {
  /** 服务端渲染出来的该区块根元素（保留原始属性 / 内联样式 / 子节点）。 */
  el: Element
}

/** 区块名 → 组件 的登记项。区块名取 `wp-block-<suffix>` 的 `<suffix>`。 */
export type BlockRegistry = Map<string, Component>
