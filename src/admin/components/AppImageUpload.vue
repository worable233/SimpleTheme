<script setup lang="ts">
defineProps<{
  modelValue: string
  placeholder?: string
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', val: string): void
}>()

declare global {
  interface Window {
    wp?: {
      media: (opts: {
        title: string
        button: { text: string }
        multiple: boolean
      }) => {
        on: (event: string, cb: () => void) => void
        open: () => void
        state: () => {
          get: (sel: string) => {
            first: () => {
              toJSON: () => { url: string }
            }
          }
        }
      }
    }
  }
}

function openMedia() {
  if (typeof window.wp !== 'undefined' && window.wp.media) {
    const frame = window.wp.media({
      title: '选择图片',
      button: { text: '使用此图片' },
      multiple: false,
    })
    frame.on('select', () => {
      const attachment = frame.state().get('selection').first().toJSON()
      emit('update:modelValue', attachment.url)
    })
    frame.open()
  }
}
</script>

<template>
  <div class="xh-upload">
    <div class="xh-input-row">
      <input
        type="text"
        class="xh-input"
        :value="modelValue"
        :placeholder="placeholder || '输入图片 URL 或点击选择'"
        @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      />
      <button type="button" class="xh-btn" @click="openMedia">选择图片</button>
    </div>
    <div v-if="modelValue" class="xh-upload__preview">
      <img :src="modelValue" alt="预览" />
    </div>
  </div>
</template>

<style scoped>
.xh-upload {
  max-width: 400px;
}
.xh-upload__preview {
  margin-top: 12px;
  border-radius: 10px;
  overflow: hidden;
  border: 1px solid var(--xh-border);
}
.xh-upload__preview img {
  display: block;
  width: 100%;
  height: auto;
  max-height: 200px;
  object-fit: cover;
}
</style>
