<template>
  <div class="project-list-skeleton">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <!-- Project Image -->
      <div class="md:col-span-1">
        <SkeletonLoader
          variant="rectangular"
          width="100%"
          height="128px"
          rounded
        />
      </div>

      <!-- Project Details -->
      <div class="md:col-span-2 space-y-3">
        <!-- Title -->
        <SkeletonLoader
          variant="text"
          width="75%"
          height="24px"
        />
        
        <!-- Subtitle -->
        <SkeletonLoader
          variant="text"
          width="50%"
          height="14px"
        />

        <!-- Description -->
        <div class="space-y-2">
          <SkeletonLoader
            variant="text"
            width="100%"
            height="14px"
          />
          <SkeletonLoader
            variant="text"
            width="85%"
            height="14px"
          />
        </div>

        <!-- Technologies -->
        <div class="flex flex-wrap gap-1">
          <SkeletonLoader
            v-for="i in 6"
            :key="i"
            variant="rectangular"
            :width="`${Math.random() * 40 + 50}px`"
            height="20px"
            rounded
          />
        </div>
      </div>

      <!-- Actions -->
      <div class="md:col-span-1 space-y-2">
        <SkeletonLoader
          variant="rectangular"
          width="100%"
          height="32px"
          rounded
        />
        
        <div class="flex gap-2">
          <SkeletonLoader
            variant="rectangular"
            width="48%"
            height="32px"
            rounded
          />
          <SkeletonLoader
            variant="rectangular"
            width="48%"
            height="32px"
            rounded
          />
        </div>

        <SkeletonLoader
          variant="rectangular"
          width="100%"
          height="32px"
          rounded
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import SkeletonLoader from './SkeletonLoader.vue'
</script>

<style scoped>
.project-list-skeleton {
  background-color: #ffffff;
  border-radius: 0.5rem;
  padding: 1.5rem;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  border: 1px solid #e5e7eb;
}

@media (prefers-color-scheme: dark) {
  .project-list-skeleton {
    background-color: #1f2937;
    border-color: #374151;
  }
}
</style>