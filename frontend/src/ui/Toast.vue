<script setup lang="ts">
import { CheckCircle2, Info, AlertTriangle, XCircle, X } from 'lucide-vue-next'
import type { ToastItem } from './toast'

defineProps<{ toast: ToastItem }>()
defineEmits<{ (e: 'dismiss'): void }>()

const icons = {
  info: Info,
  success: CheckCircle2,
  warning: AlertTriangle,
  danger: XCircle,
}
const accents = {
  info: 'text-brand',
  success: 'text-success',
  warning: 'text-warning',
  danger: 'text-danger',
}
</script>

<template>
  <div
    class="pointer-events-auto flex w-80 items-start gap-3 rounded-card border border-line bg-surface p-3.5 shadow-pop"
  >
    <component :is="icons[toast.tone]" class="mt-0.5 h-5 w-5 shrink-0" :class="accents[toast.tone]" />
    <div class="min-w-0 flex-1">
      <p class="text-sm font-semibold text-ink">{{ toast.title }}</p>
      <p v-if="toast.text" class="mt-0.5 text-sm text-ink-soft">{{ toast.text }}</p>
    </div>
    <button
      class="rounded p-0.5 text-ink-soft transition hover:text-ink"
      aria-label="Скрыть уведомление"
      @click="$emit('dismiss')"
    >
      <X class="h-4 w-4" />
    </button>
  </div>
</template>
