<script setup lang="ts">
import { computed } from 'vue'
import { initials } from '@/shared/lib/format'

const props = withDefaults(
  defineProps<{ name: string; size?: 'sm' | 'md' | 'lg'; highlight?: boolean }>(),
  { size: 'md', highlight: false },
)

const sizes = {
  sm: 'h-7 w-7 text-[11px]',
  md: 'h-9 w-9 text-xs',
  lg: 'h-12 w-12 text-sm',
}

// Стабильный оттенок из имени — в пределах спокойной палитры.
const hue = computed(() => {
  let h = 0
  for (const ch of props.name) h = (h * 31 + ch.charCodeAt(0)) % 360
  return h
})
</script>

<template>
  <span
    class="inline-flex select-none items-center justify-center rounded-full border font-semibold uppercase"
    :class="[
      sizes[size],
      highlight ? 'border-brand bg-brand text-white' : 'border-line text-ink',
    ]"
    :style="
      highlight
        ? undefined
        : { backgroundColor: `hsl(${hue} 40% 94%)`, color: `hsl(${hue} 45% 32%)` }
    "
  >
    {{ initials(name) }}
  </span>
</template>
