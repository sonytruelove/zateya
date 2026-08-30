<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    data: number[]
    width?: number
    height?: number
    tone?: string
    variant?: 'line' | 'bars'
    fill?: boolean
  }>(),
  { width: 240, height: 56, tone: 'var(--z-accent)', variant: 'line', fill: true },
)

const max = computed(() => Math.max(1, ...props.data))
const stepX = computed(() => (props.data.length > 1 ? props.width / (props.data.length - 1) : 0))

const points = computed(() =>
  props.data.map((v, i) => {
    const x = i * stepX.value
    const y = props.height - (v / max.value) * (props.height - 4) - 2
    return [x, y] as const
  }),
)

const linePath = computed(() =>
  points.value.map(([x, y], i) => `${i === 0 ? 'M' : 'L'}${x.toFixed(1)} ${y.toFixed(1)}`).join(' '),
)

const areaPath = computed(
  () => `${linePath.value} L${props.width} ${props.height} L0 ${props.height} Z`,
)

const barWidth = computed(() => (props.width / props.data.length) * 0.62)
</script>

<template>
  <svg
    :viewBox="`0 0 ${width} ${height}`"
    :width="width"
    :height="height"
    preserveAspectRatio="none"
    class="block w-full"
    aria-hidden="true"
  >
    <template v-if="variant === 'bars'">
      <rect
        v-for="(v, i) in data"
        :key="i"
        :x="i * (width / data.length) + (width / data.length - barWidth) / 2"
        :y="height - (v / max) * (height - 2)"
        :width="barWidth"
        :height="(v / max) * (height - 2)"
        rx="1.5"
        :fill="tone"
        :opacity="0.85"
      />
    </template>
    <template v-else>
      <path v-if="fill" :d="areaPath" :fill="tone" opacity="0.10" />
      <path :d="linePath" :stroke="tone" stroke-width="2" fill="none" stroke-linejoin="round" />
      <circle
        v-if="points.length"
        :cx="points[points.length - 1][0]"
        :cy="points[points.length - 1][1]"
        r="2.5"
        :fill="tone"
      />
    </template>
  </svg>
</template>
