<script setup lang="ts">
import type { Component } from 'vue'
import { ArrowDownRight, ArrowUpRight } from 'lucide-vue-next'

withDefaults(
  defineProps<{
    label: string
    value: string | number
    icon?: Component
    hint?: string
    trend?: number
  }>(),
  {},
)
</script>

<template>
  <div class="rounded-card border border-line bg-surface p-4 shadow-soft">
    <div class="flex items-center justify-between">
      <span class="text-sm text-ink-soft">{{ label }}</span>
      <span
        v-if="icon"
        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-brand-soft text-brand"
      >
        <component :is="icon" class="h-4 w-4" />
      </span>
    </div>
    <p class="mt-2 text-2xl font-extrabold tabular-nums tracking-tight">{{ value }}</p>
    <div class="mt-1 flex items-center gap-1 text-xs">
      <template v-if="trend !== undefined">
        <ArrowUpRight v-if="trend >= 0" class="h-3.5 w-3.5 text-success" />
        <ArrowDownRight v-else class="h-3.5 w-3.5 text-danger" />
        <span :class="trend >= 0 ? 'text-success' : 'text-danger'">
          {{ trend >= 0 ? '+' : '' }}{{ trend }}%
        </span>
      </template>
      <span v-if="hint" class="text-ink-soft">{{ hint }}</span>
    </div>
  </div>
</template>
