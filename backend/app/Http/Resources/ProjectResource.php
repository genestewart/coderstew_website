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
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'description' => $this->description,
            'featured_image' => $this->featured_image,
        ];
    }
}
