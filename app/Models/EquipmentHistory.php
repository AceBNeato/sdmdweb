<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentHistory extends Model
{
    protected $table = 'equipment_history';

    protected $fillable = [
        'equipment_id',
        'date',
        'jo_number',
        'action_taken',
        'remarks',
        'responsible_person',
        'user_id'
    ];

    protected $casts = [
        'date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the equipment that owns the history record.
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * Get the user who created the history record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the correction activities for this history entry.
     */
    public function corrections(): HasMany
    {
        return $this->hasMany(Activity::class, 'equipment_history_id')
                    ->where('type', 'equipment_history_corrected')
                    ->with('user');
    }
}
