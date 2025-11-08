<?php

namespace App\Models;

use App\Models\Campus;
use App\Models\EquipmentType;
use App\Models\MaintenanceLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Equipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'model_number',
        'serial_number',
        'equipment_type_id',
        'description',
        'purchase_date',
        'cost_of_purchase',
        'condition',
        'qr_code',
        'qr_code_image_path',
        'office_id',
        'campus_id',
        'category_id',
        'status',
        'notes',
        'assigned_by_id',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'cost_of_purchase' => 'decimal:2',
    ];

    /**
     * Get the history records for the equipment.
     */
    public function history(): HasMany
    {
        return $this->hasMany(EquipmentHistory::class)->latest();
    }

    /**
     * Equipment statuses
     */
    const STATUS_SERVICEABLE = 'serviceable';
    const STATUS_FOR_REPAIR = 'for_repair';
    const STATUS_DEFECTIVE = 'defective';

    /**
     * Get the office that owns the equipment.
     */
    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * Equipment conditions
     */
    const CONDITION_GOOD = 'good';
    const CONDITION_NOT_WORKING = 'not_working';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($equipment) {
            // Generate a unique QR code for each equipment
            if (empty($equipment->qr_code)) {
                $equipment->qr_code = 'EQP-' . Str::upper(Str::random(8));
            }

            // Set default status
            if (empty($equipment->status)) {
                $equipment->status = self::STATUS_SERVICEABLE;
            }
        });

        static::forceDeleting(function ($equipment) {
            // Clean up QR code image file when equipment is permanently deleted
            if ($equipment->qr_code_image_path && Storage::disk('public')->exists($equipment->qr_code_image_path)) {
                Storage::disk('public')->delete($equipment->qr_code_image_path);
            }
        });
    }

    /**
     * Get the campus that owns the equipment (through office).
     */
    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the category that the equipment belongs to.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the equipment type that the equipment belongs to.
     */
    public function equipmentType()
    {
        return $this->belongsTo(EquipmentType::class, 'equipment_type_id');
    }

    /**
     * Get the maintenance logs for the equipment.
     */
    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class);
    }

    /**
     * Check if equipment is available for assignment.
     */
    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_SERVICEABLE;
    }

    /**
     * Generate QR code for the equipment
     */
    public function generateQrCode($size = 300)
    {
        $data = [
            'id' => $this->id,
            'model' => $this->model_number,
            'serial' => $this->serial_number,
            'type' => $this->equipmentType?->name ?? 'Unknown',
            'office' => $this->office?->name ?? 'N/A',
            'status' => $this->status,
            'last_updated' => now()->toDateTimeString()
        ];

        return QrCode::size($size)
            ->format('svg')
            ->generate(json_encode($data));
    }

    /**
     * Get the QR code URL for the equipment
     */
    public function getQrCodeUrl()
    {
        return route('admin.equipment.qrcode', $this->id);
    }

    /**
     * Get the download URL for the QR code
     */
    public function getQrCodeDownloadUrl()
    {
        return route('admin.equipment.download-qrcode', $this->id);
    }

    /**
     * Get the print URL for the QR code
     */
    public function getQrCodePrintUrl()
    {
        return route('admin.equipment.print-qrcode', $this->id);
    }

    /**
     * Scope to get available equipment
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_SERVICEABLE);
    }

    /**
     * Scope to get equipment by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
