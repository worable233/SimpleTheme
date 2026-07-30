<script setup lang="ts">
const props = defineProps<{
  modelValue: boolean
  label?: string
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', val: boolean): void
}>()

function toggle() {
  emit('update:modelValue', !props.modelValue)
}
</script>

<template>
  <label class="xh-toggle" @click.prevent="toggle">
    <span class="xh-toggle__track" :class="{ 'xh-toggle__track--on': modelValue }">
      <span class="xh-toggle__thumb"></span>
    </span>
    <span v-if="label" class="xh-toggle__label">{{ label }}</span>
  </label>
</template>

<style scoped>
.xh-toggle {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  user-select: none;
}
.xh-toggle__track {
  position: relative;
  width: 40px;
  height: 22px;
  border-radius: 11px;
  background: var(--xh-border);
  transition: background 0.2s ease;
  flex-shrink: 0;
}
.xh-toggle__track--on {
  background: var(--xh-primary);
}
.xh-toggle__thumb {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: var(--xh-card);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
  transition: transform 0.2s ease;
}
.xh-toggle__track--on .xh-toggle__thumb {
  transform: translateX(18px);
}
.xh-toggle__label {
  font-size: 14px;
  color: var(--xh-text);
  line-height: 1.4;
}
</style>
