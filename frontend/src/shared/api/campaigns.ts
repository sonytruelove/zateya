import { apiRequest } from './http'
import type {
  AttemptPayload,
  AttemptResult,
  Campaign,
  Leaderboard,
  QuizQuestion,
  WheelSector,
} from './types'

export function getCampaign(slug: string): Promise<Campaign> {
  return apiRequest<Campaign>(`/campaigns/${slug}`)
}

export function getLeaderboard(slug: string, limit = 10): Promise<Leaderboard> {
  return apiRequest<Leaderboard>(`/campaigns/${slug}/leaderboard`, { query: { limit } })
}

export function getQuiz(slug: string): Promise<{ questions: QuizQuestion[] }> {
  return apiRequest<{ questions: QuizQuestion[] }>(`/campaigns/${slug}/quiz`)
}

export function getWheel(slug: string): Promise<{ sectors: WheelSector[] }> {
  return apiRequest<{ sectors: WheelSector[] }>(`/campaigns/${slug}/wheel`)
}

export type AttemptResponse = AttemptResult & { sector_id?: number }

export function submitAttempt(slug: string, payload: AttemptPayload): Promise<AttemptResponse> {
  return apiRequest<AttemptResponse>(`/campaigns/${slug}/attempts`, {
    method: 'POST',
    body: payload,
  })
}
