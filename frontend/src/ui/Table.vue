<script setup lang="ts" generic="Row extends Record<string, unknown>">
export interface Column<R> {
  key: string
  label: string
  align?: 'left' | 'right' | 'center'
  width?: string
  get?: (row: R) => unknown
}

withDefaults(
  defineProps<{
    columns: Column<Row>[]
    rows: Row[]
    rowKey?: (row: Row, index: number) => string | number
    dense?: boolean
  }>(),
  { dense: false },
)

const alignClass = { left: 'text-left', right: 'text-right', center: 'text-center' }
</script>

<template>
  <div class="z-scroll-x rounded-card border border-line bg-surface">
    <table class="w-full min-w-[560px] border-collapse text-sm">
      <thead>
        <tr class="border-b border-line">
          <th
            v-for="col in columns"
            :key="col.key"
            class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-ink-soft"
            :class="alignClass[col.align ?? 'left']"
            :style="col.width ? { width: col.width } : undefined"
          >
            {{ col.label }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="!rows.length">
          <td :colspan="columns.length" class="px-4 py-10 text-center text-ink-soft">
            <slot name="empty">Нет данных</slot>
          </td>
        </tr>
        <tr
          v-for="(row, index) in rows"
          :key="rowKey ? rowKey(row, index) : index"
          class="border-b border-line last:border-0 transition hover:bg-surface-muted"
        >
          <td
            v-for="col in columns"
            :key="col.key"
            class="px-4 align-middle"
            :class="[alignClass[col.align ?? 'left'], dense ? 'py-2' : 'py-3']"
          >
            <slot :name="`cell:${col.key}`" :row="row" :value="col.get ? col.get(row) : row[col.key]">
              {{ col.get ? col.get(row) : row[col.key] }}
            </slot>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
