<?php

namespace App\Models;

use App\Enums\BudgetProposalStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetProposal extends Model
{
    /** @use HasFactory<\Database\Factories\BudgetProposalFactory> */
    use HasFactory;

    public $incrementing = false;

    protected $fillable = [
        'id',
        'budget_id',
        'number',
        'slug',
        'title',
        'type',
        'scope',
        'status',
        'status_label',
        'proposer_label',
        'submitted_at',
        'pdf_url',
        'text',
    ];

    protected function casts(): array
    {
        return [
            'status' => BudgetProposalStatus::class,
            'submitted_at' => 'datetime',
        ];
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function parties(): BelongsToMany
    {
        return $this->belongsToMany(Party::class)->withTimestamps();
    }

    public function themes(): BelongsToMany
    {
        return $this->belongsToMany(BudgetTheme::class)->withTimestamps();
    }

    public function votes(): HasMany
    {
        return $this->hasMany(BudgetVote::class)->orderBy('sequence');
    }

    /**
     * Matches whole words, ignoring case and accents, and lets each word be the start of a
     * longer one ("habita" finds "habitação"). MySQL does it with its full-text index; the
     * SQLite fallback only exists for tests that do not exercise the search itself.
     */
    public function scopeSearch(Builder $query, string $terms): void
    {
        $words = preg_split('/\s+/u', trim(preg_replace('/[+\-<>()~*"@]+/u', ' ', $terms)), -1, PREG_SPLIT_NO_EMPTY);

        if ($words === []) {
            return;
        }

        if ($query->getConnection()->getDriverName() === 'mysql') {
            $query->whereFullText(['title', 'text'], collect($words)->map(fn (string $word): string => "+{$word}*")->implode(' '), ['mode' => 'boolean']);

            return;
        }

        foreach ($words as $word) {
            $query->where('title', 'like', "%{$word}%");
        }
    }
}
