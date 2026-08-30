<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { WheelSector } from '@/shared/api/types'
import Button from '@/ui/Button.vue'

const props = withDefaults(
  defineProps<{
    sectors: WheelSector[]
    targetSectorId: number | null
    disabled?: boolean
    busy?: boolean
  }>(),
  { disabled: false, busy: false },
)

const emit = defineEmits<{ (e: 'spin'): void; (e: 'settled'): void }>()

const SIZE = 300
const R = 138
const CENTER = SIZE / 2

const rotation = ref(0)
const spinning = ref(false)
const transition = ref('none')

const seg = computed(() => 360 / Math.max(1, props.sectors.length))

function polar(angleDeg: number, radius: number): [number, number] {
  const rad = ((angleDeg - 90) * Math.PI) / 180
  return [CENTER + radius * Math.cos(rad), CENTER + radius * Math.sin(rad)]
}

function wedgePath(index: number): string {
  const a0 = index * seg.value
  const a1 = (index + 1) * seg.value
  const [x0, y0] = polar(a0, R)
  const [x1, y1] = polar(a1, R)
  const largeArc = seg.value > 180 ? 1 : 0
  return `M ${CENTER} ${CENTER} L ${x0} ${y0} A ${R} ${R} 0 ${largeArc} 1 ${x1} ${y1} Z`
}

function labelTransform(index: number): string {
  const mid = index * seg.value + seg.value / 2
  const [x, y] = polar(mid, R * 0.66)
  return `translate(${x} ${y}) rotate(${mid})`
}

function onSpinClick() {
  if (props.disabled || spinning.value) return
  spinning.value = true
  transition.value = 'transform 0.9s linear'
  rotation.value += 720
  emit('spin')
}

watch(
  () => props.targetSectorId,
  (id) => {
    if (id == null) return
    const idx = props.sectors.findIndex((s) => s.id === id)
    if (idx < 0) return
    const mid = idx * seg.value + seg.value / 2
    const jitter = (Math.random() - 0.5) * (seg.value * 0.5)
    const base = Math.ceil(rotation.value / 360) * 360
    transition.value = 'transform 3.6s cubic-bezier(0.16, 1, 0.3, 1)'
    rotation.value = base + 360 * 4 + (360 - mid) - jitter
    window.setTimeout(() => {
      spinning.value = false
      emit('settled')
    }, 3700)
  },
)
</script>

<template>
  <div class="flex flex-col items-center gap-5">
    <div class="relative" :style="{ width: SIZE + 'px', maxWidth: '100%' }">
      <svg :viewBox="`0 0 ${SIZE} ${SIZE}`" class="w-full drop-shadow-[0_8px_24px_rgba(16,24,40,0.12)]">
        <circle :cx="CENTER" :cy="CENTER" :r="R + 8" fill="var(--z-surface)" stroke="var(--z-border)" />
        <g
          :style="{
            transform: `rotate(${rotation}deg)`,
            transformOrigin: `${CENTER}px ${CENTER}px`,
            transition,
          }"
        >
          <g v-for="(sector, i) in sectors" :key="sector.id">
            <path :d="wedgePath(i)" :fill="sector.color" :opacity="sector.prize ? 0.92 : 0.32" />
            <text
              :transform="labelTransform(i)"
              text-anchor="middle"
              dominant-baseline="middle"
              fill="#fff"
              font-size="11"
              font-weight="700"
              :style="{ paintOrder: 'stroke' }"
            >
              {{ sector.label }}
            </text>
          </g>
          <circle :cx="CENTER" :cy="CENTER" r="18" fill="var(--z-surface)" stroke="var(--z-border)" />
        </g>
        <polygon
          :points="`${CENTER - 12},6 ${CENTER + 12},6 ${CENTER},30`"
          fill="var(--z-accent)"
        />
      </svg>
    </div>

    <Button size="lg" :loading="busy || spinning" :disabled="disabled" @click="onSpinClick">
      Крутить колесо
    </Button>
  </div>
</template>
