<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Plus } from 'lucide-vue-next'
import { listCampaigns } from '@/shared/api/admin'
import type { AdminCampaign, CampaignStatus, Mechanic } from '@/shared/api/types'
import { formatPeriod } from '@/shared/lib/period'
import { formatNumber } from '@/shared/lib/format'
import Button from '@/ui/Button.vue'
import Badge from '@/ui/Badge.vue'
import Table from '@/ui/Table.vue'
import type { Column } from '@/ui/Table.vue'

const router = useRouter()
const rows = ref<AdminCampaign[]>([])
const loading = ref(true)

const mechanicLabel: Record<Mechanic, string> = {
  quiz: 'Викторина',
  wheel: 'Колесо фортуны',
  collection: 'Коллекция',
  promo: 'Промокоды',
}

const statusMeta: Record<CampaignStatus, { tone: 'neutral' | 'brand' | 'success' | 'warning'; text: string }> = {
  draft: { tone: 'neutral', text: 'Черновик' },
  scheduled: { tone: 'warning', text: 'Запланирована' },
  active: { tone: 'success', text: 'Активна' },
  finished: { tone: 'neutral', text: 'Завершена' },
}

const columns = computed<Column<AdminCampaign>[]>(() => [
  { key: 'title', label: 'Название' },
  { key: 'mechanic', label: 'Механика' },
  { key: 'status', label: 'Статус' },
  { key: 'period', label: 'Период' },
  { key: 'participants', label: 'Участники', align: 'right' },
])

onMounted(async () => {
  try {
    rows.value = (await listCampaigns()).items
  } finally {
    loading.value = false
  }
})

function open(row: AdminCampaign) {
  router.push(`/admin/campaigns/${row.id}`)
}
</script>

<template>
  <div class="mx-auto max-w-content">
    <div class="mb-6 flex items-end justify-between gap-4">
      <div>
        <h1 class="text-2xl font-black tracking-tight">Кампании</h1>
        <p class="mt-1 text-sm text-ink-soft">Промо-механики и мотивационные программы</p>
      </div>
      <Button @click="router.push('/admin/new')">
        <Plus class="h-4 w-4" />
        Создать кампанию
      </Button>
    </div>

    <div v-if="loading" class="h-64 animate-pulse rounded-card bg-surface-muted" />

    <Table
      v-else
      :columns="columns"
      :rows="rows"
      :row-key="(r) => r.id"
    >
      <template #cell:title="{ row }">
        <button class="text-left font-semibold text-ink hover:text-brand" @click="open(row)">
          {{ row.title }}
          <span class="block text-xs font-normal text-ink-soft">/c/{{ row.slug }}</span>
        </button>
      </template>
      <template #cell:mechanic="{ row }">
        {{ mechanicLabel[row.mechanic] }}
      </template>
      <template #cell:status="{ row }">
        <Badge :tone="statusMeta[row.status].tone" dot>{{ statusMeta[row.status].text }}</Badge>
      </template>
      <template #cell:period="{ row }">
        <span class="text-ink-soft">{{ formatPeriod(row.period) }}</span>
      </template>
      <template #cell:participants="{ row }">
        <span class="tabular-nums font-medium">{{ formatNumber(row.participants) }}</span>
      </template>
      <template #empty>Ещё нет ни одной кампании</template>
    </Table>
  </div>
</template>
