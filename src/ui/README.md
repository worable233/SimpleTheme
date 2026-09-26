# src/ui — 组件库规范

本目录是主题的**基础组件层**。业务层（`src/components/`、`src/views/`、`src/admin/`）
只允许"拼装"这里的组件，不再手写重复的样式组合。

## 分层

```
src/styles/tokens.css   ← 设计令牌，唯一事实来源
src/ui/                 ← 基础组件层（本目录）：与业务无关的通用控件
src/components/         ← 业务组件层：由基础层拼出来的文章卡片、评论项…
```

## 铁律

1. **取值只能在 `src/styles/tokens.css` 定义。**
   组件内禁止硬编码颜色（`#fff`、`rgb(...)` 一律不许）。
   统一消费语义令牌（`--card` / `--foreground` / `--danger`…）或组件令牌（`--st-*`）。

2. **对外契约 = props + slots + emits。**
   不接受 `class` 透传做样式覆盖，不暴露内部 class 名给调用方。
   要改外观就加语义 prop（如 `type` / `size` / `secondary`），不要开 `class` 后门。

3. **样式一律 `<style scoped>` + BEM。**
   根 class 为 `st-<组件名>`，子元素 `st-<组件名>__<部位>`，
   状态用 `is-<状态>` 或 `st-<组件名>--<变体>`。
   不使用 Tailwind 工具类（组件必须自包含，且前后台 Tailwind 配置不同）。

   **例外 1：`reka-ui` Portal 渲染到 `body` 的浮层外壳必须用 `:global(.st-xxx)`。**
   `TooltipContent` / `PopoverContent` / `SelectContent` / `DropdownMenuContent`
   等经 Portal 逃逸出组件 DOM，内容根**拿不到**本组件的 `data-v-*`
   作用域属性，普通 `.st-xxx` scoped 选择器会**静默匹配失败**
   （浮层变透明、无边框阴影，且不报错）。`DialogPortal`（Modal / Drawer）
   的链路会传递 scope，继续用普通 scoped 即可。
   门禁 [E7] 会拦住漏网的写法。

   **例外 2：`st-transition-*` 是全局过渡工具类（`src/styles/transitions.css`），
   直接写在浮层根上即可，不需要 `:global()`——它本就定义在全局样式表里。**

4. **无障碍是组件的职责，不是调用方的。**
   焦点管理、键盘交互、`aria-*`、`role` 由组件内部实现。
   复杂浮层（Modal / Select / Tabs / Tooltip）基于 `reka-ui` 实现，
   不要自己手写焦点陷阱。

5. **受控优先。** v-model 用 `defineModel`，组件始终以 props 为准。

## 文件模板

```vue
<script setup lang="ts">
/**
 * StXxx — 一句话说明
 *
 * 用法：
 *   <StXxx v-model="value" size="small" />
 */
import { computed } from 'vue'
import type { StSize } from '../types'

defineOptions({ name: 'StXxx' })

const props = withDefaults(defineProps<{ size?: StSize }>(), { size: 'medium' })

const model = defineModel<string>({ default: '' })
</script>

<template>
  <div class="st-xxx" :class="[`st-xxx--${size}`]"><slot /></div>
</template>

<style scoped>
.st-xxx {
  /* 只消费令牌 */
  color: var(--st-text);
}
</style>
```

## 命名

- 前缀统一 `St`，与 Vue 官方 / Naive UI 风格保持一致。
- props 命名对齐 Naive UI：`type`（语义色）、`size`、`status`（校验态）、
  `disabled`、`loading`、`round`、`block`…

## 尺寸与令牌

| 令牌                    | tiny | small | medium | large |
| ----------------------- | ---- | ----- | ------ | ----- |
| `--st-height-*`         | 22px | 28px  | 34px   | 40px  |
| `--st-font-*`           | 12px | 13px  | 14px   | 15px  |

## 布局原语

不要再用裸 `div` + 手写 flex/gap 拼排布。用 `StStack`（一维）和 `StGrid`（等宽多列）：

```vue
<StStack :gap="4"><StInput /><StButton /></StStack>
<StGrid :cols="2" :gap="4">…</StGrid>
```

间距只允许取 `--st-space-*` 刻度（`0/1/2/3/4/5/6/8`），不允许各写各的 px。

> **`gap` / `cols` 同时接受数字与数字字符串。**
> 模板里写静态属性 `gap="4"` 传进来的是**字符串** `"4"`，`:gap="4"` 传的是数字。
> 两者都合法（类型 `StSpace` / `StColsInput`），组件内部归一化。
> 早期版本只声明数字联合，`gap="4"` 会触发 TS2322 —— 而它恰恰是本文档
> 自己给的示例写法，一次性污染了 22 处调用点。类型已放宽，不要再收窄。

