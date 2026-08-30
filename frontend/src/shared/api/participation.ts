import { apiRequest } from './http'
import type {
  ParticipationSession,
  ParticipationSessionRequest,
  RewardList,
} from './types'

export function createParticipationSession(
  req: ParticipationSessionRequest,
): Promise<ParticipationSession> {
  return apiRequest<ParticipationSession>('/participation/sessions', {
    method: 'POST',
    body: req,
  })
}

export function getMyRewards(): Promise<RewardList> {
  return apiRequest<RewardList>('/participants/me/rewards')
}
