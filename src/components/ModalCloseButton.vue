<script setup lang="ts">
/**
 * ModalCloseButton — 全局统一的弹窗关闭按钮
 *
 * 桌面端显示 ESC 键帽（所有弹窗均有真实的 Escape 键处理），
 * 触屏设备显示常规圆形 ×（无键盘设备上 ESC 提示无意义）。
 * 定位交由使用方通过 class 控制；点击事件通过 attrs 透传。
 */
</script>

<template>
  <button type="button" class="modal-close-btn" aria-label="关闭">
    <span class="modal-close-btn__esc" aria-hidden="true">ESC</span>
    <svg
      class="modal-close-btn__x"
      aria-hidden="true"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <line x1="18" y1="6" x2="6" y2="18" />
      <line x1="6" y1="6" x2="18" y2="18" />
    </svg>
  </button>
</template>

<style scoped>
/* ===== 桌面形态：ESC 键帽 ===== */
.modal-close-btn {
  min-width: 3rem;
  height: 1.8rem;
  padding: 0 0.5rem;
  border-radius: var(--radius-small, 4px);
  border: 1px solid var(--border, rgba(0, 0, 0, 0.15));
  background: var(--muted, rgba(0, 0, 0, 0.03));
  color: var(--foreground, #666);
  font-family: 'SF Mono', 'Cascadia Code', 'Consolas', monospace;
  font-size: 0.7rem;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  line-height: 1;
  box-shadow: 0 2px 0 var(--border, rgba(0, 0, 0, 0.12));
  user-select: none;
  transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.modal-close-btn:hover {
  transform: translateY(1px);
  box-shadow: 0 1px 0 var(--border, rgba(0, 0, 0, 0.12));
  background: var(--border, rgba(0, 0, 0, 0.06));
  color: var(--foreground, #222);
}

.modal-close-btn:active {
  transform: translateY(2px) scale(0.96);
  box-shadow: none;
}

.modal-close-btn__x {
  display: none;
  width: 18px;
  height: 18px;
}

/* ===== 触屏形态：圆形 × ===== */
@media (hover: none), (pointer: coarse) {
  .modal-close-btn {
    min-width: 0;
    width: 34px;
    height: 34px;
    padding: 0;
    border: none;
    border-radius: 50%;
    box-shadow: none;
    color: var(--secondary, #888);
  }

  .modal-close-btn:hover,
  .modal-close-btn:active {
    transform: none;
    box-shadow: none;
    background: var(--border);
    color: var(--foreground);
  }

  .modal-close-btn__esc {
    display: none;
  }

  .modal-close-btn__x {
    display: block;
  }
}
</style>
