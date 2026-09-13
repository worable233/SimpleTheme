<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'

interface Option {
  value: string
  label: string
}

const props = defineProps<{
  modelValue: string
  options: Option[]
  label: string
}>()

const emit = defineEmits<{
  (event: 'update:modelValue', value: string): void
}>()

const open = ref(false)
const root = ref<HTMLElement | null>(null)

const selectedLabel = () =>
  props.options.find((option) => option.value === props.modelValue)?.label || props.options[0]?.label || ''

function choose(value: string) {
  emit('update:modelValue', value)
  open.value = false
}

function onDocumentClick(event: MouseEvent) {
  if (root.value && !root.value.contains(event.target as Node)) open.value = false
}

onMounted(() => document.addEventListener('click', onDocumentClick))
onBeforeUnmount(() => document.removeEventListener('click', onDocumentClick))
</script>

<template>
  <div ref="root" class="relative">
    <button
      type="button"
      class="flex w-full items-center justify-between gap-3 rounded-medium border border-input bg-card px-3 py-2.5 text-left text-sm text-foreground transition-[border-color,box-shadow] hover:border-foreground/40 focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20"
      :aria-label="label"
      :aria-expanded="open"
      @click.stop="open = !open"
    >
      <span class="truncate">{{ selectedLabel() }}</span>
      <span class="text-secondary transition-transform" :class="open ? 'rotate-180' : ''">⌄</span>
    </button>
    <Transition name="select">
      <div
        v-if="open"
        class="absolute z-30 mt-2 w-full overflow-hidden rounded-medium border border-border bg-card p-1 shadow-large"
        role="listbox"
        :aria-label="label"
      >
        <button
          v-for="option in options"
          :key="option.value"
          type="button"
          role="option"
          :aria-selected="option.value === modelValue"
          class="block w-full rounded-small px-3 py-2 text-left text-sm transition-colors hover:bg-muted"
          :class="option.value === modelValue ? 'bg-accent font-medium text-primary' : 'text-foreground'"
          @click="choose(option.value)"
        >
          {{ option.label }}
        </button>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.select-enter-active,
.select-leave-active {
  transition: opacity 0.16s cubic-bezier(0.22, 1, 0.36, 1), transform 0.16s cubic-bezier(0.22, 1, 0.36, 1);
}

.select-enter-from,
.select-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
