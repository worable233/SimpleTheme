<script setup lang="ts">
/**
 * StIconPicker — 图标选择器
 *
 * 用法：
 *   <StIconPicker v-model="icon" aria-label="图标" />
 *   <StIconPicker v-model="icon" clearable placeholder="选择图标" />
 *
 * 清单来自 `@/lib/icon-library`（精选、保证可渲染）。存储值为 Tabler kebab 名，
 * 展示为最短语义名；历史里存的语义名/别名同样能被选中态与展示识别。
 */
import { computed, nextTick, ref, watch } from 'vue'
import { PopoverContent, PopoverPortal, PopoverRoot, PopoverTrigger } from 'reka-ui'
import StIcon from './StIcon.vue'
import { iconLabel, searchIcons } from '@/lib/icon-library'
import type { StSize } from '../types'

defineOptions({ name: 'StIconPicker' })

const props = withDefaults(
  defineProps<{
    /** 尺寸 */
    size?: StSize
    disabled?: boolean
    /** 是否展示「清除」按钮（仅在当前有值时出现） */
    clearable?: boolean
    /** 空值时的占位文案 */
    placeholder?: string
    /** 无障碍名称；无可见 label 时必填 */
    ariaLabel?: string
  }>(),
  {
    size: 'medium',
    clearable: false,
    placeholder: '选择图标',
  },
)

const model = defineModel<string>({ default: '' })

const emit = defineEmits<{
  (e: 'change', value: string): void
  (e: 'clear'): void
}>()

const open = ref(false)
const query = ref('')
const searchEl = ref<HTMLInputElement | null>(null)

const filtered = computed(() => searchIcons(query.value))
const label = computed(() => iconLabel(model.value))

const classes = computed(() => [
  'st-icon-picker',
  `st-icon-picker--${props.size}`,
  { 'st-icon-picker--disabled': props.disabled },
])

function commit(next: string) {
  if (model.value === next) return
  model.value = next
  emit('change', next)
}

function pick(name: string) {
  commit(name)
  open.value = false
}

function clear() {
  commit('')
  emit('clear')
  open.value = false
}

// 每次打开都重新起搜，避免残留上次的关键词
watch(open, (isOpen) => {
  if (!isOpen) return
  query.value = ''
  nextTick(() => searchEl.value?.focus())
})

function onOpenAutoFocus(event: Event) {
  // 让搜索框而不是面板容器接管初始焦点
  event.preventDefault()
  nextTick(() => searchEl.value?.focus())
}

function onSearch(event: Event) {
  query.value = (event.target as HTMLInputElement).value
}
</script>

<template>
  <PopoverRoot v-model:open="open">
    <PopoverTrigger as-child>
      <button type="button" :class="classes" :disabled="disabled" :aria-label="ariaLabel">
        <span class="st-icon-picker__preview" :class="{ 'is-empty': !model }">
          <StIcon v-if="model" :name="model" :size="18" />
        </span>
        <span class="st-icon-picker__text" :class="{ 'is-empty': !model }">
          {{ model ? label || model : placeholder }}
        </span>
        <StIcon class="st-icon-picker__caret" name="chevron-down" :size="14" />
      </button>
    </PopoverTrigger>

    <PopoverPortal>
      <PopoverContent
        class="st-icon-picker__panel"
        side="bottom"
        align="start"
        :side-offset="6"
        :collision-padding="8"
        @open-auto-focus="onOpenAutoFocus"
      >
        <div class="st-icon-picker__search">
          <StIcon class="st-icon-picker__search-icon" name="search" :size="14" />
          <input
            ref="searchEl"
            class="st-icon-picker__search-input"
            type="text"
            spellcheck="false"
            autocomplete="off"
            placeholder="搜索图标…"
            aria-label="搜索图标"
            :value="query"
            @input="onSearch"
          />
        </div>

        <div v-if="filtered.length" class="st-icon-picker__grid" role="listbox" aria-label="图标">
          <button
            v-for="item in filtered"
            :key="item.name"
            type="button"
            class="st-icon-picker__item"
            :class="{ 'is-active': item.name === model }"
            role="option"
            :aria-selected="item.name === model"
            :title="item.label"
            @click="pick(item.name)"
          >
            <StIcon :name="item.name" :size="18" />
            <StIcon
              v-if="item.name === model"
              class="st-icon-picker__check"
              name="check"
              :size="10"
            />
          </button>
        </div>
        <p v-else class="st-icon-picker__none">未找到匹配的图标</p>

        <div v-if="clearable && model" class="st-icon-picker__footer">
          <button type="button" class="st-icon-picker__clear" @click="clear">
            <StIcon name="x" :size="12" />
            <span>清除</span>
          </button>
        </div>
      </PopoverContent>
    </PopoverPortal>
  </PopoverRoot>
</template>

<style scoped>
/* ==================== 触发器 ==================== */
.st-icon-picker {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  box-sizing: border-box;
  width: 100%;
  height: var(--st-height-medium);
  padding: 0 10px;
  /* 与 StInput / StSelect / StColorPicker 共用同一套皮肤令牌 */
  border: 1px solid var(--st-input-border-color);
  border-radius: var(--radius-medium);
  background-color: var(--st-input-fill);
  color: var(--st-text);
  font-family: inherit;
  font-size: var(--st-font-medium);
  text-align: left;
  cursor: pointer;
  transition:
    border-color var(--transition-fast),
    box-shadow var(--transition-fast);
}

.st-icon-picker:hover:not(.st-icon-picker--disabled) {
  border-color: var(--st-input-border-hover);
}

.st-icon-picker:focus-visible {
  outline: none;
  border-color: var(--st-input-border-focus);
  box-shadow: var(--st-input-shadow-focus);
}

