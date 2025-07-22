<template>
  <Card class="project-card h-full hover:shadow-lg transition-shadow duration-300">
    <template #header>
      <div class="relative overflow-hidden">
        <OptimizedImage
          v-if="project.featured_image_responsive && Object.keys(project.featured_image_responsive).length > 0"
          :responsive-sources="project.featured_image_responsive"
          :alt="project.title"
          width="100%"
          height="192px"
          size="medium"
          class="w-full h-48 object-cover transition-transform duration-300 hover:scale-105"
          :show-skeleton="true"
          :enable-blur="true"
        />
        <img
          v-else-if="project.featured_image"
          :src="project.featured_image"
          :alt="project.title"
          class="w-full h-48 object-cover transition-transform duration-300 hover:scale-105"
          loading="lazy"
        />
        <div
          v-else
          class="w-full h-48 bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center"
        >
          <i class="pi pi-image text-4xl text-white opacity-50"></i>
        </div>
        
        <!-- Featured Badge -->
        <Badge
          v-if="project.is_featured"
          value="Featured"
          severity="success"
          class="absolute top-2 right-2"
        />
        
        <!-- Category Badge -->
        <Badge
          v-if="project.category"
          :value="project.category.name"
          severity="info"
          class="absolute top-2 left-2"
        />
      </div>
    </template>

    <template #title>
      <router-link
        :to="{ name: 'project', params: { slug: project.slug } }"
        class="text-lg font-semibold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
      >
        {{ project.title }}
      </router-link>
    </template>

    <template #subtitle>
      <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
        <span v-if="project.client_name">{{ project.client_name }} • </span>
        <span v-if="project.completion_date">
          {{ formatDate(project.completion_date) }}
        </span>
      </div>
    </template>

    <template #content>
      <p class="text-gray-600 dark:text-gray-300 text-sm line-clamp-3 mb-4">
        {{ project.excerpt }}
      </p>

      <!-- Technologies -->
      <div v-if="project.technologies && project.technologies.length > 0" class="mb-4">
        <div class="flex flex-wrap gap-1">
          <Tag
            v-for="tech in project.technologies.slice(0, 4)"
            :key="tech.id"
            :value="tech.name"
            severity="secondary"
            class="text-xs"
          />
          <Tag
            v-if="project.technologies.length > 4"
            :value="`+${project.technologies.length - 4}`"
            severity="secondary"
            class="text-xs"
          />
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex gap-2 mt-auto">
        <Button
          label="View Details"
          size="small"
          @click="$router.push({ name: 'project', params: { slug: project.slug } })"
        />
        
        <Button
          v-if="project.demo_url"
          icon="pi pi-external-link"
          severity="secondary"
          size="small"
          @click="openUrl(project.demo_url)"
          v-tooltip="'Live Demo'"
        />
        
        <Button
          v-if="project.repository_url"
          icon="pi pi-github"
          severity="secondary"
          size="small"
          @click="openUrl(project.repository_url)"
          v-tooltip="'Source Code'"
        />
      </div>
    </template>
  </Card>
</template>

<script setup lang="ts">
import { type Project } from '../services/api'

// PrimeVue Components
import Card from 'primevue/card'
import Badge from 'primevue/badge'
import Tag from 'primevue/tag'
import Button from 'primevue/button'

// Custom Components
import OptimizedImage from './OptimizedImage.vue'

interface Props {
  project: Project
}

defineProps<Props>()

// Helper functions
function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short'
  })
}

function openUrl(url: string): void {
  window.open(url, '_blank', 'noopener,noreferrer')
}
</script>

<style scoped>
.project-card {
  display: flex;
  flex-direction: column;
}

.project-card :deep(.p-card-body) {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.project-card :deep(.p-card-content) {
  flex: 1;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>