const nf = new Intl.NumberFormat('ru-RU')

export function formatNumber(value: number): string {
  return nf.format(value)
}

/** Склонение: 1 попытка, 2 попытки, 5 попыток. */
export function plural(n: number, forms: [string, string, string]): string {
  const abs = Math.abs(n) % 100
  const n1 = abs % 10
  if (abs > 10 && abs < 20) return forms[2]
  if (n1 > 1 && n1 < 5) return forms[1]
  if (n1 === 1) return forms[0]
  return forms[2]
}

export function attemptsLabel(n: number): string {
  return `${formatNumber(n)} ${plural(n, ['попытка', 'попытки', 'попыток'])}`
}

export function formatDateTime(iso: string): string {
  const d = new Date(iso)
  return d.toLocaleString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

export function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

export function initials(name: string): string {
  const clean = name.trim()
  if (!clean) return '?'
  const words = clean.split(/\s+/)
  if (words.length === 1) return words[0].slice(0, 2).toUpperCase()
  return (words[0][0] + words[1][0]).toUpperCase()
}
