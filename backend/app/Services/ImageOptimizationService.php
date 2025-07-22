<?php

namespace App\Services;

use Intervention\Image\ImageManagerStatic as Image;
use Intervention\Image\Facades\Image as ImageFacade;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageOptimizationService
{
    /**
     * Image size variants configuration
     */
    private const SIZE_VARIANTS = [
        'thumbnail' => [150, 150],
        'small' => [300, 300],
        'medium' => [600, 600],
        'large' => [1200, 1200],
        'original' => null, // Keep original size but optimize
    ];

    /**
     * Supported image formats for conversion
     */
    private const SUPPORTED_FORMATS = ['jpeg', 'jpg', 'png', 'gif', 'webp'];

    /**
     * Quality settings for different formats
     */
    private const QUALITY_SETTINGS = [
        'jpeg' => 85,
        'jpg' => 85,
        'png' => 90,
        'webp' => 80,
    ];

    /**
     * Process and optimize uploaded image, creating multiple size variants
     *
     * @param UploadedFile $file
     * @param string $folder
     * @param string|null $filename
     * @return array Array of image variants with their paths
     */
    public function processImage(UploadedFile $file, string $folder = 'images', ?string $filename = null): array
    {
        // Validate image
        if (!$this->isValidImage($file)) {
            throw new \InvalidArgumentException('Invalid image file');
        }

        // Generate filename if not provided
        if (!$filename) {
            $filename = $this->generateFilename($file);
        }

        $variants = [];
        $originalImage = Image::make($file->getPathname());

        // Get original dimensions
        $originalWidth = $originalImage->width();
        $originalHeight = $originalImage->height();

        foreach (self::SIZE_VARIANTS as $variant => $dimensions) {
            $processedImage = clone $originalImage;

            if ($dimensions) {
                [$maxWidth, $maxHeight] = $dimensions;
                
                // Only resize if image is larger than target dimensions
                if ($originalWidth > $maxWidth || $originalHeight > $maxHeight) {
                    $processedImage->resize($maxWidth, $maxHeight, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize(); // Don't upscale smaller images
                    });
                }
            }

            // Generate paths for different formats
            $baseFilename = pathinfo($filename, PATHINFO_FILENAME);
            $variants[$variant] = [];

            // Create WebP version (modern format)
            $webpPath = $this->saveImageVariant(
                $processedImage, 
                $folder, 
                $baseFilename . '_' . $variant . '.webp',
                'webp'
            );
            if ($webpPath) {
                $variants[$variant]['webp'] = $webpPath;
            }

            // Create JPEG fallback
            $jpegPath = $this->saveImageVariant(
                $processedImage,
                $folder,
                $baseFilename . '_' . $variant . '.jpg',
                'jpg'
            );
            if ($jpegPath) {
                $variants[$variant]['jpeg'] = $jpegPath;
            }
        }

        return [
            'variants' => $variants,
            'original_filename' => $file->getClientOriginalName(),
            'optimized_filename' => $filename,
            'original_size' => $file->getSize(),
            'original_dimensions' => [
                'width' => $originalWidth,
                'height' => $originalHeight
            ]
        ];
    }

    /**
     * Save image variant to storage
     *
     * @param \Intervention\Image\Image $image
     * @param string $folder
     * @param string $filename
     * @param string $format
     * @return string|null Path to saved file
     */
    private function saveImageVariant($image, string $folder, string $filename, string $format): ?string
    {
        try {
            $quality = self::QUALITY_SETTINGS[$format] ?? 85;
            
            // Apply format-specific optimization
            switch ($format) {
                case 'webp':
                    $encoded = $image->encode('webp', $quality);
                    break;
                case 'jpeg':
                case 'jpg':
                    $encoded = $image->encode('jpg', $quality);
                    break;
                case 'png':
                    $encoded = $image->encode('png');
                    break;
                default:
                    $encoded = $image->encode($format, $quality);
            }

            $path = $folder . '/' . $filename;
            
            if (Storage::disk('public')->put($path, $encoded)) {
                return Storage::disk('public')->url($path);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to save image variant: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Generate optimized filename
     *
     * @param UploadedFile $file
     * @return string
     */
    private function generateFilename(UploadedFile $file): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(8);
        $extension = $file->getClientOriginalExtension();
        
        return $timestamp . '_' . $random . '.' . $extension;
    }

    /**
     * Validate if file is a supported image
     *
     * @param UploadedFile $file
     * @return bool
     */
    private function isValidImage(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();

        $validExtensions = self::SUPPORTED_FORMATS;
        $validMimes = [
            'image/jpeg',
            'image/png', 
            'image/gif',
            'image/webp'
        ];

        return in_array($extension, $validExtensions) && in_array($mimeType, $validMimes);
    }

    /**
     * Get responsive image URLs for frontend
     *
     * @param array $variants
     * @return array
     */
    public function getResponsiveImageUrls(array $variants): array
    {
        $responsive = [];

        foreach ($variants as $size => $formats) {
            if (isset($formats['webp'], $formats['jpeg'])) {
                $responsive[$size] = [
                    'webp' => $formats['webp'],
                    'fallback' => $formats['jpeg'],
                    'default' => $formats['jpeg'] // For browsers without picture support
                ];
            } elseif (isset($formats['jpeg'])) {
                $responsive[$size] = [
                    'webp' => null,
                    'fallback' => $formats['jpeg'],
                    'default' => $formats['jpeg']
                ];
            }
        }

        return $responsive;
    }

    /**
     * Delete image variants from storage
     *
     * @param array $variants
     * @return bool
     */
    public function deleteImageVariants(array $variants): bool
    {
        $success = true;

        foreach ($variants as $sizeVariants) {
            foreach ($sizeVariants as $formatPath) {
                try {
                    $path = parse_url($formatPath, PHP_URL_PATH);
                    $path = str_replace('/storage/', '', $path);
                    
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to delete image variant: ' . $e->getMessage());
                    $success = false;
                }
            }
        }

        return $success;
    }

    /**
     * Get image dimensions from URL
     *
     * @param string $imageUrl
     * @return array|null
     */
    public function getImageDimensions(string $imageUrl): ?array
    {
        try {
            $path = parse_url($imageUrl, PHP_URL_PATH);
            $path = str_replace('/storage/', '', $path);
            
            if (Storage::disk('public')->exists($path)) {
                $fullPath = Storage::disk('public')->path($path);
                $image = Image::make($fullPath);
                
                return [
                    'width' => $image->width(),
                    'height' => $image->height()
                ];
            }
        } catch (\Exception $e) {
            \Log::error('Failed to get image dimensions: ' . $e->getMessage());
        }

        return null;
    }
}