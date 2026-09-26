<script setup lang="ts">
/**
 * core/search 区块。
 *
 * 用主题 StInput + StButton 重绘表单；保留 form 的 action/method 与 input 的
 * id/name/required（StInput 会把原生属性下沉到真正的 input）。
 * 表单提交仍由 useContentEnhancer 拦截并打开主题搜索弹窗。
 */
import { computed } from 'vue'
import { StInput, StButton } from '@/ui'
import { blockElProp, useBlock } from '../helpers'

defineOptions({ name: 'StBlockSearch' })

const props = defineProps(blockElProp)
const { attrs } = useBlock(props, 'st-block-search')

const form = computed(() => props.el as HTMLFormElement)
const labelEl = computed(() => form.value.querySelector('label'))
const inputEl = computed(() => form.value.querySelector('input'))
const buttonEl = computed(() => form.value.querySelector('button'))

const formAttrs = computed(() => {
  const out: Record<string, string> = {}
  for (const attr of Array.from(props.el.attributes)) {
    if (attr.name === 'class') continue
    out[attr.name] = attr.value
  }
  return out
})

const inputAttrs = computed(() => {
  const el = inputEl.value
  const out: Record<string, unknown> = { type: 'search' }
  if (!el) return out
  for (const attr of Array.from(el.attributes)) {
    if (attr.name === 'class' || attr.name === 'type') continue
    out[attr.name] = attr.value
  }
  return out
})
</script>

<template>
  <form v-bind="formAttrs" :class="attrs.class">
    <label v-if="labelEl" :for="inputAttrs.id as string" class="st-block-search__label">
      {{ labelEl.textContent }}
    </label>
    <div class="st-block-search__row">
      <StInput v-bind="inputAttrs" />
      <StButton attr-type="submit" type="primary" :aria-label="buttonEl?.textContent || '搜索'">
        {{ buttonEl?.textContent || '搜索' }}
      </StButton>
    </div>
  </form>
</template>
