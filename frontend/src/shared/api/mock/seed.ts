import type {
  AdminCampaign,
  Campaign,
  CampaignStats,
  LeaderboardEntry,
  PrizePoolItem,
  PromoCodePool,
  QuizQuestion,
  RewardItem,
  WheelSector,
} from '../types'

// Небольшой генератор псевдослучайных чисел с фиксированным зерном —
// чтобы мок-данные были стабильны между перезагрузками.
export function makeRng(seed: number) {
  let s = seed >>> 0
  return () => {
    s = (s * 1664525 + 1013904223) >>> 0
    return s / 0xffffffff
  }
}

function daysFromNow(days: number): string {
  const d = new Date()
  d.setDate(d.getDate() + days)
  return d.toISOString()
}

export interface CampaignRecord {
  admin: AdminCampaign
  public: Campaign
  quiz?: QuizQuestion[]
  wheel?: WheelSector[]
  stats: CampaignStats
  prizes: PrizePoolItem[]
  promo: PromoCodePool
}

const quizBrand: QuizQuestion[] = [
  {
    id: 1,
    text: 'В каком году была основана компания «Комета»?',
    options: [
      { id: 0, label: '1998' },
      { id: 1, label: '2004' },
      { id: 2, label: '2011' },
      { id: 3, label: '2016' },
    ],
    correct: 1,
  },
  {
    id: 2,
    text: 'Как называется программа лояльности «Кометы»?',
    options: [
      { id: 0, label: '«Орбита»' },
      { id: 1, label: '«Созвездие»' },
      { id: 2, label: '«Траектория»' },
      { id: 3, label: '«Перигей»' },
    ],
    correct: 0,
  },
  {
    id: 3,
    text: 'Какой цвет считается фирменным у «Кометы»?',
    options: [
      { id: 0, label: 'Изумрудный' },
      { id: 1, label: 'Пурпурный' },
      { id: 2, label: 'Космический синий' },
      { id: 3, label: 'Оранжевый' },
    ],
    correct: 2,
  },
  {
    id: 4,
    text: 'Сколько городов присутствия у «Кометы» на 2026 год?',
    options: [
      { id: 0, label: 'Больше 40' },
      { id: 1, label: 'Ровно 12' },
      { id: 2, label: '3' },
      { id: 3, label: 'Больше 120' },
    ],
    correct: 3,
  },
  {
    id: 5,
    text: 'Что «Комета» дарит новым участникам программы?',
    options: [
      { id: 0, label: '500 приветственных баллов' },
      { id: 1, label: 'Ничего' },
      { id: 2, label: 'Скидку 90%' },
      { id: 3, label: 'Годовую подписку' },
    ],
    correct: 0,
  },
]

const wheelNy: WheelSector[] = [
  { id: 0, label: '+50', color: '#0B57D0', weight: 24, prize: { title: '50 баллов' } },
  { id: 1, label: '−10%', color: '#1E8E3E', weight: 18, prize: { title: 'Скидка 10%', promo_code: 'ZATEYA-10' } },
  { id: 2, label: 'Пусто', color: '#61666D', weight: 22 },
  { id: 3, label: '+150', color: '#0B57D0', weight: 12, prize: { title: '150 баллов' } },
  { id: 4, label: 'Стикеры', color: '#B26A00', weight: 10, prize: { title: 'Цифровой стикерпак' } },
  { id: 5, label: 'Пусто', color: '#61666D', weight: 8 },
  { id: 6, label: 'Мерч', color: '#C5221F', weight: 4, prize: { title: 'Фирменная кружка', promo_code: 'MUG-9F2K' } },
  { id: 7, label: '+300', color: '#0B57D0', weight: 2, prize: { title: '300 баллов' } },
]

function statsFor(seed: number): CampaignStats {
  const rng = makeRng(seed)
  const activity = Array.from({ length: 14 }, (_, i) => {
    const base = 40 + Math.sin(i / 2) * 22
    return Math.max(3, Math.round(base + rng() * 40))
  })
  const attempts = activity.reduce((a, b) => a + b, 0) * 3
  const participants = Math.round(attempts * 0.42)
  const winners = Math.round(participants * 0.31)
  return {
    attempts,
    participants,
    winners,
    prize_pool_left: 1240 - winners,
    activity,
  }
}

