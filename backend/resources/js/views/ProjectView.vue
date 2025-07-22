<template>
  <MainLayout>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-12">
        <ProgressSpinner />
      </div>

      <!-- Error State -->
      <Message v-else-if="error" severity="error" class="mb-6">
        {{ error }}
      </Message>

      <!-- Project Details -->
      <div v-else-if="project">
        <!-- Back Button -->
        <Button
          icon="pi pi-arrow-left"
          label="Back to Portfolio"
          severity="secondary"
          class="mb-6"
          @click="$router.push({ name: 'portfolio' })"
        />

        <!-- Project Header -->
        <div class="mb-8">
          <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
            <div>
              <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
                {{ project.title }}
              </h1>
              <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                <span v-if="project.client_name">
                  <i class="pi pi-user mr-1"></i>
                  {{ project.client_name }}
                </span>
                <span v-if="project.completion_date">
                  <i class="pi pi-calendar mr-1"></i>
                  {{ formatDate(project.completion_date) }}
                </span>
                <Badge
                  v-if="project.category"
                  :value="project.category.name"
                  severity="info"
                />
                <Badge
                  v-if="project.is_featured"
                  value="Featured"
                  severity="success"
                />
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-2">
              <Button
                v-if="project.demo_url"
                icon="pi pi-external-link"
                label="Live Demo"
                @click="openUrl(project.demo_url)"
              />
              <Button
                v-if="project.repository_url"
                icon="pi pi-github"
                label="Source Code"
                severity="secondary"
                @click="openUrl(project.repository_url)"
              />
              <Button
                v-if="project.project_url"
                icon="pi pi-globe"
                label="Website"
                severity="help"
                @click="openUrl(project.project_url)"
              />
            </div>
          </div>

          <!-- Technologies -->
          <div v-if="project.technologies && project.technologies.length > 0" class="mb-6">
            <h3 class="text-lg font-semibold mb-2">Technologies Used</h3>
            <div class="flex flex-wrap gap-2">
              <Tag
                v-for="tech in project.technologies"
                :key="tech.id"
                :value="tech.name"
                :severity="getTechSeverity(tech.type)"
              />
            </div>
          </div>
        </div>

        <!-- Featured Image -->
        <div v-if="project.featured_image_responsive || project.featured_image" class="mb-8">
          <OptimizedImage
            v-if="project.featured_image_responsive && Object.keys(project.featured_image_responsive).length > 0"
            :responsive-sources="project.featured_image_responsive"
            :alt="project.title"
            width="100%"
            height="384px"
            size="large"
            class="w-full h-64 md:h-96 object-cover rounded-lg shadow-lg"
            :show-skeleton="true"
            :enable-blur="true"
            @click="openLightbox(0, 'featured')"
          />
          <img
            v-else-if="project.featured_image"
            :src="project.featured_image"
            :alt="project.title"
            class="w-full h-64 md:h-96 object-cover rounded-lg shadow-lg cursor-pointer"
            @click="openLightbox(0, 'featured')"
          />
        </div>

        <!-- Project Description -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div class="lg:col-span-2">
            <Card>
              <template #title>Project Overview</template>
              <template #content>
                <div
                  v-if="project.description"
                  class="prose dark:prose-invert max-w-none"
                  v-html="project.description"
                ></div>
                <p v-else class="text-gray-600 dark:text-gray-300">
                  {{ project.excerpt }}
                </p>
              </template>
            </Card>

            <!-- Gallery -->
            <Card v-if="hasGalleryImages" class="mt-6">
              <template #title>Project Gallery</template>
              <template #content>
                <!-- Optimized Gallery Grid -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                  <div
                    v-for="(image, index) in galleryImages"
                    :key="index"
                    class="aspect-square cursor-pointer rounded-lg overflow-hidden hover:shadow-lg transition-shadow duration-300"
                    @click="openLightbox(index, 'gallery')"
                  >
                    <OptimizedImage
                      v-if="image.responsive"
                      :responsive-sources="image.responsive"
                      :alt="`${project.title} gallery image ${index + 1}`"
                      width="100%"
                      height="100%"
                      size="medium"
                      class="w-full h-full object-cover"
                      :show-skeleton="true"
                      :enable-blur="true"
                    />
                    <img
                      v-else
                      :src="image.url"
                      :alt="`${project.title} gallery image ${index + 1}`"
                      class="w-full h-full object-cover"
                      loading="lazy"
                    />
                  </div>
                </div>
              </template>
            </Card>

            <!-- Video -->
            <Card v-if="project.video_url" class="mt-6">
              <template #title>Project Demo</template>
              <template #content>
                <div class="aspect-video">
                  <iframe
                    :src="getEmbedUrl(project.video_url)"
                    class="w-full h-full rounded-lg"
                    frameborder="0"
                    allowfullscreen
                  ></iframe>
                </div>
              </template>
            </Card>
          </div>

          <!-- Sidebar -->
          <div class="lg:col-span-1">
            <Card>
              <template #title>Project Details</template>
              <template #content>
                <div class="space-y-4">
                  <div v-if="project.client_name">
                    <h4 class="font-semibold text-gray-900 dark:text-white">Client</h4>
                    <p class="text-gray-600 dark:text-gray-300">{{ project.client_name }}</p>
                  </div>

                  <div v-if="project.project_date">
                    <h4 class="font-semibold text-gray-900 dark:text-white">Start Date</h4>
                    <p class="text-gray-600 dark:text-gray-300">{{ formatDate(project.project_date) }}</p>
                  </div>

                  <div v-if="project.completion_date">
                    <h4 class="font-semibold text-gray-900 dark:text-white">Completion Date</h4>
                    <p class="text-gray-600 dark:text-gray-300">{{ formatDate(project.completion_date) }}</p>
                  </div>

                  <div v-if="project.category">
                    <h4 class="font-semibold text-gray-900 dark:text-white">Category</h4>
                    <p class="text-gray-600 dark:text-gray-300">{{ project.category.full_path }}</p>
                  </div>

                  <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white">Status</h4>
                    <Badge
                      :value="project.status"
                      :severity="project.status === 'published' ? 'success' : 'warning'"
                    />
                  </div>
                </div>
              </template>
            </Card>

            <!-- Share Section -->
            <Card class="mt-6">
              <template #title>Share Project</template>
              <template #content>
                <div class="space-y-3">
                  <div class="grid grid-cols-2 gap-2">
                    <Button
                      icon="pi pi-twitter"
                      label="Twitter"
                      severity="info"
                      size="small"
                      class="w-full"
                      @click="shareOnTwitter"
                    />
                    <Button
                      icon="pi pi-linkedin"
                      label="LinkedIn"
                      severity="info"
                      size="small"
                      class="w-full"
                      @click="shareOnLinkedIn"
                    />
                    <Button
                      icon="pi pi-facebook"
                      label="Facebook"
                      severity="info"
                      size="small"
                      class="w-full"
                      @click="shareOnFacebook"
                    />
                    <Button
                      icon="pi pi-reddit"
                      label="Reddit"
                      severity="secondary"
                      size="small"
                      class="w-full"
                      @click="shareOnReddit"
                    />
                  </div>
                  
                  <div class="border-t pt-3">
                    <Button
                      icon="pi pi-copy"
                      label="Copy URL"
                      severity="secondary"
                      size="small"
                      class="w-full"
                      @click="copyUrl"
                    />
                  </div>

                  <div v-if="urlCopied" class="text-center text-sm text-green-600 dark:text-green-400">
                    URL copied to clipboard!
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </div>

        <!-- Project Navigation -->
        <div v-if="adjacentProjects.previous || adjacentProjects.next" class="mt-12 border-t pt-8">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Previous Project -->
            <div v-if="adjacentProjects.previous" class="text-left">
              <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Previous Project</p>
              <router-link
                :to="{ name: 'project', params: { slug: adjacentProjects.previous.slug } }"
                class="group block"
              >
                <div class="flex items-center space-x-4 p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:shadow-md transition-all duration-300">
                  <i class="pi pi-chevron-left text-gray-400 group-hover:text-blue-600"></i>
                  <div class="flex-1">
                    <div class="flex items-start space-x-3">
                      <OptimizedImage
                        v-if="adjacentProjects.previous.featured_image_responsive"
                        :responsive-sources="adjacentProjects.previous.featured_image_responsive"
                        :alt="adjacentProjects.previous.title"
                        width="60px"
                        height="60px"
                        size="thumbnail"
                        class="w-15 h-15 object-cover rounded"
                      />
                      <img
                        v-else-if="adjacentProjects.previous.featured_image"
                        :src="adjacentProjects.previous.featured_image"
                        :alt="adjacentProjects.previous.title"
                        class="w-15 h-15 object-cover rounded"
                      />
                      <div class="flex-1">
                        <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 transition-colors">
                          {{ adjacentProjects.previous.title }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                          {{ adjacentProjects.previous.excerpt }}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </router-link>
            </div>

            <!-- Next Project -->
            <div v-if="adjacentProjects.next" class="text-right">
              <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Next Project</p>
              <router-link
                :to="{ name: 'project', params: { slug: adjacentProjects.next.slug } }"
                class="group block"
              >
                <div class="flex items-center space-x-4 p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:shadow-md transition-all duration-300">
                  <div class="flex-1">
                    <div class="flex items-start space-x-3">
                      <div class="flex-1">
                        <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 transition-colors">
                          {{ adjacentProjects.next.title }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                          {{ adjacentProjects.next.excerpt }}
                        </p>
                      </div>
                      <OptimizedImage
                        v-if="adjacentProjects.next.featured_image_responsive"
                        :responsive-sources="adjacentProjects.next.featured_image_responsive"
                        :alt="adjacentProjects.next.title"
                        width="60px"
                        height="60px"
                        size="thumbnail"
                        class="w-15 h-15 object-cover rounded"
                      />
                      <img
                        v-else-if="adjacentProjects.next.featured_image"
                        :src="adjacentProjects.next.featured_image"
                        :alt="adjacentProjects.next.title"
                        class="w-15 h-15 object-cover rounded"
                      />
                    </div>
                  </div>
                  <i class="pi pi-chevron-right text-gray-400 group-hover:text-blue-600"></i>
                </div>
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Image Lightbox -->
    <div
      v-if="lightboxVisible"
      class="fixed inset-0 z-50 bg-black bg-opacity-90 flex items-center justify-center"
      @click="closeLightbox"
      @keydown.esc="closeLightbox"
    >
      <div class="relative max-w-7xl max-h-full mx-4" @click.stop>
        <!-- Close Button -->
        <button
          @click="closeLightbox"
          class="absolute top-4 right-4 z-10 text-white hover:text-gray-300 text-2xl"
        >
          <i class="pi pi-times"></i>
        </button>

        <!-- Navigation Buttons -->
        <button
          v-if="lightboxIndex > 0"
          @click="prevLightboxImage"
          class="absolute left-4 top-1/2 transform -translate-y-1/2 z-10 text-white hover:text-gray-300 text-3xl"
        >
          <i class="pi pi-chevron-left"></i>
        </button>

        <button
          v-if="lightboxIndex < lightboxImages.length - 1"
          @click="nextLightboxImage"
          class="absolute right-4 top-1/2 transform -translate-y-1/2 z-10 text-white hover:text-gray-300 text-3xl"
        >
          <i class="pi pi-chevron-right"></i>
        </button>

        <!-- Image -->
        <img
          v-if="lightboxImages[lightboxIndex]"
          :src="lightboxImages[lightboxIndex]"
          :alt="`${project?.title} image ${lightboxIndex + 1}`"
          class="max-w-full max-h-full object-contain"
        />

        <!-- Image Counter -->
        <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 text-white text-sm">
          {{ lightboxIndex + 1 }} of {{ lightboxImages.length }}
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { usePortfolioStore } from '../stores/portfolio'
import { type Project } from '../services/api'
import { useSEO } from '../composables/useSEO'

// PrimeVue Components
import Button from 'primevue/button'
import Card from 'primevue/card'
import Badge from 'primevue/badge'
import Tag from 'primevue/tag'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'
import Galleria from 'primevue/galleria'

// Components
import MainLayout from '../components/layouts/MainLayout.vue'
import OptimizedImage from '../components/OptimizedImage.vue'

const route = useRoute()
const portfolioStore = usePortfolioStore()
const { setSEO, generateProjectSEO, cleanupSEO } = useSEO()

// State
const project = ref<Project | null>(null)
const loading = ref(false)
const error = ref<string | null>(null)
const lightboxVisible = ref(false)
const lightboxImages = ref<string[]>([])
const lightboxIndex = ref(0)
const adjacentProjects = ref<{ previous: Project | null, next: Project | null }>({
  previous: null,
  next: null
})
const urlCopied = ref(false)

// Computed properties for gallery
const hasGalleryImages = computed(() => {
  return (project.value?.gallery_images_responsive && project.value.gallery_images_responsive.length > 0) ||
         (project.value?.gallery_images && project.value.gallery_images.length > 0)
})

const galleryImages = computed(() => {
  if (!project.value) return []
  
  const images: Array<{ responsive?: any, url: string }> = []
  
  // Use responsive images if available
  if (project.value.gallery_images_responsive && project.value.gallery_images_responsive.length > 0) {
    project.value.gallery_images_responsive.forEach((responsive, index) => {
      images.push({
        responsive,
        url: project.value!.gallery_images?.[index] || ''
      })
    })
  } else if (project.value.gallery_images && project.value.gallery_images.length > 0) {
    // Fallback to legacy images
    project.value.gallery_images.forEach(url => {
      images.push({ url })
    })
  }
  
  return images
})

const allLightboxImages = computed(() => {
  const images: string[] = []
  
  // Add featured image first
  if (project.value?.featured_image_responsive) {
    const featured = project.value.featured_image_responsive.large || 
                    project.value.featured_image_responsive.original ||
                    project.value.featured_image_responsive.medium
    if (featured?.fallback || featured?.default) {
      images.push(featured.fallback || featured.default)
    }
  } else if (project.value?.featured_image) {
    images.push(project.value.featured_image)
  }
  
  // Add gallery images
  galleryImages.value.forEach(image => {
    if (image.responsive) {
      const large = image.responsive.large || image.responsive.original || image.responsive.medium
      if (large?.fallback || large?.default) {
        images.push(large.fallback || large.default)
      }
    } else {
      images.push(image.url)
    }
  })
  
  return images
})

// Helper functions
function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

function openUrl(url: string): void {
  window.open(url, '_blank', 'noopener,noreferrer')
}

function getTechSeverity(type: string): string {
  const severityMap: Record<string, string> = {
    'language': 'success',
    'framework': 'info',
    'library': 'warning',
    'tool': 'secondary',
    'database': 'danger',
    'service': 'help'
  }
  return severityMap[type] || 'secondary'
}

function getEmbedUrl(url: string): string {
  // Convert YouTube URLs to embed format
  if (url.includes('youtube.com/watch')) {
    const videoId = url.split('v=')[1]?.split('&')[0]
    return `https://www.youtube.com/embed/${videoId}`
  }
  if (url.includes('youtu.be/')) {
    const videoId = url.split('youtu.be/')[1]?.split('?')[0]
    return `https://www.youtube.com/embed/${videoId}`
  }
  // Convert Vimeo URLs to embed format
  if (url.includes('vimeo.com/')) {
    const videoId = url.split('vimeo.com/')[1]
    return `https://player.vimeo.com/video/${videoId}`
  }
  return url
}

// Enhanced social sharing functions
async function shareOnTwitter() {
  const text = `Check out this project: ${project.value?.title} - ${project.value?.excerpt || ''}`
  const url = window.location.href
  const hashtags = project.value?.technologies?.slice(0, 3).map(t => t.name.replace(/[^a-zA-Z0-9]/g, '')).join(',') || ''
  
  const twitterUrl = new URL('https://twitter.com/intent/tweet')
  twitterUrl.searchParams.set('text', text)
  twitterUrl.searchParams.set('url', url)
  if (hashtags) {
    twitterUrl.searchParams.set('hashtags', hashtags)
  }
  
  window.open(twitterUrl.toString(), '_blank', 'width=600,height=400')
}

async function shareOnLinkedIn() {
  const url = window.location.href
  const linkedInUrl = new URL('https://www.linkedin.com/sharing/share-offsite/')
  linkedInUrl.searchParams.set('url', url)
  
  window.open(linkedInUrl.toString(), '_blank', 'width=600,height=400')
}

async function shareOnFacebook() {
  const url = window.location.href
  const facebookUrl = new URL('https://www.facebook.com/sharer/sharer.php')
  facebookUrl.searchParams.set('u', url)
  
  window.open(facebookUrl.toString(), '_blank', 'width=600,height=400')
}

async function shareOnReddit() {
  const title = `${project.value?.title} - ${project.value?.excerpt || ''}`
  const url = window.location.href
  
  const redditUrl = new URL('https://reddit.com/submit')
  redditUrl.searchParams.set('url', url)
  redditUrl.searchParams.set('title', title)
  
  window.open(redditUrl.toString(), '_blank', 'width=600,height=400')
}

async function copyUrl() {
  try {
    await navigator.clipboard.writeText(window.location.href)
    urlCopied.value = true
    
    // Hide the message after 3 seconds
    setTimeout(() => {
      urlCopied.value = false
    }, 3000)
  } catch (err) {
    console.error('Failed to copy URL:', err)
    // Fallback for browsers that don't support clipboard API
    try {
      const textArea = document.createElement('textarea')
      textArea.value = window.location.href
      document.body.appendChild(textArea)
      textArea.focus()
      textArea.select()
      document.execCommand('copy')
      document.body.removeChild(textArea)
      
      urlCopied.value = true
      setTimeout(() => {
        urlCopied.value = false
      }, 3000)
    } catch (fallbackErr) {
      console.error('Fallback copy failed:', fallbackErr)
    }
  }
}

// Lightbox functionality
function openLightbox(index: number, type: 'featured' | 'gallery') {
  lightboxImages.value = allLightboxImages.value
  
  if (type === 'featured') {
    lightboxIndex.value = 0
  } else {
    // Adjust index to account for featured image
    lightboxIndex.value = project.value?.featured_image || project.value?.featured_image_responsive ? index + 1 : index
  }
  
  lightboxVisible.value = true
}

function closeLightbox() {
  lightboxVisible.value = false
}

function nextLightboxImage() {
  if (lightboxIndex.value < lightboxImages.value.length - 1) {
    lightboxIndex.value++
  }
}

function prevLightboxImage() {
  if (lightboxIndex.value > 0) {
    lightboxIndex.value--
  }
}

// Keyboard support for lightbox
function handleKeydown(event: KeyboardEvent) {
  if (!lightboxVisible.value) return
  
  switch (event.key) {
    case 'Escape':
      closeLightbox()
      break
    case 'ArrowLeft':
      prevLightboxImage()
      break
    case 'ArrowRight':
      nextLightboxImage()
      break
  }
}

// Lifecycle
onMounted(async () => {
  const slug = route.params.slug as string
  if (slug) {
    loading.value = true
    error.value = null
    
    try {
      project.value = await portfolioStore.loadProject(slug)
      if (!project.value) {
        error.value = 'Project not found'
      } else {
        // Load adjacent projects for navigation
        adjacentProjects.value = await portfolioStore.getAdjacentProjects(slug)
        
        // Set SEO meta tags for the project
        setSEO(generateProjectSEO(project.value))
      }
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to load project'
    } finally {
      loading.value = false
    }
  }

  // Add keyboard event listener
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
  cleanupSEO()
})
</script>

<style scoped>
/* Custom styles for prose content */
.prose :deep(h1),
.prose :deep(h2),
.prose :deep(h3),
.prose :deep(h4),
.prose :deep(h5),
.prose :deep(h6) {
  color: #1f2937;
}

.prose :deep(p) {
  color: #6b7280;
}

.prose :deep(a) {
  color: #2563eb;
  text-decoration: underline;
}

.prose :deep(code) {
  background-color: #f3f4f6;
  border-radius: 0.25rem;
  padding: 0.125rem 0.25rem;
}

.prose :deep(pre) {
  background-color: #f3f4f6;
  border-radius: 0.375rem;
  padding: 1rem;
  overflow-x: auto;
}

/* Dark mode styles */
@media (prefers-color-scheme: dark) {
  .prose :deep(h1),
  .prose :deep(h2),
  .prose :deep(h3),
  .prose :deep(h4),
  .prose :deep(h5),
  .prose :deep(h6) {
    color: #ffffff;
  }

  .prose :deep(p) {
    color: #d1d5db;
  }

  .prose :deep(a) {
    color: #60a5fa;
  }

  .prose :deep(code) {
    background-color: #1f2937;
  }

  .prose :deep(pre) {
    background-color: #1f2937;
  }
}
</style>