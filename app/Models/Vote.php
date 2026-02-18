<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vote extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'initiative_id',
        'date',
        'result',
        'unanimous',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'unanimous' => 'boolean',
        ];
    }

    public function initiative(): BelongsTo
    {
        return $this->belongsTo(Initiative::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(VotePosition::class);
    }
}