export function buildCampaigns(): Record<string, CampaignRecord> {
  const list: CampaignRecord[] = [
    {
      admin: {
        id: 'cmp_ny2026',
        slug: 'ny-2026',
        title: 'Новогодний розыгрыш',
        mechanic: 'wheel',
        status: 'active',
        period: { starts_at: daysFromNow(-6), ends_at: daysFromNow(20) },
        participants: 4821,
      },
      public: {
        slug: 'ny-2026',
        title: 'Новогодний розыгрыш',
        mechanic: 'wheel',
        period: { starts_at: daysFromNow(-6), ends_at: daysFromNow(20) },
        theme: { color: '#0B57D0', emoji: '🎄' },
        texts: {
          tagline: 'Крутите колесо и забирайте новогодние призы каждый день',
          description:
            'Каждый день — одна бесплатная попытка. Дополнительные попытки начисляются за покупки в приложении «Комета».',
          rules: 'Один участник — до 3 попыток в сутки. Призы ограничены и разыгрываются до окончания фонда.',
          cta: 'Крутить колесо',
          win: 'Поздравляем! Приз уже в разделе «Мои призы».',
          lose: 'В этот раз не повезло — возвращайтесь завтра за новой попыткой.',
        },
        attempts_left: 3,
      },
      wheel: wheelNy,
      stats: statsFor(101),
      prizes: [
        { id: 'pz_1', title: '50 баллов', quantity: 5000, awarded: 3120 },
        { id: 'pz_2', title: 'Скидка 10%', quantity: 2000, awarded: 1440 },
        { id: 'pz_3', title: 'Цифровой стикерпак', quantity: 800, awarded: 402 },
        { id: 'pz_4', title: 'Фирменная кружка', quantity: 120, awarded: 88 },
      ],
      promo: { total: 2000, issued: 1440, left: 560 },
    },
    {
      admin: {
        id: 'cmp_quizbrand',
        slug: 'quiz-brand',
        title: 'Викторина «Знаток бренда»',
        mechanic: 'quiz',
        status: 'active',
        period: { starts_at: daysFromNow(-14), ends_at: daysFromNow(7) },
        participants: 2043,
      },
      public: {
        slug: 'quiz-brand',
        title: 'Викторина «Знаток бренда»',
        mechanic: 'quiz',
        period: { starts_at: daysFromNow(-14), ends_at: daysFromNow(7) },
        theme: { color: '#1E8E3E', emoji: '🧠' },
        texts: {
          tagline: 'Проверьте, насколько хорошо вы знаете «Комету»',
          description:
            'Пять вопросов, одна минута. За каждый верный ответ — баллы в общий рейтинг. Три верных подряд открывают промокод.',
          rules: 'Одна попытка в день. Порядок вопросов и вариантов перемешивается.',
          cta: 'Начать викторину',
          win: 'Отличный результат! Промокод — в разделе «Мои призы».',
          lose: 'Неплохо, но до приза не хватило. Попробуйте завтра.',
        },
        attempts_left: 1,
      },
      quiz: quizBrand,
      stats: statsFor(202),
      prizes: [
        { id: 'pz_5', title: 'Промокод -15%', quantity: 1500, awarded: 640 },
        { id: 'pz_6', title: '200 баллов', quantity: 3000, awarded: 1210 },
      ],
      promo: { total: 1500, issued: 640, left: 860 },
    },
    {
      admin: {
        id: 'cmp_collection',
        slug: 'collection-badges',
        title: 'Собери коллекцию значков',
        mechanic: 'collection',
        status: 'scheduled',
        period: { starts_at: daysFromNow(5), ends_at: daysFromNow(45) },
        participants: 0,
      },
      public: {
        slug: 'collection-badges',
        title: 'Собери коллекцию значков',
        mechanic: 'collection',
        period: { starts_at: daysFromNow(5), ends_at: daysFromNow(45) },
        theme: { color: '#B26A00', emoji: '🏅' },
        texts: {
          tagline: 'Открывайте значки за действия в приложении и собирайте наборы',
          description: 'Механика запускается совсем скоро. Загляните позже.',
          cta: 'Скоро',
        },
        attempts_left: 0,
      },
      stats: statsFor(303),
      prizes: [{ id: 'pz_7', title: 'Набор из 6 значков', quantity: 500, awarded: 0 }],
      promo: { total: 0, issued: 0, left: 0 },
    },
    {
      admin: {
        id: 'cmp_promo',
        slug: 'promo-weekend',
        title: 'Промокоды выходного дня',
        mechanic: 'promo',
        status: 'finished',
        period: { starts_at: daysFromNow(-40), ends_at: daysFromNow(-2) },
        participants: 9550,
      },
      public: {
        slug: 'promo-weekend',
        title: 'Промокоды выходного дня',
        mechanic: 'promo',
        period: { starts_at: daysFromNow(-40), ends_at: daysFromNow(-2) },
        theme: { color: '#C5221F', emoji: '🎟️' },
        texts: {
          tagline: 'Кампания завершена — спасибо всем участникам',
          description: 'Промокоды больше не выдаются. Итоги подведены.',
          cta: 'Кампания завершена',
        },
        attempts_left: 0,
      },
      stats: statsFor(404),
      prizes: [{ id: 'pz_8', title: 'Промокод -20%', quantity: 10000, awarded: 10000 }],
      promo: { total: 10000, issued: 10000, left: 0 },
    },
    {
      admin: {
        id: 'cmp_summer',
        slug: 'summer-run',
        title: 'Летний марафон активностей',
        mechanic: 'quiz',
        status: 'draft',
        period: { starts_at: daysFromNow(30), ends_at: daysFromNow(90) },
        participants: 0,
      },
      public: {
        slug: 'summer-run',
        title: 'Летний марафон активностей',
        mechanic: 'quiz',
        period: { starts_at: daysFromNow(30), ends_at: daysFromNow(90) },
        theme: { color: '#0B57D0', emoji: '☀️' },
        texts: {
          tagline: 'Черновик кампании',
          description: 'Кампания ещё не опубликована.',
          cta: 'Недоступно',
        },
        attempts_left: 0,
      },
      quiz: quizBrand,
      stats: statsFor(505),
      prizes: [],
      promo: { total: 0, issued: 0, left: 0 },
    },
  ]

  const map: Record<string, CampaignRecord> = {}
  for (const rec of list) map[rec.public.slug] = rec
  return map
}

