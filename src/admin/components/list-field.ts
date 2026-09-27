import type { StOption } from '@/ui'

/** SettingsListEditor 的单字段描述。独立成文件以便其它标签页复用同一类型。 */
export interface ListField {
  key: string
  label: string
  type: 'text' | 'select'
  placeholder?: string
  options?: StOption[]
}
