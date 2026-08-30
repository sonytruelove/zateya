<script setup lang="ts">
import { computed } from 'vue'

type Tone = 'brand' | 'success' | 'warning' | 'danger'

const props = withDefaults(
  defineProps<{
    value: number
    max?: number
    tone?: Tone
    label?: string
    showValue?: boolean
  }>(),
  { max: 100, tone: 'brand', showValue: false },
)

const pct = computed(() => {
  if (props.max <= 0) return 0
  return Math.min(100, Math.max(0, (props.value / props.max) * 100))
})

const tones: Record<Tone, string> = {
  brand: 'bg-brand',
  success: 'bg-success',
  warning: 'bg-warning',
  danger: 'bg-danger',
}
</script>

<template>
  <div>
    <div
      v-if="label || showValue"
      class="mb-1 flex items-center justify-between text-xs text-ink-soft"
    >
      <span>{{ label }}</span>
      <span v-if="showValue" class="tabular-nums">{{ Math.round(pct) }}%</span>
    </div>
    <div
      class="h-2 w-full overflow-hidden rounded-full bg-surface-muted"
      role="progressbar"
      :aria-valuenow="value"
      :aria-valuemin="0"
      :aria-valuemax="max"
    >
      <div
        class="h-full rounded-full transition-[width] duration-300 ease-out"
        :class="tones[tone]"
        :style="{ width: pct + '%' }"
      />
    </div>
  </div>
</template>
