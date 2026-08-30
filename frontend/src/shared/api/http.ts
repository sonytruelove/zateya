import { handleMock } from './mock'

export const API_BASE = import.meta.env.VITE_API_BASE ?? '/api/v1'

// МОК-режим включён по умолчанию и выключается только явным VITE_API_MOCK=false.
export const MOCK_ENABLED = import.meta.env.VITE_API_MOCK !== 'false'

export class ApiError extends Error {
  status: number
  constructor(status: number, message: string) {
    super(message)
    this.status = status
    this.name = 'ApiError'
  }
}

interface RequestOptions {
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'
  body?: unknown
  auth?: string | null
  query?: Record<string, string | number | undefined>
}

function withQuery(path: string, query?: RequestOptions['query']): string {
  if (!query) return path
  const params = new URLSearchParams()
  for (const [k, v] of Object.entries(query)) {
    if (v !== undefined) params.set(k, String(v))
  }
  const qs = params.toString()
  return qs ? `${path}?${qs}` : path
}

export async function apiRequest<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const method = options.method ?? 'GET'
  const fullPath = withQuery(path, options.query)

  if (MOCK_ENABLED) {
    const res = await handleMock(method, fullPath, options.body)
    if (res.status >= 400) {
      throw new ApiError(res.status, `Запрос ${method} ${path} вернул ${res.status}`)
    }
    return res.body as T
  }

  const headers: Record<string, string> = { 'Content-Type': 'application/json' }
  if (options.auth) headers.Authorization = `Bearer ${options.auth}`

  const response = await fetch(`${API_BASE}${fullPath}`, {
    method,
    headers,
    body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
  })

  if (!response.ok) {
    throw new ApiError(response.status, `Запрос ${method} ${path} вернул ${response.status}`)
  }
  if (response.status === 204) return undefined as T
  return (await response.json()) as T
}
