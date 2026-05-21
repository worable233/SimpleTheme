<script setup lang="ts">
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
  <router-link
    :to="{ path: '/go', query: { url: link.url } }"
    class="link-card"
  >
    <div class="link-card__inner">
      <!-- 头像 -->
      <div class="link-card__avatar-wrap">
        <img
          v-if="link.image"
          :src="link.image"
          :alt="link.name"
          class="link-card__avatar"
          loading="lazy"
          referrerpolicy="no-referrer"
        />
        <span v-else class="link-card__avatar-letter">{{ link.name.charAt(0) }}</span>
      </div>

      <!-- 站点名称 -->
      <h3 class="link-card__name">{{ decodeHtml(link.name) }}</h3>
    </div>

    <!-- Hover tooltip -->
    <div class="link-card__tip">
      <div class="link-card__tip-top">
        <div class="link-card__tip-avatar">
          <img v-if="link.image" :src="link.image" alt="" />
          <span v-else>{{ link.name.charAt(0) }}</span>
        </div>
        <div class="link-card__tip-info">
          <span class="link-card__tip-name">{{ decodeHtml(link.name) }}</span>
          <span class="link-card__tip-url">{{ getDomain(link.url) }}</span>
        </div>
      </div>
      <div v-if="link.description" class="link-card__tip-desc">{{ decodeHtml(link.description) }}</div>
    </div>
  </router-link>
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
  transition: all 0.25s cubic-bezier(0.55, 0, 0.85, 0.25);
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

/* Avatar */
.link-card__avatar-wrap {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
  background: var(--muted);
}

.link-card__avatar {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.link-card__avatar-letter {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  font-weight: 600;
  color: var(--primary);
  background: var(--muted);
  user-select: none;
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

/* ============ Hover Tooltip ============ */
.link-card__tip {
  position: absolute;
  bottom: calc(100% + 6px);
  left: 50%;
  transform: translateX(-50%) translateY(8px);
  width: 260px;
  background: var(--card);
  border: none;
  border-radius: var(--radius-large, 8px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.2s 0.2s, visibility 0s 0.4s, transform 0.25s 0.2s cubic-bezier(0.55, 0, 0.8, 0.25);
  pointer-events: none;
  z-index: 10;
}

body[data-theme='dark'] .link-card__tip {
  box-shadow: 0 6px 24px rgba(0, 0, 0, 0.45);
}

.link-card:hover .link-card__tip {
  transition-delay: 0s;
  opacity: 1;
  visibility: visible;
  transform: translateX(-50%) translateY(0);
  pointer-events: auto;
}

/* Arrow */
.link-card__tip::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 50%;
  margin-left: -5px;
  border: 5px solid transparent;
  border-top-color: var(--card);
  filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.08));
}

/* Tip avatar */
.link-card__tip-avatar {
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
  background: var(--muted);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--primary);
}

.link-card__tip-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
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

.link-card__tip-desc {
  padding: 0.5rem 0.75rem 0.5rem;
  background: var(--muted);
  border-radius: 0 0 var(--radius-large, 8px) var(--radius-large, 8px);
  font-size: 0.75rem;
  color: var(--secondary);
  line-height: 1.45;
  max-width: 100%;
  word-wrap: break-word;
}

/* Tip staggered entrance */
.link-card__tip-avatar,
.link-card__tip-name,
.link-card__tip-url,
.link-card__tip-desc {
  opacity: 0;
  transform: translateY(4px);
  transition: all 0.2s cubic-bezier(0.55, 0, 0.8, 0.25);
  transition-delay: 0.2s;
}

.link-card:hover .link-card__tip-avatar {
  opacity: 1;
  transform: translateY(0);
  transition-delay: 0s;
}

.link-card:hover .link-card__tip-name {
  opacity: 1;
  transform: translateY(0);
  transition-delay: 0.02s;
}

.link-card:hover .link-card__tip-url {
  opacity: 1;
  transform: translateY(0);
  transition-delay: 0.04s;
}

.link-card:hover .link-card__tip-desc {
  opacity: 1;
  transform: translateY(0);
  transition-delay: 0.06s;
}

/* Top row */
.link-card__tip-top {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  padding: 0.625rem 0.75rem 0.5rem;
}

/* ============ Responsive ============ */
@media (max-width: 640px) {
  .link-card__inner {
    padding: 0.75rem;
  }
}
</style>
