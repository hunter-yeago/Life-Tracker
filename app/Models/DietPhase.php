<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DietPhase extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'phase_type',
        'notes',
        'target_calories',
        'target_protein',
        'current_weight',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'target_calories' => 'decimal:2',
            'target_protein' => 'decimal:2',
            'current_weight' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
