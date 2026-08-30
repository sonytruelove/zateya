import { describe, expect, it } from 'vitest'
import { emptyLeaderboard, reduceLeaderboard } from '../leaderboard'
import type { LeaderboardEntry } from '../../api/types'

const e = (name: string, score: number): LeaderboardEntry => ({ rank: 0, display_name: name, score })

describe('reduceLeaderboard', () => {
  it('сортирует по убыванию очков и проставляет ранги', () => {
    const state = reduceLeaderboard(emptyLeaderboard(), {
      entries: [e('А', 10), e('Б', 30), e('В', 20)],
    })
    expect(state.rows.map((r) => r.display_name)).toEqual(['Б', 'В', 'А'])
    expect(state.rows.map((r) => r.rank)).toEqual([1, 2, 3])
  })

  it('первый снимок не помечает строки как new', () => {
    const state = reduceLeaderboard(emptyLeaderboard(), { entries: [e('А', 10), e('Б', 5)] })
    expect(state.rows.every((r) => r.trend === 'same')).toBe(true)
  })

  it('считает подъём и падение позиций между обновлениями', () => {
    const first = reduceLeaderboard(emptyLeaderboard(), {
      entries: [e('А', 30), e('Б', 20), e('В', 10)],
    })
    const second = reduceLeaderboard(first, {
      entries: [e('А', 30), e('Б', 20), e('В', 40)],
    })
    const byName = Object.fromEntries(second.rows.map((r) => [r.display_name, r]))
    expect(byName['В'].trend).toBe('up')
    expect(byName['В'].delta).toBe(2)
    expect(byName['А'].trend).toBe('down')
    expect(byName['А'].delta).toBe(-1)
    expect(byName['Б'].trend).toBe('down')
  })

  it('новая запись после первого снимка помечается trend=new', () => {
    const first = reduceLeaderboard(emptyLeaderboard(), { entries: [e('А', 30)] })
    const second = reduceLeaderboard(first, { entries: [e('А', 30), e('Г', 25)] })
    expect(second.rows.find((r) => r.display_name === 'Г')?.trend).toBe('new')
  })

  it('сохраняет позицию me из нового события или прежнюю', () => {
    const first = reduceLeaderboard(emptyLeaderboard(), {
      entries: [e('А', 30)],
      me: { rank: 5, display_name: 'Вы', score: 12 },
    })
    expect(first.me?.rank).toBe(5)
    const second = reduceLeaderboard(first, { entries: [e('А', 30)] })
    expect(second.me?.rank).toBe(5)
  })

  it('проставляет updatedAt из аргумента now', () => {
    const state = reduceLeaderboard(emptyLeaderboard(), { entries: [e('А', 1)] }, 1234)
    expect(state.updatedAt).toBe(1234)
  })
})
