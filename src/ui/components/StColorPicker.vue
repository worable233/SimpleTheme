<script setup lang="ts">
/**
 * StColorPicker — 颜色选择器
 *
 * 用法：
 *   <StColorPicker v-model="primary" aria-label="主色" />
 *   <StColorPicker v-model="bg" show-clear :presets="brands" @clear="reset" />
 *
 * presets 是唯一允许出现十六进制字面量的处所：它只作为数据（调用方可整体替换），
 * 不参与任何样式声明。组件自身的样式一律消费设计令牌。
 *
 * 受控：hex 文本非法时保留用户输入并标红，但不写回 model，避免脏值进入设置。
 */
import { computed, ref, watch } from 'vue'
import { PopoverContent, PopoverPortal, PopoverRoot, PopoverTrigger } from 'reka-ui'
import StIcon from './StIcon.vue'
import type { StSize } from '../types'

defineOptions({ name: 'StColorPicker' })

const props = withDefaults(
  defineProps<{
    /** 尺寸 */
    size?: StSize
    disabled?: boolean
    /** 可选色板；传空数组则不展示色板区 */
    presets?: string[]
    /** 是否展示手动输入 hex 的输入框 */
    showInput?: boolean
    /** 是否展示「清除」按钮（仅在当前有值时出现） */
    showClear?: boolean
    /** 无障碍名称；无可见 label 时必填 */
    ariaLabel?: string
  }>(),
  {
    size: 'medium',
    // 默认色板：中性阶 + 常用语义色。
    // 这里是全组件唯一允许出现字面色值的处所 —— 它只作为数据，不参与样式声明；
    // 组件样式一律消费设计令牌。
    presets: () => [
      '#333333',
      '#666666',
      '#999999',
      '#cccccc',
      '#ffffff',
      '#d64545',
      '#d97706',
      '#15803d',
      '#2563eb',
      '#7c3aed',
    ],
    showInput: true,
    showClear: false,
  },
)

const model = defineModel<string>({ default: '' })

const emit = defineEmits<{
  (e: 'change', value: string): void
  (e: 'clear'): void
}>()

const open = ref(false)
/** hex 输入框草稿：非法时保留用户输入，不提交 */
const draft = ref('')
const invalid = ref(false)
/** 聚焦期间不用 model 覆盖草稿：三位短写法会被即时补全成六位，从而打断输入 */
const hexFocused = ref(false)

const HEX_RE = /^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i

/** #RGB/#RRGGBB → 小写 #rrggbb；非法返回 null，调用方据此拒绝提交 */
function normalizeHex(raw: string): string | null {
  const value = raw.trim()
  if (!HEX_RE.test(value)) return null
  const body = value.slice(1).toLowerCase()
  return body.length === 3
    ? `#${body[0]}${body[0]}${body[1]}${body[1]}${body[2]}${body[2]}`
    : `#${body}`
}

const normalized = computed(() => normalizeHex(model.value))

/** 触发器色块：非法值也原样透出，方便调用方发现脏数据 */
const swatchColor = computed(() => normalized.value ?? model.value ?? '')
const swatchStyle = computed(() => ({ backgroundColor: swatchColor.value || 'transparent' }))
/** 原生取色器只接受合法 hex；无值时退到色板首色，色板为空则交给浏览器默认值 */
const nativeValue = computed(() => normalized.value ?? normalizeHex(props.presets[0] ?? '') ?? '')

const classes = computed(() => [
  'st-color-picker',
  `st-color-picker--${props.size}`,
  {
    'st-color-picker--disabled': props.disabled,
  },
])

function commit(next: string) {
  if (model.value === next) return
  model.value = next
  emit('change', next)
}

// 外部改值时同步草稿，但不打断正在输入的 hex 文本
watch(
  () => model.value,
  (value) => {
    if (!hexFocused.value) draft.value = value
  },
)

// 每次打开都从当前值重新起稿，避免残留上次编辑的半截输入
watch(open, (isOpen) => {
  if (!isOpen) return
  draft.value = model.value
  invalid.value = false
})

function isActivePreset(preset: string) {
  return normalized.value !== null && normalizeHex(preset) === normalized.value
}

function pick(preset: string) {
  const hex = normalizeHex(preset)
  if (!hex) return
  draft.value = hex
  invalid.value = false
  commit(hex)
  open.value = false
}

function onNativeInput(ev: Event) {
  const hex = normalizeHex((ev.target as HTMLInputElement).value)
  if (!hex) return
  draft.value = hex
  invalid.value = false
  commit(hex)
}

