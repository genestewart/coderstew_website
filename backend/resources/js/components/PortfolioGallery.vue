<template>
  <div class="portfolio-gallery">
    <!-- Header Section -->
    <div class="mb-8">
      <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Portfolio</h2>
      <p class="text-lg text-gray-600 dark:text-gray-300">
        Explore our latest projects and technical achievements
      </p>
    </div>

    <!-- Search and Filters -->
    <div class="mb-8">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Search Input -->
        <div class="lg:col-span-2">
          <IconField iconPosition="left">
            <InputIcon class="pi pi-search" />
            <InputText
              v-model="searchTerm"
              placeholder="Search projects..."
              class="w-full"
              @input="handleSearchInput"
            />
          </IconField>
        </div>

        <!-- Category Filter -->
        <Dropdown
          v-model="selectedCategory"
          :options="categoryOptions"
          option-label="label"
          option-value="value"
          placeholder="All Categories"
          class="w-full"
          showClear
          @change="handleCategoryChange"
        />

        <!-- Technology Filter -->
        <Dropdown
          v-model="selectedTechnology"
          :options="technologyOptions"
          option-label="label"
          option-value="value"
          placeholder="All Technologies"
          class="w-full"
          showClear
          @change="handleTechnologyChange"
        />
      </div>

      <!-- Filter Tags and Clear -->
      <div class="flex flex-wrap items-center gap-2 mb-4">
        <Chip
          v-if="portfolioStore.filters.featured"
          label="Featured"
          removable
          @remove="portfolioStore.setFeatured(undefined)"
        />
        <Chip
          v-if="selectedCategoryName"
          :label="`Category: ${selectedCategoryName}`"
          removable
          @remove="handleCategoryChange(null)"
        />
        <Chip
          v-if="selectedTechnologyName"
          :label="`Technology: ${selectedTechnologyName}`"
          removable
          @remove="handleTechnologyChange(null)"
        />
        <Button
          v-if="hasActiveFilters"
          label="Clear All"
          severity="secondary"
          size="small"
          @click="portfolioStore.clearFilters"
        />
      </div>

      <!-- Results Count and View Options -->
      <div class="flex justify-between items-center">
        <div class="text-sm text-gray-600 dark:text-gray-400">
          Showing {{ portfolioStore.paginationInfo.from }}-{{ portfolioStore.paginationInfo.to }} 
          of {{ portfolioStore.paginationInfo.total }} projects
        </div>
        <div class="flex items-center gap-2">
          <Button
            :severity="viewMode === 'grid' ? 'primary' : 'secondary'"
            size="small"
            @click="viewMode = 'grid'"
          >
            <i class="pi pi-th-large"></i>
          </Button>
          <Button
            :severity="viewMode === 'list' ? 'primary' : 'secondary'"
            size="small"
            @click="viewMode = 'list'"
          >
            <i class="pi pi-list"></i>
          </Button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="portfolioStore.loading">
      <!-- Grid View Skeletons -->
      <div
        v-if="viewMode === 'grid'"
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"
      >
        <ProjectCardSkeleton
          v-for="i in portfolioStore.perPage"
          :key="i"
        />
      </div>

      <!-- List View Skeletons -->
      <div v-else class="space-y-6">
        <ProjectListSkeleton
          v-for="i in portfolioStore.perPage"
          :key="i"
        />
      </div>
    </div>

    <!-- Error State -->
    <Message v-else-if="portfolioStore.error" severity="error" class="mb-6">
      {{ portfolioStore.error }}
    </Message>

    <!-- Empty State -->
    <div v-else-if="portfolioStore.projects.length === 0" class="text-center py-12">
      <i class="pi pi-search text-4xl text-gray-400 mb-4"></i>
      <h3 class="text-xl font-semibold text-gray-600 dark:text-gray-400 mb-2">
        No projects found
      </h3>
      <p class="text-gray-500 dark:text-gray-500 mb-4">
        Try adjusting your search criteria or filters
      </p>
      <Button
        label="Clear Filters"
        severity="secondary"
        @click="portfolioStore.clearFilters"
      />
    </div>

    <!-- Projects Grid/List -->
    <div v-else>
      <!-- Grid View -->
      <div
        v-if="viewMode === 'grid'"
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"
      >
        <ProjectCard
          v-for="project in portfolioStore.projects"
          :key="project.id"
          :project="project"
        />
      </div>

      <!-- List View -->
      <div v-else class="space-y-6">
        <ProjectListItem
          v-for="project in portfolioStore.projects"
          :key="project.id"
          :project="project"
        />
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="portfolioStore.lastPage > 1" class="mt-8 flex justify-center">
      <Paginator
        v-model:first="paginatorFirst"
        :rows="portfolioStore.perPage"
        :total-records="portfolioStore.total"
        :rows-per-page-options="[6, 12, 24, 48]"
        template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
        @page="handlePageChange"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { usePortfolioStore } from '../stores/portfolio'
