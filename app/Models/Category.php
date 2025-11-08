<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'color',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the equipment that belongs to this category.
     */
    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }

    /**
     * Scope to get only active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get categories with equipment count
     */
    public function scopeWithEquipmentCount($query)
    {
        return $query->withCount('equipment');
    }

    /**
     * Get the total count of equipment in this category
     */
    public function getEquipmentCountAttribute(): int
    {
        return $this->equipment()->count();
    }

    /**
     * Get available equipment count in this category
     */
    public function getAvailableEquipmentCountAttribute(): int
    {
        return $this->equipment()->where('status', Equipment::STATUS_AVAILABLE)->count();
    }

    /**
     * Check if category has any equipment
     */
    public function hasEquipment(): bool
    {
        return $this->equipment()->exists();
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKeyName()
    {
        return 'name';
    }
}
