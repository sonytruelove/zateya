<script setup lang="ts">
import { computed, useId } from 'vue'

const props = withDefaults(
  defineProps<{
    modelValue?: string
    label?: string
    hint?: string
    error?: string
    placeholder?: string
    type?: string
    disabled?: boolean
    prefix?: string
  }>(),
  { modelValue: '', type: 'text', disabled: false },
)

const emit = defineEmits<{ (e: 'update:modelValue', value: string): void }>()

const uid = useId()
const describedBy = computed(() =>
  props.error ? `${uid}-err` : props.hint ? `${uid}-hint` : undefined,
)

function onInput(event: Event) {
  emit('update:modelValue', (event.target as HTMLInputElement).value)
}
</script>

<template>
  <div>
    <label v-if="label" :for="uid" class="z-field-label">{{ label }}</label>
    <div
      class="flex items-center rounded border bg-surface transition focus-within:border-brand"
      :class="error ? 'border-danger' : 'border-line'"
    >
      <span v-if="prefix" class="pl-3 text-sm text-ink-soft">{{ prefix }}</span>
      <input
        :id="uid"
        :value="modelValue"
        :type="type"
        :placeholder="placeholder"
        :disabled="disabled"
        :aria-invalid="Boolean(error)"
        :aria-describedby="describedBy"
        class="h-10 w-full bg-transparent px-3 text-sm text-ink outline-none placeholder:text-ink-soft disabled:opacity-50"
        @input="onInput"
      />
    </div>
    <p v-if="error" :id="`${uid}-err`" class="mt-1 text-xs text-danger">{{ error }}</p>
    <p v-else-if="hint" :id="`${uid}-hint`" class="z-hint">{{ hint }}</p>
  </div>
</template>
