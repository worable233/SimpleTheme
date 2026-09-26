<script setup lang="ts">
/**
 * 列表型动态区块的统一组件：core/categories、core/archives、core/latest-posts、
 * core/page-list、core/rss、core/tag-cloud。
 *
 * 这些区块服务端已把真实数据渲染进 `content.rendered`（无需前端再次请求）。
 * 本组件只做「结构提升」：把原 `<li><a>标签</a></li>` 提取成
 * `{ label, href, meta }`，用主题令牌重排为统一的链接列表 / 标签胶囊，
 * 从而与主题组件观感一致，同时不产生加载态、不破坏 SEO。
 */
import { computed } from 'vue'
import { blockElProp, useBlock } from '../helpers'

defineOptions({ name: 'StBlockLinkList' })

const props = defineProps(blockElProp)
const { attrs } = useBlock(props)

interface Item {
  label: string
  href: string
  meta: string
}

/** 判定是否标签云（根元素为 <p>，渲染为胶囊）。 */
const isTagCloud = computed(() => props.el.classList.contains('wp-block-tag-cloud'))

const items = computed<Item[]>(() => {
  const el = props.el as HTMLElement

  // 标签云：根即容器，链接是其直接子元素。
  if (isTagCloud.value) {
    return Array.from(el.querySelectorAll('a')).map((a) => ({
      label: (a.textContent || '').trim(),
      href: a.getAttribute('href') || '',
      meta: '',
    }))
  }

  // 列表类：每行取首个链接，其余文本作为附加信息（日期 / 摘要）。
  const nodes = Array.from(el.querySelectorAll('li'))
  const source = nodes.length ? nodes : Array.from(el.children)
  return source
    .map((node) => {
      const link = node.matches?.('a') ? (node as HTMLAnchorElement) : node.querySelector('a')
      if (!link) return null
      const href = link.getAttribute('href') || ''
      const clone = node.cloneNode(true) as HTMLElement
      clone.querySelectorAll('a').forEach((a) => a.remove())
      const meta = (clone.textContent || '').replace(/\s+/g, ' ').trim()
      return { label: (link.textContent || '').trim(), href, meta }
    })
    .filter((x): x is Item => !!x && !!x.label)
})
</script>

<template>
  <ul v-if="isTagCloud" v-bind="attrs" class="st-block-tag-cloud">
    <li v-for="(item, i) in items" :key="i">
      <a :href="item.href" class="st-block-tag-cloud__link">{{ item.label }}</a>
    </li>
  </ul>
  <ul v-else v-bind="attrs" class="st-block-link-list">
    <li v-for="(item, i) in items" :key="i" class="st-block-link-list__item">
      <a :href="item.href" class="st-block-link-list__link">{{ item.label }}</a>
      <span v-if="item.meta" class="st-block-link-list__meta">{{ item.meta }}</span>
    </li>
  </ul>
</template>