## 原生属性的透传约定

输入类组件（`StInput` / `StTextarea` / `StNumberInput`）用
`inheritAttrs: false` 做**属性分流**：

- `class` / `style` → 留在组件根元素（保留布局钩子）
- 其余非声明属性（`required` / `minlength` / `id` / `name` / `inputmode` /
  `pattern` / `aria-describedby`…）→ 下沉到内层真正的原生控件

否则原生表单校验与 `<label for>` 关联会静默失效。
**新增输入类组件时必须沿用这个约定**，不然调用方会遇到"属性写了没反应"。

> `StSelect` 尚未处理：它基于 reka-ui `SelectTrigger`，属性应落到触发器
> 按钮而非隐藏 input，需要单独设计。

## 动效

曲线与时长**对齐 Naive UI 的节奏约定**（`tokens.css` C 段）：

| 令牌                    | 用途                              | 值                            |
| ----------------------- | --------------------------------- | ----------------------------- |
| `--ease-out`            | cubicBezierEaseOut（进场/退场）   | `cubic-bezier(0, 0, .2, 1)`   |
| `--ease-in`             | cubicBezierEaseIn（退场）         | `cubic-bezier(.4, 0, 1, 1)`   |
| `--ease-in-out`         | cubicBezierEaseInOut（几何折叠）  | `cubic-bezier(.4, 0, .2, 1)`  |
| `--duration-enter`      | 进场时长                          | `0.3s`                        |
| `--duration-leave`      | 退场时长                          | `0.2s`                        |
| `--st-duration-modal`   | Modal 进出场同值刻度              | `0.25s`                       |

> 关于"进慢出快"：这适用于 **StDrawer**（进场 `--duration-enter` + `--ease-out`，
> 退场 `--duration-leave` + `--ease-in`，与 Naive drawer 的 `-bezier-in/-out` 一致）。
> 但**不要套用到所有组件**——Naive 的 `StModal` 与 `StToast` 本身就是**对称**的：
> Modal 进出都是 `.25s`，Toast 高度折叠进出都是 `.3s`。抄之前先核对该组件源码。

> 已内置的动效样板（可直接参照）：
> `StModal` / `StDrawer` 的 `[data-state='closed']` 退场（基于 reka-ui 的
> `Presence`，它在 `animationend` 后才卸载节点，所以退场动画必须挂在
> `Overlay` / `Content` **元素自身**上）；`StToast` 按 Naive
> `fadeInHeightExpandTransition` 逐值实现的高度折叠；`StButton` 的
> 图标↔spinner 交叉切换与点击波纹；`StSwitch` 的橡胶拉伸。

**共享过渡层：`src/styles/transitions.css`。** 对齐 Naive
`_styles/transitions/` 的可复用过渡类，供浮层直接复用，避免每个组件各写一份
`@keyframes`。用法是把工具类加到 reka-ui Portal 浮层的**内容根**上：

```vue
<PopoverContent class="st-popover__content st-transition-fade-scale" />
```

| 类名                      | 效果                            |
| ------------------------- | ------------------------------- |
| `st-transition-fade`      | 纯淡入淡出                      |
| `st-transition-fade-scale`| 淡入 + 轻微放大（浮层默认节奏） |
| `st-transition-slide`     | 淡入 + 位移（方向由 `--st-transition-shift-x/-y` 给出） |

进场用 `--duration-enter` + `--ease-out`，退场用 `--duration-leave` +
`--ease-in`（Naive「进慢出快」约定）。退场写在 `[data-state='closed']` 上，
reka 的 `Presence` 依据 `animationend` 延迟卸载。`st-transition-*` 是**全局**
工具类，门禁 E7 已为其开例外，不需要 `:global()`。

**所有动效都必须包一层 `@media (prefers-reduced-motion: reduce)` 关闭。**

## 何时**不该**用 StButton

`StButton` 把默认插槽包在 `.st-button__content`（`display:inline-block;
overflow:hidden`）里，按钮本身是 `justify-content:center` 的 inline-flex。
所以它**只适合"单一内容的按钮"**：文字、图标，或 `#icon` + 文字。

以下情况请保留原生 `<button>`（并沿用业务 scoped CSS）：

- 按钮内部要自己做复杂布局（如"左标题 + 右计数"的 `justify-between` 卡片头）；
- 整块卡片即按钮，且依赖 `hover:-translate-y` / `scale` / 任意栅格几何；
- 位于**共享全局 CSS**（`src/styles/*.css`）里的面板单元格（如表情面板的
  36×36 固定格 + 选中下划线）——迁进组件会孤儿化那些全局规则。

