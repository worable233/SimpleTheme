<script setup lang="ts">
defineProps<{
  settings: Record<string, unknown>
  defaults: Record<string, unknown>
}>()

const emit = defineEmits<{
  (e: 'update', key: string, value: unknown): void
}>()

function openMediaLibrary(key: string) {
  if (typeof wp !== 'undefined' && wp.media) {
    const frame = wp.media({
      title: '选择图片',
      button: { text: '使用此图片' },
      multiple: false,
    })
    frame.on('select', () => {
      const attachment = frame.state().get('selection').first().toJSON()
      emit('update', key, attachment.url)
    })
    frame.open()
  }
}
</script>

<template>
  <!-- 个人信息卡片 -->
  <div class="sta-section">
    <h3 class="sta-section__title">个人信息卡片</h3>
    <p class="sta-section__desc">配置封面区域的背景图和头像等信息。</p>
    <div class="sta-field">
      <label class="sta-field__label">背景图 URL</label>
      <div class="sta-input-row">
        <input
          class="sta-input"
          type="text"
          :value="(settings.hero_image as string) || ''"
          placeholder="输入背景图 URL 或点击选择"
          @input="emit('update', 'hero_image', ($event.target as HTMLInputElement).value)"
        />
        <button class="sta-btn" type="button" @click="openMediaLibrary('hero_image')">选择图片</button>
      </div>
      <div v-if="settings.hero_image" class="hero-preview">
        <img :src="settings.hero_image as string" alt="背景图预览" />
      </div>
    </div>
    <div class="sta-field">
      <label class="sta-checkbox">
        <input
          type="checkbox"
          :checked="settings.hero_show_avatar !== false"
          @change="emit('update', 'hero_show_avatar', ($event.target as HTMLInputElement).checked)"
        />
        <span class="sta-checkbox__label">显示头像</span>
      </label>
    </div>
    <div v-if="settings.hero_show_avatar !== false" class="sta-field">
      <label class="sta-field__label">头像 URL</label>
      <div class="sta-input-row">
        <input
          class="sta-input"
          type="text"
          :value="(settings.hero_avatar as string) || ''"
          placeholder="输入头像 URL 或点击选择"
          @input="emit('update', 'hero_avatar', ($event.target as HTMLInputElement).value)"
        />
        <button class="sta-btn" type="button" @click="openMediaLibrary('hero_avatar')">选择图片</button>
      </div>
    </div>
    <div class="sta-field sta-field--full">
      <label class="sta-field__label">描述语</label>
      <textarea
        class="sta-textarea"
        :value="(settings.hero_subtitle as string) || ''"
        placeholder="输入描述语"
        @input="emit('update', 'hero_subtitle', ($event.target as HTMLTextAreaElement).value)"
      ></textarea>
    </div>
  </div>

  <!-- 侧边栏卡片 -->
  <div class="sta-section">
    <h3 class="sta-section__title">侧边栏卡片</h3>
    <p class="sta-section__desc">控制右侧面板显示哪些卡片区域。</p>
    <div class="sta-grid">
      <div class="sta-field">
        <label class="sta-checkbox">
          <input
            type="checkbox"
            :checked="settings.sidebar_show_stats !== false"
            @change="emit('update', 'sidebar_show_stats', ($event.target as HTMLInputElement).checked)"
          />
          <span class="sta-checkbox__label">显示站点统计</span>
        </label>
      </div>
      <div class="sta-field">
        <label class="sta-checkbox">
          <input
            type="checkbox"
            :checked="settings.sidebar_show_heatmap !== false"
            @change="emit('update', 'sidebar_show_heatmap', ($event.target as HTMLInputElement).checked)"
          />
          <span class="sta-checkbox__label">显示贡献热力图</span>
        </label>
      </div>
      <div class="sta-field">
        <label class="sta-checkbox">
          <input
            type="checkbox"
            :checked="settings.sidebar_show_social !== false"
            @change="emit('update', 'sidebar_show_social', ($event.target as HTMLInputElement).checked)"
          />
          <span class="sta-checkbox__label">显示社交链接</span>
        </label>
      </div>
    </div>
    <div v-if="settings.sidebar_show_social !== false" class="sta-field sta-field--full">
      <label class="sta-field__label">社交链接</label>
      <textarea
        class="sta-textarea"
        :value="(settings.social_links as string) || ''"
        placeholder='[{"label":"GitHub","url":"https://github.com/...","icon":"github"}]'
        @input="emit('update', 'social_links', ($event.target as HTMLTextAreaElement).value)"
      ></textarea>
      <p class="sta-field__desc">JSON 数组格式：{"label":"...","url":"...","icon":"..."}</p>
    </div>
    <div class="sta-field sta-field--full">
      <label class="sta-field__label">技术信息</label>
      <textarea
        class="sta-textarea"
        :value="(settings.tech_info_items as string) || ''"
        placeholder='[{"label":"运行天数","value":"365"}]'
        @input="emit('update', 'tech_info_items', ($event.target as HTMLTextAreaElement).value)"
      ></textarea>
      <p class="sta-field__desc">JSON 数组格式：{"label":"...","value":"..."}</p>
    </div>
  </div>

  <!-- 评论设置 -->
  <div class="sta-section">
    <h3 class="sta-section__title">评论设置</h3>
    <p class="sta-section__desc">配置评论表单的显示选项。</p>
    <div class="sta-grid">
      <div class="sta-field">
        <label class="sta-checkbox">
          <input
            type="checkbox"
            :checked="settings.comment_show_email !== false"
            @change="emit('update', 'comment_show_email', ($event.target as HTMLInputElement).checked)"
          />
          <span class="sta-checkbox__label">显示邮箱字段</span>
        </label>
      </div>
      <div class="sta-field">
        <label class="sta-checkbox">
          <input
            type="checkbox"
            :checked="settings.comment_show_url !== false"
            @change="emit('update', 'comment_show_url', ($event.target as HTMLInputElement).checked)"
          />
          <span class="sta-checkbox__label">显示网址字段</span>
        </label>
      </div>
      <div class="sta-field">
        <label class="sta-checkbox">
          <input
            type="checkbox"
            :checked="settings.comment_show_cookies !== false"
            @change="emit('update', 'comment_show_cookies', ($event.target as HTMLInputElement).checked)"
          />
          <span class="sta-checkbox__label">显示 Cookie 保存选项</span>
        </label>
      </div>
      <div class="sta-field">
        <label class="sta-checkbox">
          <input
            type="checkbox"
            :checked="settings.comment_captcha_enabled !== false"
            @change="emit('update', 'comment_captcha_enabled', ($event.target as HTMLInputElement).checked)"
          />
          <span class="sta-checkbox__label">启用验证码</span>
        </label>
      </div>
      <div class="sta-field">
        <label class="sta-checkbox">
          <input
            type="checkbox"
            :checked="settings.comment_show_private !== false"
            @change="emit('update', 'comment_show_private', ($event.target as HTMLInputElement).checked)"
          />
          <span class="sta-checkbox__label">显示私密评论选项</span>
        </label>
      </div>
      <div class="sta-field">
        <label class="sta-checkbox">
          <input
            type="checkbox"
            :checked="settings.comment_show_markdown !== false"
            @change="emit('update', 'comment_show_markdown', ($event.target as HTMLInputElement).checked)"
          />
          <span class="sta-checkbox__label">支持 Markdown</span>
        </label>
      </div>
    </div>
    <div class="sta-field">
      <label class="sta-field__label">Gravatar 基础 URL</label>
      <input
        class="sta-input"
        type="text"
        :value="(settings.gravatar_base_url as string) || ''"
        placeholder="如 https://cn.gravatar.com/avatar/"
        @input="emit('update', 'gravatar_base_url', ($event.target as HTMLInputElement).value)"
      />
      <p class="sta-field__desc">用于加载 Gravatar 头像的 CDN 地址，默认 https://secure.gravatar.com/avatar/。</p>
    </div>
  </div>

  <!-- 页脚版权 -->
  <div class="sta-section">
    <h3 class="sta-section__title">页脚版权</h3>
    <p class="sta-section__desc">配置页脚版权信息和备案号。</p>
    <div class="sta-field">
      <label class="sta-field__label">版权信息样式</label>
      <select
        class="sta-select"
        :value="(settings.copyright_style as string) || 'detailed'"
        @change="emit('update', 'copyright_style', ($event.target as HTMLSelectElement).value)"
      >
        <option value="detailed">详细 — Copyright © 2026 站点名称 All Rights Reserved.</option>
        <option value="simple">简洁 — 2026 © 站点名称.</option>
        <option value="none">不显示</option>
      </select>
    </div>
    <div class="sta-field">
      <label class="sta-field__label">文章许可协议</label>
      <select
        class="sta-select"
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
    <div class="sta-field">
      <label class="sta-field__label">底部寄语</label>
      <p class="sta-field__desc">显示在文章列表和评论区末尾</p>
      <input
        class="sta-input"
        type="text"
        :value="(settings.end_note as string) || ''"
        placeholder="输入底部寄语"
        @input="emit('update', 'end_note', ($event.target as HTMLInputElement).value)"
      />
    </div>
    <div class="sta-field">
      <label class="sta-field__label">ICP 备案号</label>
      <input
        class="sta-input"
        type="text"
        :value="(settings.icp_text as string) || ''"
        placeholder="如 京ICP备2021000000号-1"
        @input="emit('update', 'icp_text', ($event.target as HTMLInputElement).value)"
      />
    </div>
    <div class="sta-field">
      <label class="sta-field__label">公安备案号</label>
      <input
        class="sta-input"
        type="text"
        :value="(settings.icp_gov_text as string) || ''"
        placeholder="如 京公网安备 11010802000001号"
        @input="emit('update', 'icp_gov_text', ($event.target as HTMLInputElement).value)"
      />
    </div>
    <div class="sta-field">
      <label class="sta-checkbox">
        <input
          type="checkbox"
          :checked="settings.show_theme_credit !== false"
          @change="emit('update', 'show_theme_credit', ($event.target as HTMLInputElement).checked)"
        />
        <span class="sta-checkbox__label">显示主题版权信息</span>
      </label>
    </div>
  </div>
</template>

<style scoped>
.sta-checkbox {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
}

.sta-checkbox__label {
  font-weight: 500;
}

.hero-preview {
  margin-top: 12px;
  border-radius: 8px;
  overflow: hidden;
  max-width: 300px;
}

.hero-preview img {
  display: block;
  width: 100%;
  height: auto;
  object-fit: cover;
}

.sta-field--full {
  grid-column: 1 / -1;
}
</style>
