<script setup lang="ts">
import AppCard from './AppCard.vue'
import AppToggle from './AppToggle.vue'
import AppImageUpload from './AppImageUpload.vue'

defineProps<{
  settings: Record<string, unknown>
  defaults: Record<string, unknown>
}>()

const emit = defineEmits<{
  (e: 'update', key: string, value: unknown): void
}>()
</script>

<template>
  <!-- Hero / Profile Card -->
  <AppCard title="个人信息卡片" description="配置首页封面区域的背景图和头像等信息。">
    <div class="xh-field">
      <label class="xh-field__label">背景图</label>
      <AppImageUpload
        :modelValue="(settings.hero_image as string) || ''"
        placeholder="输入背景图 URL 或点击选择"
        @update:modelValue="emit('update', 'hero_image', $event)"
      />
    </div>
    <div class="xh-field" style="margin-top: 16px;">
      <AppToggle
        :modelValue="settings.hero_show_avatar !== false"
        label="显示头像"
        @update:modelValue="emit('update', 'hero_show_avatar', $event)"
      />
    </div>
    <div v-if="settings.hero_show_avatar !== false" class="xh-field">
      <label class="xh-field__label">头像</label>
      <AppImageUpload
        :modelValue="(settings.hero_avatar as string) || ''"
        placeholder="输入头像 URL 或点击选择"
        @update:modelValue="emit('update', 'hero_avatar', $event)"
      />
    </div>
    <div class="xh-field xh-field--full">
      <label class="xh-field__label">描述语</label>
      <textarea
        class="xh-textarea"
        :value="(settings.hero_subtitle as string) || ''"
        placeholder="输入描述语"
        @input="emit('update', 'hero_subtitle', ($event.target as HTMLTextAreaElement).value)"
      ></textarea>
    </div>
  </AppCard>

  <!-- Sidebar Toggles -->
  <AppCard title="侧边栏卡片" description="控制右侧面板显示哪些卡片区域。">
    <div class="xh-grid">
      <div class="xh-field xh-field--compact">
        <AppToggle
          :modelValue="settings.sidebar_show_stats !== false"
          label="显示站点统计"
          @update:modelValue="emit('update', 'sidebar_show_stats', $event)"
        />
      </div>
      <div class="xh-field xh-field--compact">
        <AppToggle
          :modelValue="settings.sidebar_show_heatmap !== false"
          label="显示贡献热力图"
          @update:modelValue="emit('update', 'sidebar_show_heatmap', $event)"
        />
      </div>
      <div class="xh-field xh-field--compact">
        <AppToggle
          :modelValue="settings.sidebar_show_social !== false"
          label="显示社交链接"
          @update:modelValue="emit('update', 'sidebar_show_social', $event)"
        />
      </div>
      <div class="xh-field xh-field--compact">
        <AppToggle
          :modelValue="settings.sidebar_show_hitokoto !== false"
          label="显示一言"
          @update:modelValue="emit('update', 'sidebar_show_hitokoto', $event)"
        />
      </div>
    </div>
    <div v-if="settings.sidebar_show_hitokoto !== false" class="xh-field" style="margin-top: 16px;">
      <label class="xh-field__label">一言 API 地址</label>
      <input
        class="xh-input"
        type="url"
        :value="(settings.hitokoto_api as string) || ''"
        placeholder="https://v1.hitokoto.cn"
        @input="emit('update', 'hitokoto_api', ($event.target as HTMLInputElement).value)"
      />
      <p class="xh-field__desc">默认 hitokoto.cn；可换成自建或第三方 API，支持 hitokoto JSON（hitokoto/from 字段）或纯文本响应。</p>
    </div>
    <div v-if="settings.sidebar_show_social !== false" class="xh-field xh-field--full" style="margin-top: 16px;">
      <label class="xh-field__label">社交链接</label>
      <textarea
        class="xh-textarea"
        :value="(settings.social_links as string) || ''"
        placeholder='[{"label":"GitHub","url":"https://github.com/...","icon":"github"}]'
        @input="emit('update', 'social_links', ($event.target as HTMLTextAreaElement).value)"
      ></textarea>
      <p class="xh-field__desc">JSON 数组格式：{ "label": "...", "url": "...", "icon": "..." }</p>
    </div>
    <div class="xh-field xh-field--full" style="margin-top: 16px;">
      <label class="xh-field__label">技术信息</label>
      <textarea
        class="xh-textarea"
        :value="(settings.tech_info_items as string) || ''"
        placeholder='[{"label":"运行天数","value":"365"}]'
        @input="emit('update', 'tech_info_items', ($event.target as HTMLTextAreaElement).value)"
      ></textarea>
      <p class="xh-field__desc">JSON 数组格式：{ "label": "...", "value": "..." }</p>
    </div>
  </AppCard>

  <!-- Comments -->
  <AppCard title="评论设置" description="配置评论表单的显示选项。">
    <div class="xh-grid">
      <div class="xh-field xh-field--compact">
        <AppToggle
          :modelValue="settings.comment_show_cookies !== false"
          label="显示 Cookie 保存选项"
          @update:modelValue="emit('update', 'comment_show_cookies', $event)"
        />
      </div>
      <div class="xh-field xh-field--compact">
        <AppToggle
          :modelValue="settings.comment_captcha_enabled !== false"
          label="启用验证码"
          @update:modelValue="emit('update', 'comment_captcha_enabled', $event)"
        />
      </div>
      <div class="xh-field xh-field--compact">
        <AppToggle
          :modelValue="settings.comment_show_private !== false"
          label="显示私密评论选项"
          @update:modelValue="emit('update', 'comment_show_private', $event)"
        />
      </div>
      <div class="xh-field xh-field--compact">
        <AppToggle
          :modelValue="settings.comment_show_markdown !== false"
          label="支持 Markdown"
          @update:modelValue="emit('update', 'comment_show_markdown', $event)"
        />
      </div>
      <div class="xh-field xh-field--compact">
        <AppToggle
          :modelValue="settings.comment_image_upload_enabled !== false"
          label="允许用户上传图片"
          @update:modelValue="emit('update', 'comment_image_upload_enabled', $event)"
        />
      </div>
    </div>

    <div class="xh-field" style="margin-top: 8px;">
      <label class="xh-field__label">Gravatar 基础 URL</label>
      <input
        class="xh-input"
        type="text"
        :value="(settings.gravatar_base_url as string) || ''"
        placeholder="如 https://cn.gravatar.com/avatar/"
        @input="emit('update', 'gravatar_base_url', ($event.target as HTMLInputElement).value)"
      />
      <p class="xh-field__desc">用于加载 Gravatar 头像的 CDN 地址，默认 https://secure.gravatar.com/avatar/。</p>
    </div>

    <div class="xh-field">
      <label class="xh-field__label">IP 归属地 API</label>
      <div class="xh-select" style="display: inline-flex; align-items: center; gap: 16px; border: none; padding: 0; background: none; min-width: auto;">
        <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
          <input type="radio" value="xinyew" :checked="(settings.ip_location_api as string) === 'xinyew'" @change="emit('update', 'ip_location_api', 'xinyew')" style="accent-color: var(--xh-primary);" />
          <span style="font-size: 14px;">新野API（百度数据）</span>
        </label>
        <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
          <input type="radio" value="ip.sb" :checked="(settings.ip_location_api as string) === 'ip.sb'" @change="emit('update', 'ip_location_api', 'ip.sb')" style="accent-color: var(--xh-primary);" />
          <span style="font-size: 14px;">ip.sb</span>
        </label>
        <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
          <input type="radio" value="ip-api.com" :checked="(settings.ip_location_api as string) === 'ip-api.com'" @change="emit('update', 'ip_location_api', 'ip-api.com')" style="accent-color: var(--xh-primary);" />
          <span style="font-size: 14px;">ip-api.com</span>
        </label>
      </div>
    </div>

    <div class="xh-field xh-field--compact" style="margin-top: 8px;">
      <AppToggle
        :modelValue="settings.ip_location_cache === true"
        label="启用IP归属地缓存（永久缓存）"
        @update:modelValue="emit('update', 'ip_location_cache', $event)"
      />
      <p class="xh-field__desc">开启后永久缓存IP定位结果，减少API请求</p>
    </div>
  </AppCard>

  <!-- Footer -->
  <AppCard title="页脚版权" description="配置页脚版权信息和备案号。">
    <div class="xh-field">
      <label class="xh-field__label">版权信息样式</label>
      <select
        class="xh-select"
        :value="(settings.copyright_style as string) || 'detailed'"
        @change="emit('update', 'copyright_style', ($event.target as HTMLSelectElement).value)"
      >
        <option value="detailed">详细 — Copyright © 2026 站点名称 All Rights Reserved.</option>
        <option value="simple">简洁 — 2026 © 站点名称.</option>
        <option value="none">不显示</option>
      </select>
    </div>
    <div class="xh-field">
      <label class="xh-field__label">文章许可协议</label>
      <select
        class="xh-select"
        :value="(settings.article_license as string) || 'cc-by-nc-sa-40'"
        @change="emit('update', 'article_license', ($event.target as HTMLSelectElement).value)"
      >
        <option value="cc-by-40">CC BY 4.0 — 署名</option>
        <option value="cc-by-sa-40">CC BY-SA 4.0 — 署名-相同方式共享</option>
        <option value="cc-by-nc-40">CC BY-NC 4.0 — 署名-非商业使用</option>
        <option value="cc-by-nc-sa-40">CC BY-NC-SA 4.0 — 署名-非商业使用-相同方式共享</option>
        <option value="cc-by-nc-nd-40">CC BY-NC-ND 4.0 — 署名-非商业使用-禁止演绎</option>
        <option value="cc-by-nd-40">CC BY-ND 4.0 — 署名-禁止演绎</option>
        <option value="arr">ARR — 保留所有权利</option>
        <option value="none">不显示</option>
      </select>
    </div>
    <div class="xh-field">
      <label class="xh-field__label">底部寄语</label>
      <p class="xh-field__desc">显示在文章列表和评论区末尾</p>
      <input
        class="xh-input"
        type="text"
        :value="(settings.end_note as string) || ''"
        placeholder="输入底部寄语"
        @input="emit('update', 'end_note', ($event.target as HTMLInputElement).value)"
      />
    </div>
    <div class="xh-field">
      <label class="xh-field__label">ICP 备案号</label>
      <input
        class="xh-input"
        type="text"
        :value="(settings.icp_text as string) || ''"
        placeholder="如 京ICP备2021000000号-1"
        @input="emit('update', 'icp_text', ($event.target as HTMLInputElement).value)"
      />
    </div>
    <div class="xh-field">
      <label class="xh-field__label">公安备案号</label>
      <input
        class="xh-input"
        type="text"
        :value="(settings.icp_gov_text as string) || ''"
        placeholder="如 京公网安备 11010802000001号"
        @input="emit('update', 'icp_gov_text', ($event.target as HTMLInputElement).value)"
      />
    </div>
    <div class="xh-field xh-field--compact">
      <AppToggle
        :modelValue="settings.show_theme_credit !== false"
        label="显示主题版权信息"
        @update:modelValue="emit('update', 'show_theme_credit', $event)"
      />
    </div>
  </AppCard>
</template>
