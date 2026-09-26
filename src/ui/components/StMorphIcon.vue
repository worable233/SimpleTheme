<script setup lang="ts">
/**
 * StMorphIcon — 可形变图标（morphicons）
 *
 * 与 StIcon 同一套命名解析（语义名 / bx 类名 / fa 类名 / `<i>` HTML），
 * 但当 `name` 变化时，图标会用弹簧物理**形变**到新图标，而不是直接切换。
 * 适用于运行时会两两切换的图标：主题 sun↔moon、菜单 menu↔x、
 * 折叠 chevron-down↔up、播放 play↔pause 等。
 *
 * 原理：morphicons 是「形变动画引擎」，本身不提供图标；它消费 Tabler 的
 * 原始 IconNode 数据（`src/lib/tabler-icons.generated.ts` 的 ICON_MORPH_NODES，
 * 由映射表 _morphIcons 白名单驱动生成）。不在白名单里的图标自动降级为
 * StIcon 静态渲染，调用方可无脑替换。
 *
 * 默认尊重 `prefers-reduced-motion`（morphicons 出厂是 "never"，与本主题
 * 动效规范不符，故这里默认 "user"）。
 */
import { computed } from 'vue'
import { MorphIcon } from 'morphicons/vue'
import StIcon from './StIcon.vue'
import { resolveIconName } from '@/lib/icon-resolver'
import { ICON_MORPH_NODES } from '@/lib/tabler-icons.generated'

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
const morphNode = computed(() => ICON_MORPH_NODES[resolved.value.name])
</script>

<template>
  <MorphIcon
    v-if="morphNode"
    class="st-morph-icon"
    :icon="morphNode"
    :size="size"
    :stroke-width="stroke"
    :spring="spring"
    :reduced-motion="reducedMotion"
    :label="label"
  />
  <StIcon
    v-else
    class="st-morph-icon"
    :name="name"
    :size="size"
    :stroke="stroke"
  />
</template>

<style scoped>
/* 不设 display：调用方（如移动端顶栏的响应式显隐）需要用自己的 class 控制它。
 * 只做内联基线对齐，svg 默认即 inline。 */
.st-morph-icon {
  flex: none;
  vertical-align: -0.15em;
}
</style>
