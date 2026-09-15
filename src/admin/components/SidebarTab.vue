<script setup lang="ts">
import {
  StButton,
  StCard,
  StFormItem,
  StGrid,
  StImageUpload,
  StInput,
  StSelect,
  StStack,
  StSwitch,
  StTextarea,
} from '@/ui'
import type { StOption } from '@/ui'

defineProps<{
  settings: Record<string, unknown>
  defaults: Record<string, unknown>
}>()

const emit = defineEmits<{
  (e: 'update', key: string, value: unknown): void
}>()

// reka-ui 的 SelectItem value 不能是空字符串，这里的 value 都是哨兵值。
const commentOrderOptions: StOption[] = [
  { value: 'asc', label: '正序：最早发布的评论在上方，回复按对话顺序阅读' },
  { value: 'desc', label: '倒序：最新发布的评论在上方，优先看到最新讨论' },
]

const ipLocationOptions: StOption[] = [
  { value: 'xinyew', label: '新野 API（百度数据）' },
  { value: 'ip.sb', label: 'ip.sb' },
  { value: 'ip-api.com', label: 'ip-api.com' },
]

const copyrightStyleOptions: StOption[] = [
  { value: 'detailed', label: '详细 — Copyright © 2026 站点名称 All Rights Reserved.' },
  { value: 'simple', label: '简洁 — 2026 © 站点名称.' },
  { value: 'none', label: '不显示' },
]

const articleLicenseOptions: StOption[] = [
  { value: 'cc-by-40', label: 'CC BY 4.0 — 署名' },
  { value: 'cc-by-sa-40', label: 'CC BY-SA 4.0 — 署名-相同方式共享' },
  { value: 'cc-by-nc-40', label: 'CC BY-NC 4.0 — 署名-非商业使用' },
  { value: 'cc-by-nc-sa-40', label: 'CC BY-NC-SA 4.0 — 署名-非商业使用-相同方式共享' },
  { value: 'cc-by-nc-nd-40', label: 'CC BY-NC-ND 4.0 — 署名-非商业使用-禁止演绎' },
  { value: 'cc-by-nd-40', label: 'CC BY-ND 4.0 — 署名-禁止演绎' },
  { value: 'arr', label: 'ARR — 保留所有权利' },
  { value: 'none', label: '不显示' },
]
</script>