function onHexInput(ev: Event) {
  const raw = (ev.target as HTMLInputElement).value
  draft.value = raw
  const hex = normalizeHex(raw)
  invalid.value = raw.trim() !== '' && hex === null
  if (hex) commit(hex)
}

function onHexBlur() {
  hexFocused.value = false
  // 失焦时清理草稿：非法输入回退到当前值，合法输入统一显示归一化结果
  draft.value = model.value
  invalid.value = false
}

function clear() {
  draft.value = ''
  invalid.value = false
  commit('')
  emit('clear')
  open.value = false
}
</script>

<template>
  <PopoverRoot v-model:open="open">
    <PopoverTrigger as-child>
      <button type="button" :class="classes" :disabled="disabled" :aria-label="ariaLabel">
        <span
          class="st-color-picker__swatch"
          :class="{ 'is-empty': !swatchColor }"
          :style="swatchStyle"
        />
        <span class="st-color-picker__text" :class="{ 'is-empty': !swatchColor }">
          {{ swatchColor || '未设置' }}
        </span>
        <StIcon class="st-color-picker__caret" name="chevron-down" :size="14" />
      </button>
    </PopoverTrigger>

    <PopoverPortal>
      <PopoverContent
        class="st-color-picker__panel"
        side="bottom"
        align="start"
        :side-offset="6"
        :collision-padding="8"
      >
        <div class="st-color-picker__block">
          <span class="st-color-picker__label">取色</span>
          <input
            class="st-color-picker__native"
            type="color"
            :value="nativeValue"
            :disabled="disabled"
            :aria-label="ariaLabel ? `${ariaLabel}：取色器` : '取色器'"
            @input="onNativeInput"
          />
        </div>

        <div v-if="presets.length" class="st-color-picker__block">
          <span class="st-color-picker__label">色板</span>
          <div class="st-color-picker__presets">
            <button
              v-for="preset in presets"
              :key="preset"
              type="button"
              class="st-color-picker__preset"
              :class="{ 'is-active': isActivePreset(preset) }"
              :style="{ backgroundColor: preset }"
              :aria-label="preset"
              :aria-pressed="isActivePreset(preset)"
              @click="pick(preset)"
            />
          </div>
        </div>

        <div v-if="showInput" class="st-color-picker__block">
          <span class="st-color-picker__label">HEX</span>
          <input
            class="st-color-picker__hex"
            :class="{ 'is-invalid': invalid }"
            type="text"
            maxlength="7"
            spellcheck="false"
            autocomplete="off"
            aria-label="十六进制颜色值"
            :aria-invalid="invalid ? true : undefined"
            :value="draft"
            @input="onHexInput"
            @focus="hexFocused = true"
            @blur="onHexBlur"
          />
        </div>

        <div v-if="showClear && swatchColor" class="st-color-picker__footer">
          <button type="button" class="st-color-picker__clear" @click="clear">
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
.st-color-picker {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  box-sizing: border-box;
  width: 100%;
  height: var(--st-height-medium);
  padding: 0 10px;
  /* 与 StInput / StSelect 共用同一套皮肤令牌：三者是并列的输入类控件，
   * 深色主题下必须一起从「边框盒」切到「半透明填充」，否则触发器之间会
   * 一个显边、一个不显边。 */
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

.st-color-picker:hover:not(.st-color-picker--disabled) {
  border-color: var(--st-input-border-hover);
}

.st-color-picker:focus-visible {
  outline: none;
  border-color: var(--st-input-border-focus);
  box-shadow: var(--st-input-shadow-focus);
}

.st-color-picker--disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.st-color-picker--tiny {
  height: var(--st-height-tiny);
  font-size: var(--st-font-tiny);
  padding: 0 7px;
  border-radius: var(--radius-small);
}

.st-color-picker--small {
  height: var(--st-height-small);
  font-size: var(--st-font-small);
  padding: 0 9px;
  border-radius: var(--radius-small);
}

.st-color-picker--large {
  height: var(--st-height-large);
  font-size: var(--st-font-large);
  padding: 0 12px;
}

/* 色块描边保证浅色/深色下都能看出边界 */
.st-color-picker__swatch {
  flex: none;
  width: 18px;
  height: 18px;
  border: 1px solid var(--border);
  border-radius: var(--radius-small);
}

.st-color-picker--tiny .st-color-picker__swatch {
  width: 14px;
  height: 14px;
}

.st-color-picker--large .st-color-picker__swatch {
  width: 20px;
  height: 20px;
}

/* 无值时用斜杠表达「透明/未设置」，而不是退回某个具体色 */
.st-color-picker__swatch.is-empty {
  background-image: linear-gradient(
    to top right,
    transparent 45%,
    var(--muted-foreground) 45%,
    var(--muted-foreground) 55%,
    transparent 55%
  );
}

.st-color-picker__text {
  flex: 1 1 auto;
  min-width: 0;
  overflow: hidden;
  font-family: var(--font-code);
  white-space: nowrap;
  text-overflow: ellipsis;
}

.st-color-picker__text.is-empty {
  font-family: inherit;
  color: var(--st-placeholder);
}

.st-color-picker__caret {
  flex: none;
  color: var(--st-placeholder);
}

/* ==================== 浮层面板 ==================== */
/* reka-ui 把面板 Portal 到 body，面板根拿不到本组件的 data-v-*，
 * scoped 选择器不命中；用 :global() 命名空间化（st- 前缀已足够唯一）。 */
:global(.st-color-picker__panel) {
  z-index: var(--st-z-dropdown);
  display: flex;
  flex-direction: column;
  gap: 10px;
  box-sizing: border-box;
  width: 208px;
  padding: 10px;
  border: var(--st-popover-border);
  border-radius: var(--radius-medium);
  background-color: var(--st-popover-bg);
  box-shadow: var(--st-popover-shadow);
  color: var(--st-text);
  font-size: var(--st-font-small);
}

.st-color-picker__block {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.st-color-picker__label {
  font-size: var(--st-font-tiny);
  color: var(--st-placeholder);
}

.st-color-picker__native {
  box-sizing: border-box;
  width: 100%;
  height: 28px;
  padding: 2px;
  border: 1px solid var(--st-border);
  border-radius: var(--radius-small);
  background-color: var(--st-fill);
  cursor: pointer;
}

.st-color-picker__native:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.st-color-picker__native::-webkit-color-swatch-wrapper {
  padding: 0;
}

.st-color-picker__native::-webkit-color-swatch {
  border: none;
  border-radius: var(--radius-xs);
}

.st-color-picker__native::-moz-color-swatch {
  border: none;
  border-radius: var(--radius-xs);
}

.st-color-picker__presets {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 6px;
}

.st-color-picker__preset {
  width: 100%;
  aspect-ratio: 1;
  padding: 0;
  border: 1px solid var(--border);
  border-radius: var(--radius-small);
  cursor: pointer;
  transition:
    box-shadow var(--transition-fast),
    transform var(--transition-fast);
}

.st-color-picker__preset:hover {
  transform: scale(1.08);
}

.st-color-picker__preset:focus-visible {
  outline: none;
  box-shadow: var(--st-focus-ring);
}

/* 选中态用双层描边表达：内圈用面板底色隔离，避免和色块本身混在一起 */
.st-color-picker__preset.is-active {
  box-shadow:
    0 0 0 2px var(--st-popover-bg),
    0 0 0 3px var(--ring);
}

.st-color-picker__hex {
  box-sizing: border-box;
  width: 100%;
  height: var(--st-height-small);
  padding: 0 8px;
  border: 1px solid var(--st-input-border-color);
  border-radius: var(--radius-small);
  outline: none;
  background-color: var(--st-input-fill);
  color: var(--st-text);
  font-family: var(--font-code);
  font-size: var(--st-font-small);
  transition:
    border-color var(--transition-fast),
    box-shadow var(--transition-fast);
}

.st-color-picker__hex:focus {
  border-color: var(--st-input-border-focus);
  box-shadow: var(--st-input-shadow-focus);
}

.st-color-picker__hex.is-invalid {
  border-color: var(--danger);
}

.st-color-picker__hex.is-invalid:focus {
  border-color: var(--danger);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--danger) 20%, transparent);
}

.st-color-picker__footer {
  display: flex;
  justify-content: flex-end;
  border-top: 1px solid var(--border);
  padding-top: 8px;
}

.st-color-picker__clear {
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

.st-color-picker__clear:hover {
  background-color: var(--st-fill-hover);
  color: var(--danger);
}

.st-color-picker__clear:focus-visible {
  outline: none;
  box-shadow: var(--st-focus-ring);
}

@media (prefers-reduced-motion: reduce) {
  .st-color-picker,
  .st-color-picker__preset,
  .st-color-picker__hex,
  .st-color-picker__clear {
    transition: none;
  }
}
</style>
