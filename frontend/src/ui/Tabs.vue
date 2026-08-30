<script setup lang="ts">
import type { Component } from 'vue'

export interface TabItem {
  value: string
  label: string
  icon?: Component
}

defineProps<{ modelValue: string; tabs: TabItem[] }>()
const emit = defineEmits<{ (e: 'update:modelValue', value: string): void }>()
</script>

<template>
  <div class="z-scroll-x border-b border-line">
    <div class="flex gap-1" role="tablist">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        role="tab"
        :aria-selected="modelValue === tab.value"
        class="-mb-px inline-flex items-center gap-2 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition"
        :class="
          modelValue === tab.value
            ? 'border-brand text-brand'
            : 'border-transparent text-ink-soft hover:text-ink'
        "
        @click="emit('update:modelValue', tab.value)"
      >
        <component :is="tab.icon" v-if="tab.icon" class="h-4 w-4" />
        {{ tab.label }}
      </button>
    </div>
  </div>
</template>
