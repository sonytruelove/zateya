<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowLeft,
  BarChart3,
  Gift,
  Pencil,
  Rocket,
  Target,
  Ticket,
  Trophy,
  Users,
} from 'lucide-vue-next'
import {
  addPrize,
  addPromoCodes,
  getCampaignDetail,
  getCampaignStats,
  publishCampaign,
  type AdminCampaignDetail,
} from '@/shared/api/admin'
import type { CampaignStats, CampaignStatus, PrizePoolItem, PromoCodePool } from '@/shared/api/types'
import { formatPeriod } from '@/shared/lib/period'
import { formatNumber } from '@/shared/lib/format'
import { useToasts } from '@/ui'
import Button from '@/ui/Button.vue'
import Card from '@/ui/Card.vue'
import Badge from '@/ui/Badge.vue'
import Tabs from '@/ui/Tabs.vue'
import type { TabItem } from '@/ui/Tabs.vue'
import Table from '@/ui/Table.vue'
import type { Column } from '@/ui/Table.vue'
import Input from '@/ui/Input.vue'
import StatTile from '@/ui/StatTile.vue'
import Sparkline from '@/ui/Sparkline.vue'
import Progress from '@/ui/Progress.vue'

const props = defineProps<{ id: string }>()
const route = useRoute()
const router = useRouter()
const toasts = useToasts()

const detail = ref<AdminCampaignDetail | null>(null)
const stats = ref<CampaignStats | null>(null)
const loading = ref(true)
const publishing = ref(false)

const tabs: TabItem[] = [
  { value: 'summary', label: 'Сводка', icon: BarChart3 },
  { value: 'prizes', label: 'Призы', icon: Gift },
  { value: 'promo', label: 'Промокоды', icon: Ticket },
]
const tab = ref<string>(typeof route.query.tab === 'string' ? route.query.tab : 'summary')
watch(tab, (t) => router.replace({ query: { ...route.query, tab: t } }))

const statusMeta: Record<CampaignStatus, { tone: 'neutral' | 'success' | 'warning'; text: string }> = {
  draft: { tone: 'neutral', text: 'Черновик' },
  scheduled: { tone: 'warning', text: 'Запланирована' },
  active: { tone: 'success', text: 'Активна' },
  finished: { tone: 'neutral', text: 'Завершена' },
}

const prizeColumns: Column<PrizePoolItem>[] = [
  { key: 'title', label: 'Приз' },
  { key: 'quantity', label: 'Всего', align: 'right' },
  { key: 'awarded', label: 'Выдано', align: 'right' },
  { key: 'left', label: 'Остаток', align: 'right' },
]

const newPrize = ref({ title: '', quantity: '100' })
const promoInput = ref('')
const savingPrize = ref(false)
const savingPromo = ref(false)

const promo = computed<PromoCodePool>(
  () => detail.value?.promo ?? { total: 0, issued: 0, left: 0 },
)
const activity = computed(() => stats.value?.activity ?? [])

async function load() {
  loading.value = true
  try {
    detail.value = await getCampaignDetail(props.id)
    stats.value = await getCampaignStats(props.id)
  } catch {
    toasts.danger('Кампания не найдена')
    router.push('/admin')
  } finally {
    loading.value = false
  }
}

onMounted(load)

async function publish() {
  if (!detail.value) return
  publishing.value = true
  try {
    detail.value.campaign = await publishCampaign(props.id)
    toasts.success('Кампания опубликована')
  } catch {
    toasts.danger('Не удалось опубликовать')
  } finally {
    publishing.value = false
  }
}

async function submitPrize() {
  const quantity = Number(newPrize.value.quantity)
  if (!newPrize.value.title.trim() || !Number.isFinite(quantity) || quantity < 1) {
    toasts.warning('Заполните название и количество')
    return
  }
  savingPrize.value = true
  try {
    const item = await addPrize(props.id, {
      title: newPrize.value.title.trim(),
      quantity,
    })
    detail.value?.prizes.push(item)
    newPrize.value = { title: '', quantity: '100' }
    toasts.success('Приз добавлен в фонд')
  } catch {
    toasts.danger('Не удалось добавить приз')
  } finally {
    savingPrize.value = false
  }
}

async function submitPromo() {
  const codes = promoInput.value
    .split(/[\s,;]+/)
    .map((c) => c.trim())
    .filter(Boolean)
  if (!codes.length) {
    toasts.warning('Вставьте хотя бы один код')
    return
  }
  savingPromo.value = true
  try {
    const pool = await addPromoCodes(props.id, { codes })
    if (detail.value) detail.value.promo = pool
    promoInput.value = ''
    toasts.success(`Загружено кодов: ${codes.length}`)
  } catch {
    toasts.danger('Не удалось загрузить пул')
  } finally {
    savingPromo.value = false
  }
}
</script>

