import { defineStore } from 'pinia'
import { ref } from 'vue'
import { createParticipationSession } from '@/shared/api/participation'
import type { Channel, ParticipationSession } from '@/shared/api/types'

export type DemoRole = 'participant' | 'organizer'

const ROLE_KEY = 'zateya:role'

export const useSessionStore = defineStore('session', () => {
  const savedRole = (localStorage.getItem(ROLE_KEY) as DemoRole | null) ?? 'participant'
  const role = ref<DemoRole>(savedRole)
  const participant = ref<ParticipationSession | null>(null)
  const joiningSlug = ref<string | null>(null)

  function setRole(next: DemoRole) {
    role.value = next
    localStorage.setItem(ROLE_KEY, next)
  }

  function toggleRole() {
    setRole(role.value === 'participant' ? 'organizer' : 'participant')
  }

  async function join(slug: string, channel: Channel = 'web'): Promise<ParticipationSession> {
    if (participant.value && joiningSlug.value === slug) return participant.value
    const session = await createParticipationSession({
      channel,
      campaign_slug: slug,
      channel_token: 'demo-' + Math.random().toString(36).slice(2, 10),
    })
    participant.value = session
    joiningSlug.value = slug
    return session
  }

  function spendAttempt(left: number) {
    if (participant.value) participant.value.attempts_left = left
  }

  return { role, participant, joiningSlug, setRole, toggleRole, join, spendAttempt }
})
