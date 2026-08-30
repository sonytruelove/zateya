<script setup lang="ts">
import { RouterView } from 'vue-router'
import AppShell from './components/AppShell.vue'
import { ToastHost } from './ui'
</script>

<template>
  <AppShell>
    <RouterView v-slot="{ Component, route }">
      <component :is="Component" :key="route.path" class="z-page" />
    </RouterView>
  </AppShell>
  <ToastHost />
</template>

<style>
.z-page {
  animation: z-page-in 160ms ease both;
}
@keyframes z-page-in {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
  to {
    opacity: 1;
    transform: none;
  }
}
@media (prefers-reduced-motion: reduce) {
  .z-page {
    animation: none;
  }
}
</style>
