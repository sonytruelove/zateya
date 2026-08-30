<script setup lang="ts">
withDefaults(defineProps<{ title?: string; subtitle?: string; padded?: boolean }>(), {
  padded: true,
})
</script>

<template>
  <section class="rounded-card border border-line bg-surface shadow-soft">
    <header
      v-if="title || $slots.header || $slots.actions"
      class="flex items-start justify-between gap-4 border-b border-line px-5 py-4"
    >
      <div>
        <slot name="header">
          <h3 class="text-base font-bold leading-tight">{{ title }}</h3>
          <p v-if="subtitle" class="mt-0.5 text-sm text-ink-soft">{{ subtitle }}</p>
        </slot>
      </div>
      <div v-if="$slots.actions" class="shrink-0">
        <slot name="actions" />
      </div>
    </header>
    <div :class="padded ? 'p-5' : ''">
      <slot />
    </div>
    <footer v-if="$slots.footer" class="border-t border-line px-5 py-4">
      <slot name="footer" />
    </footer>
  </section>
</template>
