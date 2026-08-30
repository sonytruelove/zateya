<script setup lang="ts">
import { RouterLink, useRouter } from 'vue-router'
import { Menu, User, Wrench } from 'lucide-vue-next'
import logoFull from '@/assets/logo-full.svg'
import { useSessionStore } from '@/stores/session'

defineEmits<{ (e: 'toggle-sidebar'): void }>()

const session = useSessionStore()
const router = useRouter()

function switchTo(role: 'participant' | 'organizer') {
  session.setRole(role)
  if (role === 'organizer') router.push('/admin')
  else router.push('/c/ny-2026')
}
</script>

<template>
  <header
    class="sticky top-0 z-30 flex h-14 items-center gap-4 border-b border-line bg-surface/95 px-4 backdrop-blur sm:px-6"
  >
    <button
      class="rounded p-2 text-ink-soft transition hover:bg-surface-muted lg:hidden"
      aria-label="Меню разделов"
      @click="$emit('toggle-sidebar')"
    >
      <Menu class="h-5 w-5" />
    </button>

    <RouterLink to="/" class="flex items-center">
      <img :src="logoFull" alt="Затея" class="h-7 w-auto" />
    </RouterLink>

    <div class="ml-auto flex items-center rounded border border-line bg-surface-muted p-0.5 text-sm">
      <button
        class="inline-flex items-center gap-1.5 rounded px-3 py-1.5 font-medium transition"
        :class="
          session.role === 'participant' ? 'bg-surface text-ink shadow-soft' : 'text-ink-soft'
        "
        @click="switchTo('participant')"
      >
        <User class="h-4 w-4" />
        <span class="hidden sm:inline">Участник</span>
      </button>
      <button
        class="inline-flex items-center gap-1.5 rounded px-3 py-1.5 font-medium transition"
        :class="
          session.role === 'organizer' ? 'bg-surface text-ink shadow-soft' : 'text-ink-soft'
        "
        @click="switchTo('organizer')"
      >
        <Wrench class="h-4 w-4" />
        <span class="hidden sm:inline">Организатор</span>
      </button>
    </div>
  </header>
</template>
