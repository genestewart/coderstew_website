<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /**
     * Disable the default data wrapper.
     *
     * @var null
     */
    public static $wrap = null;
    
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'description' => $this->description,
            'featured_image' => $this->featured_image,
            'featured_image_responsive' => $this->featured_image_responsive,
            'gallery_images' => $this->gallery_images ? json_decode($this->gallery_images, true) : [],
            'gallery_images_responsive' => $this->gallery_images_responsive,
            'project_url' => $this->project_url,
            'repository_url' => $this->repository_url,
            'demo_url' => $this->demo_url,
            'video_url' => $this->video_url,
            'client_name' => $this->client_name,
            'project_date' => $this->project_date,
            'completion_date' => $this->completion_date,
            'is_featured' => $this->is_featured,
            'status' => $this->status,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                    'full_path' => $this->category->full_path,
                ];
            }),
            'technologies' => $this->whenLoaded('technologies', function () {
                return $this->technologies->map(function ($technology) {
                    return [
                        'id' => $technology->id,
                        'name' => $technology->name,
                        'type' => $technology->type,
                        'icon' => $technology->icon,
                        'color' => $technology->color,
                    ];
                });
            }),
        ];
    }
}
