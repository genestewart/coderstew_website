<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use App\Services\ImageOptimizationService;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'description',
        'featured_image',
        'featured_image_variants',
        'project_url',
        'repository_url',
        'demo_url',
        'status',
        'is_featured',
        'sort_order',
        'client_name',
        'project_date',
        'completion_date',
        'gallery_images',
        'gallery_image_variants',
        'image_metadata',
        'video_url',
        'meta_title',
        'meta_description',
        'project_category_id',
    ];

    protected $casts = [
        'gallery_images' => 'array',
        'featured_image_variants' => 'array',
        'gallery_image_variants' => 'array',
        'image_metadata' => 'array',
        'project_date' => 'date',
        'completion_date' => 'date',
        'is_featured' => 'boolean',
    ];

    protected $attributes = [
        'status' => 'draft',
        'is_featured' => false,
        'sort_order' => 0,
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug if not provided
        static::creating(function ($project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->title);
            }
        });

        static::updating(function ($project) {
            if ($project->isDirty('title') && empty($project->slug)) {
                $project->slug = Str::slug($project->title);
            }
        });
    }

    /**
     * Get the category that owns the project
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProjectCategory::class, 'project_category_id');
    }

    /**
     * Get the technologies associated with the project
     */
    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class)
                    ->withTimestamps()
                    ->orderBy('sort_order');
    }

    /**
     * Scope to get only published projects
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope to get featured projects
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to order by sort order and creation date
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at', 'desc');
    }

    /**
     * Get the project's full URL
     */
    public function getUrlAttribute(): string
    {
        return route('projects.show', $this->slug);
    }

    /**
     * Get the project's featured image URL
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->featured_image ? asset('storage/' . $this->featured_image) : null;
    }

    /**
     * Get gallery image URLs
     */
    public function getGalleryImageUrlsAttribute(): array
    {
        if (!$this->gallery_images) {
            return [];
        }

        return collect($this->gallery_images)->map(function ($image) {
            return asset('storage/' . $image);
        })->toArray();
    }

    /**
     * Get excerpt or generate from description
     */
    public function getExcerptOrDescriptionAttribute(): string
    {
        if ($this->excerpt) {
            return $this->excerpt;
        }

        return Str::limit(strip_tags($this->description), 150);
    }

    /**
     * Check if project is published
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Check if project is featured
     */
    public function isFeatured(): bool
    {
        return $this->is_featured;
    }

    /**
     * Get optimized featured image URLs for responsive loading
     */
    public function getFeaturedImageResponsiveAttribute(): array
    {
        if (!$this->featured_image_variants) {
            return $this->getLegacyImageResponse($this->featured_image);
        }

        $service = app(ImageOptimizationService::class);
        return $service->getResponsiveImageUrls($this->featured_image_variants);
    }

    /**
     * Get optimized gallery image URLs for responsive loading
     */
    public function getGalleryImagesResponsiveAttribute(): array
    {
        if (!$this->gallery_image_variants) {
            return $this->getLegacyGalleryResponse();
        }

        $service = app(ImageOptimizationService::class);
        $responsive = [];

        foreach ($this->gallery_image_variants as $index => $variants) {
            $responsive[$index] = $service->getResponsiveImageUrls($variants);
        }

        return $responsive;
    }

    /**
     * Get the best featured image URL for a specific size
     */
    public function getFeaturedImageUrl(string $size = 'medium', bool $preferWebP = true): ?string
    {
        $responsive = $this->featured_image_responsive;
        
        if (isset($responsive[$size])) {
            if ($preferWebP && $responsive[$size]['webp']) {
                return $responsive[$size]['webp'];
            }
            return $responsive[$size]['fallback'] ?? $responsive[$size]['default'];
        }

        // Fallback to largest available size
        foreach (['large', 'medium', 'small', 'thumbnail'] as $fallbackSize) {
            if (isset($responsive[$fallbackSize])) {
                if ($preferWebP && $responsive[$fallbackSize]['webp']) {
                    return $responsive[$fallbackSize]['webp'];
                }
                return $responsive[$fallbackSize]['fallback'] ?? $responsive[$fallbackSize]['default'];
            }
        }

        return $this->featured_image_url;
    }

    /**
     * Get gallery image URL for a specific index and size
     */
    public function getGalleryImageUrl(int $index, string $size = 'medium', bool $preferWebP = true): ?string
    {
        $responsive = $this->gallery_images_responsive;
        
        if (isset($responsive[$index][$size])) {
            if ($preferWebP && $responsive[$index][$size]['webp']) {
                return $responsive[$index][$size]['webp'];
            }
            return $responsive[$index][$size]['fallback'] ?? $responsive[$index][$size]['default'];
        }

        // Fallback to legacy gallery images
        $legacyUrls = $this->gallery_image_urls;
        return $legacyUrls[$index] ?? null;
    }

    /**
     * Get legacy image response for backward compatibility
     */
    private function getLegacyImageResponse(?string $imagePath): array
    {
        if (!$imagePath) {
            return [];
        }

        $url = asset('storage/' . $imagePath);
        return [
            'original' => [
                'webp' => null,
                'fallback' => $url,
                'default' => $url
            ]
        ];
    }

    /**
     * Get legacy gallery response for backward compatibility
     */
    private function getLegacyGalleryResponse(): array
    {
        if (!$this->gallery_images) {
            return [];
        }

        $responsive = [];
        foreach ($this->gallery_images as $index => $imagePath) {
            $url = asset('storage/' . $imagePath);
            $responsive[$index] = [
                'original' => [
                    'webp' => null,
                    'fallback' => $url,
                    'default' => $url
                ]
            ];
        }

        return $responsive;
    }

    /**
     * Process and store optimized images for featured image
     */
    public function optimizeFeaturedImage(\Illuminate\Http\UploadedFile $file): bool
    {
        try {
            $service = app(ImageOptimizationService::class);
            $result = $service->processImage($file, 'projects/featured');

            $this->update([
                'featured_image' => $result['optimized_filename'],
                'featured_image_variants' => $result['variants'],
                'image_metadata' => array_merge($this->image_metadata ?? [], [
                    'featured_image' => [
                        'original_filename' => $result['original_filename'],
                        'original_size' => $result['original_size'],
                        'original_dimensions' => $result['original_dimensions'],
                        'processed_at' => now()
                    ]
                ])
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to optimize featured image: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Process and store optimized images for gallery
     */
    public function optimizeGalleryImages(array $files): bool
    {
        try {
            $service = app(ImageOptimizationService::class);
            $galleryVariants = [];
            $galleryPaths = [];
            $metadata = $this->image_metadata ?? [];

            foreach ($files as $index => $file) {
                $result = $service->processImage($file, 'projects/gallery');
                
                $galleryPaths[] = $result['optimized_filename'];
                $galleryVariants[] = $result['variants'];
                
                $metadata['gallery_images'][$index] = [
                    'original_filename' => $result['original_filename'],
                    'original_size' => $result['original_size'],
                    'original_dimensions' => $result['original_dimensions'],
                    'processed_at' => now()
                ];
            }

            $this->update([
                'gallery_images' => $galleryPaths,
                'gallery_image_variants' => $galleryVariants,
                'image_metadata' => $metadata
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to optimize gallery images: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clean up optimized image files when project is deleted
     */
    protected static function booted()
    {
        static::deleting(function ($project) {
            $service = app(ImageOptimizationService::class);
            
            if ($project->featured_image_variants) {
                $service->deleteImageVariants($project->featured_image_variants);
            }
            
            if ($project->gallery_image_variants) {
                foreach ($project->gallery_image_variants as $variants) {
                    $service->deleteImageVariants($variants);
                }
            }
        });
    }
}
