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

## 引入方式

```ts
import { StButton, StCard } from '@/ui'
```

按需具名引入，保留 tree-shaking。**不要**全局注册整个组件库。
