<script setup lang="ts">
/**
 * AnnouncementModal — 首页公告弹窗
 *
 * 外壳（遮罩 / 焦点陷阱 / ESC / 滚动锁定 / 右上角 ESC 键帽关闭按钮）统一由
 * src/ui 的 StModal 提供，本组件只保留公告内容排版与关闭记忆逻辑。
 */
import { ref, onMounted } from 'vue'
import type { AnnouncementSettings } from '@/types/wordpress'
import { StButton, StModal } from '@/ui'

const props = defineProps<{
  announcement: AnnouncementSettings
}>()

const STORAGE_KEY = 'announcement_modal_dismissed'

const open = ref(false)

function readDismissed(): boolean {
  if (props.announcement.alwaysShow) return false
  try {
    return !!localStorage.getItem(STORAGE_KEY)
  } catch {
    return false
  }
}

/** 关闭（含 ESC / 遮罩 / 关闭按钮）：非「每次都展示」时记住，避免下次自动弹出 */
function close() {
  if (!props.announcement.alwaysShow) {
    try {
      localStorage.setItem(STORAGE_KEY, '1')
    } catch {
      /* localStorage 不可用时静默降级 */
    }
  }
  open.value = false
}

onMounted(() => {
  if (!readDismissed()) open.value = true
})

function isSafeExternalUrl(value: string): boolean {
  try {
    const url = new URL(value)
    return url.protocol === 'http:' || url.protocol === 'https:'
  } catch {
    return false
  }
}

function handleButtonClick(button: { action?: 'close' | 'link'; url?: string }) {
  if (button.action === 'link' && button.url && isSafeExternalUrl(button.url)) {
    window.open(button.url, '_blank', 'noopener,noreferrer')
  }
  close()
}
</script>

<template>
  <StModal
    v-model:show="open"
    :title="announcement.pageTitle || '公告'"
    size="medium"
    @close="close"
  >
    <div class="announcement-modal__body" v-html="announcement.pageContent || ''"></div>

    <template v-if="announcement.buttons?.length" #footer>
      <StButton
        v-for="(btn, i) in announcement.buttons"
        :key="i"
        type="primary"
        @click="handleButtonClick(btn)"
      >
        {{ btn.text }}
      </StButton>
    </template>
  </StModal>
</template>

<style scoped>
/* 公告正文由页面内容渲染，这里只做排版约束（外壳样式见 StModal） */
.announcement-modal__body {
  font-size: 13px;
  line-height: 1.7;
}

.announcement-modal__body :deep(> :first-child) {
  margin-top: 0;
}

.announcement-modal__body :deep(> :last-child) {
  margin-bottom: 0;
}
</style>
