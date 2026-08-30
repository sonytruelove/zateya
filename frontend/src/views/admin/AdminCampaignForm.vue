<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowLeft, Rocket } from 'lucide-vue-next'
import { createCampaign, getCampaignDetail, publishCampaign } from '@/shared/api/admin'
import type { CreateCampaignRequest, Mechanic } from '@/shared/api/types'
import { useToasts } from '@/ui'
import Button from '@/ui/Button.vue'
import Card from '@/ui/Card.vue'
import Input from '@/ui/Input.vue'
import Select from '@/ui/Select.vue'

const props = defineProps<{ id?: string }>()
const router = useRouter()
const toasts = useToasts()

const isEdit = computed(() => Boolean(props.id))
const saving = ref(false)
const publishing = ref(false)
const touched = ref(false)

const mechanics: { value: Mechanic; label: string }[] = [
  { value: 'quiz', label: 'Викторина' },
  { value: 'wheel', label: 'Колесо фортуны' },
  { value: 'collection', label: 'Собери набор' },
  { value: 'promo', label: 'Промокоды' },
]

const emojiChoices = ['🎄', '🧠', '🎡', '🏅', '🎟️', '☀️', '🚀', '🎁', '⭐', '🔥']

function isoToDate(iso: string): string {
  return iso ? iso.slice(0, 10) : ''
}
function dateToIso(date: string): string {
  return date ? new Date(date + 'T00:00:00').toISOString() : ''
}

const form = reactive({
  title: '',
  slug: '',
  mechanic: 'quiz' as string,
  starts_at: '',
  ends_at: '',
  color: '#0B57D0',
  emoji: '🎁',
  tagline: '',
  description: '',
  rules: '',
  cta: 'Играть',
})

// Автослаг из названия, пока пользователь не правил слаг вручную.
const slugEdited = ref(false)
watch(
  () => form.title,
  (title) => {
    if (slugEdited.value) return
    form.slug = title
      .toLowerCase()
      .replace(/[^a-zа-я0-9\s-]/gi, '')
      .replace(/[а-я]/gi, (c) => translit(c))
      .trim()
      .replace(/\s+/g, '-')
      .slice(0, 40)
  },
)

function translit(ch: string): string {
  const map: Record<string, string> = {
    а: 'a', б: 'b', в: 'v', г: 'g', д: 'd', е: 'e', ё: 'e', ж: 'zh', з: 'z', и: 'i',
    й: 'y', к: 'k', л: 'l', м: 'm', н: 'n', о: 'o', п: 'p', р: 'r', с: 's', т: 't',
    у: 'u', ф: 'f', х: 'h', ц: 'c', ч: 'ch', ш: 'sh', щ: 'sch', ъ: '', ы: 'y', ь: '',
    э: 'e', ю: 'yu', я: 'ya',
  }
  return map[ch.toLowerCase()] ?? ch
}

const errors = computed(() => {
  const e: Record<string, string> = {}
  if (!form.title.trim()) e.title = 'Укажите название'
  if (!/^[a-z0-9-]+$/.test(form.slug)) e.slug = 'Только латиница, цифры и дефис'
  if (!form.starts_at) e.starts_at = 'Укажите дату начала'
  if (!form.ends_at) e.ends_at = 'Укажите дату конца'
  if (form.starts_at && form.ends_at && form.ends_at < form.starts_at)
    e.ends_at = 'Конец раньше начала'
  return e
})
const valid = computed(() => Object.keys(errors.value).length === 0)

onMounted(async () => {
  if (!props.id) return
  try {
    const detail = await getCampaignDetail(props.id)
    const c = detail.public
    form.title = c.title
    slugEdited.value = true
    form.slug = c.slug
    form.mechanic = c.mechanic
    form.starts_at = isoToDate(c.period.starts_at)
    form.ends_at = isoToDate(c.period.ends_at)
    form.color = c.theme.color
    form.emoji = c.theme.emoji
    form.tagline = c.texts.tagline ?? ''
    form.description = c.texts.description ?? ''
    form.rules = c.texts.rules ?? ''
    form.cta = c.texts.cta ?? 'Играть'
  } catch {
    toasts.danger('Кампания не найдена')
    router.push('/admin')
  }
})

function payload(): CreateCampaignRequest {
  return {
    title: form.title.trim(),
    slug: form.slug,
    mechanic: form.mechanic as Mechanic,
    period: { starts_at: dateToIso(form.starts_at), ends_at: dateToIso(form.ends_at) },
    theme: { color: form.color, emoji: form.emoji },
    texts: {
      tagline: form.tagline.trim() || undefined,
      description: form.description.trim() || undefined,
      rules: form.rules.trim() || undefined,
      cta: form.cta.trim() || undefined,
    },
  }
}

