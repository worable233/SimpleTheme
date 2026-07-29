<script setup lang="ts">
defineProps<{
  modelValue: string
  placeholder?: string
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', val: string): void
}>()
</script>

<template>
  <div class="xh-color-row">
    <input
      type="color"
      class="xh-color-row__swatch"
      :value="modelValue || placeholder || '#ffffff'"
      @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
    <input
      type="text"
      class="xh-color-row__text"
      :value="modelValue"
      :placeholder="placeholder || '#cccccc'"
      maxlength="7"
      @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
  </div>
</template>

<style scoped>
.xh-color-row {
  display: inline-flex;
  align-items: center;
  border: 1px solid var(--xh-border);
  border-radius: var(--xh-radius-sm);
  overflow: hidden;
  transition: border-color var(--xh-transition), box-shadow var(--xh-transition);
  background: var(--xh-card);
}
.xh-color-row:focus-within {
  border-color: var(--xh-primary);
  box-shadow: 0 0 0 3px var(--xh-primary-ring);
}
.xh-color-row__swatch {
  width: 40px;
  height: 38px;
  padding: 3px;
  border: none;
  border-radius: 0;
  cursor: pointer;
  flex-shrink: 0;
  background: none;
  display: block;
}
.xh-color-row__swatch::-webkit-color-swatch-wrapper {
  padding: 2px;
}
.xh-color-row__swatch::-webkit-color-swatch {
  border: 1px solid var(--xh-border);
  border-radius: 3px;
}
.xh-color-row__swatch::-moz-color-swatch {
  border: 1px solid var(--xh-border);
  border-radius: 3px;
}
.xh-color-row__text {
  width: 108px;
  font-family: 'Menlo', 'Consolas', 'Courier New', monospace;
  border: none;
  border-radius: 0;
  border-left: 1px solid var(--xh-border);
  font-size: 13px;
  padding: 8px 10px;
  background: var(--xh-card);
  color: var(--xh-text);
  outline: none;
}
</style>
