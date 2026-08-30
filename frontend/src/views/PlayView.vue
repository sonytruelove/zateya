<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowLeft, Frown, PartyPopper, Sparkles } from 'lucide-vue-next'
import { getCampaign, getQuiz, getWheel, submitAttempt, type AttemptResponse } from '@/shared/api/campaigns'
import type { Campaign, QuizQuestion, WheelSector } from '@/shared/api/types'
import { attemptsLabel, formatNumber } from '@/shared/lib/format'
import { useSessionStore } from '@/stores/session'
import { useToasts } from '@/ui'
import QuizRunner from '@/components/QuizRunner.vue'
import WheelOfFortune from '@/components/WheelOfFortune.vue'
import EmptyState from '@/components/EmptyState.vue'
import Button from '@/ui/Button.vue'
import Card from '@/ui/Card.vue'

const props = defineProps<{ slug: string }>()
const router = useRouter()
const session = useSessionStore()
const toasts = useToasts()

const campaign = ref<Campaign | null>(null)
const questions = ref<QuizQuestion[]>([])
const sectors = ref<WheelSector[]>([])
const loading = ref(true)
const busy = ref(false)

const phase = ref<'intro' | 'playing' | 'result'>('intro')
const result = ref<AttemptResponse | null>(null)
const wheelTarget = ref<number | null>(null)

const attemptsLeft = computed(() => campaign.value?.attempts_left ?? 0)
const isReady = computed(() => !loading.value && campaign.value)
const supported = computed(
  () => campaign.value?.mechanic === 'quiz' || campaign.value?.mechanic === 'wheel',
)

onMounted(async () => {
  try {
    campaign.value = await getCampaign(props.slug)
    await session.join(props.slug)
    if (session.participant) campaign.value.attempts_left = session.participant.attempts_left
    if (campaign.value.mechanic === 'quiz') {
      questions.value = (await getQuiz(props.slug)).questions
    } else if (campaign.value.mechanic === 'wheel') {
      sectors.value = (await getWheel(props.slug)).sectors
    }
  } catch {
    toasts.danger('Не удалось загрузить механику', 'Попробуйте обновить страницу')
  } finally {
    loading.value = false
  }
})

function start() {
  phase.value = 'playing'
}

async function finishQuiz(answers: number[]) {
  busy.value = true
  try {
    const res = await submitAttempt(props.slug, { answers })
    applyResult(res)
  } finally {
    busy.value = false
  }
}

async function spinWheel() {
  busy.value = true
  try {
    const res = await submitAttempt(props.slug, {})
    result.value = res
    wheelTarget.value = res.sector_id ?? sectors.value[0]?.id ?? null
  } catch {
    busy.value = false
    toasts.danger('Попытка не засчитана')
  }
}

function onWheelSettled() {
  busy.value = false
  if (result.value) applyResult(result.value)
}

function applyResult(res: AttemptResponse) {
  result.value = res
  if (campaign.value) campaign.value.attempts_left = res.attempts_left
  session.spendAttempt(res.attempts_left)
  phase.value = 'result'
  if (res.outcome === 'win') {
    toasts.success('Есть приз!', res.prize?.title)
  }
}

function again() {
  result.value = null
  wheelTarget.value = null
  phase.value = attemptsLeft.value > 0 ? 'playing' : 'intro'
  if (attemptsLeft.value <= 0) {
    toasts.info('Попытки закончились', 'Возвращайтесь за новыми позже')
  }
}
</script>

