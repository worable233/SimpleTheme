<script setup lang="ts">
defineProps<{
  modelValue: boolean
  label: string
}>()

const emit = defineEmits<{
  (event: 'update:modelValue', value: boolean): void
}>()
</script>

<template>
  <button
    type="button"
    role="switch"
    :aria-checked="modelValue"
    class="flex w-full items-center gap-3 rounded-medium border border-border p-3 text-left text-sm transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
    @click="emit('update:modelValue', !modelValue)"
  >
    <span
      class="relative h-5 w-9 shrink-0 rounded-full transition-colors"
      :class="modelValue ? 'bg-primary' : 'bg-border'"
    >
      <span
        class="absolute top-0.5 left-0.5 size-4 rounded-full bg-card shadow-small transition-transform"
        :class="modelValue ? 'translate-x-4' : ''"
      ></span>
    </span>
    <span>{{ label }}</span>
  </button>
</template>
