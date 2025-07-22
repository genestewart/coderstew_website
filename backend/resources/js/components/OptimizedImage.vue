<template>
  <div 
    ref="containerRef"
    class="optimized-image-container"
    :class="{
      'is-loading': isLoading,
      'is-loaded': isLoaded,
      'has-error': hasError,
      'enable-blur': enableBlur && isLoading
    }"
    :style="containerStyle"
  >
    <!-- Loading Skeleton -->
    <div
      v-if="showSkeleton && (isLoading || (!shouldShowImage && !hasError))"
      class="image-skeleton"
      :style="skeletonStyle"
    >
      <div class="skeleton-shimmer"></div>
      <div v-if="showProgress && isLoading" class="loading-progress">
        <div class="progress-bar" :style="{ width: `${progress}%` }"></div>
      </div>
    </div>

    <!-- Thumbnail/Blur Preview -->
    <img
      v-if="thumbnailSrc && enableBlur && !isLoaded"
      :src="thumbnailSrc"
      :alt="alt"
      class="thumbnail-preview"
      :style="thumbnailStyle"
      loading="eager"
    />

    <!-- Main Image -->
    <picture v-if="shouldShowImage" class="main-image">
      <!-- WebP Source -->
      <source
        v-if="webpSrc"
        :srcset="webpSrcSet || webpSrc"
        :sizes="sizes"
        type="image/webp"
      />
      
      <!-- Fallback Source -->
      <img
        ref="imageRef"
        :src="fallbackSrc"
        :srcset="fallbackSrcSet"
        :sizes="sizes"
        :alt="alt"
        :loading="nativeLazyLoading ? 'lazy' : 'eager'"
        class="responsive-image"
        :style="imageStyle"
        @load="handleImageLoad"
        @error="handleImageError"
      />
    </picture>

    <!-- Error State -->
    <div v-if="hasError && !isLoading" class="error-state">
      <div class="error-content">
        <i class="pi pi-exclamation-triangle error-icon"></i>
        <p class="error-message">{{ errorMessage || 'Failed to load image' }}</p>
        <button
          v-if="showRetry && retryCount < maxRetries"
          @click="handleRetry"
          class="retry-button"
        >
          Retry ({{ retryCount + 1 }}/{{ maxRetries }})
        </button>
      </div>
    </div>

    <!-- Loading Overlay -->
    <div
      v-if="showLoadingOverlay && isLoading"
      class="loading-overlay"
    >
      <div class="loading-spinner"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { useLazyImage, useResponsiveImage, type ResponsiveImageSizes } from '../composables/useLazyImage'

interface Props {
  // Image sources
  src?: string
  responsiveSources?: ResponsiveImageSizes
  alt: string
  
  // Size and layout
  width?: number | string
  height?: number | string
  aspectRatio?: string
  size?: 'thumbnail' | 'small' | 'medium' | 'large' | 'original'
  sizes?: string
  
  // Loading behavior
  lazy?: boolean
  nativeLazyLoading?: boolean
  preload?: boolean
  
  // Visual effects
  enableBlur?: boolean
  blurAmount?: number
  transitionDuration?: number
  
  // Loading states
  showSkeleton?: boolean
  showProgress?: boolean
  showLoadingOverlay?: boolean
  showRetry?: boolean
  
  // Error handling
  errorMessage?: string
  maxRetries?: number
  retryDelay?: number
  
  // Intersection observer options
  rootMargin?: string
  threshold?: number
}

const props = withDefaults(defineProps<Props>(), {
  lazy: true,
  nativeLazyLoading: true,
  enableBlur: true,
  blurAmount: 10,
  transitionDuration: 300,
  showSkeleton: true,
  showProgress: false,
  showLoadingOverlay: false,
  showRetry: true,
  maxRetries: 3,
  retryDelay: 1000,
  rootMargin: '50px',
  threshold: 0.1,
  size: 'medium'
})

const emit = defineEmits<{
  load: [event: Event]
  error: [error: Error]
  retry: [attempt: number]
}>()

// Template refs
const containerRef = ref<HTMLElement | null>(null)
const imageRef = ref<HTMLImageElement | null>(null)

// Lazy loading composable
const {
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
} = useLazyImage(containerRef, {
  rootMargin: props.rootMargin,
  threshold: props.threshold,
  retryDelay: props.retryDelay,
  maxRetries: props.maxRetries,
  enableBlur: props.enableBlur,
  blurAmount: props.blurAmount
})

// Responsive image composable
const responsiveImage = props.responsiveSources ? 
  useResponsiveImage(props.responsiveSources, props.size) : null

// Computed properties
const webpSrc = computed(() => {
  if (props.responsiveSources && responsiveImage) {
    const source = props.responsiveSources[props.size!]
    return source?.webp
  }
  return null
})

