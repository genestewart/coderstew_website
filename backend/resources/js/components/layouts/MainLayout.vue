<template>
  <div class="flex flex-col min-h-screen">
    <header class="bg-slate-800 text-white">
      <div class="container mx-auto px-4 flex items-center justify-between py-4">
        <RouterLink to="/" class="font-bold text-lg">CoderStew</RouterLink>
        <button
          @click="menuOpen = !menuOpen"
          class="md:hidden focus:outline-none"
        >
          <span class="pi pi-bars text-xl"></span>
        </button>
        <nav
          :class="[
            'flex flex-col md:flex-row md:items-center w-full md:w-auto mt-4 md:mt-0 space-y-2 md:space-y-0',
            menuOpen ? 'block' : 'hidden md:flex'
          ]"
        >
          <RouterLink
            to="/"
            class="px-2 py-1 md:ml-4 hover:text-sunburst-500"
            @click="menuOpen = false"
          >
            Home
          </RouterLink>
          <RouterLink
            to="/about"
            class="px-2 py-1 md:ml-4 hover:text-sunburst-500"
            @click="menuOpen = false"
          >
            About
          </RouterLink>
        </nav>
      </div>
    </header>
    <main class="flex-grow container mx-auto px-4 py-6">
      <RouterView />
    </main>
    <footer class="bg-slate-800 text-white text-center py-4">
      © 2025 CoderStew
    </footer>
  </div>
</template>

<script lang="ts" setup>
import { ref } from 'vue';
import { RouterLink, RouterView } from 'vue-router';

const menuOpen = ref(false);
</script>

<style scoped>
.hover\:text-sunburst-500:hover {
  color: #FF9410;
}
</style>
