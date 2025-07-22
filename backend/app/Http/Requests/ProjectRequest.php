<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow all authenticated users for now
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('projects', 'slug')->ignore($this->route('id')),
            ],
            'excerpt' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'project_url' => 'nullable|url|max:255',
            'repository_url' => 'nullable|url|max:255',
            'demo_url' => 'nullable|url|max:255',
            'status' => 'required|in:draft,published,archived',
            'is_featured' => 'boolean',
            'sort_order' => 'integer|min:0',
            'client_name' => 'nullable|string|max:255',
            'project_date' => 'nullable|date',
            'completion_date' => 'nullable|date|after_or_equal:project_date',
            'video_url' => 'nullable|url|max:255',
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'project_category_id' => 'nullable|exists:project_categories,id',
            'technologies' => 'nullable|array',
            'technologies.*' => 'exists:technologies,id',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ];

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The project title is required.',
            'title.max' => 'The project title may not be greater than 255 characters.',
            'slug.unique' => 'This slug is already taken. Please choose a different one.',
            'slug.regex' => 'The slug may only contain lowercase letters, numbers, and hyphens.',
            'excerpt.max' => 'The excerpt may not be greater than 500 characters.',
            'project_url.url' => 'The project URL must be a valid URL.',
            'repository_url.url' => 'The repository URL must be a valid URL.',
            'demo_url.url' => 'The demo URL must be a valid URL.',
            'status.required' => 'Please select a project status.',
            'status.in' => 'The selected status is invalid.',
            'project_date.date' => 'The project date must be a valid date.',
            'completion_date.date' => 'The completion date must be a valid date.',
            'completion_date.after_or_equal' => 'The completion date must be after or equal to the project date.',
            'video_url.url' => 'The video URL must be a valid URL.',
            'meta_title.max' => 'The meta title may not be greater than 60 characters.',
            'meta_description.max' => 'The meta description may not be greater than 160 characters.',
            'project_category_id.exists' => 'The selected category is invalid.',
            'technologies.*.exists' => 'One or more selected technologies are invalid.',
            'featured_image.image' => 'The featured image must be an image file.',
            'featured_image.mimes' => 'The featured image must be a file of type: jpeg, png, jpg, gif, webp.',
            'featured_image.max' => 'The featured image may not be greater than 2MB.',
            'gallery_images.*.image' => 'All gallery files must be images.',
            'gallery_images.*.mimes' => 'Gallery images must be files of type: jpeg, png, jpg, gif, webp.',
            'gallery_images.*.max' => 'Gallery images may not be greater than 2MB each.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'project_category_id' => 'category',
            'is_featured' => 'featured',
            'sort_order' => 'sort order',
            'client_name' => 'client name',
            'project_date' => 'project date',
            'completion_date' => 'completion date',
            'video_url' => 'video URL',
            'meta_title' => 'meta title',
            'meta_description' => 'meta description',
            'featured_image' => 'featured image',
            'gallery_images' => 'gallery images',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Auto-generate slug from title if not provided
        if (empty($this->slug) && !empty($this->title)) {
            $this->merge([
                'slug' => \Str::slug($this->title)
            ]);
        }

        // Convert is_featured to boolean
        if ($this->has('is_featured')) {
            $this->merge([
                'is_featured' => (bool) $this->is_featured
            ]);
        }

        // Ensure sort_order is integer
        if ($this->has('sort_order')) {
            $this->merge([
                'sort_order' => (int) $this->sort_order
            ]);
        }
    }
}