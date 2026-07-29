<script setup lang="ts">
import { ref, onMounted } from 'vue'

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
    <div
      v-if="visible"
      class="cookie-consent fixed bottom-7 left-1/2 z-[99998] inline-flex w-auto max-w-[520px] -translate-x-1/2 items-center gap-3 rounded-full border border-border bg-card py-2.5 pr-[18px] pl-3.5 text-[13px] leading-normal text-foreground shadow-[0_4px_24px_rgba(0,0,0,0.08)] backdrop-blur-2xl max-sm:w-[calc(100%-32px)] max-sm:flex-wrap max-sm:gap-2.5 max-sm:rounded-large max-sm:px-4 max-sm:py-3"
    >
      <i class="bx bxs-cookie -mt-px shrink-0 text-[22px] text-muted-foreground"></i>
      <p
        class="m-0 flex-1 overflow-hidden text-ellipsis whitespace-nowrap max-sm:whitespace-normal"
      >
        {{ message }}
      </p>
      <button
        class="shrink-0 cursor-pointer rounded-full border-none bg-primary px-4 py-[5px] text-xs font-medium whitespace-nowrap text-primary-foreground transition-[opacity,transform] duration-200 hover:scale-[1.03] hover:opacity-85 active:scale-[0.97]"
        @click="accept"
      >
        知道了
      </button>
    </div>
  </Teleport>
</template>

<style scoped>
/* Entry animation (keyframes stay in CSS — utility exception) */
.cookie-consent {
  animation: cookie-in 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes cookie-in {
  from {
    opacity: 0;
    transform: translateX(-50%) translateY(16px) scale(0.96);
  }
  to {
    opacity: 1;
    transform: translateX(-50%) translateY(0) scale(1);
  }
}
</style>
