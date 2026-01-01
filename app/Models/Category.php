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
    ];

    protected $casts = [
    ];

    /**
     * Get the equipment that belongs to this category.
     */
    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
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
        return $this->equipment()->where('status', Equipment::STATUS_SERVICEABLE)->count();
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
