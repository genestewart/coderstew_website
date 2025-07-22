import { ref, onMounted, onUnmounted, Ref } from 'vue'

export interface LazyImageOptions {
  rootMargin?: string
  threshold?: number
  retryDelay?: number
  maxRetries?: number
  enableBlur?: boolean
  blurAmount?: number
}

export interface LazyImageState {
  isIntersecting: Ref<boolean>
  isLoading: Ref<boolean>
  isLoaded: Ref<boolean>
  hasError: Ref<boolean>
  retryCount: Ref<number>
  shouldShowImage: Ref<boolean>
  progress: Ref<number>
}

export function useLazyImage(
  elementRef: Ref<HTMLElement | null>,
  options: LazyImageOptions = {}
): LazyImageState {
  const {
    rootMargin = '50px',
    threshold = 0.1,
    retryDelay = 1000,
    maxRetries = 3,
    enableBlur = true,
    blurAmount = 10
  } = options

  // State
  const isIntersecting = ref(false)
  const isLoading = ref(false)
  const isLoaded = ref(false)
  const hasError = ref(false)
  const retryCount = ref(0)
  const progress = ref(0)

  // Computed
  const shouldShowImage = ref(false)

  let observer: IntersectionObserver | null = null
  let retryTimer: NodeJS.Timeout | null = null

  // Initialize intersection observer
  const initObserver = () => {
    if (!('IntersectionObserver' in window)) {
      // Fallback for browsers without IntersectionObserver
      isIntersecting.value = true
      return
    }

    observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            isIntersecting.value = true
            shouldShowImage.value = true
            // Stop observing once image is in view
            if (observer && elementRef.value) {
              observer.unobserve(elementRef.value)
            }
          }
        })
      },
      {
        rootMargin,
        threshold
      }
    )

    if (elementRef.value) {
      observer.observe(elementRef.value)
    }
  }

  // Load image with retry logic
  const loadImage = (src: string): Promise<void> => {
    return new Promise((resolve, reject) => {
      const img = new Image()
      
      // Track loading progress (simulated)
      let progressInterval: NodeJS.Timeout
      
      img.onload = () => {
        clearInterval(progressInterval)
        progress.value = 100
        isLoading.value = false
        isLoaded.value = true
        hasError.value = false
        resolve()
      }

      img.onerror = () => {
        clearInterval(progressInterval)
        isLoading.value = false
        hasError.value = true
        
        if (retryCount.value < maxRetries) {
          retryCount.value++
          retryTimer = setTimeout(() => {
            loadImage(src).then(resolve).catch(reject)
          }, retryDelay * retryCount.value)
        } else {
          reject(new Error('Failed to load image after retries'))
        }
      }

      // Start loading
      isLoading.value = true
      isLoaded.value = false
      hasError.value = false
      progress.value = 0

      // Simulate loading progress
      progressInterval = setInterval(() => {
        if (progress.value < 90) {
          progress.value += Math.random() * 20
        }
      }, 100)

      img.src = src
    })
  }

  // Manual retry function
  const retry = (src: string) => {
    retryCount.value = 0
    hasError.value = false
    return loadImage(src)
  }

  // Reset state
  const reset = () => {
    isIntersecting.value = false
    isLoading.value = false
    isLoaded.value = false
    hasError.value = false
    retryCount.value = 0
    shouldShowImage.value = false
    progress.value = 0

    if (retryTimer) {
      clearTimeout(retryTimer)
      retryTimer = null
    }
  }

  // Lifecycle
  onMounted(() => {
    initObserver()
  })

  onUnmounted(() => {
    if (observer) {
      observer.disconnect()
    }
    if (retryTimer) {
      clearTimeout(retryTimer)
    }
  })

  return {
    isIntersecting,
    isLoading,
    isLoaded,
    hasError,
    retryCount,
    shouldShowImage,
    progress,
    loadImage,
    retry,
    reset
  }
}

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

export function useResponsiveImage(
  sources: ResponsiveImageSizes,
  preferredSize: string = 'medium'
) {
  const supportsWebP = ref<boolean | null>(null)

  // Detect WebP support
  const detectWebPSupport = (): Promise<boolean> => {
    return new Promise((resolve) => {
      const webP = new Image()
      webP.onload = webP.onerror = () => {
        resolve(webP.height === 2)
      }
      webP.src = 'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA'
    })
  }

  // Get the best image source for current conditions
  const getBestSource = (size: string = preferredSize): string => {
    const sizeSource = sources[size as keyof ResponsiveImageSizes]
    
    if (!sizeSource) {
      // Fallback to largest available size
      const fallbackOrder = ['large', 'medium', 'small', 'thumbnail', 'original']
      for (const fallbackSize of fallbackOrder) {
        const fallback = sources[fallbackSize as keyof ResponsiveImageSizes]
        if (fallback) {
          return getBestFormat(fallback)
        }
      }
      return ''
    }

    return getBestFormat(sizeSource)
  }

  // Get the best format (WebP vs fallback)
  const getBestFormat = (source: ResponsiveImageSource): string => {
    if (supportsWebP.value && source.webp) {
      return source.webp
    }
    return source.fallback || source.default
  }

  // Get srcset for responsive images
  const getSrcSet = (): string => {
    const srcSetItems: string[] = []
    
    Object.entries(sources).forEach(([size, source]) => {
      if (source) {
        const url = getBestFormat(source)
        if (url) {
          const width = getSizeWidth(size)
          if (width) {
            srcSetItems.push(`${url} ${width}w`)
          }
        }
      }
    })

    return srcSetItems.join(', ')
  }

  // Get approximate width for size
  const getSizeWidth = (size: string): number | null => {
    const sizeMap: Record<string, number> = {
      thumbnail: 150,
      small: 300,
      medium: 600,
      large: 1200,
      original: 1920
    }
    return sizeMap[size] || null
  }

  // Initialize WebP detection
  onMounted(async () => {
    supportsWebP.value = await detectWebPSupport()
  })

  return {
    supportsWebP,
    getBestSource,
    getBestFormat,
    getSrcSet,
    detectWebPSupport
  }
}