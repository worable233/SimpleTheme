<script setup lang="ts">
import AppCard from './AppCard.vue'
import AppToggle from './AppToggle.vue'

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
]
</script>

<template>
  <!-- Card Meta -->
  <AppCard title="卡片信息" description="控制首页文章卡片上显示哪些元信息。">
    <div class="xh-grid">
      <div v-for="field in metaFields" :key="field.key" class="xh-field xh-field--compact">
        <AppToggle
          :modelValue="!!settings[field.key]"
          :label="`显示 ${field.label}`"
          @update:modelValue="emit('update', field.key, $event)"
        />
      </div>
    </div>
  </AppCard>

  <!-- Article Meta -->
  <AppCard title="文章页面信息" description="控制文章页面（标题下方）显示哪些元信息。">
    <div class="xh-grid">
      <div v-for="field in articleMetaFields" :key="field.key" class="xh-field xh-field--compact">
        <AppToggle
          :modelValue="!!settings[field.key]"
          :label="`显示 ${field.label}`"
          @update:modelValue="emit('update', field.key, $event)"
        />
      </div>
    </div>
  </AppCard>

  <!-- Reading Speed -->
  <AppCard title="阅读速度" description="用于估算文章阅读时间。">
    <div class="xh-field">
      <label class="xh-field__label">阅读速度（字/分钟）</label>
      <input
        type="number"
        class="xh-input xh-input--number"
        min="100" max="600"
        :value="(settings.reading_speed as number) || 300"
        @input="emit('update', 'reading_speed', Number(($event.target as HTMLInputElement).value))"
      />
    </div>
  </AppCard>
</template>
