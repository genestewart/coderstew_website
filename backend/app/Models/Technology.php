<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Technology extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'website_url',
        'type',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'type' => 'other',
        'sort_order' => 0,
        'is_active' => true,
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug if not provided
        static::creating(function ($technology) {
            if (empty($technology->slug)) {
                $technology->slug = Str::slug($technology->name);
            }
        });

        static::updating(function ($technology) {
            if ($technology->isDirty('name') && empty($technology->slug)) {
                $technology->slug = Str::slug($technology->name);
            }
        });
    }

    /**
     * Get the projects that use this technology
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
                    ->withTimestamps()
                    ->orderBy('project_date', 'desc');
    }

    /**
     * Scope to get only active technologies
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to order by sort order and name
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get the technology's icon URL
     */
    public function getIconUrlAttribute(): ?string
    {
        return $this->icon ? asset('storage/' . $this->icon) : null;
    }

    /**
     * Get the technology type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'language' => 'Programming Language',
            'framework' => 'Framework',
            'library' => 'Library',
            'tool' => 'Tool',
            'database' => 'Database',
            'service' => 'Service',
            default => 'Other'
        };
    }

    /**
     * Get available technology types
     */
    public static function getTypes(): array
    {
        return [
            'language' => 'Programming Language',
            'framework' => 'Framework',
            'library' => 'Library',
            'tool' => 'Tool',
            'database' => 'Database',
            'service' => 'Service',
            'other' => 'Other'
        ];
    }

    /**
     * Check if technology is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }
}
