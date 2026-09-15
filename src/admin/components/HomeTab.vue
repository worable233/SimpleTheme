<script setup lang="ts">
/**
 * 首页设置页：由 src/ui 基础组件拼装，不再手写旧后台的扁平类名。
 *
 * 每个开关自带文案，直接放进 StGrid 的格子里；不再套一层仅剩死类名的
 * 包装 div，避免"标签 + 开关"重复播报两遍同样的话。
 */
import { StCard, StFormItem, StGrid, StNumberInput, StSwitch } from '@/ui'

defineProps<{
  settings: Record<string, unknown>
  defaults: Record<string, unknown>
}>()

const emit = defineEmits<{
  (e: 'update', key: string, value: unknown): void
}>()

const metaFields = [
  { key: 'meta_show_category', label: '分类' },
  { key: 'meta_show_publish_date', label: '发布日期' },
  { key: 'meta_show_modified_date', label: '修改日期' },
  { key: 'meta_show_comment_count', label: '评论数' },
  { key: 'meta_show_view_count', label: '浏览量' },
  { key: 'meta_show_reading_time', label: '阅读时间' },
  { key: 'meta_show_word_count', label: '字数' },
  { key: 'meta_show_author', label: '作者' },
]

const articleMetaFields = [
  { key: 'article_meta_show_category', label: '分类' },
  { key: 'article_meta_show_publish_date', label: '发布日期' },
  { key: 'article_meta_show_modified_date', label: '修改日期' },
  { key: 'article_meta_show_comment_count', label: '评论数' },
  { key: 'article_meta_show_view_count', label: '浏览量' },
  { key: 'article_meta_show_reading_time', label: '阅读时间' },
  { key: 'article_meta_show_word_count', label: '字数' },
  { key: 'article_meta_show_author', label: '作者' },
  { key: 'article_meta_show_edit_link', label: '编辑文章（仅作者可见）' },
]
</script>

<template>
  <!-- Card Meta -->
  <StCard title="卡片信息" subtitle="控制首页文章卡片上显示哪些元信息。">
    <StGrid :cols="2" :gap="3">
      <StSwitch
        v-for="field in metaFields"
        :key="field.key"
        :model-value="!!settings[field.key]"
        @update:model-value="emit('update', field.key, $event)"
      >
        显示 {{ field.label }}
      </StSwitch>
    </StGrid>
  </StCard>

  <!-- Article Meta -->
  <StCard title="文章页面信息" subtitle="控制文章页面（标题下方）显示哪些元信息。">
    <StGrid :cols="2" :gap="3">
      <StSwitch
        v-for="field in articleMetaFields"
        :key="field.key"
        :model-value="!!settings[field.key]"
        @update:model-value="emit('update', field.key, $event)"
      >
        显示 {{ field.label }}
      </StSwitch>
    </StGrid>
  </StCard>

  <!-- Reading Speed -->
  <StCard title="阅读速度" subtitle="用于估算文章阅读时间。">
    <StFormItem label="阅读速度（字/分钟）">
      <StNumberInput
        :model-value="(settings.reading_speed as number) || 300"
        :min="100"
        :max="600"
        aria-label="阅读速度（字/分钟）"
        @update:model-value="emit('update', 'reading_speed', $event)"
      />
    </StFormItem>
  </StCard>
</template>
