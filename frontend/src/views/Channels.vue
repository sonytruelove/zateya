<script setup lang="ts">
import { Globe, MessageCircle, Radio, Share2 } from 'lucide-vue-next'
import Card from '@/ui/Card.vue'
import Badge from '@/ui/Badge.vue'
import EmptyState from '@/components/EmptyState.vue'

const channels = [
  { icon: Globe, name: 'Веб-виджет', status: 'Подключён', tone: 'success' as const, note: 'Витрина /c/:slug' },
  { icon: MessageCircle, name: 'Telegram-бот', status: 'Подключён', tone: 'success' as const, note: '@zateya_demo_bot' },
  { icon: Share2, name: 'VK Mini App', status: 'Не настроен', tone: 'neutral' as const, note: 'Нужен app_id' },
]
</script>

<template>
  <div class="mx-auto max-w-content">
    <h1 class="text-2xl font-black tracking-tight">Каналы</h1>
    <p class="mt-1 text-sm text-ink-soft">
      Точки входа участников в механики: веб, Telegram, VK. Один участник — один прогресс во всех каналах.
    </p>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
      <Card v-for="ch in channels" :key="ch.name" padded>
        <div class="flex items-start justify-between">
          <span
            class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-brand-soft text-brand"
          >
            <component :is="ch.icon" class="h-5 w-5" />
          </span>
          <Badge :tone="ch.tone" dot>{{ ch.status }}</Badge>
        </div>
        <h3 class="mt-3 text-base font-bold">{{ ch.name }}</h3>
        <p class="mt-0.5 text-sm text-ink-soft">{{ ch.note }}</p>
      </Card>
    </div>

    <div class="mt-6">
      <EmptyState
        :icon="Radio"
        title="Журнал событий каналов пуст"
        description="Здесь появятся входящие сессии участников и вебхуки от Telegram и VK, когда они начнут поступать."
      />
    </div>
  </div>
</template>
