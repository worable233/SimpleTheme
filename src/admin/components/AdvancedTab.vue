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
</script>

<template>
  <!-- Collections / Home -->
  <AppCard title="首页集合" description="配置首页各区块的标题和显示数量。">
    <div class="xh-field xh-field--compact">
      <AppToggle
        :modelValue="!!settings.show_shuoshuo_section"
        label="显示说说板块"
        @update:modelValue="emit('update', 'show_shuoshuo_section', $event)"
      />
    </div>

    <div class="xh-grid" style="margin-top: 20px;">
      <div class="xh-field xh-field--compact">
        <label class="xh-field__label">文章区块标题</label>
        <input type="text" class="xh-input" :value="(settings.posts_title as string) || ''" @input="emit('update', 'posts_title', ($event.target as HTMLInputElement).value)" />
      </div>
      <div class="xh-field xh-field--compact">
        <label class="xh-field__label">文章区块副标题</label>
        <input type="text" class="xh-input" :value="(settings.posts_subtitle as string) || ''" @input="emit('update', 'posts_subtitle', ($event.target as HTMLInputElement).value)" />
      </div>
      <template v-if="settings.show_shuoshuo_section">
        <div class="xh-field xh-field--compact">
          <label class="xh-field__label">说说区块标题</label>
          <input type="text" class="xh-input" :value="(settings.shuoshuo_title as string) || ''" @input="emit('update', 'shuoshuo_title', ($event.target as HTMLInputElement).value)" />
        </div>
        <div class="xh-field xh-field--compact">
          <label class="xh-field__label">说说区块副标题</label>
          <input type="text" class="xh-input" :value="(settings.shuoshuo_subtitle as string) || ''" @input="emit('update', 'shuoshuo_subtitle', ($event.target as HTMLInputElement).value)" />
        </div>
      </template>
      <div class="xh-field xh-field--compact">
        <label class="xh-field__label">首页文章数量</label>
          <input type="number" class="xh-input xh-input--number" min="3" max="20" :value="(settings.home_post_count as number) ?? 6" @input="emit('update', 'home_post_count', Number(($event.target as HTMLInputElement).value))" />
      </div>
      <template v-if="settings.show_shuoshuo_section">
        <div class="xh-field xh-field--compact">
          <label class="xh-field__label">首页说说数量</label>
          <input type="number" class="xh-input xh-input--number" min="0" max="12" :value="(settings.home_shuoshuo_count as number) ?? 3" @input="emit('update', 'home_shuoshuo_count', Number(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="xh-field xh-field--compact">
          <label class="xh-field__label">说说每页数量</label>
          <input type="number" class="xh-input xh-input--number" min="6" max="24" :value="(settings.shuoshuo_page_size as number) ?? 12" @input="emit('update', 'shuoshuo_page_size', Number(($event.target as HTMLInputElement).value))" />
        </div>
      </template>
    </div>
  </AppCard>

  <!-- Performance -->
  <AppCard title="性能" description="性能与调试相关设置。">
    <div class="xh-field xh-field--compact">
      <AppToggle
        :modelValue="!!settings.suppress_console_warnings"
        label="过滤控制台警告"
        @update:modelValue="emit('update', 'suppress_console_warnings', $event)"
      />
      <p class="xh-field__desc">屏蔽插件（如 WPOPT）在浏览器控制台输出的广告/提示信息。</p>
    </div>
  </AppCard>

  <!-- Local Avatars -->
  <AppCard title="本地头像" description="允许用户在个人资料页上传自定义头像替代 Gravatar。">
    <div class="xh-field xh-field--compact">
      <AppToggle
        :modelValue="!!settings.local_avatars_enabled"
        label="启用本地头像"
        @update:modelValue="emit('update', 'local_avatars_enabled', $event)"
      />
      <p class="xh-field__desc">开启后，用户可以在 wp-admin/profile.php 上传自己的头像，将不再依赖 Gravatar。</p>
    </div>
  </AppCard>

  <!-- Admin Bar -->
  <AppCard title="Admin Bar" description="控制顶部工具栏的显示。">
    <div class="xh-field xh-field--compact">
      <AppToggle
        :modelValue="!!settings.hide_admin_bar"
        label="隐藏前台 Admin Bar"
        @update:modelValue="emit('update', 'hide_admin_bar', $event)"
      />
      <p class="xh-field__desc">开启后，已登录用户在前台页面将不再显示 WordPress 顶部工具栏。</p>
    </div>
  </AppCard>

  <!-- Announcement -->
  <AppCard title="公告弹窗" description="配置首页公告弹窗或胶囊横幅。">
    <div class="xh-field xh-field--compact">
      <AppToggle
        :modelValue="!!settings.announcement_enabled"
        label="启用公告"
        @update:modelValue="emit('update', 'announcement_enabled', $event)"
      />
    </div>
    <template v-if="settings.announcement_enabled">
      <div class="xh-field xh-field--compact" style="margin-top: 16px;">
        <label class="xh-field__label">显示模式</label>
        <select class="xh-select" :value="(settings.announcement_mode as string) || 'modal'" @change="emit('update', 'announcement_mode', ($event.target as HTMLSelectElement).value)">
          <option value="modal">弹窗</option>
          <option value="capsule">胶囊横幅</option>
        </select>
      </div>
      <div class="xh-field xh-field--compact">
        <label class="xh-field__label">页面 ID</label>
        <input type="number" class="xh-input xh-input--number" min="0" :value="(settings.announcement_page_id as number) || 0" @input="emit('update', 'announcement_page_id', Number(($event.target as HTMLInputElement).value))" />
        <p class="xh-field__desc">指定要展示内容的 WordPress 页面 ID。</p>
      </div>
      <div class="xh-field xh-field--compact">
        <label class="xh-field__label">胶囊标题</label>
        <input type="text" class="xh-input" :value="(settings.announcement_capsule_title as string) || ''" @input="emit('update', 'announcement_capsule_title', ($event.target as HTMLInputElement).value)" />
      </div>
      <div class="xh-field xh-field--compact">
        <label class="xh-field__label">图标</label>
        <input type="text" class="xh-input" :value="(settings.announcement_icon as string) || ''" @input="emit('update', 'announcement_icon', ($event.target as HTMLInputElement).value)" />
        <p class="xh-field__desc">显示在胶囊标题前的 Emoji 或文本图标。</p>
      </div>
    </template>
  </AppCard>

  <!-- Cookie Consent -->
  <AppCard title="Cookie 同意" description="配置 Cookie 同意横幅。">
    <div class="xh-field xh-field--compact">
      <AppToggle
        :modelValue="!!settings.cookie_consent_enabled"
        label="启用 Cookie 同意横幅"
        @update:modelValue="emit('update', 'cookie_consent_enabled', $event)"
      />
    </div>
    <template v-if="settings.cookie_consent_enabled">
      <div class="xh-field xh-field--compact" style="margin-top: 16px;">
        <label class="xh-field__label">提示文字</label>
        <input type="text" class="xh-input" :value="(settings.cookie_consent_message as string) || ''" @input="emit('update', 'cookie_consent_message', ($event.target as HTMLInputElement).value)" />
      </div>
    </template>
  </AppCard>
</template>
