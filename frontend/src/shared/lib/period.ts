import type { Period } from '../api/types'

const MONTHS_GEN = [
  'января',
  'февраля',
  'марта',
  'апреля',
  'мая',
  'июня',
  'июля',
  'августа',
  'сентября',
  'октября',
  'ноября',
  'декабря',
]

function parts(iso: string): { d: number; m: number; y: number } {
  const date = new Date(iso)
  return { d: date.getDate(), m: date.getMonth(), y: date.getFullYear() }
}

/**
 * Человекочитаемый период кампании.
 * «5 — 25 марта 2026» если один месяц и год,
 * «28 февраля — 12 марта 2026» если разные месяцы,
 * «20 декабря 2025 — 10 января 2026» если разные годы.
 */
export function formatPeriod(period: Period): string {
  const a = parts(period.starts_at)
  const b = parts(period.ends_at)

  if (a.y === b.y && a.m === b.m) {
    return `${a.d} — ${b.d} ${MONTHS_GEN[b.m]} ${b.y}`
  }
  if (a.y === b.y) {
    return `${a.d} ${MONTHS_GEN[a.m]} — ${b.d} ${MONTHS_GEN[b.m]} ${b.y}`
  }
  return `${a.d} ${MONTHS_GEN[a.m]} ${a.y} — ${b.d} ${MONTHS_GEN[b.m]} ${b.y}`
}

export type PeriodState = 'upcoming' | 'running' | 'ended'

export function periodState(period: Period, now: Date = new Date()): PeriodState {
  const t = now.getTime()
  if (t < new Date(period.starts_at).getTime()) return 'upcoming'
  if (t > new Date(period.ends_at).getTime()) return 'ended'
  return 'running'
}

/** Сколько полных дней осталось до конца периода (0, если уже закончился). */
export function daysLeft(period: Period, now: Date = new Date()): number {
  const diff = new Date(period.ends_at).getTime() - now.getTime()
  return Math.max(0, Math.ceil(diff / 86_400_000))
}
