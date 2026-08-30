import type {
  AddPrizeRequest,
  AddPromoCodesRequest,
  AdminCampaign,
  AttemptResult,
  Campaign,
  CampaignStats,
  CreateCampaignRequest,
  Leaderboard,
  ParticipationSession,
  ParticipationSessionRequest,
  PrizePoolItem,
  QuizAnswerPayload,
  QuizQuestion,
  RewardList,
  WheelSector,
} from '../types'
import { buildCampaigns, buildLeaderboard, seedRewards, type CampaignRecord } from './seed'

// Изменяемое состояние мок-«бэкенда» на время жизни вкладки.
const campaigns: Record<string, CampaignRecord> = buildCampaigns()
const leaderboards: Record<string, ReturnType<typeof buildLeaderboard>> = {}
const rewards = [...seedRewards]
let participantScore = 980

function bySlug(slug: string): CampaignRecord | undefined {
  return campaigns[slug]
}

function byId(id: string): CampaignRecord | undefined {
  return Object.values(campaigns).find((c) => c.admin.id === id)
}

function ensureLeaderboard(slug: string) {
  if (!leaderboards[slug]) leaderboards[slug] = buildLeaderboard(slug)
  return leaderboards[slug]
}

function meEntry(slug: string) {
  const board = ensureLeaderboard(slug)
  const above = board.filter((e) => e.score > participantScore).length
  return { rank: above + 1, display_name: 'Вы', score: participantScore }
}

function pickWeighted<T extends { weight: number }>(items: T[], r: number): T {
  const total = items.reduce((a, b) => a + b.weight, 0)
  let acc = r * total
  for (const it of items) {
    acc -= it.weight
    if (acc <= 0) return it
  }
  return items[items.length - 1]
}

function delay<T>(value: T, ms = 220): Promise<T> {
  return new Promise((resolve) => setTimeout(() => resolve(value), ms))
}

export interface MockResponse {
  status: number
  body: unknown
}

function ok(body: unknown): MockResponse {
  return { status: 200, body }
}

function notFound(): MockResponse {
  return { status: 404, body: { error: 'not_found' } }
}

