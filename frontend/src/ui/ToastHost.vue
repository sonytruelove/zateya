<script setup lang="ts">
import Toast from './Toast.vue'
import { useToasts } from './toast'

const toasts = useToasts()
</script>

<template>
  <Teleport to="body">
    <div class="pointer-events-none fixed bottom-4 right-4 z-[60] flex flex-col gap-2">
      <TransitionGroup name="z-toast">
        <Toast
          v-for="t in toasts.items"
          :key="t.id"
          :toast="t"
          @dismiss="toasts.dismiss(t.id)"
        />
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style scoped>
.z-toast-enter-active,
.z-toast-leave-active {
  transition: all 180ms ease;
}
.z-toast-enter-from,
.z-toast-leave-to {
  opacity: 0;
  transform: translateX(16px);
}
</style>
