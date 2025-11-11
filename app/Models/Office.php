<?php

namespace App\Models;

use App\Models\Campus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Office extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'offices';
    protected $fillable = [
        'name',
        'code',
        'campus_id',
        'address',
        'contact_number',
        'email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the campus that owns the office.
     */
    public function campus()
    {
        return $this->belongsTo(Campus::class, 'campus_id', 'id');
    }

    /**
     * Get the staff members associated with this office.
     */
    public function staff()
    {
        return $this->hasMany(Staff::class, 'office_id', 'id');
    }

    /**
     * Get the equipment associated with this office.
     */
    public function equipment()
    {
        return $this->hasMany(Equipment::class);
    }

    /**
     * Scope to get only active offices
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
