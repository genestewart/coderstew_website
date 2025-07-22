import { onMounted, onUnmounted } from 'vue'

export interface SEOData {
  title?: string
  description?: string
  keywords?: string
  image?: string
  url?: string
  type?: 'website' | 'article'
  siteName?: string
  author?: string
  publishedTime?: string
  modifiedTime?: string
}

export function useSEO() {
  const defaultSEO: SEOData = {
    title: 'CoderStew - Full-Stack Web Development Studio',
    description: 'Professional web development services specializing in Laravel, Vue.js, and modern JavaScript applications. Custom solutions for your business needs.',
    keywords: 'web development, Laravel, Vue.js, JavaScript, PHP, full-stack developer, custom software',
    type: 'website',
    siteName: 'CoderStew',
    author: 'CoderStew'
  }

  const createdElements: HTMLElement[] = []

  function setMetaTag(property: string, content: string, useProperty = false) {
    if (!content) return

    const selector = useProperty ? `meta[property="${property}"]` : `meta[name="${property}"]`
    let element = document.querySelector(selector) as HTMLMetaElement
    
    if (!element) {
      element = document.createElement('meta')
      if (useProperty) {
        element.setAttribute('property', property)
      } else {
        element.setAttribute('name', property)
      }
      document.head.appendChild(element)
      createdElements.push(element)
    }
    
    element.setAttribute('content', content)
  }

  function setTitle(title: string) {
    if (title) {
      document.title = title
    }
  }

  function setCanonicalUrl(url: string) {
    if (!url) return

    let element = document.querySelector('link[rel="canonical"]') as HTMLLinkElement
    
    if (!element) {
      element = document.createElement('link')
      element.setAttribute('rel', 'canonical')
      document.head.appendChild(element)
      createdElements.push(element)
    }
    
    element.setAttribute('href', url)
  }

  function setSEO(seoData: SEOData) {
    const data = { ...defaultSEO, ...seoData }
    const currentUrl = data.url || window.location.href

    // Basic meta tags
    setTitle(data.title!)
    setMetaTag('description', data.description!)
    setMetaTag('keywords', data.keywords!)
    setMetaTag('author', data.author!)

    // Open Graph tags
    setMetaTag('og:title', data.title!, true)
    setMetaTag('og:description', data.description!, true)
    setMetaTag('og:type', data.type!, true)
    setMetaTag('og:url', currentUrl, true)
    setMetaTag('og:site_name', data.siteName!, true)
    
    if (data.image) {
      setMetaTag('og:image', data.image, true)
      setMetaTag('og:image:alt', data.title!, true)
    }

    // Twitter Card tags
    setMetaTag('twitter:card', 'summary_large_image')
    setMetaTag('twitter:title', data.title!)
    setMetaTag('twitter:description', data.description!)
    
    if (data.image) {
      setMetaTag('twitter:image', data.image)
      setMetaTag('twitter:image:alt', data.title!)
    }

    // Article specific tags
    if (data.type === 'article') {
      if (data.publishedTime) {
        setMetaTag('article:published_time', data.publishedTime, true)
      }
      if (data.modifiedTime) {
        setMetaTag('article:modified_time', data.modifiedTime, true)
      }
      if (data.author) {
        setMetaTag('article:author', data.author, true)
      }
    }

    // Canonical URL
    setCanonicalUrl(currentUrl)
  }

  function generateProjectSEO(project: any): SEOData {
    const baseUrl = window.location.origin
    const projectUrl = `${baseUrl}/portfolio/${project.slug}`
    
    return {
      title: `${project.title} | CoderStew Portfolio`,
      description: project.meta_description || project.excerpt || `${project.title} - A project by CoderStew showcasing ${project.technologies?.slice(0, 3).map((t: any) => t.name).join(', ')} development.`,
      keywords: `${project.title}, ${project.technologies?.map((t: any) => t.name).join(', ')}, web development, portfolio`,
      image: project.featured_image_responsive?.large?.fallback || 
             project.featured_image_responsive?.medium?.fallback || 
             project.featured_image,
      url: projectUrl,
      type: 'article',
      publishedTime: project.created_at,
      modifiedTime: project.updated_at
    }
  }

  function generateBlogSEO(post: any): SEOData {
    const baseUrl = window.location.origin
    const postUrl = `${baseUrl}/blog/${post.slug}`
    
    return {
      title: `${post.title} | CoderStew Blog`,
      description: post.meta_description || post.excerpt || `${post.title} - A blog post by CoderStew.`,
      keywords: post.tags?.map((t: any) => t.name).join(', ') || 'web development, blog',
      image: post.featured_image_responsive?.large?.fallback || 
             post.featured_image_responsive?.medium?.fallback || 
             post.featured_image,
      url: postUrl,
      type: 'article',
      publishedTime: post.published_at,
      modifiedTime: post.updated_at
    }
  }

  function cleanupSEO() {
    // Remove any meta tags we created
    createdElements.forEach(element => {
      if (element.parentNode) {
        element.parentNode.removeChild(element)
      }
    })
    createdElements.length = 0
  }

  return {
    setSEO,
    generateProjectSEO,
    generateBlogSEO,
    cleanupSEO,
    defaultSEO
  }
}