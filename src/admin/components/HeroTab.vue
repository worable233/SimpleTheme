<script setup lang="ts">
defineProps<{
  settings: Record<string, unknown>
  defaults: Record<string, unknown>
}>()

const emit = defineEmits<{
  (e: 'update', key: string, value: unknown): void
}>()

function openMediaLibrary(key: string) {
  const wp = (window as any).wp
  if (!wp?.media) return
  const frame = wp.media({
    title: '选择图片',
    button: { text: '使用此图片' },
    multiple: false,
  })
  frame.on('select', () => {
    const attachment = frame.state().get('selection').first().toJSON()
    emit('update', key, attachment.url)
  })
  frame.open()
}
</script>

<template>
  <div class="sta-section">
    <h3 class="sta-section__title">封面区域</h3>
    <p class="sta-section__desc">配置首页侧边栏顶部封面区域的背景图和头像。封面区域始终显示（当设置了背景图或头像时）。</p>
  </div>

  <div class="sta-section">
    <h3 class="sta-section__title">背景图</h3>
    <div class="sta-field">
      <label class="sta-field__label">背景图 URL</label>
      <div class="sta-input-row">
        <input
          type="url"
          class="sta-input sta-input--flex"
          :value="(settings.hero_image as string) || ''"
          placeholder="https://example.com/image.jpg"
          @input="emit('update', 'hero_image', ($event.target as HTMLInputElement).value)"
        />
        <button type="button" class="sta-btn" @click="openMediaLibrary('hero_image')">选择图片</button>
      </div>
      <p class="sta-field__desc">显示在侧边栏封面区域顶部的背景图片。</p>
    </div>
  </div>

  <div class="sta-section">
    <h3 class="sta-section__title">头像</h3>
    <div class="sta-field">
      <label class="sta-checkbox">
        <input
          type="checkbox"
          :checked="!!settings.hero_show_avatar"
          @change="emit('update', 'hero_show_avatar', ($event.target as HTMLInputElement).checked)"
        />
        在封面区域显示头像
      </label>
    </div>
    <div class="sta-field">
      <label class="sta-field__label">头像 URL</label>
      <div class="sta-input-row">
        <input
          type="url"
          class="sta-input sta-input--flex"
          :value="(settings.hero_avatar as string) || ''"
          placeholder="https://example.com/avatar.jpg"
          @input="emit('update', 'hero_avatar', ($event.target as HTMLInputElement).value)"
        />
        <button type="button" class="sta-btn" @click="openMediaLibrary('hero_avatar')">选择图片</button>
      </div>
    </div>
  </div>

  <div class="sta-section">
    <h3 class="sta-section__title">描述语</h3>
    <div class="sta-field">
      <label class="sta-field__label">副标题/描述</label>
      <textarea
        class="sta-textarea"
        rows="2"
        :value="(settings.hero_subtitle as string) || ''"
        placeholder="写给自己的介绍..."
        @input="emit('update', 'hero_subtitle', ($event.target as HTMLTextAreaElement).value)"
      ></textarea>
      <p class="sta-field__desc">显示在头像下方的简短描述。</p>
    </div>
  </div>
</template>
