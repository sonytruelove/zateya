<script setup lang="ts">
import { useId } from 'vue'
import { ChevronDown } from 'lucide-vue-next'

export interface SelectOption {
  value: string
  label: string
}

withDefaults(
  defineProps<{
    modelValue?: string
    options: SelectOption[]
    label?: string
    hint?: string
    error?: string
    disabled?: boolean
    placeholder?: string
  }>(),
  { modelValue: '', disabled: false },
)

const emit = defineEmits<{ (e: 'update:modelValue', value: string): void }>()
const uid = useId()

function onChange(event: Event) {
  emit('update:modelValue', (event.target as HTMLSelectElement).value)
}
</script>

<template>
  <div>
    <label v-if="label" :for="uid" class="z-field-label">{{ label }}</label>
    <div class="relative">
      <select
        :id="uid"
        :value="modelValue"
        :disabled="disabled"
        class="h-10 w-full appearance-none rounded border bg-surface px-3 pr-9 text-sm text-ink outline-none transition focus:border-brand disabled:opacity-50"
        :class="error ? 'border-danger' : 'border-line'"
        @change="onChange"
      >
        <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
        <option v-for="opt in options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>
      <ChevronDown
        class="pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-soft"
      />
    </div>
    <p v-if="error" class="mt-1 text-xs text-danger">{{ error }}</p>
    <p v-else-if="hint" class="z-hint">{{ hint }}</p>
  </div>
</template>
