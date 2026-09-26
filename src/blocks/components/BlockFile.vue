<script setup lang="ts">
/**
 * core/file 区块。
 *
 * 服务端结构：`<div class="wp-block-file"><a>文件名</a><a class="wp-block-file__button wp-element-button" download>Download</a></div>`。
 * 重绘为「文件名 + 主题 StButton 下载按钮」，保留下载链接与 aria-describedby。
 */
import { computed } from 'vue'
import { StButton } from '@/ui'
import { blockElProp, useBlock } from '../helpers'

defineOptions({ name: 'StBlockFile' })

const props = defineProps(blockElProp)
const { attrs } = useBlock(props, 'st-block-file')

const links = computed(() => Array.from(props.el.querySelectorAll('a')))
const fileLink = computed(
  () => links.value.find((a) => !a.classList.contains('wp-block-file__button')) || links.value[0],
)
const buttonLink = computed(() =>
  links.value.find((a) => a.classList.contains('wp-block-file__button')),
)

const fileName = computed(() => fileLink.value?.textContent?.trim() || '')

const btnAttrs = computed(() => {
  const el = buttonLink.value
  const out: Record<string, string> = {}
  if (!el) return out
  for (const attr of Array.from(el.attributes)) {
    if (attr.name === 'class') continue
    out[attr.name] = attr.value
  }
  return out
})
</script>

<template>
  <div v-bind="attrs">
    <a v-if="fileLink" :href="fileLink.getAttribute('href') || undefined" class="st-block-file__name">
      {{ fileName }}
    </a>
    <StButton v-if="buttonLink" tag="a" type="primary" size="small" v-bind="btnAttrs">
      {{ buttonLink.textContent || '下载' }}
    </StButton>
  </div>
</template>
