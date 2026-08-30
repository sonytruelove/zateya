<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, shallowRef } from 'vue'
import { ChevronUp, ChevronDown, Minus, Radio } from 'lucide-vue-next'
import { getLeaderboard } from '@/shared/api/campaigns'
import {
  emptyLeaderboard,
  reduceLeaderboard,
  type LeaderboardState,
} from '@/shared/lib/leaderboard'
import { REALTIME_MODE, subscribeLeaderboard, type Unsubscribe } from '@/shared/realtime'
import { formatNumber } from '@/shared/lib/format'
import Avatar from '@/ui/Avatar.vue'
import Badge from '@/ui/Badge.vue'

const props = withDefaults(defineProps<{ slug: string; limit?: number }>(), { limit: 10 })

const state = shallowRef<LeaderboardState>(emptyLeaderboard())
const loading = ref(true)
let stop: Unsubscribe | null = null

onMounted(async () => {
  try {
    const initial = await getLeaderboard(props.slug, props.limit)
    state.value = reduceLeaderboard(emptyLeaderboard(), initial)
  } finally {
    loading.value = false
  }
  stop = subscribeLeaderboard(props.slug, (event) => {
    state.value = reduceLeaderboard(state.value, event)
  })
})

onBeforeUnmount(() => stop?.())
</script>

<template>
  <div>
    <div class="mb-3 flex items-center justify-between">
      <h3 class="text-base font-bold">Рейтинг</h3>
      <Badge tone="brand" dot>
        <Radio class="h-3 w-3" />
        {{ REALTIME_MODE === 'centrifugo' ? 'В эфире' : 'Живое обновление' }}
      </Badge>
    </div>

    <div v-if="loading" class="space-y-2">
      <div v-for="i in 6" :key="i" class="h-11 animate-pulse rounded bg-surface-muted" />
    </div>

    <ol v-else class="space-y-1">
      <li
        v-for="row in state.rows"
        :key="row.display_name"
        class="flex items-center gap-3 rounded px-2 py-2 transition"
        :class="row.trend === 'up' ? 'bg-[#E6F4EA]' : row.trend === 'down' ? 'bg-[#FBE9E8]' : ''"
      >
        <span class="w-6 text-center text-sm font-bold tabular-nums text-ink-soft">
          {{ row.rank }}
        </span>
        <Avatar :name="row.display_name" size="sm" />
        <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ row.display_name }}</span>
        <span class="inline-flex w-6 justify-center">
          <ChevronUp v-if="row.trend === 'up'" class="h-4 w-4 text-success" />
          <ChevronDown v-else-if="row.trend === 'down'" class="h-4 w-4 text-danger" />
          <Minus v-else class="h-4 w-4 text-ink-soft/40" />
        </span>
        <span class="w-16 text-right text-sm font-bold tabular-nums">
          {{ formatNumber(row.score) }}
        </span>
      </li>
    </ol>

    <div
      v-if="state.me"
      class="mt-3 flex items-center gap-3 rounded border border-brand bg-brand-soft px-2 py-2"
    >
      <span class="w-6 text-center text-sm font-bold tabular-nums text-brand">
        {{ state.me.rank }}
      </span>
      <Avatar :name="state.me.display_name" size="sm" highlight />
      <span class="flex-1 text-sm font-semibold text-brand">Моя позиция</span>
      <span class="w-16 text-right text-sm font-bold tabular-nums text-brand">
        {{ formatNumber(state.me.score) }}
      </span>
    </div>
  </div>
</template>
