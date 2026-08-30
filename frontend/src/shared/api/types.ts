// Контракт публичного и админского API платформы «Затея».
// Соответствует /api/v1 и /api/v1/admin.

export type Channel = 'web' | 'telegram' | 'vk'
export type Mechanic = 'quiz' | 'wheel' | 'collection' | 'promo'
export type CampaignStatus = 'draft' | 'scheduled' | 'active' | 'finished'
export type Outcome = 'win' | 'lose'

export interface Period {
  starts_at: string
  ends_at: string
}

export interface CampaignTheme {
  color: string
  emoji: string
}

export interface CampaignTexts {
  tagline?: string
  description?: string
  rules?: string
  cta?: string
  win?: string
  lose?: string
}

export interface Campaign {
  slug: string
  title: string
  mechanic: Mechanic
  period: Period
  theme: CampaignTheme
  texts: CampaignTexts
  attempts_left: number
}

export interface AdminCampaign {
  id: string
  slug: string
  title: string
  mechanic: Mechanic
  status: CampaignStatus
  period: Period
  participants: number
}

export interface ParticipationSessionRequest {
  channel: Channel
  campaign_slug: string
  channel_token: string
}

export interface ParticipationSession {
  participant_id: string
  display_name: string
  attempts_left: number
  token: string
}

export interface LeaderboardEntry {
  rank: number
  display_name: string
  score: number
}

export interface Leaderboard {
  entries: LeaderboardEntry[]
  me: LeaderboardEntry | null
}

export interface Prize {
  title: string
  promo_code?: string
}

export interface QuizAnswerPayload {
  answers: number[]
}

export interface WheelSpinPayload {
  bet?: number
}

export type AttemptPayload = QuizAnswerPayload | WheelSpinPayload

export interface AttemptResult {
  outcome: Outcome
  score: number
  attempts_left: number
  prize?: Prize
}

export interface RewardItem {
  title: string
  promo_code?: string
  awarded_at: string
}

export interface RewardList {
  items: RewardItem[]
}

// --- Админ ---

export interface CreateCampaignRequest {
  title: string
  slug: string
  mechanic: Mechanic
  period: Period
  theme: CampaignTheme
  texts: CampaignTexts
}

export interface PrizePoolItem {
  id: string
  title: string
  quantity: number
  awarded: number
}

export interface AddPrizeRequest {
  title: string
  quantity: number
}

export interface AddPromoCodesRequest {
  codes: string[]
}

export interface PromoCodePool {
  total: number
  issued: number
  left: number
}

export interface CampaignStats {
  attempts: number
  participants: number
  winners: number
  prize_pool_left: number
  activity: number[]
}

// --- Викторина: контент для игрового экрана ---

export interface QuizOption {
  id: number
  label: string
}

export interface QuizQuestion {
  id: number
  text: string
  options: QuizOption[]
  correct: number
}

export interface WheelSector {
  id: number
  label: string
  color: string
  weight: number
  prize?: Prize
}