import { debounce } from 'lodash-es'

// PrimeVue Components
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Dropdown from 'primevue/dropdown'
import Button from 'primevue/button'
import Chip from 'primevue/chip'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'
import Paginator from 'primevue/paginator'

// Components
import ProjectCard from './ProjectCard.vue'
import ProjectListItem from './ProjectListItem.vue'
import ProjectCardSkeleton from './ProjectCardSkeleton.vue'
import ProjectListSkeleton from './ProjectListSkeleton.vue'

const portfolioStore = usePortfolioStore()

// Local state
const searchTerm = ref('')
const selectedCategory = ref<number | undefined>()
const selectedTechnology = ref<number | undefined>()
const viewMode = ref<'grid' | 'list'>('grid')

// Computed properties
const categoryOptions = computed(() => [
  ...portfolioStore.categories.map(cat => ({
    label: cat.name,
    value: cat.id
  }))
])

const technologyOptions = computed(() => [
  ...portfolioStore.technologies.map(tech => ({
    label: tech.name,
    value: tech.id
  }))
])

const selectedCategoryName = computed(() => {
  const category = portfolioStore.categories.find(cat => cat.id === selectedCategory.value)
  return category?.name
})

const selectedTechnologyName = computed(() => {
  const technology = portfolioStore.technologies.find(tech => tech.id === selectedTechnology.value)
  return technology?.name
})

const hasActiveFilters = computed(() => {
  return searchTerm.value || 
         selectedCategory.value || 
         selectedTechnology.value || 
         portfolioStore.filters.featured
})

const paginatorFirst = computed({
  get: () => (portfolioStore.currentPage - 1) * portfolioStore.perPage,
  set: () => {} // Handled by handlePageChange
})

// Debounced search handler
const debouncedSearch = debounce((value: string) => {
  portfolioStore.setSearch(value)
}, 300)

// Event handlers
function handleSearchInput() {
  debouncedSearch(searchTerm.value)
}

function handleCategoryChange(value: number | null) {
  selectedCategory.value = value || undefined
  portfolioStore.setCategory(selectedCategory.value)
}

function handleTechnologyChange(value: number | null) {
  selectedTechnology.value = value || undefined
  portfolioStore.setTechnology(selectedTechnology.value)
}

function handlePageChange(event: any) {
  const page = Math.floor(event.first / event.rows) + 1
  portfolioStore.goToPage(page)
  
  // Update per page if changed
  if (event.rows !== portfolioStore.perPage) {
    portfolioStore.setPerPage(event.rows)
  }
}

// Watchers
watch(() => portfolioStore.filters.search, (newValue) => {
  if (newValue !== searchTerm.value) {
    searchTerm.value = newValue
  }
})

watch(() => portfolioStore.filters.category, (newValue) => {
  selectedCategory.value = newValue
})

watch(() => portfolioStore.filters.technology, (newValue) => {
  selectedTechnology.value = newValue
})

// Lifecycle
onMounted(async () => {
  await portfolioStore.loadFilters()
  await portfolioStore.loadProjects()
})
</script>

<style scoped>
.portfolio-gallery {
  max-width: 80rem;
  margin: 0 auto;
  padding: 2rem 1rem;
}

@media (min-width: 640px) {
  .portfolio-gallery {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
  }
}

@media (min-width: 1024px) {
  .portfolio-gallery {
    padding-left: 2rem;
    padding-right: 2rem;
  }
}
</style>