<?php

namespace App\Models;

use App\Enums\BudgetProposalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An article of the Government's budget bill, as the Parliament votes it in the detailed stage.
 */
class BudgetArticle extends Model
{
    /** @use HasFactory<\Database\Factories\BudgetArticleFactory> */
    use HasFactory;

    public $incrementing = false;

    protected $fillable = [
        'id',
        'budget_id',
        'position',
        'number',
        'number_slug',
        'slug',
        'title',
        'text',
        'status',
        'status_label',
        'law_title',
        'law_subtitle',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'status' => BudgetProposalStatus::class,
        ];
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    /**
     * The amendments proposed to this article.
     */
    public function proposals(): BelongsToMany
    {
        return $this->belongsToMany(BudgetProposal::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(BudgetVote::class)->orderBy('sequence');
    }

    public function finalVotes(): HasMany
    {
        return $this->hasMany(BudgetVote::class)->where('is_final', true)->orderBy('sequence');
    }

    public function amendsLaw(): bool
    {
        return $this->law_title !== null;
    }
}
