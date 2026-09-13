<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AppIcon from '@/components/AppIcon.vue'

defineProps<{
  message: string
}>()

const visible = ref(false)

onMounted(() => {
  if (!localStorage.getItem('cookie_consent')) {
    visible.value = true
  }
})

function accept() {
  localStorage.setItem('cookie_consent', '1')
  visible.value = false
}
</script>

<template>
  <Teleport to="body">
    <Transition name="cookie" appear>
      <div
        v-if="visible"
        class="cookie-consent fixed bottom-7 left-1/2 z-[99998] inline-flex w-auto max-w-[520px] -translate-x-1/2 items-center gap-3 rounded-full border border-border bg-card py-2.5 pr-[18px] pl-3.5 text-[13px] leading-normal text-foreground shadow-[0_4px_24px_rgba(0,0,0,0.08)] backdrop-blur-2xl max-sm:w-[calc(100%-32px)] max-sm:flex-wrap max-sm:gap-2.5 max-sm:rounded-large max-sm:px-4 max-sm:py-3"
      >
        <AppIcon name="cookie" filled :size="22" class="-mt-px shrink-0 text-muted-foreground" />
        <p
          class="m-0 flex-1 overflow-hidden text-ellipsis whitespace-nowrap max-sm:whitespace-normal"
        >
          {{ message }}
        </p>
        <button
          class="shrink-0 cursor-pointer rounded-full border-none bg-primary px-4 py-[5px] text-xs font-medium whitespace-nowrap text-primary-foreground transition-opacity duration-200 hover:opacity-85 active:opacity-70"
          @click="accept"
        >
          知道了
        </button>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
/* Use a spring-like ease-out for both mounting and dismissal without overscaling. */
.cookie-enter-active,
.cookie-leave-active {
  transition:
    opacity 0.38s cubic-bezier(0.22, 1, 0.36, 1),
    translate 0.48s cubic-bezier(0.16, 1, 0.3, 1);
}

.cookie-enter-from,
.cookie-leave-to {
  opacity: 0;
  translate: 0 18px;
}

@media (prefers-reduced-motion: reduce) {
  .cookie-enter-active,
  .cookie-leave-active {
    transition-duration: 0.01ms;
  }
}
</style>
