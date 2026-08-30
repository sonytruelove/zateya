import { Centrifuge } from 'centrifuge'
import type { LeaderboardEntry } from '../api/types'
import { currentLeaderboard, nudgeLeaderboard } from '../api/mock'

export interface LeaderboardEvent {
  entries: LeaderboardEntry[]
  me: LeaderboardEntry | null
}

export interface PrizeEvent {
  type: 'prize_awarded'
  prize: { title: string; promo_code?: string }
}

export type Unsubscribe = () => void

const CENTRIFUGO_URL = import.meta.env.VITE_CENTRIFUGO_URL
const CENTRIFUGO_TOKEN = import.meta.env.VITE_CENTRIFUGO_TOKEN ?? ''

export const REALTIME_MODE: 'centrifugo' | 'emulator' = CENTRIFUGO_URL ? 'centrifugo' : 'emulator'

let sharedClient: Centrifuge | null = null

function client(): Centrifuge {
  if (!sharedClient) {
    sharedClient = new Centrifuge(CENTRIFUGO_URL as string, {
      token: CENTRIFUGO_TOKEN,
    })
    sharedClient.connect()
  }
  return sharedClient
}

/**
 * Подписка на живой рейтинг кампании.
 * При заданном VITE_CENTRIFUGO_URL — реальный канал `campaign:{slug}:leaderboard`.
 * Иначе — эмулятор, присылающий новые позиции каждые 2–3 секунды.
 */
export function subscribeLeaderboard(
  slug: string,
  onEvent: (event: LeaderboardEvent) => void,
): Unsubscribe {
  if (REALTIME_MODE === 'centrifugo') {
    const c = client()
    const channel = `campaign:${slug}:leaderboard`
    const sub = c.getSubscription(channel) ?? c.newSubscription(channel)
    const handler = (ctx: { data: LeaderboardEvent }) => onEvent(ctx.data)
    sub.on('publication', handler)
    sub.subscribe()
    return () => {
      sub.off('publication', handler)
      sub.unsubscribe()
    }
  }

  let stopped = false
  let timer: ReturnType<typeof setTimeout>

  const tick = () => {
    if (stopped) return
    nudgeLeaderboard(slug)
    const board = currentLeaderboard(slug, 10)
    onEvent({ entries: board.entries, me: board.me })
    timer = setTimeout(tick, 2000 + Math.random() * 1200)
  }
  // первый снимок сразу, затем — по таймеру
  timer = setTimeout(tick, 400)

  return () => {
    stopped = true
    clearTimeout(timer)
  }
}

/**
 * Подписка на события участника (`participant:{id}`), в т.ч. начисление приза.
 * В режиме эмулятора канал молчит — призы приходят синхронно из ответа на попытку.
 */
export function subscribeParticipant(
  participantId: string,
  onPrize: (event: PrizeEvent) => void,
): Unsubscribe {
  if (REALTIME_MODE !== 'centrifugo') {
    return () => {}
  }
  const c = client()
  const channel = `participant:${participantId}`
  const sub = c.getSubscription(channel) ?? c.newSubscription(channel)
  const handler = (ctx: { data: PrizeEvent }) => onPrize(ctx.data)
  sub.on('publication', handler)
  sub.subscribe()
  return () => {
    sub.off('publication', handler)
    sub.unsubscribe()
  }
}
