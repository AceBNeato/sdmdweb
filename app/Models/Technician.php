<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\HasRolesAndPermissions;

class Technician extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRolesAndPermissions;

    /**
     * The authentication guard for the model.
     *
     * @var string
     */
    protected $guard = 'technician';

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'password',
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

    protected $hidden = [
        'password',
        'remember_token',
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
     * Override hasPermissionTo to delegate to the associated User.
     */
    public function hasPermissionTo($permission): bool
    {
        if ($this->user) {
            return $this->user->hasPermissionTo($permission);
        }
        return false;
    }

    /**
     * Get the guard name for the technician.
     *
     * @return string
     */
    public function guardName()
    {
        return 'technician';
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

        $name = trim(collect(explode(' ', $this->first_name))->map(function ($segment) {
            return mb_substr($segment, 0, 1);
        })->join(' '));

        return 'https://ui-avatars.com/api/?name='.urlencode($name).'&color=7F9CF5&background=EBF4FF';
    }
}
