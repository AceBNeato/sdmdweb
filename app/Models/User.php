<?php

namespace App\Models;

use App\Traits\HasRolesAndPermissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use App\Models\Office;
use App\Notifications\CustomResetPassword;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Auth\Passwords\CanResetPassword;


class User extends Authenticatable
{
    use CanResetPassword;
    use HasFactory, Notifiable, HasRolesAndPermissions, \Illuminate\Database\Eloquent\SoftDeletes;

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        // Automatically hash password when setting it
        static::saving(function ($user) {
            if ($user->isDirty('password')) {
                $user->password = Hash::needsRehash($user->password)
                    ? Hash::make($user->password)
                    : $user->password;
            }
        });

            // Note: Removed global scope for active users to allow authentication
        // of inactive users (they will be checked after authentication)
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPassword($token));
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'is_active',
        'email_verified_at',
        'email_verification_token',
        'email_verification_token_expires_at',
        'phone',
        'profile_photo',
        'position',
        'office_id',
        'campus_id',
        'last_login_at',
        'last_login_ip',
        'qr_code_image_path',
        'address',
        'employee_id',
        'specialization',
        'skills',
        'profile_image',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the office that the user belongs to.
     */
    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * Get the campus that the user belongs to.
     */
    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'is_admin',
        'is_technician',
        'is_staff',
        'role_names',
    ];

    /**
     * The roles that belong to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * The permissions that belong to the user.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class)->withPivot('is_active')->withTimestamps();
    }

    /**
     * Get the technician profile associated with the user.
     */
    public function technician()
    {
        return $this->hasOne(\App\Models\Technician::class, 'user_id');
    }

    /**
     * Get the password reset requests for the user.
     */
    public function passwordResetRequests(): HasMany
    {
        return $this->hasMany(PasswordResetRequest::class);
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole($role): self
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $this->roles()->syncWithoutDetaching([$role->id]);
        return $this;
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole($role): self
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $this->roles()->detach($role->id);
        return $this;
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole($roles): bool
    {
        if (is_string($roles)) {
            return $this->roles->contains('name', $roles);
        }

        if ($roles instanceof Role) {
            return $this->roles->contains('id', $roles->id);
        }

        if (is_array($roles)) {
            return $this->roles->whereIn('name', $roles)->isNotEmpty();
        }

        return false;
    }

    /**
     * Check if the user has all of the given roles.
     */
    public function hasAllRoles($roles): bool
    {
        if (is_string($roles)) {
            return $this->hasRole($roles);
        }

        $roleNames = $roles instanceof Collection
            ? $roles->pluck('name')->toArray()
            : $roles;

        return $this->roles->whereIn('name', $roleNames)->count() === count($roleNames);
    }

    /**
     * Revoke a permission from the user.
     */
    public function revokePermissionTo($permission): self
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        $this->permissions()->detach($permission->id);
        return $this;
    }

    /**
     * Check if user is a super admin.
     */
    public function getIsSuperAdminAttribute(): bool
    {
        return $this->hasRole('super-admin');
    }

    /**
     * Check if user is an admin (includes super admin).
     */
    public function getIsAdminAttribute(): bool
    {
        return $this->hasRole(['admin', 'super-admin']);
    }

    /**
     * Check if user is a technician.
     */
    public function getIsTechnicianAttribute(): bool
    {
        return $this->hasRole('technician');
    }

    /**
     * Check if user is staff.
     */
    public function getIsStaffAttribute(): bool
    {
        return $this->hasRole('staff');
    }

    /**
     * Get all role names as a collection.
     */
    public function getRoleNamesAttribute()
    {
        return $this->roles->pluck('name');
    }

    /**
     * Override the can method to use custom permission system.
     */
    public function can($ability, $arguments = [])
    {
        return $this->hasPermissionTo($ability);
    }

    /**
     * Get the user's full name.
     */
    public function getNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Get all permissions as a collection.
     */
    public function getAllPermissions()
    {
        return $this->permissions;
    }

    /**
     * Check if the user's email is verified.
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Mark the user's email as verified.
     */
    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
            'email_verification_token' => null,
            'email_verification_token_expires_at' => null,
        ])->save();
    }

    /**
     * Send email verification notification.
     */
    public function sendEmailVerificationNotification()
    {
        // This will be handled by the EmailVerificationController
        // when admin creates user
    }

    /**
     * Get the email verification URL.
     */
    public function getEmailVerificationUrl(): string
    {
        return route('email.verify', [
            'token' => $this->email_verification_token
        ]);
    }
}
