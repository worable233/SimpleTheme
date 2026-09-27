<script setup lang="ts">
import { StCard, StFormItem, StGrid, StInput, StSelect, StStack, StSwitch } from '@/ui'
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

const commentOrderOptions = [
  { value: 'asc', label: '正序（最早在上）' },
  { value: 'desc', label: '倒序（最新在上）' },
]

const ipLocationApiOptions = [
  { value: 'xinyew', label: '鑫烨（百度渠道）' },
  { value: 'ip.sb', label: 'IP.SB（海外）' },
  { value: 'ip-api.com', label: 'ip-api.com（海外）' },
]
</script>

<template>
  <StStack :gap="5">
    <StCard title="评论表单" subtitle="控制前台评论表单提供哪些选项。">
      <StGrid :cols="2" :gap="3">
        <StSwitch
          :model-value="fields.checked('comment_show_cookies', true)"
          @update:model-value="emit('update', 'comment_show_cookies', $event)"
        >
          显示 Cookie 保存选项
        </StSwitch>
        <StSwitch
          :model-value="fields.checked('comment_captcha_enabled')"
          @update:model-value="emit('update', 'comment_captcha_enabled', $event)"
        >
          启用评论验证码
        </StSwitch>
        <StSwitch
          :model-value="fields.checked('comment_show_private', true)"
          @update:model-value="emit('update', 'comment_show_private', $event)"
        >
          显示私密评论选项
        </StSwitch>
        <StSwitch
          :model-value="fields.checked('comment_show_markdown', true)"
          @update:model-value="emit('update', 'comment_show_markdown', $event)"
        >
          支持 Markdown
        </StSwitch>
      </StGrid>
    </StCard>

    <StCard title="展示与头像" subtitle="评论排序方式与 Gravatar 头像 CDN。">
      <StStack :gap="4">
        <StFormItem label="评论排序方式">
          <StSelect
            :model-value="fields.text('comment_order', 'asc')"
            :options="commentOrderOptions"
            aria-label="评论排序方式"
            @update:model-value="emit('update', 'comment_order', $event)"
          />
        </StFormItem>
        <StFormItem label="Gravatar 基础 URL">
          <StInput
            :model-value="fields.text('gravatar_base_url')"
            placeholder="https://secure.gravatar.com/avatar/"
            aria-label="Gravatar 基础 URL"
            @update:model-value="emit('update', 'gravatar_base_url', $event)"
          />
        </StFormItem>
      </StStack>
    </StCard>

    <StCard
      title="IP 归属地"
      subtitle="评论提交时 WordPress 已记录访客 IP，前台显示时按此处配置解析并缓存。"
    >
      <StStack :gap="4">
        <StFormItem label="解析接口">
          <StSelect
            :model-value="fields.text('ip_location_api', 'xinyew')"
            :options="ipLocationApiOptions"
            aria-label="解析接口"
            @update:model-value="emit('update', 'ip_location_api', $event)"
          />
        </StFormItem>
        <StSwitch
          :model-value="fields.checked('ip_location_cache', true)"
          @update:model-value="emit('update', 'ip_location_cache', $event)"
        >
          启用 IP 归属地缓存（永久缓存）
        </StSwitch>
      </StStack>
    </StCard>
  </StStack>
</template>
