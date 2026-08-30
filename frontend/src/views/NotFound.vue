<script setup lang="ts">
import { useRouter } from 'vue-router'
import { Compass } from 'lucide-vue-next'
import Button from '@/ui/Button.vue'

const router = useRouter()
</script>

<template>
  <div class="mx-auto flex max-w-md flex-col items-center py-20 text-center">
    <span
      class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-brand-soft text-brand"
    >
      <Compass class="h-7 w-7" />
    </span>
    <p class="mt-6 text-5xl font-black tracking-tight">404</p>
    <h1 class="mt-2 text-lg font-bold">Страница потерялась</h1>
    <p class="mt-1 text-sm text-ink-soft">
      Такой затеи нет или ссылка устарела. Вернитесь на витрину или к списку кампаний.
    </p>
    <div class="mt-6 flex gap-3">
      <Button @click="router.push('/c/ny-2026')">На витрину</Button>
      <Button variant="secondary" @click="router.push('/admin')">К кампаниям</Button>
    </div>
  </div>
</template>
