/**
 * src/ui — Simple Theme 组件库
 *
 * 统一出口。业务层按需具名引入，不要全局注册，以保留 tree-shaking：
 *
 *   import { StButton, StCard } from '@/ui'
 *
 * 组件规范见 ./README.md。合规性由 `npm run check:ui` 强制。
 *
 * 注意：本文件由维护者统一维护。新增组件时在这里登记，
 * 组件本身不要互相 import 出口。
 */

// ── 基础元件 ──
export { default as StIcon } from './components/StIcon.vue'
export { default as StMorphIcon } from './components/StMorphIcon.vue'
export { default as StSpinner } from './components/StSpinner.vue'
export { default as StButton } from './components/StButton.vue'
export { default as StDivider } from './components/StDivider.vue'

// ── 布局容器 ──
export { default as StCard } from './components/StCard.vue'
export { default as StStack } from './components/StStack.vue'
export { default as StGrid } from './components/StGrid.vue'

// ── 数据录入 ──
export { default as StInput } from './components/StInput.vue'
export { default as StTextarea } from './components/StTextarea.vue'
export { default as StNumberInput } from './components/StNumberInput.vue'
export { default as StSelect } from './components/StSelect.vue'
export { default as StIconPicker } from './components/StIconPicker.vue'
export { default as StCheckbox } from './components/StCheckbox.vue'
export { default as StRadioGroup } from './components/StRadioGroup.vue'
export { default as StSwitch } from './components/StSwitch.vue'
export { default as StSegmented } from './components/StSegmented.vue'
export { default as StColorPicker } from './components/StColorPicker.vue'
export { default as StImageUpload } from './components/StImageUpload.vue'
export { default as StFormItem } from './components/StFormItem.vue'

// ── 数据展示 ──
export { default as StTag } from './components/StTag.vue'
export { default as StBadge } from './components/StBadge.vue'
export { default as StAvatar } from './components/StAvatar.vue'
export { default as StSkeleton } from './components/StSkeleton.vue'
export { default as StEmpty } from './components/StEmpty.vue'
export { default as StProgress } from './components/StProgress.vue'

// ── 导航 ──
export { default as StTabs } from './components/StTabs.vue'
export { default as StTabPane } from './components/StTabPane.vue'
export { default as StCollapse } from './components/StCollapse.vue'
export { default as StCollapseItem } from './components/StCollapseItem.vue'
export { default as StDropdown } from './components/StDropdown.vue'
export { default as StBreadcrumb } from './components/StBreadcrumb.vue'
export { default as StPagination } from './components/StPagination.vue'

// ── 反馈与浮层 ──
export { default as StAlert } from './components/StAlert.vue'
export { default as StToast } from './components/StToast.vue'
export { default as StModal } from './components/StModal.vue'
export { default as StDrawer } from './components/StDrawer.vue'
export { default as StTooltip } from './components/StTooltip.vue'
export { default as StPopover } from './components/StPopover.vue'

// ── 组合式 ──
export { useToast, showToast, removeToast, clearToasts, toasts } from './composables/useToast'

// ── 类型契约 ──
export type { StSize, StStatus, StIntent, StPlacement, StOption } from './types'
export type { StSpace, StSpaceScale, StCols, StColsInput } from './types'
export type { StToastType, StToastItem, StToastApi } from './composables/useToast'
export { ST_SIZES } from './types'
