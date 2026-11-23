<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * What a group of a budget's amendments is about, found in their subjects on every import
 * (see BudgetTopicExtractor). Unlike the themes, nobody files the amendments under it.
 */
class BudgetTopic extends Model
{
    /** @use HasFactory<\Database\Factories\BudgetTopicFactory> */
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'slug',
        'label',
        'proposals_count',
        'parties_count',
        'voted_count',
        'division',
    ];

    protected function casts(): array
    {
        return [
            'proposals_count' => 'integer',
            'parties_count' => 'integer',
            'voted_count' => 'integer',
            'division' => 'float',
        ];
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function proposals(): BelongsToMany
    {
        return $this->belongsToMany(BudgetProposal::class);
    }
}
