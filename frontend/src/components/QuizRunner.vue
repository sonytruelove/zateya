<script setup lang="ts">
import { computed, ref } from 'vue'
import { ArrowRight, Check } from 'lucide-vue-next'
import type { QuizQuestion } from '@/shared/api/types'
import Button from '@/ui/Button.vue'
import Progress from '@/ui/Progress.vue'

const props = defineProps<{ questions: QuizQuestion[]; busy?: boolean }>()
const emit = defineEmits<{ (e: 'finish', answers: number[]): void }>()

const index = ref(0)
const answers = ref<number[]>([])
const selected = ref<number | null>(null)

const current = computed(() => props.questions[index.value])
const isLast = computed(() => index.value === props.questions.length - 1)

function choose(optionId: number) {
  selected.value = optionId
}

function next() {
  if (selected.value === null) return
  answers.value[index.value] = selected.value
  if (isLast.value) {
    emit('finish', [...answers.value])
    return
  }
  index.value += 1
  selected.value = answers.value[index.value] ?? null
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <div class="mb-2 flex items-center justify-between text-sm text-ink-soft">
        <span>Вопрос {{ index + 1 }} из {{ questions.length }}</span>
        <span class="tabular-nums">
          {{ Math.round(((index + 1) / questions.length) * 100) }}%
        </span>
      </div>
      <Progress :value="index + 1" :max="questions.length" />
    </div>

    <h2 class="text-xl font-extrabold leading-snug">{{ current.text }}</h2>

    <div class="grid gap-2">
      <button
        v-for="opt in current.options"
        :key="opt.id"
        class="flex items-center gap-3 rounded-card border px-4 py-3 text-left text-sm font-medium transition"
        :class="
          selected === opt.id
            ? 'border-brand bg-brand-soft text-brand'
            : 'border-line bg-surface hover:border-ink-soft'
        "
        @click="choose(opt.id)"
      >
        <span
          class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border"
          :class="selected === opt.id ? 'border-brand bg-brand text-white' : 'border-line'"
        >
          <Check v-if="selected === opt.id" class="h-3 w-3" />
        </span>
        {{ opt.label }}
      </button>
    </div>

    <div class="flex justify-end">
      <Button :disabled="selected === null" :loading="busy && isLast" @click="next">
        {{ isLast ? 'Завершить' : 'Дальше' }}
        <ArrowRight class="h-4 w-4" />
      </Button>
    </div>
  </div>
</template>
