# src/blocks — 核心区块组件化渲染层

把详情 / 页面 / 关于 / 友链等正文（`content.rendered`，纯 HTML）解析成 DOM，
再递归映射为 Vue 组件，使常用核心区块统一用 `src/ui` 的 `St*` 组件与主题令牌渲染。

## 为什么需要它

`content.rendered` 是 WordPress 服务端渲染好的 HTML（区块分隔注释已被消费）。
直接 `v-html` 只能得到 WP 默认外观；本层在挂载后把它「再组件化」一次。

- **编辑器设置全部保留**：未登记的区块与未知元素按「同标签 + 原属性 + 递归子节点」
  透传，内联 `style`、`has-*-background-color` 预设类、对齐 / 长宽高类都原样生效。
- **常用区块统一**：已登记区块走主题组件，观感与站点其余部分一致。
- **不影响 SEO / 无 JS**：服务端 `templates/parts/static-content.php` 仍输出同一份
  语义化 HTML；本层只作用于 SPA 挂载后的 DOM。

## 目录

| 文件 | 职责 |
| --- | --- |
| `BlockContent.vue` | 入口组件：`<BlockContent class="prose-content" :html="…" />` |
| `renderer.ts` | DOM 节点 → VNode 的递归映射、登记表、`RawNode`（SVG/MathML） |
| `registry.ts` | 登记表：通用透传 + 专用组件覆盖 |
| `generic.ts` | 通用透传组件与 `GENERIC_BLOCK_NAMES` 清单 |
| `helpers.ts` | 区块组件共享的 `useBlock` / `blockElProp` 等 |
| `components/*.vue` | 需要重绘结构的区块（按钮 / 搜索 / 文件 / 图标 / 数学 / 列表型动态区块） |
| `../styles/blocks.css` | 组件重绘后新增的 `st-block-*` 钩子样式 |

## 渲染流程

1. `BlockContent` 用 `innerHTML` 把 HTML 解析进一个游离 `<div>`。
2. `renderChildren` 递归遍历，遇到 `wp-block-<name>` 且已登记 → 渲染对应组件；
   否则按原标签 / 原属性 / 递归子节点透传。
3. 组件通过 `useBlock(props)` 拿到根元素属性与已转换的子 VNode。
4. SVG / MathML 名称空间无法用 `h()` 正确创建（`createElement` 会退化为
   `HTMLUnknownElement`），故交给 `RawNode` 用 `innerHTML` 注入。

### 与 `useContentEnhancer` 的协作

正文增强（标题锚点、代码块外壳、Fancybox、自定义音频、手风琴…）仍是挂载后的
命令式 DOM 改写。为免 Vue diff 与这些改写互相打架，`BlockContent` 用 `key`
让内容变化时**整体重建**根元素：旧子树（含增强产生的、未经 Vue 跟踪的节点）
随根元素一并销毁，不残留。

## 新增一个区块组件

1. 在 `components/` 新建 `BlockXxx.vue`，`defineProps(blockElProp)`，
   用 `useBlock(props, 'st-block-xxx')` 取 `attrs` / `children`。
2. 在 `registry.ts` 的 `registerBlocks()` 里 `registerBlock('xxx', BlockXxx)`
   **覆盖**通用登记（若该区块已在 `GENERIC_BLOCK_NAMES`）。
3. 若重绘后原 WP 类名不再命中样式，在 `src/styles/blocks.css` 补 `st-block-xxx`。

> 注意：组件根元素务必保留原始 `class`（`useBlock` 已合并），否则
> `prose.css` 的 `.wp-block-*` 兜底样式与编辑器预设类会一起失效。
