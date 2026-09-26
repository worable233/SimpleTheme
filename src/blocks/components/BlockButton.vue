<script setup lang="ts">
/**
 * core/button（单个按钮）。
 *
 * 用主题 StButton 重绘，`tag="a"` 保留链接语义；原始 href / target / rel / style
 * 等属性透传，因此区块编辑器里设置的颜色、宽度、圆角等内联样式照常生效。
 *
 * 语义映射（还原核心区块的可视语言，同时避免覆盖编辑器自定义色）：
 *   - `is-style-outline` → ghost（透明底 + 描边）
 *   - 有自定义背景（内联 style 或 `has-*-background-color` 预设类）→ quaternary
 *     （不设背景），让编辑器色值直接显示；
 *   - 其余 → primary（与主题强调色一致）。
 * 预设色类一并转发到 StButton 根元素，保证调色板预设也能生效。
 */
import { computed } from 'vue'
import { StButton } from '@/ui'
import type { StIntent } from '@/ui'
import { blockElProp } from '../helpers'

defineOptions({ name: 'StBlockButton' })

const props = defineProps(blockElProp)

const anchor = computed(() => props.el.querySelector('a'))
const isLink = computed(() => !!anchor.value?.getAttribute('href'))
const isOutline = computed(() => props.el.classList.contains('is-style-outline'))

const attrs = computed(() => Array.from((anchor.value || props.el).attributes))
const styleAttr = computed(() => attrs.value.find((a) => a.name === 'style')?.value || '')
const classAttr = computed(() => attrs.value.find((a) => a.name === 'class')?.value || '')
const hasPresetBg = computed(
  () => /has-[a-z0-9-]+-background-color/.test(classAttr.value) || /background/.test(styleAttr.value),
)

const type = computed<StIntent>(() => {
  if (isOutline.value) return 'ghost'
  if (hasPresetBg.value) return 'default'
  return 'primary'
})

/** 透传属性：非 class 的原生属性（href/target/rel/style…）。 */
const forwarded = computed(() => {
  const out: Record<string, string> = {}
  for (const attr of attrs.value) {
    if (attr.name === 'class') continue
    out[attr.name] = attr.value
  }
  return out
})

/** 保留编辑器的预设色类（转发到 StButton 根元素）。 */
const forwardedClasses = computed(() =>
  classAttr.value
    .split(/\s+/)
    .filter((c) => /^has-[a-z0-9-]+-(background-)?color$/.test(c))
    .join(' '),
)

/** 按钮正文：优先取 <a> 的 innerHTML，否则取容器文本。 */
const inner = computed(() => (anchor.value ? anchor.value.innerHTML : props.el.textContent || ''))
</script>

<template>
  <StButton
    class="st-block-button"
    :class="forwardedClasses"
    :type="type"
    :quaternary="hasPresetBg && !isOutline"
    :tag="isLink ? 'a' : 'button'"
    :attr-type="isLink ? undefined : 'button'"
    v-bind="forwarded"
  >
    <span v-html="inner" />
  </StButton>
</template>
