import axios from 'axios'

export interface ResponsiveImageSource {
  webp?: string
  fallback?: string
  default: string
}

export interface ResponsiveImageSizes {
  thumbnail?: ResponsiveImageSource
  small?: ResponsiveImageSource
  medium?: ResponsiveImageSource
  large?: ResponsiveImageSource
  original?: ResponsiveImageSource
}

export interface Project {
  id: number
  title: string
  slug: string
  excerpt: string
  description: string
  featured_image: string | null
  featured_image_responsive?: ResponsiveImageSizes
  gallery_images: string[]
  gallery_images_responsive?: ResponsiveImageSizes[]
  project_url: string | null
  repository_url: string | null
  demo_url: string | null
  video_url: string | null
  client_name: string | null
  project_date: string | null
  completion_date: string | null
  is_featured: boolean
  status: 'draft' | 'published' | 'archived'
  meta_title: string | null
  meta_description: string | null
  created_at: string
  updated_at: string
  category?: {
    id: number
    name: string
    slug: string
    full_path: string
  }
  technologies?: Array<{
    id: number
    name: string
    type: string
    icon: string | null
    color: string | null
  }>
}

export interface ProjectCategory {
  id: number
  name: string
  slug: string
}

export interface Technology {
  id: number
  name: string
  type: string
}

export interface ProjectFilters {
  search?: string
  category?: number
  technology?: number
  featured?: boolean
  per_page?: number
  page?: number
}

export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

class ApiService {
  private baseURL = '/api'

  async getProjects(filters: ProjectFilters = {}): Promise<PaginatedResponse<Project>> {
    const params = new URLSearchParams()
    
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '') {
        params.append(key, value.toString())
      }
    })

    const response = await axios.get(`${this.baseURL}/projects?${params.toString()}`)
    return response.data
  }

  async getProject(slug: string): Promise<Project> {
    const response = await axios.get(`${this.baseURL}/projects/${slug}`)
    return response.data
  }

  async getProjectFilters(): Promise<{
    categories: ProjectCategory[]
    technologies: Technology[]
  }> {
    const response = await axios.get(`${this.baseURL}/projects-filters`)
    return response.data
  }
}

export const apiService = new ApiService()