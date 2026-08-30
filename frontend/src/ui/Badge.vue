<script setup lang="ts">
import { computed } from 'vue'

type Tone = 'neutral' | 'brand' | 'success' | 'warning' | 'danger'

const props = withDefaults(defineProps<{ tone?: Tone; dot?: boolean }>(), {
  tone: 'neutral',
  dot: false,
})

const tones: Record<Tone, string> = {
  neutral: 'bg-surface-muted text-ink-soft border-line',
  brand: 'bg-brand-soft text-brand border-transparent',
  success: 'bg-[#E6F4EA] text-success border-transparent',
  warning: 'bg-[#FBF1E3] text-warning border-transparent',
  danger: 'bg-[#FBE9E8] text-danger border-transparent',
}

const dotColor: Record<Tone, string> = {
  neutral: 'bg-ink-soft',
  brand: 'bg-brand',
  success: 'bg-success',
  warning: 'bg-warning',
  danger: 'bg-danger',
}

const cls = computed(() => tones[props.tone])
</script>

<template>
  <span
    class="inline-flex items-center gap-1.5 rounded border px-2 py-0.5 text-xs font-medium"
    :class="cls"
  >
    <span v-if="dot" class="h-1.5 w-1.5 rounded-full" :class="dotColor[tone]" />
    <slot />
  </span>
</template>