async function save() {
  touched.value = true
  if (!valid.value) {
    toasts.warning('Проверьте форму', 'Некоторые поля заполнены неверно')
    return
  }
  saving.value = true
  try {
    const created = await createCampaign(payload())
    toasts.success(isEdit.value ? 'Изменения сохранены' : 'Кампания создана', `/c/${created.slug}`)
    router.push(`/admin/campaigns/${created.id}`)
  } catch {
    toasts.danger('Не удалось сохранить')
  } finally {
    saving.value = false
  }
}

async function saveAndPublish() {
  touched.value = true
  if (!valid.value) return
  publishing.value = true
  try {
    const created = await createCampaign(payload())
    await publishCampaign(created.id)
    toasts.success('Кампания опубликована')
    router.push(`/admin/campaigns/${created.id}`)
  } catch {
    toasts.danger('Не удалось опубликовать')
  } finally {
    publishing.value = false
  }
}
</script>

<template>
  <div class="mx-auto max-w-3xl">
    <button
      class="mb-4 inline-flex items-center gap-1.5 text-sm text-ink-soft transition hover:text-ink"
      @click="router.push('/admin')"
    >
      <ArrowLeft class="h-4 w-4" />
      К списку кампаний
    </button>

    <h1 class="text-2xl font-black tracking-tight">
      {{ isEdit ? 'Редактирование кампании' : 'Новая кампания' }}
    </h1>

    <form class="mt-6 space-y-6" @submit.prevent="save">
      <Card title="Основное">
        <div class="grid gap-4 sm:grid-cols-2">
          <Input
            v-model="form.title"
            label="Название"
            placeholder="Например, Новогодний розыгрыш"
            :error="touched ? errors.title : ''"
          />
          <Input
            v-model="form.slug"
            label="Слаг (адрес витрины)"
            prefix="/c/"
            :error="touched ? errors.slug : ''"
            @update:model-value="slugEdited = true"
          />
          <Select
            v-model="form.mechanic"
            label="Механика"
            :options="mechanics"
          />
          <div class="grid grid-cols-2 gap-3">
            <Input v-model="form.starts_at" label="Начало" type="date" :error="touched ? errors.starts_at : ''" />
            <Input v-model="form.ends_at" label="Конец" type="date" :error="touched ? errors.ends_at : ''" />
          </div>
        </div>
      </Card>

      <Card title="Оформление">
        <div class="flex flex-wrap items-end gap-6">
          <div>
            <label class="z-field-label">Акцентный цвет</label>
            <div class="flex items-center gap-2">
              <input
                v-model="form.color"
                type="color"
                class="h-10 w-14 cursor-pointer rounded border border-line bg-surface"
              />
              <code class="text-sm text-ink-soft">{{ form.color }}</code>
            </div>
          </div>
          <div>
            <label class="z-field-label">Эмодзи</label>
            <div class="flex flex-wrap gap-1.5">
              <button
                v-for="e in emojiChoices"
                :key="e"
                type="button"
                class="flex h-10 w-10 items-center justify-center rounded border text-lg transition"
                :class="form.emoji === e ? 'border-brand bg-brand-soft' : 'border-line hover:bg-surface-muted'"
                @click="form.emoji = e"
              >
                {{ e }}
              </button>
            </div>
          </div>
          <div
            class="ml-auto flex items-center gap-3 rounded-card px-4 py-3 text-white"
            :style="{ background: form.color }"
          >
            <span class="text-2xl">{{ form.emoji }}</span>
            <span class="text-sm font-bold">{{ form.title || 'Превью витрины' }}</span>
          </div>
        </div>
      </Card>

      <Card title="Тексты">
        <div class="space-y-4">
          <Input v-model="form.tagline" label="Подзаголовок" placeholder="Одна фраза о сути затеи" />
          <div>
            <label class="z-field-label">Описание</label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full rounded border border-line bg-surface px-3 py-2 text-sm outline-none transition focus:border-brand"
              placeholder="Что делает участник и что получает"
            />
          </div>
          <div>
            <label class="z-field-label">Правила</label>
            <textarea
              v-model="form.rules"
              rows="2"
              class="w-full rounded border border-line bg-surface px-3 py-2 text-sm outline-none transition focus:border-brand"
              placeholder="Ограничения, частота попыток и т.п."
            />
          </div>
          <Input v-model="form.cta" label="Текст кнопки" placeholder="Играть" />
        </div>
      </Card>

      <div class="flex flex-wrap gap-3">
        <Button type="submit" :loading="saving">
          {{ isEdit ? 'Сохранить' : 'Создать черновик' }}
        </Button>
        <Button type="button" variant="secondary" :loading="publishing" @click="saveAndPublish">
          <Rocket class="h-4 w-4" />
          Создать и опубликовать
        </Button>
        <Button type="button" variant="ghost" @click="router.push('/admin')">Отмена</Button>
      </div>
    </form>
  </div>
</template>
