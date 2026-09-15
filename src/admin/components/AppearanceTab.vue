<script setup lang="ts">
/**
 * 外观设置页：由 src/ui 基础组件拼装，不再手写旧后台的扁平类名。
 *
 * 旧后台的 field / grid 类名的 CSS 定义早已随 layers.css 删除，保留它们只会
 * 误导后来者以为有样式；这里统一换成 StFormItem / StGrid 的语义布局。
 */
import {
  StCard,
  StColorPicker,
  StFormItem,
  StGrid,
  StInput,
  StNumberInput,
  StSelect,
  StSwitch,
} from '@/ui'

const props = defineProps<{
  settings: Record<string, unknown>
  defaults: Record<string, unknown>
}>()

const emit = defineEmits<{
  (e: 'update', key: string, value: unknown): void
}>()

const lightColors = [
  { key: 'background_light', label: '背景色' },
  { key: 'card_light', label: '卡片背景' },
  { key: 'foreground_light', label: '文字颜色' },
  { key: 'accent_light', label: '强调色' },
  { key: 'border_light', label: '边框色' },
]

const darkColors = [
  { key: 'background_dark', label: '背景色' },
  { key: 'card_dark', label: '卡片背景' },
  { key: 'foreground_dark', label: '文字颜色' },
  { key: 'accent_dark', label: '强调色' },
  { key: 'border_dark', label: '边框色' },
]

const radiusOptions = [
  { value: 'small', label: '小' },
  { value: 'medium', label: '中' },
  { value: 'large', label: '大' },
]

const shadowOptions = [
  { value: 'none', label: '无' },
  { value: 'small', label: '轻' },
  { value: 'medium', label: '中' },
  { value: 'large', label: '重' },
]

/**
 * StColorPicker 没有旧 AppColorPicker 的 placeholder/fallback 能力，
 * 空值兜底改由调用方拼在 model 上：设置值 → 默认值 → 保底字面量。
 */
function colorValue(key: string, fallback: string) {
  return (props.settings[key] as string) || (props.defaults[key] as string) || fallback
}
</script>

<template>
  <!-- Primary Color -->
  <StCard title="主题主色" subtitle="主题主色将自动生成完整的 Material Design 3 配色方案。">
    <StFormItem label="主色">
      <StColorPicker
        :model-value="(settings.primary_color as string) || '#333333'"
        aria-label="主色"
        @update:model-value="emit('update', 'primary_color', $event)"
      />
    </StFormItem>
  </StCard>

  <!-- Fonts -->
  <StCard
    title="字体设置"
    subtitle="全局字体用于正文和标题，代码字体对代码块、&lt;code&gt; 标签生效。"
  >
    <StGrid :cols="2" :gap="4">
      <StFormItem label="全局字体">
        <StInput
          :model-value="(settings.body_font as string) || ''"
          aria-label="全局字体"
          @update:model-value="emit('update', 'body_font', $event)"
        />
      </StFormItem>
      <StFormItem label="代码字体">
        <StInput
          :model-value="(settings.code_font as string) || ''"
          mono
          aria-label="代码字体"
          @update:model-value="emit('update', 'code_font', $event)"
        />
      </StFormItem>
    </StGrid>
  </StCard>

  <!-- Light Colors -->
  <StCard
    title="配色方案（浅色模式）"
    subtitle="配置浅色模式下的背景、卡片、文字、强调和边框颜色。"
  >
    <StGrid :cols="2" :gap="4">
      <StFormItem v-for="field in lightColors" :key="field.key" :label="field.label">
        <StColorPicker
          :model-value="colorValue(field.key, '#cccccc')"
          :aria-label="field.label"
          @update:model-value="emit('update', field.key, $event)"
        />
      </StFormItem>
    </StGrid>
  </StCard>

  <!-- Dark Colors -->
  <StCard title="配色方案（深色模式）" subtitle="配置深色模式下的对应颜色。">
    <StGrid :cols="2" :gap="4">
      <StFormItem v-for="field in darkColors" :key="field.key" :label="field.label">
        <StColorPicker
          :model-value="colorValue(field.key, '#cccccc')"
          :aria-label="field.label"
          @update:model-value="emit('update', field.key, $event)"
        />
      </StFormItem>
    </StGrid>
  </StCard>

  <!-- Radius & Shadow -->
  <StCard title="圆角与阴影">
    <StGrid :cols="2" :gap="4">
      <StFormItem label="圆角大小">
        <StSelect
          :model-value="(settings.radius as string) || 'medium'"
          :options="radiusOptions"
          aria-label="圆角大小"
          @update:model-value="emit('update', 'radius', $event)"
        />
      </StFormItem>
      <StFormItem label="阴影强度">
        <StSelect
          :model-value="(settings.shadow as string) || 'small'"
          :options="shadowOptions"
          aria-label="阴影强度"
          @update:model-value="emit('update', 'shadow', $event)"
        />
      </StFormItem>
    </StGrid>
  </StCard>

  <!-- Layout -->
  <StCard title="布局">
    <StGrid :cols="2" :gap="4">
      <StFormItem label="容器最大宽度 (px)">
        <StNumberInput
          :model-value="(settings.container_max_width as number) || 1500"
          :min="960"
          :max="2000"
          :step="10"
          aria-label="容器最大宽度"
          @update:model-value="emit('update', 'container_max_width', $event)"
        />
      </StFormItem>
      <StFormItem label="文章最大宽度 (px)">
        <StNumberInput
          :model-value="(settings.article_max_width as number) || 900"
          :min="680"
          :max="1200"
          :step="10"
          aria-label="文章最大宽度"
          @update:model-value="emit('update', 'article_max_width', $event)"
        />
      </StFormItem>
    </StGrid>
  </StCard>

  <!-- Prism -->
  <StCard title="代码高亮" subtitle="使用 Prism.js 对文章中的代码块和行内代码进行语法高亮。">
    <StSwitch
      :model-value="!!settings.enable_prism_highlight"
      @update:model-value="emit('update', 'enable_prism_highlight', $event)"
    >
      启用代码高亮
    </StSwitch>
  </StCard>
</template>
