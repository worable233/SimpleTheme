<script setup lang="ts">
import { computed, ref } from 'vue'

const props = defineProps<{
  modelValue: string
  fallback?: string
  label: string
}>()

const emit = defineEmits<{
  (event: 'update:modelValue', value: string): void
}>()

const open = ref(false)
const palette = ['#333333', '#666666', '#999999', '#d64545', '#d97706', '#15803d', '#2563eb', '#7c3aed', '#ffffff']
const value = computed(() => props.modelValue || props.fallback || '#333333')

function update(value: string) {
  emit('update:modelValue', value)
}
</script>

<template>
  <div class="relative">
    <button
      type="button"
      class="flex h-10 w-full items-center gap-3 rounded-medium border border-input bg-card px-3 text-left text-sm transition-[border-color,box-shadow] hover:border-foreground/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
      :aria-label="label"
      @click="open = !open"
    >
      <span class="size-5 shrink-0 rounded border border-border" :style="{ backgroundColor: value }"></span>
      <span class="font-mono text-xs uppercase">{{ value }}</span>
      <span class="ml-auto text-secondary">⌄</span>
    </button>
    <div
      v-if="open"
      class="absolute z-30 mt-2 w-full rounded-medium border border-border bg-card p-3 shadow-large"
    >
      <div class="grid grid-cols-9 gap-1.5">
        <button
          v-for="color in palette"
          :key="color"
          type="button"
          class="size-6 rounded border border-border transition-transform hover:scale-110 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30"
          :style="{ backgroundColor: color }"
          :aria-label="color"
          @click="update(color); open = false"
        ></button>
      </div>
      <label class="mt-3 block text-xs text-secondary">
        HEX
        <input
          type="text"
          class="mt-1 w-full rounded-small border border-input bg-background px-2.5 py-2 font-mono text-xs text-foreground outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
          :value="value"
          @input="update(($event.target as HTMLInputElement).value)"
        />
      </label>
    </div>
  </div>
</template>
