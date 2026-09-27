<script setup lang="ts">
import {
  StCard,
  StFormItem,
  StGrid,
  StImageUpload,
  StNumberInput,
  StStack,
  StSwitch,
  StTextarea,
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

const homeNumberFields: Array<[string, string, number, number]> = [
  ['home_post_count', '首页文章数量', 3, 20],
  ['shuoshuo_page_size', '说说每页数量', 6, 24],
]

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
  <StStack :gap="5">
    <StCard title="内容数量" subtitle="首页文章列表与说说页每页加载的数量。">
      <StGrid :cols="2" :gap="4">
        <StFormItem v-for="[key, label, min, max] in homeNumberFields" :key="key" :label="label">
          <StNumberInput
            :model-value="fields.number(key, Number(props.defaults[key] || min))"
            :min="min"
            :max="max"
            :aria-label="label"
            @update:model-value="emit('update', key, $event ?? Number(props.defaults[key] || min))"
          />
        </StFormItem>
      </StGrid>
    </StCard>

    <StCard title="首页封面" subtitle="配置首页封面区域的背景图、头像与描述语。">
      <StStack :gap="4">
        <StFormItem label="背景图">
          <StImageUpload
            :model-value="fields.text('hero_image')"
            placeholder="选择背景图"
            url-placeholder="或粘贴图片地址"
            aria-label="首页封面背景图"
            @update:model-value="emit('update', 'hero_image', $event)"
          />
        </StFormItem>
        <StSwitch
          :model-value="fields.checked('hero_show_avatar')"
          @update:model-value="emit('update', 'hero_show_avatar', $event)"
        >
          显示头像
        </StSwitch>
        <StFormItem v-if="fields.checked('hero_show_avatar')" label="头像">
          <StImageUpload
            :model-value="fields.text('hero_avatar')"
            placeholder="选择头像"
            url-placeholder="或粘贴图片地址"
            aspect="1 / 1"
            aria-label="首页封面头像"
            @update:model-value="emit('update', 'hero_avatar', $event)"
          />
        </StFormItem>
        <StFormItem label="描述语">
          <StTextarea
            :model-value="fields.text('hero_subtitle')"
            :rows="2"
            placeholder="输入描述语"
            aria-label="首页封面描述语"
            @update:model-value="emit('update', 'hero_subtitle', $event)"
          />
        </StFormItem>
      </StStack>
    </StCard>

    <StCard title="卡片信息" subtitle="控制首页文章卡片上显示哪些元信息。">
      <StGrid :cols="2" :gap="3">
        <StSwitch
          v-for="field in metaFields"
          :key="field.key"
          :model-value="fields.checked(field.key)"
          @update:model-value="emit('update', field.key, $event)"
        >
          显示 {{ field.label }}
        </StSwitch>
      </StGrid>
    </StCard>

    <StCard title="文章页面信息" subtitle="控制文章页标题下方显示哪些元信息。">
      <StGrid :cols="2" :gap="3">
        <StSwitch
          v-for="field in articleMetaFields"
          :key="field.key"
          :model-value="fields.checked(field.key)"
          @update:model-value="emit('update', field.key, $event)"
        >
          显示 {{ field.label }}
        </StSwitch>
      </StGrid>
    </StCard>

    <StCard title="阅读速度" subtitle="用于估算文章阅读时间。">
      <StFormItem label="阅读速度（字/分钟）">
        <StNumberInput
          :model-value="fields.number('reading_speed', 300)"
          :min="100"
          :max="600"
          aria-label="阅读速度"
          @update:model-value="emit('update', 'reading_speed', $event ?? 300)"
        />
      </StFormItem>
    </StCard>
  </StStack>
</template>
