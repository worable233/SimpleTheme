<script setup lang="ts">
/**
 * unDraw 插画组件 — 构建时内联 SVG，无运行时 HTTP 请求
 *
 * 使用:
 *   <UndrawIllustration name="team-spirit" width="400" />
 *   <UndrawIllustration name="programming" width="100%" height="300px" />
 *
 * 可用插画名:
 *   参见 public/illustrations/ 目录或浏览 https://undraw.co/illustrations
 */
import { ref, computed, watch, onMounted } from 'vue'
import { getIllustration } from '@/data/illustrations'

const props = withDefaults(
  defineProps<{
    /** 插画名称（不含 .svg 后缀） */
    name: string
    /** 宽度，数字单位为 px */
    width?: string | number
    /** 高度，数字单位为 px */
    height?: string | number
    /** CSS class */
    svgClass?: string
  }>(),
  {
    width: '100%',
    height: 'auto',
    svgClass: '',
  },
)

const emit = defineEmits<{
  load: []
  error: [message: string]
}>()

const svgContent = ref<string>('')
const loading = ref(true)
const hasError = ref(false)

const containerStyle = computed(() => ({
  width: typeof props.width === 'number' ? `${props.width}px` : props.width,
  height: typeof props.height === 'number' ? `${props.height}px` : props.height,
}))

function loadIllustration(name: string) {
  if (!name) {
    hasError.value = true
    loading.value = false
    svgContent.value = ''
    return
  }

  loading.value = true
  hasError.value = false

  const content = getIllustration(name)
  if (content) {
    svgContent.value = content
    loading.value = false
    emit('load')
  } else {
    console.error(`[UndrawIllustration] 找不到插画: ${name}`)
    hasError.value = true
    loading.value = false
    emit('error', `找不到插画: ${name}`)
  }
}

watch(() => props.name, (newName) => {
  loadIllustration(newName)
})

onMounted(() => {
  loadIllustration(props.name)
})
</script>

<template>
  <div
    class="undraw-illustration"
    :class="[svgClass, { 'undraw-illustration--loading': loading, 'undraw-illustration--error': hasError }]"
    :style="containerStyle"
    role="img"
    aria-hidden="true"
  >
    <!-- 加载中占位 -->
    <div v-if="loading" class="undraw-illustration__placeholder">
      <div class="undraw-illustration__skeleton" />
    </div>

    <!-- 错误占位 -->
    <div v-else-if="hasError" class="undraw-illustration__placeholder">
      <span class="undraw-illustration__error-icon">🖼</span>
    </div>

    <!-- SVG 内容 -->
    <div v-else v-html="svgContent" class="undraw-illustration__svg" />
  </div>
</template>

<style scoped>
.undraw-illustration {
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  line-height: 0;
}

.undraw-illustration__svg {
  width: 100%;
  height: 100%;
}

.undraw-illustration__svg :deep(svg) {
  width: 100%;
  height: 100%;
  max-height: inherit;
}

.undraw-illustration__placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  min-height: 60px;
}

.undraw-illustration__skeleton {
  width: 60%;
  height: 60%;
  border-radius: 8px;
  background: linear-gradient(
    90deg,
    transparent 0%,
    rgba(128, 128, 128, 0.15) 50%,
    transparent 100%
  );
  background-size: 200% 100%;
  animation: undraw-shimmer 1.5s infinite;
}

.undraw-illustration__error-icon {
  font-size: 2em;
  opacity: 0.3;
}

@keyframes undraw-shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}
</style>