<template>
  <div class="mx-auto max-w-2xl">
    <button
      class="mb-4 inline-flex items-center gap-1.5 text-sm text-ink-soft transition hover:text-ink"
      @click="router.push(`/c/${slug}`)"
    >
      <ArrowLeft class="h-4 w-4" />
      На витрину
    </button>

    <div v-if="loading" class="h-96 animate-pulse rounded-card bg-surface-muted" />

    <template v-else-if="isReady && campaign">
      <EmptyState
        v-if="!supported"
        :icon="Sparkles"
        title="Механика скоро откроется"
        :description="`«${campaign.title}» — механика «${campaign.mechanic === 'collection' ? 'Собери набор' : 'Промокоды'}» ещё в разработке. Свёрстанный экран-заглушка.`"
      >
        <Button variant="secondary" @click="router.push(`/c/${slug}`)">Вернуться</Button>
      </EmptyState>

      <!-- Результат -->
      <Card v-else-if="phase === 'result' && result" padded>
        <div class="flex flex-col items-center py-4 text-center">
          <span
            class="inline-flex h-16 w-16 items-center justify-center rounded-full"
            :class="result.outcome === 'win' ? 'bg-[#E6F4EA] text-success' : 'bg-surface-muted text-ink-soft'"
          >
            <PartyPopper v-if="result.outcome === 'win'" class="h-7 w-7" />
            <Frown v-else class="h-7 w-7" />
          </span>
          <h1 class="mt-4 text-2xl font-black">
            {{ result.outcome === 'win' ? 'Победа!' : 'В этот раз мимо' }}
          </h1>
          <p class="mt-1 text-sm text-ink-soft">
            {{ result.outcome === 'win' ? campaign.texts.win : campaign.texts.lose }}
          </p>

          <div class="mt-5 flex items-center gap-6">
            <div>
              <p class="text-xs uppercase tracking-wide text-ink-soft">Очки</p>
              <p class="text-2xl font-extrabold tabular-nums">+{{ formatNumber(result.score) }}</p>
            </div>
            <div class="h-10 w-px bg-line" />
            <div>
              <p class="text-xs uppercase tracking-wide text-ink-soft">Осталось попыток</p>
              <p class="text-2xl font-extrabold tabular-nums">{{ result.attempts_left }}</p>
            </div>
          </div>

          <div
            v-if="result.prize"
            class="mt-5 w-full rounded-card border border-brand bg-brand-soft p-4"
          >
            <p class="text-sm font-semibold text-brand">Ваш приз: {{ result.prize.title }}</p>
            <code
              v-if="result.prize.promo_code"
              class="mt-2 inline-block rounded bg-surface px-3 py-1.5 text-sm font-bold tracking-widest"
            >
              {{ result.prize.promo_code }}
            </code>
          </div>

          <div class="mt-6 flex gap-3">
            <Button :disabled="result.attempts_left <= 0" @click="again">Сыграть ещё</Button>
            <Button variant="secondary" @click="router.push(`/c/${slug}`)">К витрине</Button>
          </div>
        </div>
      </Card>

      <!-- Игра -->
      <Card v-else padded>
        <template v-if="phase === 'intro'">
          <div class="py-4 text-center">
            <h1 class="text-2xl font-black">{{ campaign.title }}</h1>
            <p class="mx-auto mt-2 max-w-md text-sm text-ink-soft">
              {{ campaign.texts.description }}
            </p>
            <p class="mt-4 text-sm">
              Доступно: <strong>{{ attemptsLabel(attemptsLeft) }}</strong>
            </p>
            <Button
              size="lg"
              class="mt-5"
              :disabled="attemptsLeft <= 0"
              @click="start"
            >
              {{ campaign.mechanic === 'wheel' ? 'К колесу' : 'Начать' }}
            </Button>
          </div>
        </template>

        <QuizRunner
          v-else-if="campaign.mechanic === 'quiz'"
          :questions="questions"
          :busy="busy"
          @finish="finishQuiz"
        />

        <WheelOfFortune
          v-else-if="campaign.mechanic === 'wheel'"
          :sectors="sectors"
          :target-sector-id="wheelTarget"
          :busy="busy"
          :disabled="attemptsLeft <= 0"
          @spin="spinWheel"
          @settled="onWheelSettled"
        />
      </Card>
    </template>
  </div>
</template>
