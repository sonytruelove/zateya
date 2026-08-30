import { describe, expect, it } from 'vitest'
import { daysLeft, formatPeriod, periodState } from '../period'

const iso = (y: number, m: number, d: number) => new Date(y, m - 1, d, 12).toISOString()

describe('formatPeriod', () => {
  it('один месяц и год — сокращённая запись', () => {
    expect(formatPeriod({ starts_at: iso(2026, 3, 5), ends_at: iso(2026, 3, 25) })).toBe(
      '5 — 25 марта 2026',
    )
  })

  it('разные месяцы одного года', () => {
    expect(formatPeriod({ starts_at: iso(2026, 2, 28), ends_at: iso(2026, 3, 12) })).toBe(
      '28 февраля — 12 марта 2026',
    )
  })

  it('переход через год', () => {
    expect(formatPeriod({ starts_at: iso(2025, 12, 20), ends_at: iso(2026, 1, 10) })).toBe(
      '20 декабря 2025 — 10 января 2026',
    )
  })
})

describe('periodState', () => {
  const period = { starts_at: iso(2026, 6, 1), ends_at: iso(2026, 6, 30) }

  it('upcoming до старта', () => {
    expect(periodState(period, new Date(2026, 4, 1))).toBe('upcoming')
  })
  it('running в интервале', () => {
    expect(periodState(period, new Date(2026, 5, 15))).toBe('running')
  })
  it('ended после конца', () => {
    expect(periodState(period, new Date(2026, 7, 1))).toBe('ended')
  })
})

describe('daysLeft', () => {
  it('считает полные дни до конца', () => {
    const period = { starts_at: iso(2026, 6, 1), ends_at: iso(2026, 6, 30) }
    expect(daysLeft(period, new Date(2026, 5, 20, 12))).toBe(10)
  })
  it('ноль после завершения', () => {
    const period = { starts_at: iso(2026, 6, 1), ends_at: iso(2026, 6, 30) }
    expect(daysLeft(period, new Date(2026, 6, 5))).toBe(0)
  })
})