const fallbackSrc = computed(() => {
  if (props.responsiveSources && responsiveImage) {
    return responsiveImage.getBestSource(props.size)
  }
  return props.src || ''
})

const webpSrcSet = computed(() => {
  if (props.responsiveSources && responsiveImage) {
    return responsiveImage.getSrcSet()
  }
  return null
})

const fallbackSrcSet = computed(() => {
  // For now, return single source - could be expanded for multiple sizes
  return fallbackSrc.value
})

const thumbnailSrc = computed(() => {
  if (props.responsiveSources) {
    const thumbnail = props.responsiveSources.thumbnail
    return thumbnail ? (thumbnail.webp || thumbnail.fallback || thumbnail.default) : null
  }
  return null
})

// Styles
const containerStyle = computed(() => ({
  width: typeof props.width === 'number' ? `${props.width}px` : props.width,
  height: typeof props.height === 'number' ? `${props.height}px` : props.height,
  aspectRatio: props.aspectRatio,
  '--transition-duration': `${props.transitionDuration}ms`,
  '--blur-amount': `${props.blurAmount}px`
}))

const skeletonStyle = computed(() => ({
  width: '100%',
  height: '100%',
  aspectRatio: props.aspectRatio
}))

const thumbnailStyle = computed(() => ({
  filter: `blur(${props.blurAmount}px)`,
  transform: 'scale(1.05)', // Slight scale to hide blur edges
  transition: `opacity ${props.transitionDuration}ms ease-out`
}))

const imageStyle = computed(() => ({
  opacity: isLoaded.value ? 1 : 0,
  filter: isLoaded.value ? 'none' : `blur(${props.blurAmount}px)`,
  transition: `all ${props.transitionDuration}ms ease-out`
}))

// Event handlers
const handleImageLoad = (event: Event) => {
  nextTick(() => {
    isLoaded.value = true
    isLoading.value = false
    emit('load', event)
  })
}

const handleImageError = (event: Event) => {
  hasError.value = true
  isLoading.value = false
  emit('error', new Error('Image failed to load'))
}

const handleRetry = async () => {
  emit('retry', retryCount.value + 1)
  try {
    await retry(fallbackSrc.value)
  } catch (error) {
    console.error('Retry failed:', error)
  }
}

// Watch for source changes
watch([fallbackSrc], ([newSrc]) => {
  if (newSrc && shouldShowImage.value && !props.lazy) {
    loadImage(newSrc)
  }
})

// Watch for intersection
watch(shouldShowImage, (show) => {
  if (show && fallbackSrc.value) {
    loadImage(fallbackSrc.value)
  }
})

// Preload if requested
onMounted(() => {
  if (props.preload && fallbackSrc.value) {
    loadImage(fallbackSrc.value)
  }
})
</script>

<style scoped>
.optimized-image-container {
  position: relative;
  display: inline-block;
  overflow: hidden;
  background-color: #f3f4f6;
  border-radius: 0.375rem;
}

.image-skeleton {
  position: relative;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

.skeleton-shimmer {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(
    90deg,
    transparent 25%,
    rgba(255, 255, 255, 0.5) 50%,
    transparent 75%
  );
  background-size: 200% 100%;
  animation: shimmer 2s infinite;
}

.loading-progress {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 4px;
  background-color: rgba(0, 0, 0, 0.1);
}

.progress-bar {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #60a5fa);
  transition: width 0.3s ease;
}

.thumbnail-preview {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  z-index: 1;
}

.main-image {
  position: relative;
  display: block;
  width: 100%;
  height: 100%;
  z-index: 2;
}

.responsive-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.error-state {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #fef2f2;
  z-index: 3;
}

.error-content {
  text-align: center;
  padding: 1rem;
}

.error-icon {
  font-size: 2rem;
  color: #ef4444;
  margin-bottom: 0.5rem;
}

.error-message {
  font-size: 0.875rem;
  color: #991b1b;
  margin-bottom: 1rem;
}

.retry-button {
  background-color: #dc2626;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.retry-button:hover {
  background-color: #b91c1c;
}

.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(255, 255, 255, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 4;
}

.loading-spinner {
  width: 2rem;
  height: 2rem;
  border: 2px solid #e5e7eb;
  border-top: 2px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* State classes */
.is-loading .responsive-image {
  opacity: 0;
}

.is-loaded .thumbnail-preview {
  opacity: 0;
}

.enable-blur .responsive-image {
  filter: blur(var(--blur-amount));
}

.is-loaded.enable-blur .responsive-image {
  filter: none;
}

/* Responsive behavior */
@media (prefers-reduced-motion: reduce) {
  .optimized-image-container * {
    animation-duration: 0.01s !important;
    transition-duration: 0.01s !important;
  }
}
</style>