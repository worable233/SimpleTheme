<script setup lang="ts">
defineProps<{
  settings: Record<string, unknown>
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
]
</script>

<template>
  <div class="sta-section">
    <h3 class="sta-section__title">卡片信息</h3>
    <p class="sta-section__desc">控制首页文章卡片上显示哪些元信息。</p>
    <div class="sta-grid">
      <div v-for="field in metaFields" :key="field.key" class="sta-field">
        <label class="sta-checkbox">
          <input
            type="checkbox"
            :checked="!!settings[field.key]"
            @change="emit('update', field.key, ($event.target as HTMLInputElement).checked)"
          />
          显示 {{ field.label }}
        </label>
      </div>
    </div>
  </div>
</template>
