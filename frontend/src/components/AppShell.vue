<script setup lang="ts">
import { ref } from 'vue'
import AppHeader from './AppHeader.vue'
import AppSidebar from './AppSidebar.vue'

const mobileOpen = ref(false)
</script>

<template>
  <div class="min-h-screen bg-app">
    <AppHeader @toggle-sidebar="mobileOpen = !mobileOpen" />

    <div class="mx-auto flex w-full max-w-[1400px]">
      <AppSidebar :open="mobileOpen" @navigate="mobileOpen = false" />

      <div
        v-if="mobileOpen"
        class="fixed inset-0 z-30 bg-black/30 lg:hidden"
        @click="mobileOpen = false"
      />

      <main class="min-w-0 flex-1 px-4 py-6 sm:px-6 lg:px-8">
        <slot />
      </main>
    </div>
  </div>
</template>
