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
import type { StOption } from '@/ui'

const props = defineProps<{
  settings: Record<string, unknown>
  defaults: Record<string, unknown>
}>()

const emit = defineEmits<{
  (e: 'update', key: string, value: unknown): void
}>()

/** settings 是扁平 Record<string, unknown>，这里收敛读值，模板里不再各写一次断言 */
function text(key: string) {
  return (props.settings[key] as string) || ''
}

/** 数字用 ?? 而非 ||：0 是合法值（如公告页面 ID），不能被当成空 */
function num(key: string, fallback: number) {
  return (props.settings[key] as number | null | undefined) ?? fallback
}

function flag(key: string) {
  return !!props.settings[key]
}

const redirectEnabledOptions: StOption[] = [
  { value: 'enabled', label: '开启' },
  { value: 'disabled', label: '关闭' },
]

const redirectTargetOptions: StOption[] = [
  { value: '_self', label: '当前窗口（_self）' },
  { value: '_blank', label: '新标签页（_blank）' },
  { value: '_parent', label: '父框架（_parent）' },
  { value: '_top', label: '整个窗口（_top）' },
]

const announcementModeOptions: StOption[] = [
  { value: 'modal', label: '弹窗' },
  { value: 'capsule', label: '胶囊横幅' },
]
</script>

