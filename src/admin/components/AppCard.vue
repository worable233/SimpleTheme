<script setup lang="ts">
defineProps<{
  title?: string
  description?: string
}>()
</script>

<template>
  <section class="xh-card" :class="{ 'xh-animate-in': true }">
    <h3 v-if="title" class="xh-card__title">{{ title }}</h3>
    <p v-if="description" class="xh-card__desc">{{ description }}</p>
    <slot />
  </section>
</template>
