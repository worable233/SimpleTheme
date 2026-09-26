<script setup lang="ts">
import { StAvatar, StPopover } from '@/ui'

defineProps<{
  link: {
    id: number
    name: string
    url: string
    description: string
    image: string
  }
}>()

function getDomain(url: string): string {
  try {
    const u = new URL(url)
    return u.hostname
  } catch {
    return url
  }
}

/** Decode HTML entities (&#039; → ', &amp; → &, etc.) */
function decodeHtml(str: string): string {
  const el = document.createElement('div')
  el.innerHTML = str
  return el.textContent || ''
}
</script>

<template>
  <!--
    悬停预览统一用 src/ui 的 StPopover（reka-ui）：定位 / 碰撞翻转 / 端口
    由浮层库负责；点击仍走 router-link 跳转到 /go。外观沿用本主题令牌。
  -->
  <StPopover
    trigger="hover"
    placement="top"
    width="260px"
    show-arrow
    block
  >
    <template #trigger>
      <router-link
        :to="{ path: '/go', query: { url: link.url } }"
        class="link-card"
      >
        <div class="link-card__inner">
          <!-- 头像 -->
          <StAvatar
            :src="link.image"
            :alt="link.name"
            :fallback-text="link.name"
            :size="40"
            referrer-policy="no-referrer"
          />

          <!-- 站点名称 -->
          <h3 class="link-card__name">{{ decodeHtml(link.name) }}</h3>
        </div>
      </router-link>
    </template>

    <!-- 悬停预览卡片 -->
    <div class="link-card__tip-top">
      <StAvatar :src="link.image" alt="" :size="36" :fallback-text="link.name" />
      <div class="link-card__tip-info">
        <span class="link-card__tip-name">{{ decodeHtml(link.name) }}</span>
        <span class="link-card__tip-url">{{ getDomain(link.url) }}</span>
      </div>
    </div>
    <div v-if="link.description" class="link-card__tip-desc">{{ decodeHtml(link.description) }}</div>
  </StPopover>
</template>

<style scoped>
/* ============ Link Card ============ */
.link-card {
  position: relative;
  display: block;
  text-decoration: none;
  color: inherit;
  border-radius: var(--radius-large, 8px);
  background: var(--card);
  border: 1px solid var(--border, transparent);
  transition: all 0.25s var(--ease-in-out);
}

.link-card:hover {
  border-color: var(--primary);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

/* Inner layout */
.link-card__inner {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem;
}

/* Name */
.link-card__name {
  flex: 1;
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--foreground);
  margin: 0;
  line-height: 1.3;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ============ Hover Popover ============ */
.link-card__tip-top {
  display: flex;
  align-items: center;
  gap: 0.625rem;
}

.link-card__tip-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.link-card__tip-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--foreground);
  line-height: 1.3;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.link-card__tip-url {
  font-size: 0.6875rem;
  color: var(--primary);
  opacity: 0.55;
  line-height: 1.25;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* 描述区铺满浮层下沿：抵消 StPopover 内容区的 12px 内边距 */
.link-card__tip-desc {
  margin: 10px -12px -12px;
  padding: 8px 12px;
  background: var(--muted);
  border-radius: 0 0 var(--radius-medium) var(--radius-medium);
  font-size: 0.75rem;
  color: var(--secondary);
  line-height: 1.45;
  max-width: 100%;
  word-wrap: break-word;
}

/* ============ Responsive ============ */
@media (max-width: 640px) {
  .link-card__inner {
    padding: 0.75rem;
  }
}
</style>
