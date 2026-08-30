<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { CalendarDays, Gift, Play, Ticket, Trophy } from 'lucide-vue-next'
import { getCampaign } from '@/shared/api/campaigns'
import { getMyRewards } from '@/shared/api/participation'
import type { Campaign, RewardItem } from '@/shared/api/types'
import { formatPeriod, periodState } from '@/shared/lib/period'
import { attemptsLabel, formatDate } from '@/shared/lib/format'
import { useSessionStore } from '@/stores/session'
import LeaderboardPanel from '@/components/LeaderboardPanel.vue'
import EmptyState from '@/components/EmptyState.vue'
import Button from '@/ui/Button.vue'
import Card from '@/ui/Card.vue'

const props = defineProps<{ slug: string }>()
const router = useRouter()
const session = useSessionStore()

const campaign = ref<Campaign | null>(null)
const rewards = ref<RewardItem[]>([])
const loading = ref(true)
const failed = ref(false)

const state = computed(() => (campaign.value ? periodState(campaign.value.period) : 'upcoming'))
const canPlay = computed(
  () => state.value === 'running' && (campaign.value?.attempts_left ?? 0) > 0,
)
const mechanicLabel: Record<string, string> = {
  quiz: 'Викторина',
  wheel: 'Колесо фортуны',
  collection: 'Коллекция',
  promo: 'Промокоды',
}
const stateBadge = computed(() => {
  if (state.value === 'running') return { tone: 'success' as const, text: 'Идёт сейчас' }
  if (state.value === 'upcoming') return { tone: 'warning' as const, text: 'Скоро старт' }
  return { tone: 'neutral' as const, text: 'Завершена' }
})

onMounted(async () => {
  try {
    campaign.value = await getCampaign(props.slug)
    await session.join(props.slug)
    if (session.participant) {
      campaign.value.attempts_left = session.participant.attempts_left
    }
    rewards.value = (await getMyRewards()).items
  } catch {
    failed.value = true
  } finally {
    loading.value = false
  }
})

function play() {
  router.push(`/c/${props.slug}/play`)
}
</script>

<template>
  <div class="mx-auto max-w-content">
    <div v-if="loading" class="space-y-4">
      <div class="h-52 animate-pulse rounded-card bg-surface-muted" />
      <div class="h-64 animate-pulse rounded-card bg-surface-muted" />
    </div>

    <EmptyState
      v-else-if="failed || !campaign"
      title="Кампания не найдена"
      description="Проверьте ссылку — возможно, кампания завершена или ещё не опубликована."
    >
      <Button variant="secondary" @click="router.push('/admin')">К списку кампаний</Button>
    </EmptyState>

    <template v-else>
      <!-- Герой -->
      <section
        class="relative overflow-hidden rounded-card p-6 text-white sm:p-8"
        :style="{ background: `linear-gradient(135deg, ${campaign.theme.color}, ${campaign.theme.color}cc)` }"
      >
        <div class="pointer-events-none absolute -right-6 -top-8 text-[120px] leading-none opacity-25">
          {{ campaign.theme.emoji }}
        </div>
        <div class="relative max-w-2xl">
          <div class="flex flex-wrap items-center gap-2">
            <span class="rounded bg-white/15 px-2 py-0.5 text-xs font-medium">
              {{ mechanicLabel[campaign.mechanic] }}
            </span>
            <span class="rounded bg-white/15 px-2 py-0.5 text-xs font-medium">
              {{ stateBadge.text }}
            </span>
          </div>
          <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">{{ campaign.title }}</h1>
          <p v-if="campaign.texts.tagline" class="mt-2 text-base text-white/90">
            {{ campaign.texts.tagline }}
          </p>
          <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-white/90">
            <span class="inline-flex items-center gap-1.5">
              <CalendarDays class="h-4 w-4" />
              {{ formatPeriod(campaign.period) }}
            </span>
            <span class="inline-flex items-center gap-1.5">
              <Ticket class="h-4 w-4" />
              Баланс попыток: {{ attemptsLabel(campaign.attempts_left) }}
            </span>
          </div>
          <div class="mt-6 flex flex-wrap gap-3">
            <Button
              size="lg"
              variant="secondary"
              :disabled="!canPlay"
              class="!border-white !bg-white !text-ink hover:!bg-white/90"
              @click="play"
            >
              <Play class="h-4 w-4" />
              {{ campaign.texts.cta || 'Играть' }}
            </Button>
            <span v-if="!canPlay" class="self-center text-sm text-white/80">
              {{ state === 'running' ? 'Попытки закончились — вернитесь позже' : 'Игра недоступна' }}
            </span>
          </div>
        </div>
      </section>

      <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
        <div class="space-y-6">
          <Card v-if="campaign.texts.description" title="Об этой затее">
            <p class="text-sm leading-relaxed text-ink">{{ campaign.texts.description }}</p>
            <p v-if="campaign.texts.rules" class="mt-3 text-sm leading-relaxed text-ink-soft">
              {{ campaign.texts.rules }}
            </p>
          </Card>

          <Card title="Мои призы" :subtitle="`Всего: ${rewards.length}`">
            <ul v-if="rewards.length" class="divide-y divide-line">
              <li
                v-for="(reward, i) in rewards"
                :key="i"
                class="flex items-center gap-3 py-3 first:pt-0 last:pb-0"
              >
                <span
                  class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-brand-soft text-brand"
                >
                  <Gift class="h-4 w-4" />
                </span>
                <div class="min-w-0 flex-1">
                  <p class="truncate text-sm font-semibold">{{ reward.title }}</p>
                  <p class="text-xs text-ink-soft">Начислено {{ formatDate(reward.awarded_at) }}</p>
                </div>
                <code
                  v-if="reward.promo_code"
                  class="rounded bg-surface-muted px-2 py-1 text-xs font-semibold tracking-wide"
                >
                  {{ reward.promo_code }}
                </code>
              </li>
            </ul>
            <EmptyState
              v-else
              compact
              :icon="Trophy"
              title="Пока пусто"
              description="Сыграйте в механику, чтобы получить первый приз."
            />
          </Card>
        </div>

        <Card padded>
          <LeaderboardPanel :slug="slug" :limit="10" />
        </Card>
      </div>
    </template>
  </div>
</template>
