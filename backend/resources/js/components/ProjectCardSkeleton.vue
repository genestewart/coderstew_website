<template>
  <div class="project-card-skeleton">
    <!-- Image skeleton -->
    <SkeletonLoader
      variant="rectangular"
      width="100%"
      height="192px"
      class="mb-4"
    />
    
    <!-- Title skeleton -->
    <SkeletonLoader
      variant="text"
      width="80%"
      class="mb-2"
    />
    
    <!-- Subtitle skeleton -->
    <SkeletonLoader
      variant="text"
      width="60%"
      height="14px"
      class="mb-3"
    />
    
    <!-- Content skeleton -->
    <div class="mb-4">
      <SkeletonLoader
        variant="text"
        width="100%"
        height="14px"
        class="mb-1"
      />
      <SkeletonLoader
        variant="text"
        width="90%"
        height="14px"
        class="mb-1"
      />
      <SkeletonLoader
        variant="text"
        width="75%"
        height="14px"
      />
    </div>
    
    <!-- Technologies skeleton -->
    <div class="flex flex-wrap gap-1 mb-4">
      <SkeletonLoader
        v-for="i in 4"
        :key="i"
        variant="rectangular"
        :width="`${Math.random() * 40 + 40}px`"
        height="20px"
        rounded
      />
    </div>
    
    <!-- Buttons skeleton -->
    <div class="flex gap-2">
      <SkeletonLoader
        variant="rectangular"
        width="100px"
        height="32px"
        rounded
      />
      <SkeletonLoader
        variant="rectangular"
        width="32px"
        height="32px"
        rounded
      />
      <SkeletonLoader
        variant="rectangular"
        width="32px"
        height="32px"
        rounded
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import SkeletonLoader from './SkeletonLoader.vue'
</script>

<style scoped>
.project-card-skeleton {
  background-color: #ffffff;
  border-radius: 0.5rem;
  padding: 1rem;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  border: 1px solid #e5e7eb;
}

@media (prefers-color-scheme: dark) {
  .project-card-skeleton {
    background-color: #1f2937;
    border-color: #374151;
  }
}
</style>