<template>
  <!-- Collections / Home -->
  <StCard title="首页集合" subtitle="配置首页各区块的标题和显示数量。">
    <StStack :gap="5">
      <StSwitch
        :model-value="flag('show_shuoshuo_section')"
        @update:model-value="emit('update', 'show_shuoshuo_section', $event)"
      >
        显示说说板块
      </StSwitch>

      <StGrid :cols="2" :gap="4">
        <StFormItem label="文章区块标题">
          <StInput
            :model-value="text('posts_title')"
            @update:model-value="emit('update', 'posts_title', $event)"
          />
        </StFormItem>

        <StFormItem label="文章区块副标题">
          <StInput
            :model-value="text('posts_subtitle')"
            @update:model-value="emit('update', 'posts_subtitle', $event)"
          />
        </StFormItem>

        <template v-if="flag('show_shuoshuo_section')">
          <StFormItem label="说说区块标题">
            <StInput
              :model-value="text('shuoshuo_title')"
              @update:model-value="emit('update', 'shuoshuo_title', $event)"
            />
          </StFormItem>

          <StFormItem label="说说区块副标题">
            <StInput
              :model-value="text('shuoshuo_subtitle')"
              @update:model-value="emit('update', 'shuoshuo_subtitle', $event)"
            />
          </StFormItem>
        </template>

        <StFormItem label="首页文章数量">
          <StNumberInput
            :min="3"
            :max="20"
            aria-label="首页文章数量"
            :model-value="num('home_post_count', 6)"
            @update:model-value="emit('update', 'home_post_count', $event)"
          />
        </StFormItem>

        <template v-if="flag('show_shuoshuo_section')">
          <StFormItem label="首页说说数量">
            <StNumberInput
              :min="0"
              :max="12"
              aria-label="首页说说数量"
              :model-value="num('home_shuoshuo_count', 3)"
              @update:model-value="emit('update', 'home_shuoshuo_count', $event)"
            />
          </StFormItem>

          <StFormItem label="说说每页数量">
            <StNumberInput
              :min="6"
              :max="24"
              aria-label="说说每页数量"
              :model-value="num('shuoshuo_page_size', 12)"
              @update:model-value="emit('update', 'shuoshuo_page_size', $event)"
            />
          </StFormItem>
        </template>
      </StGrid>
    </StStack>
  </StCard>

  <!-- Performance -->
  <StCard title="性能" subtitle="性能与调试相关设置。">
    <StFormItem description="屏蔽插件（如 WPOPT）在浏览器控制台输出的广告/提示信息。">
      <StSwitch
        :model-value="flag('suppress_console_warnings')"
        @update:model-value="emit('update', 'suppress_console_warnings', $event)"
      >
        过滤控制台警告
      </StSwitch>
    </StFormItem>
  </StCard>

  <!-- External Link Redirect -->
  <StCard title="外链跳转" subtitle="控制文章中的外部链接是否经过本站提示页。">
    <StStack :gap="4">
      <StFormItem
        label="自动跳转"
        description="关闭后仍会显示外链确认页，但需要用户点击“继续前往”。"
      >
        <StSelect
          aria-label="自动跳转"
          :model-value="props.settings.external_redirect_enabled === false ? 'disabled' : 'enabled'"
          :options="redirectEnabledOptions"
          @update:model-value="emit('update', 'external_redirect_enabled', $event === 'enabled')"
        />
      </StFormItem>

      <StFormItem
        label="自动跳转等待时间（秒）"
        description="默认 5 秒，可设置为 1 到 30 秒。倒计时期间会显示提示 Toast。"
      >
        <StNumberInput
          :min="1"
          :max="30"
          aria-label="自动跳转等待时间（秒）"
          :model-value="num('external_redirect_delay', 5)"
          @update:model-value="emit('update', 'external_redirect_delay', $event)"
        />
      </StFormItem>

      <StFormItem
        label="目标窗口"
        description="决定确认页自动跳转和“继续前往”最终打开目标网站的位置。"
      >
        <StSelect
          aria-label="目标窗口"
          :model-value="text('external_redirect_target') || '_self'"
          :options="redirectTargetOptions"
          @update:model-value="emit('update', 'external_redirect_target', $event)"
        />
      </StFormItem>
    </StStack>
  </StCard>

  <!-- Local Avatars -->
  <StCard title="本地头像" subtitle="允许用户在个人资料页上传自定义头像替代 Gravatar。">
    <StFormItem
      description="开启后，用户可以在 wp-admin/profile.php 上传自己的头像，将不再依赖 Gravatar。"
    >
      <StSwitch
        :model-value="flag('local_avatars_enabled')"
        @update:model-value="emit('update', 'local_avatars_enabled', $event)"
      >
        启用本地头像
      </StSwitch>
    </StFormItem>
  </StCard>

  <!-- Admin Bar -->
  <StCard title="Admin Bar" subtitle="控制顶部工具栏的显示。">
    <StFormItem description="开启后，已登录用户在前台页面将不再显示 WordPress 顶部工具栏。">
      <StSwitch
        :model-value="flag('hide_admin_bar')"
        @update:model-value="emit('update', 'hide_admin_bar', $event)"
      >
        隐藏前台 Admin Bar
      </StSwitch>
    </StFormItem>
  </StCard>

  <!-- Announcement -->
  <StCard title="公告弹窗" subtitle="配置首页公告弹窗或胶囊横幅。">
    <StStack :gap="4">
      <StSwitch
        :model-value="flag('announcement_enabled')"
        @update:model-value="emit('update', 'announcement_enabled', $event)"
      >
        启用公告
      </StSwitch>

      <template v-if="flag('announcement_enabled')">
        <StFormItem label="显示模式">
          <StSelect
            aria-label="显示模式"
            :model-value="text('announcement_mode') || 'modal'"
            :options="announcementModeOptions"
            @update:model-value="emit('update', 'announcement_mode', $event)"
          />
        </StFormItem>

        <StFormItem label="页面 ID" description="指定要展示内容的 WordPress 页面 ID。">
          <StNumberInput
            :min="0"
            aria-label="页面 ID"
            :model-value="num('announcement_page_id', 0)"
            @update:model-value="emit('update', 'announcement_page_id', $event)"
          />
        </StFormItem>

        <StFormItem label="胶囊标题">
          <StInput
            :model-value="text('announcement_capsule_title')"
            @update:model-value="emit('update', 'announcement_capsule_title', $event)"
          />
        </StFormItem>

        <StFormItem label="图标" description="显示在胶囊标题前的 Emoji 或文本图标。">
          <StInput
            :model-value="text('announcement_icon')"
            @update:model-value="emit('update', 'announcement_icon', $event)"
          />
        </StFormItem>
      </template>
    </StStack>
  </StCard>

  <!-- Cookie Consent -->
  <StCard title="Cookie 同意" subtitle="配置 Cookie 同意横幅。">
    <StStack :gap="4">
      <StSwitch
        :model-value="flag('cookie_consent_enabled')"
        @update:model-value="emit('update', 'cookie_consent_enabled', $event)"
      >
        启用 Cookie 同意横幅
      </StSwitch>

      <StFormItem v-if="flag('cookie_consent_enabled')" label="提示文字">
        <StInput
          :model-value="text('cookie_consent_message')"
          @update:model-value="emit('update', 'cookie_consent_message', $event)"
        />
      </StFormItem>
    </StStack>
  </StCard>
</template>
