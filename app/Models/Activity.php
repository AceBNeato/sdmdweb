<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'description',
        'equipment_history_id',
    ];

    /**
     * Get the user that owns the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the equipment history entry related to this activity.
     */
    public function equipmentHistory(): BelongsTo
    {
        return $this->belongsTo(EquipmentHistory::class);
    }

    /**
     * Log user creation activity
     */
    public static function logUserCreation($createdUser, $createdBy = null)
    {
        $actor = $createdBy ?? auth()->user();
        
        return self::create([
            'user_id' => $actor?->id,
            'type' => 'user_created',
            'description' => sprintf(
                'Created user account for %s %s (%s) - Position: %s, Office: %s',
                $createdUser->first_name,
                $createdUser->last_name,
                $createdUser->email,
                $createdUser->position,
                $createdUser->office?->name ?? 'Unknown'
            ),
        ]);
    }

    /**
     * Log user update activity
     */
    public static function logUserUpdate($updatedUser, $changes = [], $updatedBy = null)
    {
        $actor = $updatedBy ?? auth()->user();
        
        $description = sprintf(
            'Updated user account for %s %s (%s)',
            $updatedUser->first_name,
            $updatedUser->last_name,
            $updatedUser->email
        );

        if (!empty($changes)) {
            $changeDetails = [];
            foreach ($changes as $field => $change) {
                if (is_array($change)) {
                    $changeDetails[] = "{$field}: " . implode(' → ', $change);
                } else {
                    $changeDetails[] = "{$field}: {$change}";
                }
            }
            $description .= ' - Changes: ' . implode(', ', $changeDetails);
        }

        return self::create([
            'user_id' => $actor?->id,
            'type' => 'user_updated',
            'description' => $description,
        ]);
    }

    /**
     * Log user role change activity
     */
    public static function logUserRoleChange($user, $oldRole, $newRole, $changedBy = null)
    {
        $actor = $changedBy ?? auth()->user();
        
        return self::create([
            'user_id' => $actor?->id,
            'type' => 'user_role_changed',
            'description' => sprintf(
                'Changed role for %s %s (%s) from %s to %s',
                $user->first_name,
                $user->last_name,
                $user->email,
                $oldRole?->name ?? 'none',
                $newRole?->name ?? 'none'
            ),
        ]);
    }

    /**
     * Log user status toggle activity
     */
    public static function logUserStatusToggle($user, $toggledBy = null)
    {
        $actor = $toggledBy ?? auth()->user();
        $status = $user->is_active ? 'activated' : 'deactivated';
        
        return self::create([
            'user_id' => $actor?->id,
            'type' => 'user_status_toggled',
            'description' => sprintf(
                '%s user account for %s %s (%s)',
                ucfirst($status),
                $user->first_name,
                $user->last_name,
                $user->email
            ),
        ]);
    }

    /**
     * Log user deletion activity
     */
    public static function logUserDeletion($deletedUser, $deletedBy = null)
    {
        $actor = $deletedBy ?? auth()->user();
        
        return self::create([
            'user_id' => $actor?->id,
            'type' => 'user_deleted',
            'description' => sprintf(
                'Deleted user account for %s %s (%s) - Position: %s, Office: %s',
                $deletedUser->first_name,
                $deletedUser->last_name,
                $deletedUser->email,
                $deletedUser->position,
                $deletedUser->office?->name ?? 'Unknown'
            ),
        ]);
    }

    /**
     * Log user login activity
     */
    public static function logUserLogin($user)
    {
        return self::create([
            'user_id' => $user->id,
            'type' => 'user_login',
            'description' => sprintf(
                'User %s %s (%s) logged in from %s',
                $user->first_name,
                $user->last_name,
                $user->email,
                request()->ip()
            ),
        ]);
    }

    /**
     * Log user logout activity
     */
    public static function logUserLogout($user)
    {
        return self::create([
            'user_id' => $user->id,
            'type' => 'user_logout',
            'description' => sprintf(
                'User %s %s (%s) logged out',
                $user->first_name,
                $user->last_name,
                $user->email
            ),
        ]);
    }

    /**
     * Log equipment creation activity
     */
    public static function logEquipmentCreation($equipment, $createdBy = null)
    {
        $actor = $createdBy ?? auth()->user();
        
        return self::create([
            'user_id' => $actor?->id,
            'type' => 'equipment_created',
            'description' => sprintf(
                'Created equipment: %s %s (%s) - Category: %s, Office: %s',
                $equipment->brand,
                $equipment->model_number,
                $equipment->serial_number,
                $equipment->category?->name ?? 'Unknown',
                $equipment->office?->name ?? 'Unknown'
            ),
        ]);
    }

    /**
     * Log equipment update activity
     */
    public static function logEquipmentUpdate($equipment, $changes = [], $updatedBy = null)
    {
        $actor = $updatedBy ?? auth()->user();
        
        $description = sprintf(
            'Updated equipment: %s %s (%s)',
            $equipment->brand,
            $equipment->model_number,
            $equipment->serial_number
        );

        if (!empty($changes)) {
            $changeDetails = [];
            foreach ($changes as $field => $change) {
                if (is_array($change)) {
                    $changeDetails[] = "{$field}: " . implode(' → ', $change);
                } else {
                    $changeDetails[] = "{$field}: {$change}";
                }
            }
            $description .= ' - Changes: ' . implode(', ', $changeDetails);
        }

        return self::create([
            'user_id' => $actor?->id,
            'type' => 'equipment_updated',
            'description' => $description,
        ]);
    }

    /**
     * Log equipment deletion activity
     */
    public static function logEquipmentDeletion($equipment, $deletedBy = null)
    {
        $actor = $deletedBy ?? auth()->user();
        
        return self::create([
            'user_id' => $actor?->id,
            'type' => 'equipment_deleted',
            'description' => sprintf(
                'Deleted equipment: %s %s (%s) - Category: %s, Office: %s',
                $equipment->brand,
                $equipment->model_number,
                $equipment->serial_number,
                $equipment->category?->name ?? 'Unknown',
                $equipment->office?->name ?? 'Unknown'
            ),
        ]);
    }

    /**
     * Log equipment assignment activity
     */
    public static function logEquipmentAssignment($equipment, $assignedTo, $assignedBy = null)
    {
        $actor = $assignedBy ?? auth()->user();
        
        return self::create([
            'user_id' => $actor?->id,
            'type' => 'equipment_assigned',
            'description' => sprintf(
                'Assigned equipment %s %s (%s) to %s %s',
                $equipment->brand,
                $equipment->model_number,
                $equipment->serial_number,
                $assignedTo->first_name ?? 'Unknown',
                $assignedTo->last_name ?? ''
            ),
        ]);
    }

    /**
     * Log equipment unassignment activity
     */
    public static function logEquipmentUnassignment($equipment, $unassignedBy = null)
    {
        $actor = $unassignedBy ?? auth()->user();
        
        return self::create([
            'user_id' => $actor?->id,
            'type' => 'equipment_unassigned',
            'description' => sprintf(
                'Unassigned equipment: %s %s (%s)',
                $equipment->brand,
                $equipment->model_number,
                $equipment->serial_number
            ),
        ]);
    }

    /**
     * Log equipment history creation activity
     */
    public static function logEquipmentHistoryCreation($history, $createdBy = null)
    {
        $actor = $createdBy ?? auth()->user();
        
        return self::create([
            'user_id' => $actor?->id,
            'type' => 'equipment_history_created',
            'description' => sprintf(
                'Added history entry for equipment: %s - Action: %s, Status: %s, JO: %s',
                $history->equipment?->serial_number ?? 'Unknown',
                $history->action_taken,
                $history->remarks ?? 'No remarks',
                $history->jo_number ?? 'No JO number'
            ),
        ]);
    }

    /**
     * Log maintenance log creation activity
     */
    public static function logMaintenanceCreation($maintenance, $createdBy = null)
    {
        $actor = $createdBy ?? auth()->user();
        
        return self::create([
            'user_id' => $actor?->id,
            'type' => 'maintenance_created',
            'description' => sprintf(
                'Created maintenance record for equipment: %s - Type: %s, Cost: %s',
                $maintenance->equipment?->serial_number ?? 'Unknown',
                $maintenance->maintenance_type,
                number_format($maintenance->cost, 2)
            ),
        ]);
    }

    /**
     * Log maintenance log update activity
     */
    public static function logMaintenanceUpdate($maintenance, $changes = [], $updatedBy = null)
    {
        $actor = $updatedBy ?? auth()->user();
        
        $description = sprintf(
            'Updated maintenance record for equipment: %s',
            $maintenance->equipment?->serial_number ?? 'Unknown'
        );

        if (!empty($changes)) {
            $changeDetails = [];
            foreach ($changes as $field => $change) {
                if (is_array($change)) {
                    $changeDetails[] = "{$field}: " . implode(' → ', $change);
                } else {
                    $changeDetails[] = "{$field}: {$change}";
                }
            }
            $description .= ' - Changes: ' . implode(', ', $changeDetails);
        }

        return self::create([
            'user_id' => $actor?->id,
            'type' => 'maintenance_updated',
            'description' => $description,
        ]);
    }

    /**
     * Log maintenance log deletion activity
     */
    public static function logMaintenanceDeletion($maintenance, $deletedBy = null)
    {
        $actor = $deletedBy ?? auth()->user();
        
        return self::create([
            'user_id' => $actor?->id,
            'type' => 'maintenance_deleted',
            'description' => sprintf(
                'Deleted maintenance record for equipment: %s - Type: %s',
                $maintenance->equipment?->serial_number ?? 'Unknown',
                $maintenance->maintenance_type
            ),
        ]);
    }

    /**
     * Log office creation activity
     */
    public static function logOfficeCreation($office, $createdBy = null)
    {
        $actor = $createdBy ?? auth()->user();
        
        return self::create([
            'user_id' => $actor?->id,
            'type' => 'office_created',
            'description' => sprintf(
                'Created office: %s - Campus: %s',
                $office->name,
                $office->campus?->name ?? 'Unknown'
            ),
        ]);
    }

    /**
     * Log office update activity
     */
    public static function logOfficeUpdate($office, $changes = [], $updatedBy = null)
    {
        $actor = $updatedBy ?? auth()->user();
        
        $description = sprintf('Updated office: %s', $office->name);

        if (!empty($changes)) {
            $changeDetails = [];
            foreach ($changes as $field => $change) {
                if (is_array($change)) {
                    $changeDetails[] = "{$field}: " . implode(' → ', $change);
                } else {
                    $changeDetails[] = "{$field}: {$change}";
                }
            }
            $description .= ' - Changes: ' . implode(', ', $changeDetails);
        }

        return self::create([
            'user_id' => $actor?->id,
            'type' => 'office_updated',
            'description' => $description,
        ]);
    }

    /**
     * Log office deletion activity
     */
    public static function logOfficeDeletion($office, $deletedBy = null)
    {
        $actor = $deletedBy ?? auth()->user();
        
        return self::create([
            'user_id' => $actor?->id,
            'type' => 'office_deleted',
            'description' => sprintf(
                'Deleted office: %s - Campus: %s',
                $office->name,
                $office->campus?->name ?? 'Unknown'
            ),
        ]);
    }

    /**
     * Log campus creation activity
     */
    public static function logCampusCreation($campus, $createdBy = null)
    {
        $actor = $createdBy ?? auth()->user();
        
        return self::create([
            'user_id' => $actor?->id,
            'type' => 'campus_created',
            'description' => sprintf(
                'Created campus: %s - Location: %s',
                $campus->name,
                $campus->location ?? 'Not specified'
            ),
        ]);
    }

    /**
     * Log campus update activity
     */
    public static function logCampusUpdate($campus, $changes = [], $updatedBy = null)
    {
        $actor = $updatedBy ?? auth()->user();
        
        $description = sprintf('Updated campus: %s', $campus->name);

        if (!empty($changes)) {
            $changeDetails = [];
            foreach ($changes as $field => $change) {
                if (is_array($change)) {
                    $changeDetails[] = "{$field}: " . implode(' → ', $change);
                } else {
                    $changeDetails[] = "{$field}: {$change}";
                }
            }
            $description .= ' - Changes: ' . implode(', ', $changeDetails);
        }

        return self::create([
            'user_id' => $actor?->id,
            'type' => 'campus_updated',
            'description' => $description,
        ]);
    }

    /**
     * Log campus deletion activity
     */
    public static function logCampusDeletion($campus, $deletedBy = null)
    {
        $actor = $deletedBy ?? auth()->user();
        
        return self::create([
            'user_id' => $actor?->id,
            'type' => 'campus_deleted',
            'description' => sprintf(
                'Deleted campus: %s - Location: %s',
                $campus->name,
                $campus->location ?? 'Not specified'
            ),
        ]);
    }

    /**
     * Log category creation activity
     */
    public static function logCategoryCreation($category, $createdBy = null)
    {
        $actor = $createdBy ?? auth()->user();
        
        return self::create([
            'user_id' => $actor?->id,
            'type' => 'category_created',
            'description' => sprintf(
                'Created category: %s - Color: %s',
                $category->name,
                $category->color ?? 'Default'
            ),
        ]);
    }

    /**
     * Log category update activity
     */
    public static function logCategoryUpdate($category, $changes = [], $updatedBy = null)
    {
        $actor = $updatedBy ?? auth()->user();
        
        $description = sprintf('Updated category: %s', $category->name);

        if (!empty($changes)) {
            $changeDetails = [];
            foreach ($changes as $field => $change) {
                if (is_array($change)) {
                    $changeDetails[] = "{$field}: " . implode(' → ', $change);
                } else {
                    $changeDetails[] = "{$field}: {$change}";
                }
            }
            $description .= ' - Changes: ' . implode(', ', $changeDetails);
        }

        return self::create([
            'user_id' => $actor?->id,
            'type' => 'category_updated',
            'description' => $description,
        ]);
    }

    /**
     * Log category deletion activity
     */
    public static function logCategoryDeletion($category, $deletedBy = null)
    {
        $actor = $deletedBy ?? auth()->user();
        
        return self::create([
            'user_id' => $actor?->id,
            'type' => 'category_deleted',
            'description' => sprintf(
                'Deleted category: %s - Color: %s',
                $category->name,
                $category->color ?? 'Default'
            ),
        ]);
    }

    /**
     * Log generic CRUD operation
     */
    public static function logCrudOperation($action, $resourceType, $resource, $details = [], $actor = null)
    {
        $user = $actor ?? auth()->user();
        
        $description = sprintf(
            '%s %s: %s',
            ucfirst($action),
            $resourceType,
            $resource instanceof \Illuminate\Database\Eloquent\Model 
                ? ($resource->name ?? $resource->id ?? 'Unknown')
                : 'Unknown'
        );

        if (!empty($details)) {
            $description .= ' - ' . implode(', ', $details);
        }

        return self::create([
            'user_id' => $user?->id,
            'type' => $action . '_' . $resourceType,
            'description' => $description,
        ]);
    }

    /**
     * Log settings update activity
     */
    public static function logSettingsUpdate($settingsType, $description, $oldValues = [], $newValues = [], $details = null, $actor = null)
    {
        $user = $actor ?? auth()->user();
        
        $fullDescription = sprintf(
            'Updated %s: %s',
            $settingsType,
            $description
        );

        if ($details) {
            $fullDescription .= ' - ' . $details;
        }

        return self::create([
            'user_id' => $user?->id,
            'type' => 'settings_updated',
            'description' => $fullDescription,
        ]);
    }

    /**
     * Log password change activity
     */
    public static function logPasswordChange($user, $changedBy = null)
    {
        $actor = $changedBy ?? auth()->user();
        
        return self::create([
            'user_id' => $actor?->id,
            'type' => 'password_changed',
            'description' => sprintf(
                'Changed password for %s %s (%s)',
                $user->first_name,
                $user->last_name,
                $user->email
            ),
        ]);
    }

    /**
     * Log profile photo update activity
     */
    public static function logProfilePhotoUpdate($user, $updatedBy = null)
    {
        $actor = $updatedBy ?? auth()->user();

        // Try to infer a simple role label for the description
        $roleLabel = 'User';
        if (method_exists($user, 'hasRole')) {
            if ($user->hasRole('technician')) {
                $roleLabel = 'Technician';
            } elseif ($user->hasRole('staff')) {
                $roleLabel = 'Staff';
            } elseif ($user->hasRole('admin') || ($user->is_admin ?? false)) {
                $roleLabel = 'Admin';
            }
        } elseif (property_exists($user, 'is_admin') && $user->is_admin) {
            $roleLabel = 'Admin';
        }

        $description = sprintf('%s profile photo updated', $roleLabel);

        // If someone else updated this user's photo, note it in the description
        if ($actor && $actor->id !== $user->id) {
            $description .= sprintf(
                ' by %s %s (%s)',
                $actor->first_name ?? '',
                $actor->last_name ?? '',
                $actor->email ?? ''
            );
        }

        return self::create([
            'user_id' => $user->id,
            'type' => 'profile_photo_updated',
            'description' => $description,
        ]);
    }

    /**
     * Log QR code scan activity
     */
    public static function logQrCodeScan($equipment, $scannedBy = null)
    {
        $actor = $scannedBy ?? auth()->user();

        if (!$actor) {
            return null;
        }

        return self::create([
            'user_id' => $actor->id,
            'type' => 'equipment_scanned',
            'description' => sprintf(
                'Scanned QR code for equipment: %s %s (%s)',
                $equipment->brand ?? 'Unknown',
                $equipment->model_number ?? $equipment->equipment_model ?? 'Unknown',
                $equipment->serial_number ?? 'Unknown'
            ),
        ]);
    }

    /**
     * Log equipment history update activity
     */
    public static function logEquipmentHistoryUpdate($history, $changes = [], $updatedBy = null)
    {
        $actor = $updatedBy ?? auth()->user();
        
        $description = sprintf(
            'Updated history entry for equipment: %s - Action: %s',
            $history->equipment?->serial_number ?? 'Unknown',
            $history->action_taken ?? 'Unknown'
        );

        if (!empty($changes)) {
            $changeDetails = [];
            foreach ($changes as $field => $change) {
                if (is_array($change)) {
                    $changeDetails[] = "{$field}: " . implode(' → ', $change);
                } else {
                    $changeDetails[] = "{$field}: {$change}";
                }
            }
            $description .= ' - Changes: ' . implode(', ', $changeDetails);
        }

        return self::create([
            'user_id' => $actor?->id,
            'type' => 'equipment_history_updated',
            'description' => $description,
        ]);
    }

    /**
     * Log equipment history correction activity (admin correcting technician's entry)
     */
    public static function logEquipmentHistoryCorrection($history, $originalData, $changes = [], $correctedBy = null)
    {
        $actor = $correctedBy ?? auth()->user();
        
        $description = sprintf(
            'Corrected history entry for equipment: %s (JO: %s) - Original technician: %s',
            $history->equipment?->serial_number ?? 'Unknown',
            $history->jo_number ?? 'No JO',
            $history->user?->name ?? 'Unknown'
        );

        // Add original technician's input details
        $originalDetails = [];
        if (isset($originalData['action_taken'])) {
            $originalDetails[] = "Original action: " . $originalData['action_taken'];
        }
        if (isset($originalData['remarks'])) {
            $originalDetails[] = "Original remarks: " . $originalData['remarks'];
        }

        if (!empty($originalDetails)) {
            $description .= ' - [' . implode(', ', $originalDetails) . ']';
        }

        // Add correction details
        if (!empty($changes)) {
            $changeDetails = [];
            foreach ($changes as $field => $change) {
                if (is_array($change)) {
                    $changeDetails[] = "{$field}: '" . $change[0] . "' → '" . $change[1] . "'";
                } else {
                    $changeDetails[] = "{$field}: {$change}";
                }
            }
            $description .= ' - Corrections: ' . implode(', ', $changeDetails);
        }

        return self::create([
            'user_id' => $actor?->id,
            'type' => 'equipment_history_corrected',
            'description' => $description,
            'equipment_history_id' => $history->id,
        ]);
    }

    /**
     * Log system management activity (categories, equipment types, backups, etc.)
     */
    public static function logSystemManagement($action, $description, $subjectType, $subjectId, $newValues = [], $oldValues = [], $resourceType = null, $actor = null)
    {
        $user = $actor ?? auth()->user();
        
        $fullDescription = sprintf(
            '%s: %s',
            $action,
            $description
        );

        // Add resource type if provided
        if ($resourceType) {
            $fullDescription = sprintf(
                '%s (%s): %s',
                $action,
                $resourceType,
                $description
            );
        }

        return self::create([
            'user_id' => $user?->id,
            'type' => strtolower(str_replace(' ', '_', $action)),
            'description' => $fullDescription,
        ]);
    }
}