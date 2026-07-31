<script setup lang="ts">
/**
 * HitokotoCard — 侧边栏一言卡片
 *
 * 从后台配置的 API（默认 hitokoto.cn）拉取一条随机句子。
 * 兼容三种响应：hitokoto JSON（hitokoto/from/from_who）、
 * 通用 JSON（text/content 字段）、纯文本。
 * 加载失败时整卡隐藏，不渲染错误占位。
 */
import { computed, onMounted, ref } from 'vue'

const DEFAULT_API = 'https://v1.hitokoto.cn'

const props = defineProps<{
  /** 小工具实例设置（API 地址） */
  settings?: { api: string }
}>()

const enabled = computed(() => true)
const api = computed(() => props.settings?.api || DEFAULT_API)

const sentence = ref('')
const source = ref('')
const loading = ref(false)

async function fetchHitokoto() {
  if (loading.value) return
  loading.value = true
  try {
    const controller = new AbortController()
    const timer = setTimeout(() => controller.abort(), 6000)
    const res = await fetch(api.value, { signal: controller.signal })
    clearTimeout(timer)
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    const raw = (await res.text()).trim()
    let text = ''
    let from = ''
    try {
      const data = JSON.parse(raw) as Record<string, unknown>
      // hitokoto.cn 格式优先，其余常见字段兜底
      text = String(data.hitokoto || data.text || data.content || '')
      const fromWho = typeof data.from_who === 'string' ? data.from_who : ''
      const fromName = typeof data.from === 'string' ? data.from : ''
      if (fromWho || fromName) {
        from = `${fromWho}${fromName ? `《${fromName}》` : ''}`
      }
    } catch {
      // 纯文本 API
      text = raw
    }
    // 防御异常超长响应（如错误页 HTML）
    if (text && text.length <= 300 && !text.includes('<')) {
      sentence.value = text
      source.value = from
    }
  } catch {
    // 静默失败 — 卡片不渲染
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (enabled.value) void fetchHitokoto()
})
</script>

<template>
  <div v-if="enabled && sentence" class="aside-card aside-card--hitokoto">
    <h3 class="aside-card__title">一言 <span>Hitokoto.</span></h3>
    <blockquote
      class="group m-0 cursor-pointer px-5 pb-5 text-center"
      title="换一句"
      @click="fetchHitokoto"
    >
      <p
        class="m-0 text-[13px] leading-relaxed text-foreground transition-opacity duration-300"
        :class="{ 'opacity-40': loading }"
      >
        “{{ sentence }}”
      </p>
      <footer
        v-if="source"
        class="mt-2 text-xs text-muted-foreground transition-opacity duration-300"
        :class="{ 'opacity-40': loading }"
      >
        —— {{ source }}
      </footer>
    </blockquote>
  </div>
</template>
