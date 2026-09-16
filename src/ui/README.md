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
