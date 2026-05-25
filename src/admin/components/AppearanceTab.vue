<script setup lang="ts">
import AppCard from './AppCard.vue'
import AppColorPicker from './AppColorPicker.vue'
import AppToggle from './AppToggle.vue'

defineProps<{
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
</script>

<template>
  <!-- Primary Color -->
  <AppCard title="主题主色" description="主题主色将自动生成完整的 Material Design 3 配色方案。">
    <div class="xh-field" style="max-width: 280px;">
      <label class="xh-field__label">主色</label>
      <AppColorPicker
        :modelValue="(settings.primary_color as string) || ''"
        placeholder="#333333"
        @update:modelValue="emit('update', 'primary_color', $event)"
      />
    </div>
  </AppCard>

  <!-- Fonts -->
  <AppCard title="字体设置" description="全局字体用于正文和标题，代码字体对代码块、&lt;code&gt; 标签生效。">
    <div class="xh-grid">
      <div class="xh-field xh-field--compact">
        <label class="xh-field__label">全局字体</label>
        <input
          type="text"
          class="xh-input"
          :value="(settings.body_font as string) || ''"
          @input="emit('update', 'body_font', ($event.target as HTMLInputElement).value)"
        />
      </div>
      <div class="xh-field xh-field--compact">
        <label class="xh-field__label">代码字体</label>
        <input
          type="text"
          class="xh-input xh-input--mono"
          :value="(settings.code_font as string) || ''"
          @input="emit('update', 'code_font', ($event.target as HTMLInputElement).value)"
        />
      </div>
    </div>
  </AppCard>

  <!-- Light Colors -->
  <AppCard title="配色方案（浅色模式）" description="配置浅色模式下的背景、卡片、文字、强调和边框颜色。">
    <div class="xh-grid">
      <div v-for="field in lightColors" :key="field.key" class="xh-field xh-field--compact">
        <label class="xh-field__label">{{ field.label }}</label>
        <AppColorPicker
          :modelValue="(settings[field.key] as string) || ''"
          :placeholder="(defaults[field.key] as string) || '#cccccc'"
          @update:modelValue="emit('update', field.key, $event)"
        />
      </div>
    </div>
  </AppCard>

  <!-- Dark Colors -->
  <AppCard title="配色方案（深色模式）" description="配置深色模式下的对应颜色。">
    <div class="xh-grid">
      <div v-for="field in darkColors" :key="field.key" class="xh-field xh-field--compact">
        <label class="xh-field__label">{{ field.label }}</label>
        <AppColorPicker
          :modelValue="(settings[field.key] as string) || ''"
          :placeholder="(defaults[field.key] as string) || '#cccccc'"
          @update:modelValue="emit('update', field.key, $event)"
        />
      </div>
    </div>
  </AppCard>

  <!-- Radius & Shadow -->
  <AppCard title="圆角与阴影">
    <div class="xh-grid">
      <div class="xh-field xh-field--compact">
        <label class="xh-field__label">圆角大小</label>
        <select
          class="xh-select"
          :value="(settings.radius as string) || 'medium'"
          @change="emit('update', 'radius', ($event.target as HTMLSelectElement).value)"
        >
          <option value="small">小</option>
          <option value="medium">中</option>
          <option value="large">大</option>
        </select>
      </div>
      <div class="xh-field xh-field--compact">
        <label class="xh-field__label">阴影强度</label>
        <select
          class="xh-select"
          :value="(settings.shadow as string) || 'small'"
          @change="emit('update', 'shadow', ($event.target as HTMLSelectElement).value)"
        >
          <option value="none">无</option>
          <option value="small">轻</option>
          <option value="medium">中</option>
          <option value="large">重</option>
        </select>
      </div>
    </div>
  </AppCard>

  <!-- Layout -->
  <AppCard title="布局">
    <div class="xh-grid">
      <div class="xh-field xh-field--compact">
        <label class="xh-field__label">容器最大宽度 (px)</label>
        <input
          type="number"
          class="xh-input xh-input--number"
          min="960" max="1680" step="10"
          :value="(settings.container_max_width as number) || 1400"
          @input="emit('update', 'container_max_width', Number(($event.target as HTMLInputElement).value))"
        />
      </div>
      <div class="xh-field xh-field--compact">
        <label class="xh-field__label">文章最大宽度 (px)</label>
        <input
          type="number"
          class="xh-input xh-input--number"
          min="680" max="1200" step="10"
          :value="(settings.article_max_width as number) || 900"
          @input="emit('update', 'article_max_width', Number(($event.target as HTMLInputElement).value))"
        />
      </div>
    </div>
  </AppCard>

  <!-- Prism -->
  <AppCard title="代码高亮" description="使用 Prism.js 对文章中的代码块和行内代码进行语法高亮。">
    <AppToggle
      :modelValue="!!settings.enable_prism_highlight"
      label="启用代码高亮"
      @update:modelValue="emit('update', 'enable_prism_highlight', $event)"
    />
  </AppCard>
</template>
