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
        <input type="number" class="xh-input xh-input--number" min="3" max="20" :value="(settings.home_post_count as number) || 6" @input="emit('update', 'home_post_count', Number(($event.target as HTMLInputElement).value))" />
      </div>
      <template v-if="settings.show_shuoshuo_section">
        <div class="xh-field xh-field--compact">
          <label class="xh-field__label">首页说说数量</label>
          <input type="number" class="xh-input xh-input--number" min="0" max="12" :value="(settings.home_shuoshuo_count as number) || 3" @input="emit('update', 'home_shuoshuo_count', Number(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="xh-field xh-field--compact">
          <label class="xh-field__label">说说每页数量</label>
          <input type="number" class="xh-input xh-input--number" min="6" max="24" :value="(settings.shuoshuo_page_size as number) || 12" @input="emit('update', 'shuoshuo_page_size', Number(($event.target as HTMLInputElement).value))" />
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
</template>
