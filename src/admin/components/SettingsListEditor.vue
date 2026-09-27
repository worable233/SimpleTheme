<script setup lang="ts">
import { ref, watch } from 'vue'
import { StButton, StFormItem, StIcon, StInput, StSelect } from '@/ui'
import type { ListField } from './list-field'

/**
 * SettingsListEditor — 结构化列表编辑器
 *
 * 后端把 social_links / tech_info_items / announcement_buttons 存成 JSON 字符串。
 * 直接让用户改 JSON 极易写坏且无从校验，这里把它还原成「一行一条记录」的
 * 表单，编辑时再序列化回字符串，保持存储格式不变。
 *
 * 字段类型只覆盖项目实际用到的 text / select —— 未用到的类型不预先发明。
 */

const props = withDefaults(
  defineProps<{
    /** JSON 字符串；空串或非法 JSON 都按空列表处理 */
    modelValue: string
    fields: ListField[]
    addLabel?: string
    emptyText?: string
  }>(),
  {
    addLabel: '添加一项',
    emptyText: '暂无内容。',
  },
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
}>()

type Row = Record<string, string>

function parse(raw: string): Row[] {
  if (!raw || !raw.trim()) return []
  try {
    const decoded = JSON.parse(raw)
    if (!Array.isArray(decoded)) return []
    return decoded
      .filter((item): item is Record<string, unknown> => !!item && typeof item === 'object')
      .map((item) => {
        const row: Row = {}
        for (const field of props.fields)
          row[field.key] = item[field.key] == null ? '' : String(item[field.key])
        return row
      })
  } catch {
    return []
  }
}

function blankRow(): Row {
  const row: Row = {}
  for (const field of props.fields)
    row[field.key] = field.type === 'select' ? String(field.options?.[0]?.value ?? '') : ''
  return row
}

const rows = ref<Row[]>(parse(props.modelValue))

watch(
  () => props.modelValue,
  (value) => {
    rows.value = parse(value)
  },
)

function commit() {
  emit('update:modelValue', JSON.stringify(rows.value))
}

function addRow() {
  rows.value.push(blankRow())
  commit()
}

function removeRow(index: number) {
  rows.value.splice(index, 1)
  commit()
}

function updateField(row: Row, key: string, value: string | number) {
  row[key] = String(value)
  commit()
}

function fieldAria(field: ListField, index: number) {
  return `${field.label} ${index + 1}`
}
</script>

<template>
  <div class="list-editor">
    <p v-if="rows.length === 0" class="list-editor__empty">{{ emptyText }}</p>

    <div v-for="(row, index) in rows" :key="index" class="list-editor__row">
      <StFormItem
        v-for="field in fields"
        :key="field.key"
        :label="index === 0 ? field.label : undefined"
        class="list-editor__field"
      >
        <StSelect
          v-if="field.type === 'select'"
          :model-value="row[field.key]"
          :options="field.options || []"
          :aria-label="fieldAria(field, index)"
          @update:model-value="updateField(row, field.key, $event)"
        />
        <StInput
          v-else
          :model-value="row[field.key]"
          :placeholder="field.placeholder"
          :aria-label="fieldAria(field, index)"
          @update:model-value="updateField(row, field.key, $event)"
        />
      </StFormItem>
      <StButton
        class="list-editor__remove"
        text
        type="error"
        circle
        :aria-label="`删除第 ${index + 1} 项`"
        @click="removeRow(index)"
      >
        <template #icon><StIcon name="trash" :size="16" /></template>
      </StButton>
    </div>

    <StButton dashed size="small" @click="addRow">
      <template #icon><StIcon name="plus" :size="16" /></template>
      {{ addLabel }}
    </StButton>
  </div>
</template>

<style scoped>
.list-editor {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.list-editor__empty {
  margin: 0;
  font-size: 13px;
  color: var(--secondary);
}

.list-editor__row {
  display: flex;
  align-items: flex-end;
  gap: 10px;
}

.list-editor__field {
  flex: 1;
  min-width: 0;
}

.list-editor__remove {
  flex: none;
  margin-bottom: 2px;
}
</style>
