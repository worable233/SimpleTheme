<script setup lang="ts">
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
  <div class="sta-section">
    <h3 class="sta-section__title">主题主色</h3>
    <p class="sta-section__desc">主题主色将自动生成完整的 Material Design 3 配色方案。</p>
    <div class="sta-field">
      <label class="sta-field__label">主色</label>
      <div class="sta-color-row">
        <input
          type="color"
          class="sta-input sta-input--color-picker"
          :value="(settings.primary_color as string) || '#333333'"
          @input="emit('update', 'primary_color', ($event.target as HTMLInputElement).value)"
        />
        <input
          type="text"
          class="sta-input sta-input--color-text"
          :value="(settings.primary_color as string) || ''"
          placeholder="#333333"
          maxlength="7"
          @input="emit('update', 'primary_color', ($event.target as HTMLInputElement).value)"
        />
      </div>
    </div>
  </div>

  <div class="sta-section">
    <h3 class="sta-section__title">字体设置</h3>
    <p class="sta-section__desc">配置正文字体和标题字体的 font-family。</p>
    <div class="sta-grid">
      <div class="sta-field">
        <label class="sta-field__label">正文字体</label>
        <input
          type="text"
          class="sta-input"
          :value="(settings.body_font as string) || ''"
          @input="emit('update', 'body_font', ($event.target as HTMLInputElement).value)"
        />
      </div>
      <div class="sta-field">
        <label class="sta-field__label">标题字体</label>
        <input
          type="text"
          class="sta-input"
          :value="(settings.heading_font as string) || ''"
          @input="emit('update', 'heading_font', ($event.target as HTMLInputElement).value)"
        />
      </div>
    </div>
  </div>

  <div class="sta-section">
    <h3 class="sta-section__title">配色方案（浅色模式）</h3>
    <p class="sta-section__desc">配置浅色模式下的背景、卡片、文字、强调和边框颜色。</p>
    <div class="sta-grid">
      <div v-for="field in lightColors" :key="field.key" class="sta-field">
        <label class="sta-field__label">{{ field.label }}</label>
        <div class="sta-color-row">
          <input
            type="color"
            class="sta-input sta-input--color-picker"
            :value="((settings[field.key] as string) || String(defaults[field.key] || '')) || '#ffffff'"
            @input="emit('update', field.key, ($event.target as HTMLInputElement).value)"
          />
          <input
            type="text"
            class="sta-input sta-input--color-text"
            :value="(settings[field.key] as string) || ''"
            placeholder="#cccccc"
            maxlength="7"
            @input="emit('update', field.key, ($event.target as HTMLInputElement).value)"
          />
        </div>
      </div>
    </div>
  </div>

  <div class="sta-section">
    <h3 class="sta-section__title">配色方案（深色模式）</h3>
    <p class="sta-section__desc">配置深色模式下的对应颜色。</p>
    <div class="sta-grid">
      <div v-for="field in darkColors" :key="field.key" class="sta-field">
        <label class="sta-field__label">{{ field.label }}</label>
        <div class="sta-color-row">
          <input
            type="color"
            class="sta-input sta-input--color-picker"
            :value="((settings[field.key] as string) || String(defaults[field.key] || '')) || '#222222'"
            @input="emit('update', field.key, ($event.target as HTMLInputElement).value)"
          />
          <input
            type="text"
            class="sta-input sta-input--color-text"
            :value="(settings[field.key] as string) || ''"
            placeholder="#cccccc"
            maxlength="7"
            @input="emit('update', field.key, ($event.target as HTMLInputElement).value)"
          />
        </div>
      </div>
    </div>
  </div>

  <div class="sta-section">
    <h3 class="sta-section__title">圆角与阴影</h3>
    <div class="sta-grid">
      <div class="sta-field">
        <label class="sta-field__label">圆角大小</label>
        <select
          class="sta-select"
          :value="(settings.radius as string) || 'medium'"
          @change="emit('update', 'radius', ($event.target as HTMLSelectElement).value)"
        >
          <option value="small">小</option>
          <option value="medium">中</option>
          <option value="large">大</option>
        </select>
      </div>
      <div class="sta-field">
        <label class="sta-field__label">阴影强度</label>
        <select
          class="sta-select"
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
  </div>

  <div class="sta-section">
    <h3 class="sta-section__title">布局</h3>
    <div class="sta-grid">
      <div class="sta-field">
        <label class="sta-field__label">容器最大宽度 (px)</label>
        <input
          type="number"
          class="sta-input sta-input--number"
          min="960" max="1680" step="10"
          :value="(settings.container_max_width as number) || 1400"
          @input="emit('update', 'container_max_width', Number(($event.target as HTMLInputElement).value))"
        />
      </div>
      <div class="sta-field">
        <label class="sta-field__label">文章最大宽度 (px)</label>
        <input
          type="number"
          class="sta-input sta-input--number"
          min="680" max="1200" step="10"
          :value="(settings.article_max_width as number) || 900"
          @input="emit('update', 'article_max_width', Number(($event.target as HTMLInputElement).value))"
        />
      </div>
    </div>
  </div>
</template>
