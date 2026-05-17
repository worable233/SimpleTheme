<script setup lang="ts">
defineProps<{
  variant?: 'text' | 'circular' | 'rectangular' | 'rounded'
  width?: string
  height?: string
  lines?: number
}>()
</script>

<template>
  <div
    v-if="variant === 'circular'"
    role="status"
    class="skeleton box"
    :style="{ width: width || '4rem', height: height || '4rem', borderRadius: '50%' }"
  />

  <div
    v-else-if="variant === 'rectangular' || variant === 'rounded'"
    role="status"
    class="skeleton box"
    :style="{
      width,
      height,
      borderRadius: variant === 'rounded' ? '0.5rem' : '0',
    }"
  />

  <div v-else :style="{ display: 'flex', flexDirection: 'column', gap: 'var(--space-2)' }">
    <div
      v-for="i in lines || 3"
      :key="i"
      role="status"
      class="skeleton line"
      :style="{
        width: i === (lines || 3) && lines !== 1 ? '60%' : undefined,
        marginTop: i === (lines || 3) && lines !== 1 ? 'var(--space-1)' : undefined,
      }"
    />
  </div>
</template>
