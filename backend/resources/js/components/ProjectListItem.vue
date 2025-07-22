<template>
  <Card class="project-list-item">
    <template #content>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Project Image -->
        <div class="md:col-span-1">
          <div class="relative overflow-hidden rounded-lg">
            <img
              v-if="project.featured_image"
              :src="project.featured_image"
              :alt="project.title"
              class="w-full h-32 object-cover"
              loading="lazy"
            />
            <div
              v-else
              class="w-full h-32 bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center rounded-lg"
            >
              <i class="pi pi-image text-2xl text-white opacity-50"></i>
            </div>
            
            <!-- Featured Badge -->
            <Badge
              v-if="project.is_featured"
              value="Featured"
              severity="success"
              class="absolute top-2 right-2"
            />
          </div>
        </div>

        <!-- Project Details -->
        <div class="md:col-span-2">
          <div class="flex items-start justify-between mb-2">
            <router-link
              :to="{ name: 'project', params: { slug: project.slug } }"
              class="text-xl font-semibold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
            >
              {{ project.title }}
            </router-link>
            
            <Badge
              v-if="project.category"
              :value="project.category.name"
              severity="info"
            />
          </div>

          <div class="text-sm text-gray-500 dark:text-gray-400 mb-3">
            <span v-if="project.client_name">{{ project.client_name }} • </span>
            <span v-if="project.completion_date">
              {{ formatDate(project.completion_date) }}
            </span>
          </div>

          <p class="text-gray-600 dark:text-gray-300 mb-4 line-clamp-2">
            {{ project.excerpt }}
          </p>

          <!-- Technologies -->
          <div v-if="project.technologies && project.technologies.length > 0">
            <div class="flex flex-wrap gap-1">
              <Tag
                v-for="tech in project.technologies"
                :key="tech.id"
                :value="tech.name"
                severity="secondary"
                class="text-xs"
              />
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="md:col-span-1 flex flex-col justify-center gap-2">
          <Button
            label="View Details"
            size="small"
            class="w-full"
            @click="$router.push({ name: 'project', params: { slug: project.slug } })"
          />
          
          <div class="flex gap-2">
            <Button
              v-if="project.demo_url"
              icon="pi pi-external-link"
              label="Demo"
              severity="secondary"
              size="small"
              class="flex-1"
              @click="openUrl(project.demo_url)"
            />
            
            <Button
              v-if="project.repository_url"
              icon="pi pi-github"
              label="Code"
              severity="secondary"
              size="small"
              class="flex-1"
              @click="openUrl(project.repository_url)"
            />
          </div>

          <div v-if="project.project_url" class="mt-2">
            <Button
              icon="pi pi-globe"
              label="Website"
              severity="help"
              size="small"
              class="w-full"
              @click="openUrl(project.project_url)"
            />
          </div>
        </div>
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

interface Props {
  project: Project
}

defineProps<Props>()

// Helper functions
function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

function openUrl(url: string): void {
  window.open(url, '_blank', 'noopener,noreferrer')
}
</script>

<style scoped>
.project-list-item {
  transition: box-shadow 0.3s ease;
}

.project-list-item:hover {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>