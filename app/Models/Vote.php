<?php

namespace App\Models;

use App\Enums\VoteResult;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vote extends Model
{
    /** The vote that passes or rejects a bill as a whole, at the end of the legislative process. */
    public const PHASE_FINAL_GLOBAL = 'Votação final global';

    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'initiative_id',
        'date',
        'result',
        'phase',
        'unanimous',
        'is_latest',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'result' => VoteResult::class,
            'unanimous' => 'boolean',
            'is_latest' => 'boolean',
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