// Главная точка входа мок-роутера.
export async function handleMock(
  method: string,
  path: string,
  body: unknown,
): Promise<MockResponse> {
  const m = method.toUpperCase()

  // POST /participation/sessions
  if (m === 'POST' && path === '/participation/sessions') {
    const req = body as ParticipationSessionRequest
    const rec = bySlug(req.campaign_slug)
    const res: ParticipationSession = {
      participant_id: 'prt_' + Math.random().toString(36).slice(2, 10),
      display_name: 'Вы',
      attempts_left: rec?.public.attempts_left ?? 0,
      token: 'demo.' + Math.random().toString(36).slice(2),
    }
    return delay(ok(res))
  }

  // GET /campaigns/{slug}
  let mt = path.match(/^\/campaigns\/([^/]+)$/)
  if (m === 'GET' && mt) {
    const rec = bySlug(mt[1])
    if (!rec) return delay(notFound())
    return delay(ok(rec.public satisfies Campaign))
  }

  // GET /campaigns/{slug}/leaderboard
  mt = path.match(/^\/campaigns\/([^/]+)\/leaderboard/)
  if (m === 'GET' && mt) {
    const slug = mt[1]
    const rec = bySlug(slug)
    if (!rec) return delay(notFound())
    const limit = Number(new URLSearchParams(path.split('?')[1] ?? '').get('limit') ?? 10)
    const board = ensureLeaderboard(slug)
    const res: Leaderboard = {
      entries: board.slice(0, limit),
      me: meEntry(slug),
    }
    return delay(ok(res))
  }

  // GET /campaigns/{slug}/quiz  (контент викторины для игрового экрана)
  mt = path.match(/^\/campaigns\/([^/]+)\/quiz$/)
  if (m === 'GET' && mt) {
    const rec = bySlug(mt[1])
    if (!rec?.quiz) return delay(notFound())
    return delay(ok({ questions: rec.quiz satisfies QuizQuestion[] }))
  }

  // GET /campaigns/{slug}/wheel  (сектора колеса)
  mt = path.match(/^\/campaigns\/([^/]+)\/wheel$/)
  if (m === 'GET' && mt) {
    const rec = bySlug(mt[1])
    if (!rec?.wheel) return delay(notFound())
    return delay(ok({ sectors: rec.wheel satisfies WheelSector[] }))
  }

  // POST /campaigns/{slug}/attempts
  mt = path.match(/^\/campaigns\/([^/]+)\/attempts$/)
  if (m === 'POST' && mt) {
    const slug = mt[1]
    const rec = bySlug(slug)
    if (!rec) return delay(notFound())
    if (rec.public.attempts_left <= 0) {
      return delay({ status: 409, body: { error: 'no_attempts_left' } })
    }
    rec.public.attempts_left -= 1
    rec.admin.participants += 1
    rec.stats.attempts += 1

    let result: AttemptResult
    if (rec.public.mechanic === 'quiz' && rec.quiz) {
      const answers = (body as QuizAnswerPayload).answers ?? []
      const correct = rec.quiz.reduce(
        (acc, q, i) => acc + (answers[i] === q.correct ? 1 : 0),
        0,
      )
      const score = correct * 120
      const win = correct >= 3
      result = {
        outcome: win ? 'win' : 'lose',
        score,
        attempts_left: rec.public.attempts_left,
        prize: win ? { title: 'Промокод -15%', promo_code: 'BRAND-' + rand4() } : undefined,
      }
    } else if (rec.wheel) {
      const sector = pickWeighted(rec.wheel, Math.random())
      const win = Boolean(sector.prize)
      result = {
        outcome: win ? 'win' : 'lose',
        score: win ? 100 : 0,
        attempts_left: rec.public.attempts_left,
        prize: sector.prize,
      }
      // подмешиваем id сектора для анимации колеса
      ;(result as AttemptResult & { sector_id?: number }).sector_id = sector.id
    } else {
      result = { outcome: 'lose', score: 0, attempts_left: rec.public.attempts_left }
    }

    if (result.outcome === 'win') {
      rec.stats.winners += 1
      rec.stats.prize_pool_left = Math.max(0, rec.stats.prize_pool_left - 1)
      participantScore += result.score
      if (result.prize) {
        rewards.unshift({
          title: result.prize.title,
          promo_code: result.prize.promo_code,
          awarded_at: new Date().toISOString(),
        })
      }
    } else {
      participantScore += result.score
    }
    return delay(ok(result))
  }

  // GET /participants/me/rewards
  if (m === 'GET' && path === '/participants/me/rewards') {
    return delay(ok({ items: rewards } satisfies RewardList))
  }

  // --- Админ ---

  if (m === 'GET' && path === '/admin/campaigns') {
    const items: AdminCampaign[] = Object.values(campaigns).map((c) => c.admin)
    return delay(ok({ items }))
  }

  if (m === 'POST' && path === '/admin/campaigns') {
    const req = body as CreateCampaignRequest
    const id = 'cmp_' + Math.random().toString(36).slice(2, 8)
    const admin: AdminCampaign = {
      id,
      slug: req.slug,
      title: req.title,
      mechanic: req.mechanic,
      status: 'draft',
      period: req.period,
      participants: 0,
    }
    const pub: Campaign = {
      slug: req.slug,
      title: req.title,
      mechanic: req.mechanic,
      period: req.period,
      theme: req.theme,
      texts: req.texts,
      attempts_left: 3,
    }
    campaigns[req.slug] = {
      admin,
      public: pub,
      quiz: req.mechanic === 'quiz' ? buildCampaigns()['quiz-brand'].quiz : undefined,
      wheel: req.mechanic === 'wheel' ? buildCampaigns()['ny-2026'].wheel : undefined,
      stats: { attempts: 0, participants: 0, winners: 0, prize_pool_left: 0, activity: new Array(14).fill(0) },
      prizes: [],
      promo: { total: 0, issued: 0, left: 0 },
    }
    return delay({ status: 201, body: admin })
  }

  mt = path.match(/^\/admin\/campaigns\/([^/]+)\/publish$/)
  if (m === 'POST' && mt) {
    const rec = byId(mt[1])
    if (!rec) return delay(notFound())
    rec.admin.status = 'active'
    return delay(ok(rec.admin))
  }

  mt = path.match(/^\/admin\/campaigns\/([^/]+)\/prizes$/)
  if (m === 'POST' && mt) {
    const rec = byId(mt[1])
    if (!rec) return delay(notFound())
    const req = body as AddPrizeRequest
    const item: PrizePoolItem = {
      id: 'pz_' + Math.random().toString(36).slice(2, 8),
      title: req.title,
      quantity: req.quantity,
      awarded: 0,
    }
    rec.prizes.push(item)
    rec.stats.prize_pool_left += req.quantity
    return delay({ status: 201, body: item })
  }

  mt = path.match(/^\/admin\/campaigns\/([^/]+)\/promo-codes$/)
  if (m === 'POST' && mt) {
    const rec = byId(mt[1])
    if (!rec) return delay(notFound())
    const req = body as AddPromoCodesRequest
    rec.promo.total += req.codes.length
    rec.promo.left += req.codes.length
    return delay(ok(rec.promo))
  }

  mt = path.match(/^\/admin\/campaigns\/([^/]+)\/stats$/)
  if (m === 'GET' && mt) {
    const rec = byId(mt[1])
    if (!rec) return delay(notFound())
    return delay(ok(rec.stats satisfies CampaignStats))
  }

  mt = path.match(/^\/admin\/campaigns\/([^/]+)$/)
  if (m === 'GET' && mt) {
    const rec = byId(mt[1])
    if (!rec) return delay(notFound())
    return delay(
      ok({
        campaign: rec.admin,
        public: rec.public,
        prizes: rec.prizes,
        promo: rec.promo,
      }),
    )
  }

  return delay(notFound())
}

function rand4(): string {
  return Math.random().toString(36).slice(2, 6).toUpperCase()
}

// Утилита для эмулятора рейтинга: слегка перетасовать топ.
export function nudgeLeaderboard(slug: string): void {
  const board = ensureLeaderboard(slug)
  const i = Math.floor(Math.random() * Math.min(12, board.length))
  board[i] = { ...board[i], score: board[i].score + Math.round(Math.random() * 90) }
  board.sort((a, b) => b.score - a.score)
  board.forEach((e, idx) => (e.rank = idx + 1))
}

export function currentLeaderboard(slug: string, limit = 10): Leaderboard {
  const board = ensureLeaderboard(slug)
  return { entries: board.slice(0, limit), me: meEntry(slug) }
}
