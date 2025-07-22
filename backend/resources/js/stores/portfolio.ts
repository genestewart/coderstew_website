import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { apiService, type Project, type ProjectCategory, type Technology, type ProjectFilters } from '../services/api'

export const usePortfolioStore = defineStore('portfolio', () => {
  // State
  const projects = ref<Project[]>([])
  const categories = ref<ProjectCategory[]>([])
  const technologies = ref<Technology[]>([])
  const currentPage = ref(1)
  const lastPage = ref(1)
  const perPage = ref(12)
  const total = ref(0)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Filters
  const filters = ref<ProjectFilters>({
    search: '',
    category: undefined,
    technology: undefined,
    featured: undefined,
    per_page: 12,
    page: 1
  })

  // Getters
  const featuredProjects = computed(() => 
    projects.value.filter(project => project.is_featured)
  )

  const hasNextPage = computed(() => currentPage.value < lastPage.value)
  const hasPreviousPage = computed(() => currentPage.value > 1)

  const paginationInfo = computed(() => ({
    from: ((currentPage.value - 1) * perPage.value) + 1,
    to: Math.min(currentPage.value * perPage.value, total.value),
    total: total.value,
    currentPage: currentPage.value,
    lastPage: lastPage.value
  }))

  // Actions
  async function loadProjects(resetPage = false) {
    if (resetPage) {
      filters.value.page = 1
      currentPage.value = 1
    }

    loading.value = true
    error.value = null

    try {
      const queryFilters = {
        ...filters.value,
        page: currentPage.value
      }

      const response = await apiService.getProjects(queryFilters)
      
      projects.value = response.data
      currentPage.value = response.current_page
      lastPage.value = response.last_page
      perPage.value = response.per_page
      total.value = response.total
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to load projects'
      console.error('Error loading projects:', err)
    } finally {
      loading.value = false
    }
  }

  async function loadFilters() {
    try {
      const response = await apiService.getProjectFilters()
      categories.value = response.categories
      technologies.value = response.technologies
    } catch (err) {
      console.error('Error loading filters:', err)
    }
  }

  async function loadProject(slug: string): Promise<Project | null> {
    loading.value = true
    error.value = null

    try {
      const project = await apiService.getProject(slug)
      return project
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to load project'
      console.error('Error loading project:', err)
      return null
    } finally {
      loading.value = false
    }
  }

  async function getAdjacentProjects(currentSlug: string): Promise<{
    previous: Project | null
    next: Project | null
  }> {
    try {
      // If we don't have projects loaded, load them with minimal filters
      if (projects.value.length === 0) {
        await loadProjects()
      }

      const currentIndex = projects.value.findIndex(p => p.slug === currentSlug)
      
      if (currentIndex === -1) {
        return { previous: null, next: null }
      }

      return {
        previous: currentIndex > 0 ? projects.value[currentIndex - 1] : null,
        next: currentIndex < projects.value.length - 1 ? projects.value[currentIndex + 1] : null
      }
    } catch (err) {
      console.error('Error getting adjacent projects:', err)
      return { previous: null, next: null }
    }
  }

  function setSearch(search: string) {
    filters.value.search = search
    loadProjects(true)
  }

  function setCategory(categoryId: number | undefined) {
    filters.value.category = categoryId
    loadProjects(true)
  }

  function setTechnology(technologyId: number | undefined) {
    filters.value.technology = technologyId
    loadProjects(true)
  }

  function setFeatured(featured: boolean | undefined) {
    filters.value.featured = featured
    loadProjects(true)
  }

  function setPerPage(newPerPage: number) {
    filters.value.per_page = newPerPage
    perPage.value = newPerPage
    loadProjects(true)
  }

  function goToPage(page: number) {
    if (page >= 1 && page <= lastPage.value) {
      currentPage.value = page
      filters.value.page = page
      loadProjects()
    }
  }

  function nextPage() {
    if (hasNextPage.value) {
      goToPage(currentPage.value + 1)
    }
  }

  function previousPage() {
    if (hasPreviousPage.value) {
      goToPage(currentPage.value - 1)
    }
  }

  function clearFilters() {
    filters.value = {
      search: '',
      category: undefined,
      technology: undefined,
      featured: undefined,
      per_page: 12,
      page: 1
    }
    loadProjects(true)
  }

  function reset() {
    projects.value = []
    categories.value = []
    technologies.value = []
    currentPage.value = 1
    lastPage.value = 1
    perPage.value = 12
    total.value = 0
    loading.value = false
    error.value = null
    filters.value = {
      search: '',
      category: undefined,
      technology: undefined,
      featured: undefined,
      per_page: 12,
      page: 1
    }
  }

  return {
    // State
    projects,
    categories,
    technologies,
    currentPage,
    lastPage,
    perPage,
    total,
    loading,
    error,
    filters,

    // Getters
    featuredProjects,
    hasNextPage,
    hasPreviousPage,
    paginationInfo,

    // Actions
    loadProjects,
    loadFilters,
    loadProject,
    getAdjacentProjects,
    setSearch,
    setCategory,
    setTechnology,
    setFeatured,
    setPerPage,
    goToPage,
    nextPage,
    previousPage,
    clearFilters,
    reset
  }
})