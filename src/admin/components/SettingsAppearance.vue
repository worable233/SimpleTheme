<script setup lang="ts">
import {
  StCard,
  StColorPicker,
  StFormItem,
  StGrid,
  StInput,
  StNumberInput,
  StSelect,
  StStack,
  StSwitch,
} from '@/ui'
import { useAdminFields } from '../useAdminFields'
import type { AdminSettings } from '../api'

const props = defineProps<{
  settings: AdminSettings
  defaults: AdminSettings
}>()

const emit = defineEmits<{
  (e: 'update', key: string, value: unknown): void
}>()

const fields = useAdminFields(
  () => props.settings,
  () => props.defaults,
)

const lightColors: Array<[string, string]> = [
  ['background_light', '背景色'],
  ['card_light', '卡片背景'],
  ['foreground_light', '文字颜色'],
  ['accent_light', '强调色'],
  ['border_light', '边框色'],
]
const darkColors: Array<[string, string]> = [
  ['background_dark', '背景色'],
  ['card_dark', '卡片背景'],
  ['foreground_dark', '文字颜色'],
  ['accent_dark', '强调色'],
  ['border_dark', '边框色'],
]
const colorGroups: Array<[string, Array<[string, string]>]> = [
  ['浅色模式', lightColors],
  ['深色模式', darkColors],
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
</script>

<template>
  <StStack :gap="5">
    <StCard
      title="主色与字体"
      subtitle="主色自动生成 Material Design 3 配色；字体对正文、标题与代码块生效。"
    >
      <StStack :gap="4">
        <StFormItem label="主色">
          <StColorPicker
            :model-value="fields.text('primary_color', '#333333')"
            aria-label="主色"
            @update:model-value="emit('update', 'primary_color', $event)"
          />
        </StFormItem>
        <StFormItem label="全局字体">
          <StInput
            :model-value="fields.text('body_font')"
            aria-label="全局字体"
            @update:model-value="emit('update', 'body_font', $event)"
          />
        </StFormItem>
        <StFormItem label="代码字体">
          <StInput
            :model-value="fields.text('code_font')"
            mono
            aria-label="代码字体"
            @update:model-value="emit('update', 'code_font', $event)"
          />
        </StFormItem>
      </StStack>
    </StCard>

    <StCard title="圆角与阴影" subtitle="作用于卡片、按钮、浮层等所有主题表面。">
      <StGrid :cols="2" :gap="4">
        <StFormItem label="圆角大小">
          <StSelect
            :model-value="fields.text('radius', 'medium')"
            :options="radiusOptions"
            aria-label="圆角大小"
            @update:model-value="emit('update', 'radius', $event)"
          />
        </StFormItem>
        <StFormItem label="阴影强度">
          <StSelect
            :model-value="fields.text('shadow', 'small')"
            :options="shadowOptions"
            aria-label="阴影强度"
            @update:model-value="emit('update', 'shadow', $event)"
          />
        </StFormItem>
      </StGrid>
    </StCard>

    <StCard title="布局" subtitle="页面容器与文章正文的宽度约束（单位 px）。">
      <StGrid :cols="2" :gap="4">
        <StFormItem label="容器最大宽度">
          <StNumberInput
            :model-value="fields.number('container_max_width', 1500)"
            :min="960"
            :max="2000"
            :step="10"
            aria-label="容器最大宽度"
            @update:model-value="emit('update', 'container_max_width', $event ?? 1500)"
          />
        </StFormItem>
        <StFormItem label="文章最大宽度">
          <StNumberInput
            :model-value="fields.number('article_max_width', 900)"
            :min="680"
            :max="1200"
            :step="10"
            aria-label="文章最大宽度"
            @update:model-value="emit('update', 'article_max_width', $event ?? 900)"
          />
        </StFormItem>
      </StGrid>
    </StCard>

    <StCard title="配色" subtitle="浅色与深色两套语义色板。">
      <StGrid :cols="2" :gap="6">
        <div v-for="[title, colors] in colorGroups" :key="title" class="settings-palette">
          <h3 class="settings-palette__title">{{ title }}</h3>
          <StStack :gap="3">
            <StFormItem
              v-for="[key, label] in colors"
              :key="key"
              :label="label"
              label-placement="left"
              label-width="64px"
            >
              <StColorPicker
                :model-value="fields.text(key, String(props.defaults[key] || '#ffffff'))"
                :aria-label="label"
                @update:model-value="emit('update', key, $event)"
              />
            </StFormItem>
          </StStack>
        </div>
      </StGrid>
    </StCard>

    <StCard title="代码高亮" subtitle="使用 Prism.js 对文章中的代码块与行内代码做语法高亮。">
      <StSwitch
        :model-value="fields.checked('enable_prism_highlight')"
        @update:model-value="emit('update', 'enable_prism_highlight', $event)"
      >
        启用代码高亮
      </StSwitch>
    </StCard>
  </StStack>
</template>

<style scoped>
.settings-palette {
  min-width: 0;
}

.settings-palette__title {
  margin: 0 0 12px;
  font-size: 13px;
  font-weight: 600;
  line-height: 1.4;
  color: var(--foreground);
}
</style>
