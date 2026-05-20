<script setup lang="ts">
defineProps<{
  settings: Record<string, unknown>
}>()

const emit = defineEmits<{
  (e: 'update', key: string, value: unknown): void
}>()
</script>

<template>
  <div class="sta-section">
    <h3 class="sta-section__title">页脚</h3>
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
      <label class="sta-field__label">底部寄语</label>
      <input
        type="text"
        class="sta-input"
        :value="(settings.end_note as string) || ''"
        @input="emit('update', 'end_note', ($event.target as HTMLInputElement).value)"
      />
      <p class="sta-field__desc">首页文章列表末尾显示的文字。</p>
    </div>
  </div>

  <div class="sta-section">
    <h3 class="sta-section__title">ICP 备案</h3>
    <div class="sta-grid">
      <div class="sta-field">
        <label class="sta-field__label">ICP 备案号</label>
        <input
          type="text"
          class="sta-input"
          :value="(settings.icp_text as string) || ''"
          @input="emit('update', 'icp_text', ($event.target as HTMLInputElement).value)"
        />
      </div>
      <div class="sta-field">
        <label class="sta-field__label">公安备案号</label>
        <input
          type="text"
          class="sta-input"
          :value="(settings.icp_gov_text as string) || ''"
          @input="emit('update', 'icp_gov_text', ($event.target as HTMLInputElement).value)"
        />
      </div>
    </div>
  </div>

  <div class="sta-section">
    <h3 class="sta-section__title">社交链接</h3>
    <p class="sta-field__desc" style="margin-bottom: 12px;">JSON 数组格式，每条格式：{"label": "...", "url": "...", "icon": "图标 CSS class"}</p>
    <div class="sta-field">
      <textarea
        class="sta-textarea"
        rows="6"
        :value="(settings.social_links as string) || ''"
        @input="emit('update', 'social_links', ($event.target as HTMLTextAreaElement).value)"
        placeholder='[{"label":"GitHub","url":"https://github.com/username","icon":"bx bxl-github"}]'
      ></textarea>
    </div>
  </div>

  <div class="sta-section">
    <h3 class="sta-section__title">评论设置</h3>
    <div class="sta-grid">
      <div class="sta-field">
        <label class="sta-checkbox">
          <input
            type="checkbox"
            :checked="!!settings.comment_show_email"
            @change="emit('update', 'comment_show_email', ($event.target as HTMLInputElement).checked)"
          />
          显示邮箱字段
        </label>
      </div>
      <div class="sta-field">
        <label class="sta-checkbox">
          <input
            type="checkbox"
            :checked="!!settings.comment_show_url"
            @change="emit('update', 'comment_show_url', ($event.target as HTMLInputElement).checked)"
          />
          显示网站字段
        </label>
      </div>
      <div class="sta-field">
        <label class="sta-checkbox">
          <input
            type="checkbox"
            :checked="!!settings.comment_show_cookies"
            @change="emit('update', 'comment_show_cookies', ($event.target as HTMLInputElement).checked)"
          />
          显示 Cookie 记住信息选项
        </label>
      </div>
      <div class="sta-field">
        <label class="sta-field__label">Gravatar 基础 URL</label>
        <input
          type="url"
          class="sta-input"
          :value="(settings.gravatar_base_url as string) || 'https://weavatar.com/avatar/'"
          placeholder="https://weavatar.com/avatar/"
          @input="emit('update', 'gravatar_base_url', ($event.target as HTMLInputElement).value)"
        />
        <p class="sta-field__desc">评论头像代理使用的 Gravatar 服务地址。可更换为国内镜像，如 https://cn.gravatar.com/avatar。</p>
      </div>
      <div class="sta-field">
        <label class="sta-checkbox">
          <input
            type="checkbox"
            :checked="!!settings.comment_captcha_enabled"
            @change="emit('update', 'comment_captcha_enabled', ($event.target as HTMLInputElement).checked)"
          />
          启用评论验证码
        </label>
      </div>
      <div class="sta-field">
        <label class="sta-checkbox">
          <input
            type="checkbox"
            :checked="!!settings.comment_show_private"
            @change="emit('update', 'comment_show_private', ($event.target as HTMLInputElement).checked)"
          />
          显示悄悄话选项
        </label>
      </div>
      <div class="sta-field">
        <label class="sta-checkbox">
          <input
            type="checkbox"
            :checked="!!settings.comment_show_markdown"
            @change="emit('update', 'comment_show_markdown', ($event.target as HTMLInputElement).checked)"
          />
          显示 Markdown 选项
        </label>
      </div>
    </div>
  </div>

  <div class="sta-section">
    <h3 class="sta-section__title">阅读与性能</h3>
    <div class="sta-grid">
      <div class="sta-field">
        <label class="sta-field__label">阅读速度（字/分钟）</label>
        <input
          type="number"
          class="sta-input sta-input--number"
          min="100"
          max="600"
          :value="(settings.reading_speed as number) || 300"
          @input="emit('update', 'reading_speed', Number(($event.target as HTMLInputElement).value))"
        />
      </div>
      <div class="sta-field">
        <label class="sta-checkbox">
          <input
            type="checkbox"
            :checked="!!settings.enable_prism_highlight"
            @change="emit('update', 'enable_prism_highlight', ($event.target as HTMLInputElement).checked)"
          />
          启用代码高亮
        </label>
      </div>
      <div class="sta-field">
        <label class="sta-checkbox">
          <input
            type="checkbox"
            :checked="!!settings.show_theme_credit"
            @change="emit('update', 'show_theme_credit', ($event.target as HTMLInputElement).checked)"
          />
          显示主题版权信息
        </label>
      </div>
    </div>
  </div>

  <div class="sta-section">
    <h3 class="sta-section__title">首页集合</h3>
    <div class="sta-grid">
      <div class="sta-field">
        <label class="sta-field__label">文章区块标题</label>
        <input type="text" class="sta-input" :value="(settings.posts_title as string) || ''" @input="emit('update', 'posts_title', ($event.target as HTMLInputElement).value)" />
      </div>
      <div class="sta-field">
        <label class="sta-field__label">文章区块副标题</label>
        <input type="text" class="sta-input" :value="(settings.posts_subtitle as string) || ''" @input="emit('update', 'posts_subtitle', ($event.target as HTMLInputElement).value)" />
      </div>
      <div class="sta-field">
        <label class="sta-checkbox">
          <input
            type="checkbox"
            :checked="!!settings.show_shuoshuo_section"
            @change="emit('update', 'show_shuoshuo_section', ($event.target as HTMLInputElement).checked)"
          />
          显示说说板块
        </label>
      </div>
      <div v-if="settings.show_shuoshuo_section" class="sta-field">
        <label class="sta-field__label">说说区块标题</label>
        <input type="text" class="sta-input" :value="(settings.shuoshuo_title as string) || ''" @input="emit('update', 'shuoshuo_title', ($event.target as HTMLInputElement).value)" />
      </div>
      <div v-if="settings.show_shuoshuo_section" class="sta-field">
        <label class="sta-field__label">说说区块副标题</label>
        <input type="text" class="sta-input" :value="(settings.shuoshuo_subtitle as string) || ''" @input="emit('update', 'shuoshuo_subtitle', ($event.target as HTMLInputElement).value)" />
      </div>
      <div class="sta-field">
        <label class="sta-field__label">首页文章数量</label>
        <input type="number" class="sta-input sta-input--number" min="3" max="20" :value="(settings.home_post_count as number) || 6" @input="emit('update', 'home_post_count', Number(($event.target as HTMLInputElement).value))" />
      </div>
      <div v-if="settings.show_shuoshuo_section" class="sta-field">
        <label class="sta-field__label">首页说说数量</label>
        <input type="number" class="sta-input sta-input--number" min="0" max="12" :value="(settings.home_shuoshuo_count as number) || 3" @input="emit('update', 'home_shuoshuo_count', Number(($event.target as HTMLInputElement).value))" />
      </div>
      <div v-if="settings.show_shuoshuo_section" class="sta-field">
        <label class="sta-field__label">说说每页数量</label>
        <input type="number" class="sta-input sta-input--number" min="6" max="24" :value="(settings.shuoshuo_page_size as number) || 12" @input="emit('update', 'shuoshuo_page_size', Number(($event.target as HTMLInputElement).value))" />
      </div>
    </div>
  </div>
</template>
