<script setup lang="ts">
import Skeleton from './Skeleton.vue'
</script>

<template>
  <article style="overflow: hidden; padding: 0; background: var(--card); border-radius: var(--radius-large, 12px); border: 1px solid var(--border);">
    <div style="aspect-ratio: 16 / 9; overflow: hidden;">
      <Skeleton variant="rectangular" width="100%" height="12rem" />
    </div>
    <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
      <Skeleton variant="text" :lines="1" />
      <Skeleton variant="text" :lines="3" />
      <Skeleton variant="text" :lines="1" width="8rem" />
    </div>
  </article>
</template>
