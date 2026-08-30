<script setup lang="ts">
import type { Component } from 'vue'
import { Inbox } from 'lucide-vue-next'

withDefaults(
  defineProps<{ icon?: Component; title: string; description?: string; compact?: boolean }>(),
  { icon: Inbox, compact: false },
)
</script>

<template>
  <div
    class="flex flex-col items-center justify-center rounded-card border border-dashed border-line bg-surface text-center"
    :class="compact ? 'px-6 py-8' : 'px-6 py-14'"
  >
    <span
      class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-brand-soft text-brand"
    >
      <component :is="icon" class="h-5 w-5" />
    </span>
    <h3 class="mt-4 text-base font-bold">{{ title }}</h3>
    <p v-if="description" class="mt-1 max-w-sm text-sm text-ink-soft">{{ description }}</p>
    <div v-if="$slots.default" class="mt-4">
      <slot />
    </div>
  </div>
</template>
