<?php
// app/Models/Staff.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\HasRolesAndPermissions;

class Staff extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRolesAndPermissions;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'position',
        'office_id',
        'campus_id',
        'is_active',
        'is_admin',
        'profile_photo_path',
        'qr_code_image_path',
        'employee_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'is_admin' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * The authentication guard for the model.
     *
     * @var string
     */
    protected $guard = 'staff';

    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the user that owns the staff record.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the URL to the staff member's profile photo.
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
    
    /**
     * Get the guard name for the staff member.
     *
     * @return string
     */
    public function guardName()
    {
        return 'staff';
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
     * Get the equipment assigned to this staff member.
     */
    public function equipment()
    {
        return $this->belongsToMany(\App\Models\Equipment::class, 'equipment_user', 'user_id', 'equipment_id')
            ->withTimestamps()
            ->withPivot(['assigned_at', 'returned_at', 'notes']);
    }

    /**
     * Scope to get only active staff
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

}
