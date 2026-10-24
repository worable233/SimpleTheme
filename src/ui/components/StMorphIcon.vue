<script setup lang="ts">
/**
 * StMorphIcon — 可形变图标
 *
 * 与 StIcon 同一套命名解析（语义名 / bx 类名 / fa 类名 / `<i>` HTML），
 * 但当 `name` 变化时，图标会**形变**到新图标，而不是直接切换。
 * 适用于运行时会两两切换的图标：主题 sun↔moon↔device-desktop、菜单 menu↔x、
 * 折叠 chevron-down↔up、播放 play↔pause 等。
 *
 * 引擎是项目内的轻量实现（src/lib/icon-morph.ts，零依赖），只对
 * `tabler-icon-map.json` 的 `_morphIcons` 白名单生效；不在白名单里的图标
 * 自动降级为 StIcon 静态渲染，调用方可无脑替换。
 *
 * 默认尊重 `prefers-reduced-motion`。
 */
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import StIcon from './StIcon.vue'
import { resolveIconName } from '@/lib/icon-resolver'
import { ICON_MORPH_PATHS } from '@/lib/tabler-icons-core.generated'
import { attachMorphPath, type MorphController } from '@/lib/icon-morph'

defineOptions({ name: 'StMorphIcon' })

const props = withDefaults(
  defineProps<{
    /** 图标标识：语义名 / bx|fa|ti 类名 / `<i>` HTML */
    name: string
    /** 尺寸（px 或带单位字符串） */
    size?: number | string
    /** 描边宽度 */
    stroke?: number
    /** 弹簧手感：smooth 无回弹 / snappy 轻回弹 / bouncy 活泼 */
    spring?: 'smooth' | 'snappy' | 'bouncy'
    /** 减弱动效策略；默认跟随系统（"user"） */
    reducedMotion?: 'never' | 'user' | 'always'
    /** 无障碍名称；不传则 aria-hidden（纯装饰） */
    label?: string
  }>(),
  {
    size: 20,
    stroke: 2,
    spring: 'snappy',
    reducedMotion: 'user',
  },
)

const resolved = computed(() => resolveIconName(props.name))

/** 有 morph 数据走形变，否则降级为静态 StIcon */
const morphD = computed(() => ICON_MORPH_PATHS[resolved.value.name] || '')

const pathEl = ref<SVGPathElement | null>(null)
let controller: MorphController | null = null

watch(
  [morphD, pathEl],
  ([d, el]) => {
    if (!el) {
      controller?.destroy()
      controller = null
      return
    }
    if (!d) return
    if (controller) {
      controller.morphTo(d)
      return
    }
    controller = attachMorphPath(el, {
      spring: props.spring,
      reducedMotion: props.reducedMotion,
    })
    controller.set(d)
  },
  { immediate: true, flush: 'post' },
)

onBeforeUnmount(() => {
  controller?.destroy()
  controller = null
})
</script>

<template>
  <svg
    v-if="morphD"
    class="st-morph-icon"
    viewBox="0 0 24 24"
    :width="size"
    :height="size"
    fill="none"
    stroke="currentColor"
    :stroke-width="stroke"
    stroke-linecap="round"
    stroke-linejoin="round"
    :role="label ? 'img' : undefined"
    :aria-label="label"
    :aria-hidden="label ? undefined : 'true'"
    focusable="false"
  >
    <path ref="pathEl" />
  </svg>
  <StIcon v-else class="st-morph-icon" :name="name" :size="size" :stroke="stroke" />
</template>

<style scoped>
/* 不设 display：调用方（如移动端顶栏的响应式显隐）需要用自己的 class 控制它。
 * 只做内联基线对齐，svg 默认即 inline。 */
.st-morph-icon {
  flex: none;
  vertical-align: -0.15em;
}
</style>
