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
  <AppCard title="后台美化" description="启用后将替换 WordPress 原生后台样式，使用与前端一致的主题配色和布局。">
    <AppToggle
      :model-value="!!settings.admin_theme_enabled"
      label="启用后台美化"
      @update:model-value="emit('update', 'admin_theme_enabled', $event)"
    />
  </AppCard>
</template>
