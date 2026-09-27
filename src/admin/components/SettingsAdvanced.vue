<script setup lang="ts">
import {
  StCard,
  StFormItem,
  StGrid,
  StInput,
  StNumberInput,
  StSelect,
  StStack,
  StSwitch,
} from '@/ui'
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

const redirectEnabledOptions = [
  { value: 'enabled', label: '开启' },
  { value: 'disabled', label: '关闭' },
]

const redirectTargetOptions = [
  { value: '_self', label: '当前窗口（_self）' },
  { value: '_blank', label: '新标签页（_blank）' },
  { value: '_parent', label: '父框架（_parent）' },
  { value: '_top', label: '整个窗口（_top）' },
]

const announcementModeOptions = [
  { value: 'modal', label: '弹窗' },
  { value: 'capsule', label: '胶囊横幅' },
]

const buttonFields: ListField[] = [
  { key: 'text', label: '按钮文字', type: 'text', placeholder: '知道了' },
  {
    key: 'action',
    label: '行为',
    type: 'select',
    options: [
      { value: 'close', label: '关闭弹窗' },
      { value: 'link', label: '跳转链接' },
    ],
  },
  { key: 'url', label: '链接', type: 'text', placeholder: 'https://…（行为为跳转时必填）' },
]
</script>

<template>
  <StStack :gap="5">
    <StCard title="外链跳转" subtitle="文章外链确认页的自动跳转行为。">
      <StGrid :cols="3" :gap="4">
        <StFormItem label="自动跳转">
          <StSelect
            :model-value="
              fields.checked('external_redirect_enabled', true) ? 'enabled' : 'disabled'
            "
            :options="redirectEnabledOptions"
            aria-label="自动跳转"
            @update:model-value="emit('update', 'external_redirect_enabled', $event === 'enabled')"
          />
        </StFormItem>
        <StFormItem label="等待时间（秒）">
          <StNumberInput
            :model-value="fields.number('external_redirect_delay', 5)"
            :min="1"
            :max="30"
            aria-label="等待时间（秒）"
            @update:model-value="emit('update', 'external_redirect_delay', $event ?? 5)"
          />
        </StFormItem>
        <StFormItem label="目标窗口">
          <StSelect
            :model-value="fields.text('external_redirect_target', '_self')"
            :options="redirectTargetOptions"
            aria-label="目标窗口"
            @update:model-value="emit('update', 'external_redirect_target', $event)"
          />
        </StFormItem>
      </StGrid>
    </StCard>

    <StCard title="公告" subtitle="首页公告弹窗或胶囊横幅。">
      <StStack :gap="4">
        <StSwitch
          :model-value="fields.checked('announcement_enabled')"
          @update:model-value="emit('update', 'announcement_enabled', $event)"
        >
          启用公告
        </StSwitch>
        <template v-if="fields.checked('announcement_enabled')">
          <StGrid :cols="2" :gap="4">
            <StFormItem label="显示模式">
              <StSelect
                :model-value="fields.text('announcement_mode', 'modal')"
                :options="announcementModeOptions"
                aria-label="显示模式"
                @update:model-value="emit('update', 'announcement_mode', $event)"
              />
            </StFormItem>
            <StFormItem label="页面 ID" description="要展示内容的 WordPress 页面 ID。">
              <StNumberInput
                :model-value="fields.number('announcement_page_id', 0)"
                :min="0"
                aria-label="公告页面 ID"
                @update:model-value="emit('update', 'announcement_page_id', $event ?? 0)"
              />
            </StFormItem>
            <StFormItem label="胶囊标题">
              <StInput
                :model-value="fields.text('announcement_capsule_title')"
                aria-label="胶囊标题"
                @update:model-value="emit('update', 'announcement_capsule_title', $event)"
              />
            </StFormItem>
            <StFormItem label="图标" description="显示在胶囊标题前的 Emoji 或文本图标。">
              <StInput
                :model-value="fields.text('announcement_icon')"
                aria-label="公告图标"
                @update:model-value="emit('update', 'announcement_icon', $event)"
              />
            </StFormItem>
          </StGrid>
          <StFormItem description="开启后每位访客每次访问都展示；关闭则被关闭一次后不再自动出现。">
            <StSwitch
              :model-value="fields.checked('announcement_always_show')"
              @update:model-value="emit('update', 'announcement_always_show', $event)"
            >
              每次都展示
            </StSwitch>
          </StFormItem>
          <SettingsListEditor
            :model-value="fields.text('announcement_buttons')"
            :fields="buttonFields"
            add-label="添加按钮"
            empty-text="暂无按钮，点击下方按钮添加。"
            @update:model-value="emit('update', 'announcement_buttons', $event)"
          />
        </template>
      </StStack>
    </StCard>

    <StCard title="Cookie 同意" subtitle="访问者首次进入时展示的 Cookie 同意横幅。">
      <StStack :gap="4">
        <StSwitch
          :model-value="fields.checked('cookie_consent_enabled')"
          @update:model-value="emit('update', 'cookie_consent_enabled', $event)"
        >
          启用 Cookie 同意横幅
        </StSwitch>
        <StFormItem v-if="fields.checked('cookie_consent_enabled')" label="提示文字">
          <StInput
            :model-value="fields.text('cookie_consent_message')"
            aria-label="Cookie 提示文字"
            @update:model-value="emit('update', 'cookie_consent_message', $event)"
          />
        </StFormItem>
      </StStack>
    </StCard>

    <StCard title="后台与用户" subtitle="WordPress 后台外观及用户头像相关开关。">
      <StStack :gap="4">
        <StSwitch
          :model-value="fields.checked('admin_theme_enabled')"
          @update:model-value="emit('update', 'admin_theme_enabled', $event)"
        >
          启用后台美化
        </StSwitch>
        <StSwitch
          :model-value="fields.checked('hide_admin_bar')"
          @update:model-value="emit('update', 'hide_admin_bar', $event)"
        >
          隐藏前台 Admin Bar
        </StSwitch>
        <StSwitch
          :model-value="fields.checked('local_avatars_enabled')"
          @update:model-value="emit('update', 'local_avatars_enabled', $event)"
        >
          启用本地头像
        </StSwitch>
      </StStack>
    </StCard>

    <StCard title="性能" subtitle="性能与调试相关设置。">
      <StSwitch
        :model-value="fields.checked('suppress_console_warnings')"
        @update:model-value="emit('update', 'suppress_console_warnings', $event)"
      >
        过滤控制台警告
      </StSwitch>
    </StCard>
  </StStack>
</template>