.st-icon-picker--disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.st-icon-picker--tiny {
  height: var(--st-height-tiny);
  font-size: var(--st-font-tiny);
  padding: 0 7px;
  border-radius: var(--radius-small);
}

.st-icon-picker--small {
  height: var(--st-height-small);
  font-size: var(--st-font-small);
  padding: 0 9px;
  border-radius: var(--radius-small);
}

.st-icon-picker--large {
  height: var(--st-height-large);
  font-size: var(--st-font-large);
  padding: 0 12px;
}

.st-icon-picker__preview {
  display: inline-flex;
  flex: none;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  color: var(--st-text);
}

/* 无值时用虚线小方框表达「未选择」 */
.st-icon-picker__preview.is-empty {
  border: 1px dashed var(--st-input-border-color);
  border-radius: var(--radius-xs);
}

.st-icon-picker--tiny .st-icon-picker__preview {
  width: 14px;
  height: 14px;
}

.st-icon-picker--large .st-icon-picker__preview {
  width: 20px;
  height: 20px;
}

.st-icon-picker__text {
  flex: 1 1 auto;
  min-width: 0;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.st-icon-picker__text.is-empty {
  color: var(--st-placeholder);
}

.st-icon-picker__caret {
  flex: none;
  color: var(--st-placeholder);
}

/* ==================== 浮层面板 ==================== */
/* reka-ui 把面板 Portal 到 body，面板根拿不到本组件的 data-v-*，
 * scoped 选择器不命中；用 :global() 命名空间化（st- 前缀已足够唯一）。 */
:global(.st-icon-picker__panel) {
  z-index: var(--st-z-dropdown);
  display: flex;
  flex-direction: column;
  gap: 8px;
  box-sizing: border-box;
  width: 288px;
  padding: 10px;
  border: var(--st-popover-border);
  border-radius: var(--radius-medium);
  background-color: var(--st-popover-bg);
  box-shadow: var(--st-popover-shadow);
  color: var(--st-text);
  font-size: var(--st-font-small);
}

.st-icon-picker__search {
  display: flex;
  flex: none;
  align-items: center;
  gap: 6px;
  box-sizing: border-box;
  height: var(--st-height-small);
  padding: 0 8px;
  border: 1px solid var(--st-input-border-color);
  border-radius: var(--radius-small);
  background-color: var(--st-input-fill);
  transition:
    border-color var(--transition-fast),
    box-shadow var(--transition-fast);
}

.st-icon-picker__search:focus-within {
  border-color: var(--st-input-border-focus);
  box-shadow: var(--st-input-shadow-focus);
}

.st-icon-picker__search-icon {
  flex: none;
  color: var(--st-placeholder);
}

/* 输入框：用父级类提高特异性，压过 WP 后台全局的
 * `input[type='text']:focus`（蓝框）—— portal 到 body 后仍可能命中。 */
.st-icon-picker__search .st-icon-picker__search-input {
  flex: 1 1 auto;
  min-width: 0;
  margin: 0;
  padding: 0;
  border: none;
  border-radius: 0;
  outline: none;
  appearance: none;
  -webkit-appearance: none;
  background: transparent;
  box-shadow: none;
  color: var(--st-text);
  font-family: inherit;
  font-size: var(--st-font-small);
  line-height: normal;
}

.st-icon-picker__search .st-icon-picker__search-input:focus {
  border: none;
  outline: none;
  box-shadow: none;
}

.st-icon-picker__search .st-icon-picker__search-input::placeholder {
  color: var(--st-placeholder);
}

.st-icon-picker__grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
  max-height: 236px;
  padding: 2px;
  overflow-y: auto;
  overscroll-behavior: contain;
}

.st-icon-picker__item {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  aspect-ratio: 1;
  padding: 0;
  border: 1px solid transparent;
  border-radius: var(--radius-small);
  background: transparent;
  color: var(--st-text);
  cursor: pointer;
  transition:
    background-color var(--transition-fast),
    color var(--transition-fast),
    box-shadow var(--transition-fast);
}

.st-icon-picker__item:hover {
  background-color: var(--st-fill-hover);
}

.st-icon-picker__item:focus-visible {
  outline: none;
  box-shadow: var(--st-focus-ring);
}

.st-icon-picker__item.is-active {
  background-color: var(--st-fill-hover);
  color: var(--primary);
}

/* 选中角标：用面板底色描一圈，避免和图标描边糊在一起 */
.st-icon-picker__check {
  position: absolute;
  right: 1px;
  bottom: 1px;
  color: var(--primary);
}

.st-icon-picker__none {
  margin: 0;
  padding: 12px 0;
  color: var(--st-placeholder);
  font-size: var(--st-font-small);
  text-align: center;
}

.st-icon-picker__footer {
  display: flex;
  flex: none;
  justify-content: flex-end;
  border-top: 1px solid var(--border);
  padding-top: 8px;
}

.st-icon-picker__clear {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  height: var(--st-height-small);
  padding: 0 8px;
  border: 1px solid transparent;
  border-radius: var(--radius-small);
  background: transparent;
  color: var(--st-placeholder);
  font-family: inherit;
  font-size: var(--st-font-small);
  cursor: pointer;
  transition:
    background-color var(--transition-fast),
    color var(--transition-fast);
}

.st-icon-picker__clear:hover {
  background-color: var(--st-fill-hover);
  color: var(--danger);
}

.st-icon-picker__clear:focus-visible {
  outline: none;
  box-shadow: var(--st-focus-ring);
}

@media (prefers-reduced-motion: reduce) {
  .st-icon-picker,
  .st-icon-picker__search,
  .st-icon-picker__search-input,
  .st-icon-picker__item,
  .st-icon-picker__clear {
    transition: none;
  }
}
</style>
