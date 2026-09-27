<script setup lang="ts">
import { StButton, StCard, StIcon, StStack } from '@/ui'
import SettingsListEditor from './SettingsListEditor.vue'
import { useAdminFields } from '../useAdminFields'
import type { AdminSettings } from '../api'
import type { ListField } from './list-field'

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

const socialFields: ListField[] = [
  { key: 'label', label: '名称', type: 'text', placeholder: 'GitHub' },
  { key: 'url', label: '链接', type: 'text', placeholder: 'https://github.com/…' },
  { key: 'icon', label: '图标', type: 'icon', placeholder: 'brand-github' },
]

const techFields: ListField[] = [
  { key: 'label', label: '名称', type: 'text', placeholder: '运行天数' },
  { key: 'value', label: '内容', type: 'text', placeholder: '365' },
]
</script>

<template>
  <StStack :gap="5">
    <StCard
      title="侧边栏数据"
      subtitle="小工具的添加、排序与单个实例设置请在 WordPress 小工具编辑器完成；这里维护所有实例共享的全局数据。"
    >
      <StButton tag="a" href="widgets.php" size="small">
        <template #icon><StIcon name="layout-sidebar" :size="16" /></template>
        前往外观 → 小工具配置
      </StButton>
    </StCard>

    <StCard title="社交链接" subtitle="供「个人资料卡」小工具的社交区域使用。">
      <SettingsListEditor
        :model-value="fields.text('social_links')"
        :fields="socialFields"
        add-label="添加社交链接"
        empty-text="暂无社交链接，点击下方按钮添加。"
        @update:model-value="emit('update', 'social_links', $event)"
      />
    </StCard>

    <StCard title="技术信息" subtitle="供「站点信息」小工具使用，例如运行天数、建站时间。">
      <SettingsListEditor
        :model-value="fields.text('tech_info_items')"
        :fields="techFields"
        add-label="添加技术信息"
        empty-text="暂无技术信息，点击下方按钮添加。"
        @update:model-value="emit('update', 'tech_info_items', $event)"
      />
    </StCard>
  </StStack>
</template>
