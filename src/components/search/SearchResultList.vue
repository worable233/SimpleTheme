<script setup lang="ts">
/**
 * SearchResultList — 搜索结果列表（含加载、空、错误状态）
 */
import UndrawIllustration from '@/components/UndrawIllustration.vue'
import type { WordPressPost } from '@/types/wordpress'

defineProps<{
  results: WordPressPost[]
  query: string
  isSearching: boolean
  hasSearched: boolean
  errorMessage: string
  activeIndex: number
}>()

const emit = defineEmits<{
  (e: 'navigate', index: number): void
  (e: 'retry'): void
  (e: 'update:activeIndex', index: number): void
}>()

interface HighlightPart {
  text: string
  highlighted: boolean
}

function toPlainText(value: string): string {
  const element = document.createElement('div')
  element.innerHTML = value
  return element.textContent || ''
}

function highlightText(text: string, query: string): HighlightPart[] {
  const plainText = toPlainText(text)
  if (!query) return [{ text: plainText, highlighted: false }]

  const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
  const expression = new RegExp(escaped, 'gi')
  const parts: HighlightPart[] = []
  let cursor = 0
  let match: RegExpExecArray | null

  while ((match = expression.exec(plainText)) !== null) {
    if (match.index > cursor) {
      parts.push({ text: plainText.slice(cursor, match.index), highlighted: false })
    }
    parts.push({ text: match[0], highlighted: true })
    cursor = match.index + match[0].length
  }

  if (cursor < plainText.length || parts.length === 0) {
    parts.push({ text: plainText.slice(cursor), highlighted: false })
  }

  return parts
}
</script>

<template>
  <div
    v-if="isSearching || hasSearched || !!errorMessage"
    class="search-modal__results"
  >
    <!-- Loading state -->
    <div v-if="isSearching" class="search-modal__loading">
      <div class="search-modal__loading-dot">搜索中</div>
    </div>

    <!-- Error state -->
    <div v-else-if="errorMessage && !isSearching" class="search-modal__error">
      <UndrawIllustration name="alert" width="240" height="180" svg-class="search-modal__illustration" />
      <p>搜索出错了</p>
      <button class="search-modal__retry" @click="emit('retry')">重试</button>
    </div>

    <!-- Empty state -->
    <div v-else-if="hasSearched && results.length === 0 && !isSearching" class="search-modal__empty">
      <UndrawIllustration name="searching" width="180" height="135" svg-class="search-modal__illustration" />
      <p>未找到与 <strong>"{{ query }}"</strong> 相关的内容</p>
    </div>

    <!-- Results list -->
    <div v-else-if="results.length > 0" class="search-modal__list">
      <div
        v-for="(result, index) in results"
        :key="result.id"
        class="search-modal__result"
        :class="{ 'search-modal__result--active': index === activeIndex }"
        :style="{ animationDelay: `${index * 0.06}s` }"
        @click="emit('navigate', index)"
        @mouseenter="emit('update:activeIndex', index)"
      >
        <h4 class="search-modal__result-title">
          <template v-for="(part, partIndex) in highlightText(result.title?.rendered || '(无标题)', query)" :key="partIndex">
            <mark v-if="part.highlighted" class="search-highlight">{{ part.text }}</mark>
            <template v-else>{{ part.text }}</template>
          </template>
        </h4>
        <p v-if="result.excerpt?.rendered" class="search-modal__result-excerpt">
          <template v-for="(part, partIndex) in highlightText(result.excerpt.rendered.slice(0, 120), query)" :key="partIndex">
            <mark v-if="part.highlighted" class="search-highlight">{{ part.text }}</mark>
            <template v-else>{{ part.text }}</template>
          </template>
        </p>
        <div class="search-modal__result-meta">
          <span class="search-modal__result-date">{{ new Date(result.date).toLocaleDateString('zh-CN') }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.search-modal__results {
  flex: 1;
  overflow-y: auto;
  min-height: 0;
  scrollbar-width: thin;
  scrollbar-color: transparent transparent;
}

.search-modal__results::-webkit-scrollbar {
  width: 6px;
}

.search-modal__results::-webkit-scrollbar-thumb {
  background: transparent;
  border-radius: 6px;
}

.search-modal__results:hover::-webkit-scrollbar-thumb {
  background: var(--scroll);
}

/* ── Loading ── */
.search-modal__loading {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.search-modal__loading-dot {
  font-size: 0.875rem;
  color: var(--secondary);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.search-modal__loading-dot::after {
  content: '';
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--primary);
  animation: loadingPulse 1s ease-in-out infinite;
}

@keyframes loadingPulse {
  0%, 100% { opacity: 0.3; transform: scale(0.8); }
  50% { opacity: 1; transform: scale(1.2); }
}

/* ── Empty / Error ── */
.search-modal__empty,
.search-modal__error {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 1.5rem;
  color: var(--secondary);
  gap: 0.75rem;
  text-align: center;
}

.search-modal__illustration {
  opacity: 0.45;
}

.search-modal__error {
  gap: 1rem;
  padding: 2.5rem 1.5rem;
}

.search-modal__error .search-modal__illustration {
  opacity: 0.5;
  max-width: 240px;
  margin-bottom: 0.5rem;
}

.search-modal__error p {
  font-size: 1.1rem;
  font-weight: 625;
  color: var(--foreground);
  margin: 0;
}

.search-modal__empty strong {
  color: var(--foreground);
}

.search-modal__retry {
  margin-top: 0.25rem;
  padding: 0.5rem 1.25rem;
  font-size: 0.875rem;
  font-weight: 500;
  background: var(--muted);
  border: none;
  border-radius: var(--radius-medium);
  color: var(--foreground);
  cursor: pointer;
  transition: background-color var(--transition-fast);
}

.search-modal__retry:hover {
  background: var(--accent);
}

/* ── Results list ── */
.search-modal__list {
  padding: 0.5rem 0;
}

.search-modal__result {
  display: block;
  padding: 0.875rem 1.25rem;
  cursor: pointer;
  border-left: 3px solid transparent;
  transition: background-color var(--transition-fast), border-color var(--transition-fast);
  text-decoration: none;
  color: inherit;
  animation: resultSlideIn 0.35s var(--ease-out-quart) both;
}

.search-modal__result:hover,
.search-modal__result--active {
  background-color: var(--muted);
  border-left-color: var(--primary);
}

.search-modal__result-title {
  margin: 0 0 0.35rem;
  font-size: 0.95rem;
  font-weight: 600;
  line-height: 1.4;
  color: var(--foreground);
}

.search-modal__result :deep(.search-highlight) {
  background-color: transparent;
  color: var(--foreground);
  font-weight: 700;
  text-decoration: underline;
  text-underline-offset: 2px;
  text-decoration-color: var(--primary);
}

.search-modal__result-excerpt {
  margin: 0 0 0.35rem;
  font-size: 0.825rem;
  line-height: 1.5;
  color: var(--secondary);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.search-modal__result-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.search-modal__result-date {
  font-size: 0.75rem;
  color: var(--secondary);
  opacity: 0.65;
}

@keyframes resultSlideIn {
  from {
    opacity: 0;
    transform: translateY(8px) scale(0.97);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
</style>
