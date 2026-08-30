<script setup lang="ts">
import type { Component } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { Store, LayoutGrid, Gift, Ticket, Radio, BarChart3 } from 'lucide-vue-next'

defineProps<{ open: boolean }>()
defineEmits<{ (e: 'navigate'): void }>()

const route = useRoute()

function isActive(to: string): boolean {
  if (to.includes('?')) return route.fullPath === to
  if (to === '/admin') return route.path === '/admin'
  return route.path === to || route.path.startsWith(to + '/')
}

interface NavItem {
  label: string
  to: string
  icon: Component
  hint: string
}

// Пиктограммы-метафоры разделов.
const items: NavItem[] = [
  { label: 'Витрина', to: '/c/ny-2026', icon: Store, hint: 'Как видит участник' },
  { label: 'Кампании', to: '/admin', icon: LayoutGrid, hint: 'Список и запуск' },
  {
    label: 'Призы',
    to: '/admin/campaigns/cmp_ny2026?tab=prizes',
    icon: Gift,
    hint: 'Призовой фонд',
  },
  {
    label: 'Промокоды',
    to: '/admin/campaigns/cmp_ny2026?tab=promo',
    icon: Ticket,
    hint: 'Пулы кодов',
  },
  { label: 'Каналы', to: '/channels', icon: Radio, hint: 'Веб, Telegram, VK' },
  {
    label: 'Сводка',
    to: '/admin/campaigns/cmp_ny2026?tab=summary',
    icon: BarChart3,
    hint: 'Метрики кампании',
  },
]
</script>

<template>
  <aside
    class="fixed inset-y-0 left-0 z-40 w-64 shrink-0 border-r border-line bg-surface transition-transform duration-200 lg:static lg:translate-x-0"
    :class="open ? 'translate-x-0' : '-translate-x-full'"
  >
    <div class="flex h-14 items-center gap-2 border-b border-line px-5">
      <span class="text-sm font-semibold text-ink-soft">Разделы</span>
    </div>
    <nav class="flex flex-col gap-1 p-3">
      <RouterLink
        v-for="item in items"
        :key="item.label"
        :to="item.to"
        class="group flex items-center gap-3 rounded px-3 py-2.5 text-sm font-medium transition hover:bg-surface-muted hover:text-ink"
        :class="isActive(item.to) ? 'bg-brand-soft text-brand' : 'text-ink-soft'"
        @click="$emit('navigate')"
      >
        <span
          class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-surface-muted text-ink-soft transition group-hover:bg-brand-soft group-hover:text-brand"
        >
          <component :is="item.icon" class="h-4 w-4" />
        </span>
        <span class="flex flex-col leading-tight">
          {{ item.label }}
          <span class="text-xs font-normal text-ink-soft">{{ item.hint }}</span>
        </span>
      </RouterLink>
    </nav>
    <div class="absolute inset-x-0 bottom-0 border-t border-line p-4 text-xs text-ink-soft">
      Демо-режим · данные подставные
    </div>
  </aside>
</template>
