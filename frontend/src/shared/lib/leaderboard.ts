import type { LeaderboardEntry } from '../api/types'

export type RankTrend = 'up' | 'down' | 'same' | 'new'

export interface LeaderboardRow extends LeaderboardEntry {
  /** Изменение позиции с прошлого обновления. Плюс — поднялся вверх. */
  delta: number
  trend: RankTrend
}

export interface LeaderboardState {
  rows: LeaderboardRow[]
  /** Позиция участника «Вы» среди всех, либо null. */
  me: LeaderboardEntry | null
  updatedAt: number
}

export function emptyLeaderboard(): LeaderboardState {
  return { rows: [], me: null, updatedAt: 0 }
}

function keyOf(e: LeaderboardEntry): string {
  return e.display_name
}

/**
 * Чистый редьюсер живого рейтинга: принимает предыдущее состояние и новый
 * список позиций, возвращает состояние с посчитанными трендами по каждой строке.
 */
export function reduceLeaderboard(
  prev: LeaderboardState,
  next: { entries: LeaderboardEntry[]; me?: LeaderboardEntry | null },
  now: number = Date.now(),
): LeaderboardState {
  const prevRankByKey = new Map<string, number>()
  for (const row of prev.rows) prevRankByKey.set(keyOf(row), row.rank)

  const sorted = [...next.entries].sort((a, b) => b.score - a.score)

  const rows: LeaderboardRow[] = sorted.map((entry, index) => {
    const rank = index + 1
    const before = prevRankByKey.get(keyOf(entry))
    if (before === undefined) {
      return { ...entry, rank, delta: 0, trend: prev.rows.length ? 'new' : 'same' }
    }
    const delta = before - rank
    const trend: RankTrend = delta > 0 ? 'up' : delta < 0 ? 'down' : 'same'
    return { ...entry, rank, delta, trend }
  })

  return {
    rows,
    me: next.me ?? prev.me,
    updatedAt: now,
  }
}
