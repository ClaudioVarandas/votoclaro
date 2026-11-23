<?php

namespace App\Models;

use App\Enums\BudgetVotePosition as Position;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How a parliamentary group voted, or a single deputy who voted apart from it.
 */
class BudgetVotePosition extends Model
{
    protected $fillable = [
        'budget_vote_id',
        'party',
        'deputy',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => Position::class,
        ];
    }

    public function vote(): BelongsTo
    {
        return $this->belongsTo(BudgetVote::class, 'budget_vote_id');
    }
}
