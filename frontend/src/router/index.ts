import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  { path: '/', redirect: '/c/ny-2026' },
  {
    path: '/c/:slug',
    name: 'campaign',
    component: () => import('@/views/CampaignShowcase.vue'),
    props: true,
  },
  {
    path: '/c/:slug/play',
    name: 'play',
    component: () => import('@/views/PlayView.vue'),
    props: true,
  },
  {
    path: '/admin',
    name: 'admin-campaigns',
    component: () => import('@/views/admin/AdminCampaigns.vue'),
  },
  {
    path: '/admin/new',
    name: 'admin-campaign-new',
    component: () => import('@/views/admin/AdminCampaignForm.vue'),
  },
  {
    path: '/admin/campaigns/:id',
    name: 'admin-campaign',
    component: () => import('@/views/admin/AdminCampaignDetail.vue'),
    props: true,
  },
  {
    path: '/admin/campaigns/:id/edit',
    name: 'admin-campaign-edit',
    component: () => import('@/views/admin/AdminCampaignForm.vue'),
    props: true,
  },
  {
    path: '/channels',
    name: 'channels',
    component: () => import('@/views/Channels.vue'),
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/views/NotFound.vue'),
  },
]

export const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})