判断标准：如果替换后需要给 `St*` 传 `class` 才能还原布局，那就说明**不该换**
（契约不允许 class 透传，且 scoped 样式会按 specificity 压掉）。

## 引入方式

```ts
import { StButton, StCard } from '@/ui'
```

按需具名引入，保留 tree-shaking。**不要**全局注册整个组件库。

## 可形变图标（StMorphIcon）

`StMorphIcon` 与 `StIcon` 同命名解析（语义名 / bx 类名 / fa 类名 / `<i>` HTML），
但 `name` 变化时用弹簧物理**形变**到新图标。

```vue
<StMorphIcon :name="isDark ? 'sun' : 'moon'" :size="24" />
```

底层是 **morphicons**（形变动画引擎，非图标库）；图标数据仍来自 `@tabler/icons`。
只有 `src/lib/tabler-icon-map.json` 的 `_morphIcons` 白名单里的图标才有形变数据
（由 `bin/gen-tabler-icons.mjs` 生成 `ICON_MORPH_NODES`，避免全量多出 ~13KB gzip）；
白名单外的图标自动降级为静态 `StIcon`，所以可以安全替换而不必担心。

- `spring`：`smooth` / `snappy`(默认) / `bouncy`。
- `reducedMotion`：默认 `"user"`，尊重系统 `prefers-reduced-motion`
  （morphicons 出厂默认 `"never"`，与本主题动效规范不符，已在组件内改掉）。
- 只在图标**真的会切换**的地方用它；静态图标用 `StIcon` 即可，否则纯亏体积。

已铺开的切换点（其余静态图标保持 `StIcon`）：

| 位置 | 形变 | spring |
| --- | --- | --- |
| `SidebarActions` / `admin/UserDropdown` | `sun↔moon` | `snappy` |
| `SidebarMobileHeader` | `menu↔close` | `snappy` |
| `StInput` / `AuthModal`（×3）密码可见性 | `eye↔eye-off` | `snappy` |
| `StCheckbox` 半选态 | `minus↔check` | `snappy` |
| `StCollapseItem` / `SidebarNav` 折叠箭头 | `chevron-down↔up` | `smooth` |
| `TechInfo` 展开箭头 | `chevron-down↔right` | `smooth` |
| `useContentEnhancer` 正文音频播放器 | `player-play↔player-pause`、`volume↔volume-off`、`repeat↔repeat-off` | `snappy` |

> chevron 统一用 `smooth`（不越冲、减速落定），符合主题曲线约定。折叠/子菜单箭头原先靠
> CSS `rotate()` 实现，现改为绑定展开态的形变，对应的 `rotate` 规则已从各组件与
> `src/styles/sidebar.css` 移除。
>
> 音频播放器不用 `StMorphIcon` 组件，而用 morphicons 的 `<morph-icon>` **自定义元素**
> （`morphicons/element`）：内容增强器是命令式 DOM，元素在 `disconnectedCallback`
> 里自行销毁控制器、随正文一起被回收，不会像挂 Vue 应用那样泄漏。播放/暂停沿用实心
> （CSS `fill: currentColor; stroke: none` 填充 Tabler path），其余图标保持线性描边。
> 版式参考主流音乐条：顶部整宽细进度条 + 左时间 + 中走带（快退 10s / 播放暂停 /
> 快进 10s）+ 右工具（循环、静音、下载）；`rewind-backward-10`/`rewind-forward-10`
> 为静态图标，已登记进 `tabler-icon-map.json`。

## 业务层门禁

组件库自身的契约由 `npm run check:ui` 强制（见 `bin/check-ui.mjs`）。
业务层（`src/App.vue`、`src/components`、`src/views`、`src/admin`）的「组件库优先」由
`npm run check:ui:business` 强制（见 `bin/check-ui-business.mjs`）：

- 禁裸 `<button>` → 优先 `StButton`；
- 禁裸 `<input>` / `<textarea>` / `<select>` → 优先 `StInput` / `StTextarea` / `StSelect`；
- 禁静态 `class="..."` 里的 Tailwind 工具类 → 收敛为 scoped CSS + 令牌。

**登记制**：确实需要保留原生的，在 `bin/check-ui-business.mjs` 的白名单里
显式登记理由，并在对应组件的 template 里补一段注释说明「为何不迁」。
保留理由属于代码，不属于 commit message——否则后人只看到裸标签，会误以为漏迁。
