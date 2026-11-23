<?php

namespace App\Models;

use App\Enums\BudgetProposalStatus;
use App\Enums\BudgetVoteStage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetVote extends Model
{
    /** @use HasFactory<\Database\Factories\BudgetVoteFactory> */
    use HasFactory;

    protected $fillable = [
        'budget_proposal_id',
        'sequence',
        'stage',
        'date',
        'result',
        'result_label',
        'subject',
        'is_final',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'stage' => BudgetVoteStage::class,
            'date' => 'date',
            'result' => BudgetProposalStatus::class,
            'is_final' => 'boolean',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(BudgetProposal::class, 'budget_proposal_id');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(BudgetVotePosition::class);
    }
}
