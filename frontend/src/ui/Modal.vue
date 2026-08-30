<script setup lang="ts">
import { onBeforeUnmount, onMounted, watch } from 'vue'
import { X } from 'lucide-vue-next'

const props = withDefaults(
  defineProps<{ open: boolean; title?: string; size?: 'sm' | 'md' | 'lg' }>(),
  { size: 'md' },
)
const emit = defineEmits<{ (e: 'close'): void }>()

const sizes = { sm: 'max-w-sm', md: 'max-w-lg', lg: 'max-w-2xl' }

function onKey(e: KeyboardEvent) {
  if (e.key === 'Escape') emit('close')
}

watch(
  () => props.open,
  (open) => {
    document.body.style.overflow = open ? 'hidden' : ''
  },
)

onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKey)
  document.body.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <Transition name="z-modal">
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-0 sm:items-center sm:p-4"
        @click.self="emit('close')"
      >
        <div
          class="w-full rounded-t-card bg-surface shadow-pop sm:rounded-card"
          :class="sizes[size]"
          role="dialog"
          aria-modal="true"
        >
          <header class="flex items-center justify-between border-b border-line px-5 py-4">
            <h3 class="text-base font-bold">{{ title }}</h3>
            <button
              class="rounded p-1 text-ink-soft transition hover:bg-surface-muted hover:text-ink"
              aria-label="Закрыть"
              @click="emit('close')"
            >
              <X class="h-5 w-5" />
            </button>
          </header>
          <div class="px-5 py-5">
            <slot />
          </div>
          <footer v-if="$slots.footer" class="border-t border-line px-5 py-4">
            <slot name="footer" />
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.z-modal-enter-active,
.z-modal-leave-active {
  transition: opacity 160ms ease;
}
.z-modal-enter-from,
.z-modal-leave-to {
  opacity: 0;
}
</style>
