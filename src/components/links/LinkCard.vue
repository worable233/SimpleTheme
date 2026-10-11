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
    animation="slide"
    :padded="false"
    :arrow-color="link.description ? 'var(--muted)' : undefined"
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

    <!-- 悬停预览卡片：头部 + 描述条各自贴边，描述条铺满浮层下沿 -->
    <div class="link-card__tip">
      <div class="link-card__tip-top">
        <StAvatar
          class="link-card__tip-avatar"
          :src="link.image"
          alt=""
          :size="36"
          :fallback-text="link.name"
        />
        <div class="link-card__tip-info">
          <span class="link-card__tip-name">{{ decodeHtml(link.name) }}</span>
          <span class="link-card__tip-url">{{ getDomain(link.url) }}</span>
        </div>
      </div>
      <div v-if="link.description" class="link-card__tip-desc">
        {{ decodeHtml(link.description) }}
      </div>
    </div>
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
/* 裸面板（:padded="false"）由本组件自行贴边排版 */
.link-card__tip {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border-radius: inherit;
}

.link-card__tip-top {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  padding: 0.75rem;
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

/* 描述区铺满浮层下沿：裸面板下无需负边距，直接整宽 */
.link-card__tip-desc {
  padding: 8px 12px;
  background: var(--muted);
  font-size: 0.75rem;
  color: var(--secondary);
  line-height: 1.45;
  word-wrap: break-word;
  overflow-wrap: anywhere;
}

/* ============ 内容分层错峰进场（复刻 v2.0.5 悬停指示器） ============
 * 面板滑入由 StPopover 的 slide 档负责（0.25s cubic-bezier(0.55,0,0.8,0.25)）；
 * 这里让 头像 / 名称 / 域名 / 描述 各自以 0.2s + 错峰延迟上浮淡入，形成层次感。
 * reka 在打开时才挂载内容节点，首帧即终态，transition 不会触发；
 * 故用 animation（挂载即播放）+ animation-delay 复刻 v2.0.5 的错峰节奏。 */
.link-card__tip-avatar,
.link-card__tip-name,
.link-card__tip-url,
.link-card__tip-desc {
  animation: st-link-tip-in 0.2s cubic-bezier(0.55, 0, 0.8, 0.25) both;
}

.link-card__tip-avatar {
  animation-delay: 0s;
}

.link-card__tip-name {
  animation-delay: 0.02s;
}

.link-card__tip-url {
  animation-delay: 0.04s;
}

.link-card__tip-desc {
  animation-delay: 0.06s;
}

@media (prefers-reduced-motion: reduce) {
  .link-card__tip-avatar,
  .link-card__tip-name,
  .link-card__tip-url,
  .link-card__tip-desc {
    animation: none;
  }
}

@keyframes st-link-tip-in {
  from {
    opacity: 0;
    transform: translateY(4px);
  }
}

/* ============ Responsive ============ */
@media (max-width: 640px) {
  .link-card__inner {
    padding: 0.75rem;
  }
}
</style>
