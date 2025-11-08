<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'equipment_id',
        'user_id',
        'maintenance_type',
        'description',
        'status',
        'scheduled_date',
        'completed_date',
        'priority',
        'cost',
        'parts_used',
        'work_performed',
        'recommendations',
        'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_date' => 'date',
        'cost' => 'decimal:2',
        'priority' => 'integer',
        'parts_used' => 'array',
    ];

    /**
     * Maintenance types
     */
    const TYPE_SCHEDULED = 'scheduled';
    const TYPE_REPAIR = 'repair';
    const TYPE_EMERGENCY = 'emergency';
    const TYPE_INSPECTION = 'inspection';

    /**
     * Maintenance statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Priority levels
     */
    const PRIORITY_HIGH = 1;
    const PRIORITY_MEDIUM = 2;
    const PRIORITY_LOW = 3;

    /**
     * Get the equipment that this maintenance log belongs to.
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * Get the technician who performed the maintenance.
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope to get maintenance logs by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('maintenance_type', $type);
    }

    /**
     * Scope to get maintenance logs by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get maintenance logs by priority
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to get high priority maintenance logs
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', self::PRIORITY_HIGH);
    }

    /**
     * Scope to get pending maintenance logs
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get completed maintenance logs
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to get in-progress maintenance logs
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    /**
     * Scope to get scheduled maintenance logs
     */
    public function scopeScheduled($query)
    {
        return $query->where('maintenance_type', self::TYPE_SCHEDULED);
    }

    /**
     * Scope to get emergency maintenance logs
     */
    public function scopeEmergency($query)
    {
        return $query->where('maintenance_type', self::TYPE_EMERGENCY);
    }

    /**
     * Scope to get repair maintenance logs
     */
    public function scopeRepairs($query)
    {
        return $query->where('maintenance_type', self::TYPE_REPAIR);
    }

    /**
     * Scope to get inspection maintenance logs
     */
    public function scopeInspections($query)
    {
        return $query->where('maintenance_type', self::TYPE_INSPECTION);
    }

    /**
     * Scope to get overdue maintenance (scheduled but not completed)
     */
    public function scopeOverdue($query)
    {
        return $query->where('scheduled_date', '<', now())
                    ->whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_PROGRESS]);
    }

    /**
     * Scope to get maintenance logs for a specific equipment
     */
    public function scopeForEquipment($query, $equipmentId)
    {
        return $query->where('equipment_id', $equipmentId);
    }

    /**
     * Scope to get maintenance logs by technician
     */
    public function scopeByTechnician($query, $technicianId)
    {
        return $query->where('user_id', $technicianId);
    }

    /**
     * Get the priority label
     */
    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_LOW => 'Low',
            default => 'Unknown'
        };
    }

    /**
     * Get the status label with color
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            default => 'Unknown'
        };
    }

    /**
     * Get the maintenance type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->maintenance_type) {
            self::TYPE_SCHEDULED => 'Scheduled',
            self::TYPE_REPAIR => 'Repair',
            self::TYPE_EMERGENCY => 'Emergency',
            self::TYPE_INSPECTION => 'Inspection',
            default => 'Unknown'
        };
    }

    /**
     * Check if maintenance is overdue
     */
    public function isOverdue(): bool
    {
        return $this->scheduled_date < now()->toDateString()
               && in_array($this->status, [self::STATUS_PENDING, self::STATUS_IN_PROGRESS]);
    }

    /**
     * Check if maintenance is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if maintenance is in progress
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }
}