<template>
  <div class="mx-auto max-w-content">
    <button
      class="mb-4 inline-flex items-center gap-1.5 text-sm text-ink-soft transition hover:text-ink"
      @click="router.push('/admin')"
    >
      <ArrowLeft class="h-4 w-4" />
      К списку кампаний
    </button>

    <div v-if="loading" class="h-96 animate-pulse rounded-card bg-surface-muted" />

    <template v-else-if="detail">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-black tracking-tight">{{ detail.campaign.title }}</h1>
            <Badge :tone="statusMeta[detail.campaign.status].tone" dot>
              {{ statusMeta[detail.campaign.status].text }}
            </Badge>
          </div>
          <p class="mt-1 text-sm text-ink-soft">
            /c/{{ detail.campaign.slug }} · {{ formatPeriod(detail.campaign.period) }}
          </p>
        </div>
        <div class="flex gap-2">
          <Button variant="secondary" @click="router.push(`/admin/campaigns/${id}/edit`)">
            <Pencil class="h-4 w-4" />
            Редактировать
          </Button>
          <Button
            v-if="detail.campaign.status === 'draft'"
            :loading="publishing"
            @click="publish"
          >
            <Rocket class="h-4 w-4" />
            Опубликовать
          </Button>
        </div>
      </div>

      <div class="mt-6">
        <Tabs v-model="tab" :tabs="tabs" />
      </div>

      <!-- Сводка -->
      <div v-if="tab === 'summary'" class="mt-6 space-y-6">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <StatTile
            label="Попытки"
            :value="formatNumber(stats?.attempts ?? 0)"
            :icon="Target"
            hint="за всё время"
          />
          <StatTile
            label="Участники"
            :value="formatNumber(stats?.participants ?? 0)"
            :icon="Users"
            :trend="12"
          />
          <StatTile
            label="Победители"
            :value="formatNumber(stats?.winners ?? 0)"
            :icon="Trophy"
            :trend="8"
          />
          <StatTile
            label="Остаток фонда"
            :value="formatNumber(stats?.prize_pool_left ?? 0)"
            :icon="Gift"
            hint="призов"
          />
        </div>

        <Card title="Активность по дням" subtitle="Попытки за последние 14 дней">
          <Sparkline
            :data="activity"
            variant="bars"
            :height="120"
            :width="720"
            tone="var(--z-accent)"
          />
          <div class="mt-2 flex justify-between text-xs text-ink-soft">
            <span>14 дней назад</span>
            <span>сегодня</span>
          </div>
        </Card>
      </div>

      <!-- Призы -->
      <div v-else-if="tab === 'prizes'" class="mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">
        <Table
          :columns="prizeColumns"
          :rows="detail.prizes"
          :row-key="(r) => r.id"
        >
          <template #cell:quantity="{ row }">
            <span class="tabular-nums">{{ formatNumber(row.quantity) }}</span>
          </template>
          <template #cell:awarded="{ row }">
            <span class="tabular-nums">{{ formatNumber(row.awarded) }}</span>
          </template>
          <template #cell:left="{ row }">
            <span class="tabular-nums font-medium">
              {{ formatNumber(Math.max(0, row.quantity - row.awarded)) }}
            </span>
          </template>
          <template #empty>Призовой фонд пуст — добавьте первый приз</template>
        </Table>

        <Card title="Добавить приз">
          <form class="space-y-4" @submit.prevent="submitPrize">
            <Input v-model="newPrize.title" label="Название приза" placeholder="Например, 100 баллов" />
            <Input v-model="newPrize.quantity" label="Количество" type="number" />
            <Button type="submit" block :loading="savingPrize">В фонд</Button>
          </form>
        </Card>
      </div>

      <!-- Промокоды -->
      <div v-else class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
        <Card title="Пул промокодов">
          <div class="grid grid-cols-3 gap-4 text-center">
            <div>
              <p class="text-2xl font-extrabold tabular-nums">{{ formatNumber(promo.total) }}</p>
              <p class="text-xs text-ink-soft">Всего</p>
            </div>
            <div>
              <p class="text-2xl font-extrabold tabular-nums text-warning">
                {{ formatNumber(promo.issued) }}
              </p>
              <p class="text-xs text-ink-soft">Выдано</p>
            </div>
            <div>
              <p class="text-2xl font-extrabold tabular-nums text-success">
                {{ formatNumber(promo.left) }}
              </p>
              <p class="text-xs text-ink-soft">Остаток</p>
            </div>
          </div>
          <div class="mt-4">
            <Progress
              :value="promo.issued"
              :max="Math.max(1, promo.total)"
              tone="warning"
              label="Расход пула"
              show-value
            />
          </div>
        </Card>

        <Card title="Загрузить коды">
          <form class="space-y-3" @submit.prevent="submitPromo">
            <textarea
              v-model="promoInput"
              rows="6"
              class="w-full rounded border border-line bg-surface px-3 py-2 font-mono text-xs outline-none transition focus:border-brand"
              placeholder="Один код в строке&#10;NY2026-AB12&#10;NY2026-CD34"
            />
            <Button type="submit" block :loading="savingPromo">Добавить в пул</Button>
          </form>
        </Card>
      </div>
    </template>
  </div>
</template>
