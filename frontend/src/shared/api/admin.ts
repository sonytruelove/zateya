import { apiRequest } from './http'
import type {
  AddPrizeRequest,
  AddPromoCodesRequest,
  AdminCampaign,
  Campaign,
  CampaignStats,
  CreateCampaignRequest,
  PrizePoolItem,
  PromoCodePool,
} from './types'

// Демо-токен организатора. В реальном приложении пришёл бы после входа.
const ADMIN_TOKEN = 'demo-admin-token'

export function listCampaigns(): Promise<{ items: AdminCampaign[] }> {
  return apiRequest<{ items: AdminCampaign[] }>('/admin/campaigns', { auth: ADMIN_TOKEN })
}

export interface AdminCampaignDetail {
  campaign: AdminCampaign
  public: Campaign
  prizes: PrizePoolItem[]
  promo: PromoCodePool
}

export function getCampaignDetail(id: string): Promise<AdminCampaignDetail> {
  return apiRequest<AdminCampaignDetail>(`/admin/campaigns/${id}`, { auth: ADMIN_TOKEN })
}

export function createCampaign(req: CreateCampaignRequest): Promise<AdminCampaign> {
  return apiRequest<AdminCampaign>('/admin/campaigns', {
    method: 'POST',
    body: req,
    auth: ADMIN_TOKEN,
  })
}

export function publishCampaign(id: string): Promise<AdminCampaign> {
  return apiRequest<AdminCampaign>(`/admin/campaigns/${id}/publish`, {
    method: 'POST',
    auth: ADMIN_TOKEN,
  })
}

export function getCampaignStats(id: string): Promise<CampaignStats> {
  return apiRequest<CampaignStats>(`/admin/campaigns/${id}/stats`, { auth: ADMIN_TOKEN })
}

export function addPrize(id: string, req: AddPrizeRequest): Promise<PrizePoolItem> {
  return apiRequest<PrizePoolItem>(`/admin/campaigns/${id}/prizes`, {
    method: 'POST',
    body: req,
    auth: ADMIN_TOKEN,
  })
}

export function addPromoCodes(id: string, req: AddPromoCodesRequest): Promise<PromoCodePool> {
  return apiRequest<PromoCodePool>(`/admin/campaigns/${id}/promo-codes`, {
    method: 'POST',
    body: req,
    auth: ADMIN_TOKEN,
  })
}
