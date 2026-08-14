<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $description
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\EquipmentHistory|null $equipmentHistory
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereUserId($value)
 */
	class Activity extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $code
 * @property string $address
 * @property string|null $contact_number
 * @property string|null $email
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Office> $offices
 * @property-read int|null $offices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campus active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campus newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campus newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campus onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campus query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campus whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campus whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campus whereContactNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campus whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campus whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campus whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campus whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campus whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campus whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campus whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campus withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campus withoutTrashed()
 */
	class Campus extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Equipment> $equipment
 * @property-read int $equipment_count
 * @property-read int $available_equipment_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category withEquipmentCount()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category withoutTrashed()
 */
	class Category extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $brand
 * @property string $model_number
 * @property string $serial_number
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $purchase_date
 * @property numeric|null $cost_of_purchase
 * @property string|null $condition
 * @property string $status
 * @property int $office_id
 * @property int|null $category_id
 * @property int|null $equipment_type_id
 * @property int|null $assigned_by_id
 * @property string|null $qr_code
 * @property string|null $qr_code_image_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Campus|null $campus
 * @property-read \App\Models\Category|null $category
 * @property-read \App\Models\EquipmentType|null $equipmentType
 * @property-read mixed $equipment_model
 * @property-read mixed $full_model
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EquipmentHistory> $history
 * @property-read int|null $history_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MaintenanceLog> $maintenanceLogs
 * @property-read int|null $maintenance_logs_count
 * @property-read \App\Models\Office|null $office
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment available()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment byStatus($status)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereAssignedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereCondition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereCostOfPurchase($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereEquipmentTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereModelNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereOfficeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment wherePurchaseDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereQrCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereQrCodeImagePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment withoutTrashed()
 */
	class Equipment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $equipment_id
 * @property \Illuminate\Support\Carbon|null $date
 * @property string|null $jo_number Job Order Number
 * @property string $action_taken
 * @property string|null $remarks
 * @property string $responsible_person
 * @property int|null $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Activity> $corrections
 * @property-read int|null $corrections_count
 * @property-read \App\Models\Equipment|null $equipment
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentHistory whereActionTaken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentHistory whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentHistory whereEquipmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentHistory whereJoNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentHistory whereRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentHistory whereResponsiblePerson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentHistory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentHistory whereUserId($value)
 */
	class EquipmentHistory extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Equipment> $equipment
 * @property-read int|null $equipment_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentType whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentType whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentType whereUpdatedAt($value)
 */
	class EquipmentType extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read \App\Models\Equipment|null $equipment
 * @property-read string $priority_label
 * @property-read string $status_label
 * @property-read string $type_label
 * @property-read \App\Models\User|null $technician
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaintenanceLog byPriority($priority)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaintenanceLog byStatus($status)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaintenanceLog byTechnician($technicianId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaintenanceLog byType($type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaintenanceLog completed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaintenanceLog emergency()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaintenanceLog forEquipment($equipmentId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaintenanceLog highPriority()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaintenanceLog inProgress()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaintenanceLog inspections()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaintenanceLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaintenanceLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaintenanceLog onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaintenanceLog overdue()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaintenanceLog pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaintenanceLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaintenanceLog repairs()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaintenanceLog scheduled()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaintenanceLog withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaintenanceLog withoutTrashed()
 */
	class MaintenanceLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $location
 * @property string|null $contact_number
 * @property string|null $email
 * @property int $campus_id
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Campus|null $campus
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Equipment> $equipment
 * @property-read int|null $equipment_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Staff> $staff
 * @property-read int|null $staff_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Office active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Office newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Office newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Office onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Office query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Office whereCampusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Office whereContactNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Office whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Office whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Office whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Office whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Office whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Office whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Office whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Office whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Office withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Office withoutTrashed()
 */
	class Office extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $email
 * @property int|null $user_id
 * @property string $token
 * @property string $otp
 * @property \Illuminate\Support\Carbon $expires_at
 * @property bool $is_used
 * @property \Illuminate\Support\Carbon|null $used_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordResetOtp newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordResetOtp newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordResetOtp query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordResetOtp whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordResetOtp whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordResetOtp whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordResetOtp whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordResetOtp whereIsUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordResetOtp whereOtp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordResetOtp whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordResetOtp whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordResetOtp whereUsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordResetOtp whereUserId($value)
 */
	class PasswordResetOtp extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read \App\Models\User|null $processedBy
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordResetRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordResetRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordResetRequest pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PasswordResetRequest query()
 */
	class PasswordResetRequest extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $display_name
 * @property string|null $description
 * @property string|null $group
 * @property int $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
 * @property-read int|null $roles_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereDisplayName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereUpdatedAt($value)
 */
	class Permission extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $display_name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereDisplayName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereUpdatedAt($value)
 */
	class Role extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property string $type
 * @property string|null $description
 * @property int $is_public
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereIsPublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereValue($value)
 */
	class Setting extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read \App\Models\Campus|null $campus
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Equipment> $equipment
 * @property-read int|null $equipment_count
 * @property-read string $profile_photo_url
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\Office|null $office
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Staff active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Staff newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Staff newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Staff query()
 */
	class Staff extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read string $profile_photo_url
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\Staff|null $staff
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technician newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technician newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Technician query()
 */
	class Technician extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $role_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $password
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $position
 * @property int|null $office_id
 * @property int|null $campus_id
 * @property bool $is_active
 * @property int $is_available
 * @property string|null $employee_id
 * @property string|null $profile_photo_path
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property int $must_change_password
 * @property string|null $password_changed_at
 * @property string|null $email_verification_token
 * @property string|null $email_verification_token_expires_at
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Campus|null $campus
 * @property-read bool $is_admin
 * @property-read bool $is_staff
 * @property-read bool $is_super_admin
 * @property-read bool $is_technician
 * @property-read string $name
 * @property-read mixed $role_names
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\Office|null $office
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PasswordResetRequest> $passwordResetRequests
 * @property-read int|null $password_reset_requests_count
 * @property-read \App\Models\Role|null $role
 * @property-read \App\Models\Technician|null $technician
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCampusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerificationToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerificationTokenExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereMustChangePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereOfficeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePasswordChangedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereProfilePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 */
	class User extends \Eloquent {}
}

