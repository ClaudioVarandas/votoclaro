<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    /** @use HasFactory<\Database\Factories\BudgetFactory> */
    use HasFactory;

    public const KIND_ORDINARY = 'Or';

    protected $fillable = [
        'legislature_id',
        'year',
        'kind',
        'initiative_id',
        'proposals_url',
        'budget_url',
        'party_order',
        'last_imported_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'party_order' => 'array',
            'last_imported_at' => 'datetime',
        ];
    }

    public function legislature(): BelongsTo
    {
        return $this->belongsTo(Legislature::class);
    }

    public function initiative(): BelongsTo
    {
        return $this->belongsTo(Initiative::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(BudgetProposal::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(BudgetArticle::class)->orderBy('position');
    }

    public function topics(): HasMany
    {
        return $this->hasMany(BudgetTopic::class);
    }
}