<template>
  <!-- Hero / Profile Card -->
  <StCard title="个人信息卡片" subtitle="配置首页封面区域的背景图和头像等信息。">
    <StStack :gap="4" block>
      <StFormItem label="背景图">
        <StImageUpload
          :model-value="(settings.hero_image as string) || ''"
          placeholder="背景图"
          url-placeholder="输入背景图 URL 或点击选择"
          aria-label="背景图"
          @update:model-value="emit('update', 'hero_image', $event)"
        />
      </StFormItem>

      <StFormItem>
        <StSwitch
          :model-value="settings.hero_show_avatar === true"
          @update:model-value="emit('update', 'hero_show_avatar', $event)"
        >
          显示头像
        </StSwitch>
      </StFormItem>

      <StFormItem v-if="settings.hero_show_avatar === true" label="头像">
        <StImageUpload
          :model-value="(settings.hero_avatar as string) || ''"
          placeholder="头像"
          url-placeholder="输入头像 URL 或点击选择"
          aspect="1 / 1"
          aria-label="头像"
          @update:model-value="emit('update', 'hero_avatar', $event)"
        />
      </StFormItem>

      <StFormItem label="描述语">
        <StTextarea
          :model-value="(settings.hero_subtitle as string) || ''"
          placeholder="输入描述语"
          aria-label="描述语"
          @update:model-value="emit('update', 'hero_subtitle', $event)"
        />
      </StFormItem>
    </StStack>
  </StCard>

  <!-- Sidebar Widgets (global data stays here; per-instance options live in WP Widgets) -->
  <StCard
    title="侧边栏数据与小工具"
    subtitle="小工具的添加、排序和单个实例设置请在 WordPress 小工具编辑器中完成；这里仅维护所有小工具共享的全局数据。"
  >
    <StStack :gap="4" block>
      <StFormItem
        description="拖拽“主题：个人资料卡 / 一言 / 站点信息”到“右侧栏”，并在每个实例展开面板后配置显示项或 API。下面的社交链接、技术信息会被所有对应实例共享。"
      >
        <StButton tag="a" href="widgets.php" type="primary">前往外观 → 小工具配置</StButton>
      </StFormItem>

      <StFormItem
        label="社交链接"
        description='供“个人资料卡”小工具的社交区域使用。JSON 数组格式：{ "label": "...", "url": "...", "icon": "..." }'
      >
        <StTextarea
          :model-value="(settings.social_links as string) || ''"
          placeholder='[{"label":"GitHub","url":"https://github.com/...","icon":"github"}]'
          aria-label="社交链接"
          @update:model-value="emit('update', 'social_links', $event)"
        />
      </StFormItem>

      <StFormItem
        label="技术信息"
        description='供“站点信息”小工具使用。JSON 数组格式：{ "label": "...", "value": "..." }'
      >
        <StTextarea
          :model-value="(settings.tech_info_items as string) || ''"
          placeholder='[{"label":"运行天数","value":"365"}]'
          aria-label="技术信息"
          @update:model-value="emit('update', 'tech_info_items', $event)"
        />
      </StFormItem>
    </StStack>
  </StCard>

  <!-- Comments -->
  <StCard title="评论设置" subtitle="配置评论表单的显示选项。">
    <StStack :gap="4" block>
      <StGrid :cols="2" :gap="4">
        <StSwitch
          :model-value="settings.comment_show_cookies === true"
          @update:model-value="emit('update', 'comment_show_cookies', $event)"
        >
          显示 Cookie 保存选项
        </StSwitch>
        <StSwitch
          :model-value="settings.comment_captcha_enabled === true"
          @update:model-value="emit('update', 'comment_captcha_enabled', $event)"
        >
          启用验证码
        </StSwitch>
        <StSwitch
          :model-value="settings.comment_show_private === true"
          @update:model-value="emit('update', 'comment_show_private', $event)"
        >
          显示私密评论选项
        </StSwitch>
        <StSwitch
          :model-value="settings.comment_show_markdown === true"
          @update:model-value="emit('update', 'comment_show_markdown', $event)"
        >
          支持 Markdown
        </StSwitch>
      </StGrid>

      <StFormItem
        label="评论排序方式"
        description="正序适合按时间完整阅读讨论；倒序适合关注最近的留言。置顶评论仍会优先显示。"
      >
        <StSelect
          :model-value="(settings.comment_order as string) || 'asc'"
          :options="commentOrderOptions"
          aria-label="评论排序方式"
          @update:model-value="emit('update', 'comment_order', $event)"
        />
      </StFormItem>

      <StFormItem
        label="Gravatar 基础 URL"
        description="用于加载 Gravatar 头像的 CDN 地址，默认 https://secure.gravatar.com/avatar/。"
      >
        <StInput
          :model-value="(settings.gravatar_base_url as string) || ''"
          placeholder="如 https://cn.gravatar.com/avatar/"
          aria-label="Gravatar 基础 URL"
          @update:model-value="emit('update', 'gravatar_base_url', $event)"
        />
      </StFormItem>

      <StFormItem label="IP 归属地 API">
        <StSelect
          :model-value="(settings.ip_location_api as string) || 'xinyew'"
          :options="ipLocationOptions"
          aria-label="IP 归属地 API"
          @update:model-value="emit('update', 'ip_location_api', $event)"
        />
      </StFormItem>

      <StFormItem description="开启后永久缓存IP定位结果，减少API请求">
        <StSwitch
          :model-value="settings.ip_location_cache === true"
          @update:model-value="emit('update', 'ip_location_cache', $event)"
        >
          启用IP归属地缓存（永久缓存）
        </StSwitch>
      </StFormItem>
    </StStack>
  </StCard>

  <!-- Footer -->
  <StCard title="页脚版权" subtitle="配置页脚版权信息和备案号。">
    <StStack :gap="4" block>
      <StFormItem label="版权信息样式">
        <StSelect
          :model-value="(settings.copyright_style as string) || 'detailed'"
          :options="copyrightStyleOptions"
          aria-label="版权信息样式"
          @update:model-value="emit('update', 'copyright_style', $event)"
        />
      </StFormItem>

      <StFormItem label="文章许可协议">
        <StSelect
          :model-value="(settings.article_license as string) || 'cc-by-nc-sa-40'"
          :options="articleLicenseOptions"
          aria-label="文章许可协议"
          @update:model-value="emit('update', 'article_license', $event)"
        />
      </StFormItem>

      <StFormItem label="底部寄语" description="显示在文章列表和评论区末尾">
        <StInput
          :model-value="(settings.end_note as string) || ''"
          placeholder="输入底部寄语"
          aria-label="底部寄语"
          @update:model-value="emit('update', 'end_note', $event)"
        />
      </StFormItem>

      <StFormItem label="ICP 备案号">
        <StInput
          :model-value="(settings.icp_text as string) || ''"
          placeholder="如 京ICP备2021000000号-1"
          aria-label="ICP 备案号"
          @update:model-value="emit('update', 'icp_text', $event)"
        />
      </StFormItem>

      <StFormItem label="公安备案号">
        <StInput
          :model-value="(settings.icp_gov_text as string) || ''"
          placeholder="如 京公网安备 11010802000001号"
          aria-label="公安备案号"
          @update:model-value="emit('update', 'icp_gov_text', $event)"
        />
      </StFormItem>

      <StFormItem>
        <StSwitch
          :model-value="settings.show_theme_credit === true"
          @update:model-value="emit('update', 'show_theme_credit', $event)"
        >
          显示主题版权信息
        </StSwitch>
      </StFormItem>
    </StStack>
  </StCard>
</template>
