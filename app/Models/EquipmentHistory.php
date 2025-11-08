<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