const firstNames = [
  'Мария',
  'Иван',
  'Анна',
  'Дмитрий',
  'Ольга',
  'Сергей',
  'Екатерина',
  'Алексей',
  'Наталья',
  'Пётр',
  'Юлия',
  'Артём',
  'Ксения',
  'Максим',
  'Вера',
]
const lastInitials = ['К.', 'П.', 'С.', 'М.', 'Р.', 'Т.', 'Ш.', 'Б.', 'Л.', 'Ж.']

export function buildLeaderboard(slug: string): LeaderboardEntry[] {
  const rng = makeRng(slug.split('').reduce((a, c) => a + c.charCodeAt(0), 7))
  const entries: LeaderboardEntry[] = []
  let score = 4200
  for (let i = 0; i < 40; i++) {
    score -= Math.round(30 + rng() * 160)
    entries.push({
      rank: i + 1,
      display_name: `${firstNames[Math.floor(rng() * firstNames.length)]} ${
        lastInitials[Math.floor(rng() * lastInitials.length)]
      }`,
      score: Math.max(20, score),
    })
  }
  return entries
}

export const seedRewards: RewardItem[] = [
  { title: 'Скидка 10%', promo_code: 'ZATEYA-10', awarded_at: daysFromNow(-1) },
  { title: '150 баллов', awarded_at: daysFromNow(-3) },
  { title: 'Цифровой стикерпак', awarded_at: daysFromNow(-8) },
]
