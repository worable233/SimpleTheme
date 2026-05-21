<script setup lang="ts">
defineProps<{
  settings: Record<string, unknown>
  defaults: Record<string, unknown>
}>()

const emit = defineEmits<{
  (e: 'update', key: string, value: unknown): void
}>()
</script>

<template>
  <div class="sta-section">
    <h3 class="sta-section__title">首页集合</h3>
    <div class="sta-field">
      <label class="sta-checkbox">
        <input
          type="checkbox"
          :checked="!!settings.show_shuoshuo_section"
          @change="emit('update', 'show_shuoshuo_section', ($event.target as HTMLInputElement).checked)"
        />
        <span class="sta-checkbox__label">显示说说板块</span>
      </label>
    </div>
    <div class="sta-grid">
      <div class="sta-field">
        <label class="sta-field__label">文章区块标题</label>
        <input type="text" class="sta-input" :value="(settings.posts_title as string) || ''" @input="emit('update', 'posts_title', ($event.target as HTMLInputElement).value)" />
      </div>
      <div class="sta-field">
        <label class="sta-field__label">文章区块副标题</label>
        <input type="text" class="sta-input" :value="(settings.posts_subtitle as string) || ''" @input="emit('update', 'posts_subtitle', ($event.target as HTMLInputElement).value)" />
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

  <div class="sta-section">
    <h3 class="sta-section__title">性能</h3>
    <div class="sta-field">
      <label class="sta-checkbox">
        <input
          type="checkbox"
          :checked="!!settings.suppress_console_warnings"
          @change="emit('update', 'suppress_console_warnings', ($event.target as HTMLInputElement).checked)"
        />
        <span class="sta-checkbox__label">过滤控制台警告</span>
      </label>
      <p class="sta-field__desc">屏蔽插件（如 WPOPT）在浏览器控制台输出的广告/提示信息。</p>
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
</style>
