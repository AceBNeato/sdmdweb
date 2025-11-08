<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Technician extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'specialization',
        'staff_id',
        'is_active',
        'employee_id',
        'hire_date',
        'certification',
        'skills',
        'emergency_contact',
        'emergency_phone',
        'hourly_rate',
        'shift',
        'profile_photo_path',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'hire_date' => 'date',
        'hourly_rate' => 'decimal:2'
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Get the user associated with this technician.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the URL to the technician's profile photo.
     *
     * @return string
     */
    public function getProfilePhotoUrlAttribute()
    {
        if ($this->profile_photo_path) {
            return asset('storage/' . $this->profile_photo_path);
        }

        $name = trim(collect(explode(' ', $this->name))->map(function ($segment) {
            return mb_substr($segment, 0, 1);
        })->join(' '));

        return 'https://ui-avatars.com/api/?name='.urlencode($name).'&color=7F9CF5&background=EBF4FF';
    }
}
