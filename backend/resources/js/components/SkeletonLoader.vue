<template>
  <div 
    class="skeleton-loader"
    :class="[
      `skeleton-${variant}`,
      {
        'skeleton-animated': animated,
        'skeleton-rounded': rounded
      }
    ]"
    :style="skeletonStyle"
  >
    <div v-if="animated" class="skeleton-shimmer"></div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  width?: number | string
  height?: number | string
  variant?: 'text' | 'circular' | 'rectangular' | 'card'
  animated?: boolean
  rounded?: boolean
  lines?: number
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'rectangular',
  animated: true,
  rounded: false,
  lines: 1
})

const skeletonStyle = computed(() => {
  const style: Record<string, any> = {}
  
  if (props.width) {
    style.width = typeof props.width === 'number' ? `${props.width}px` : props.width
  }
  
  if (props.height) {
    style.height = typeof props.height === 'number' ? `${props.height}px` : props.height
  }
  
  // Text variant specific styles
  if (props.variant === 'text') {
    style.height = '1em'
    if (props.lines > 1) {
      style.marginBottom = '0.5em'
    }
  }
  
  return style
})
</script>

<style scoped>
.skeleton-loader {
  position: relative;
  background-color: #e5e7eb;
  overflow: hidden;
}

.skeleton-text {
  height: 1em;
  border-radius: 0.25rem;
  margin-bottom: 0.5em;
}

.skeleton-text:last-child {
  margin-bottom: 0;
  width: 75%; /* Last line typically shorter */
}

.skeleton-circular {
  border-radius: 50%;
}

.skeleton-rectangular {
  border-radius: 0.375rem;
}

.skeleton-card {
  border-radius: 0.5rem;
  padding: 1rem;
}

.skeleton-rounded {
  border-radius: 0.75rem;
}

.skeleton-animated {
  background: linear-gradient(90deg, #e5e7eb 25%, #d1d5db 50%, #e5e7eb 75%);
  background-size: 200% 100%;
  animation: skeleton-loading 1.5s infinite;
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
    rgba(255, 255, 255, 0.6) 50%,
    transparent 75%
  );
  background-size: 200% 100%;
  animation: skeleton-shimmer 2s infinite;
}

@keyframes skeleton-loading {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

@keyframes skeleton-shimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .skeleton-loader {
    background-color: #374151;
  }
  
  .skeleton-animated {
    background: linear-gradient(90deg, #374151 25%, #4b5563 50%, #374151 75%);
    background-size: 200% 100%;
  }
  
  .skeleton-shimmer {
    background: linear-gradient(
      90deg,
      transparent 25%,
      rgba(255, 255, 255, 0.1) 50%,
      transparent 75%
    );
    background-size: 200% 100%;
  }
}

/* Reduced motion accessibility */
@media (prefers-reduced-motion: reduce) {
  .skeleton-animated,
  .skeleton-shimmer {
    animation: none;
  }
  
  .skeleton-loader {
    background-color: #e5e7eb;
  }
  
  @media (prefers-color-scheme: dark) {
    .skeleton-loader {
      background-color: #374151;
    }
  }
}
</style>