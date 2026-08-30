import { reactive } from 'vue'

export type ToastTone = 'info' | 'success' | 'warning' | 'danger'

export interface ToastItem {
  id: number
  tone: ToastTone
  title: string
  text?: string
}

const state = reactive<{ items: ToastItem[] }>({ items: [] })
let seq = 0

export function useToasts() {
  function push(tone: ToastTone, title: string, text?: string, timeout = 4200) {
    const id = ++seq
    state.items.push({ id, tone, title, text })
    if (timeout > 0) {
      setTimeout(() => dismiss(id), timeout)
    }
    return id
  }

  function dismiss(id: number) {
    const i = state.items.findIndex((t) => t.id === id)
    if (i !== -1) state.items.splice(i, 1)
  }

  return {
    items: state.items,
    dismiss,
    info: (title: string, text?: string) => push('info', title, text),
    success: (title: string, text?: string) => push('success', title, text),
    warning: (title: string, text?: string) => push('warning', title, text),
    danger: (title: string, text?: string) => push('danger', title, text),
  }
}
