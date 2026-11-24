<?php

namespace App\Models;

use App\Enums\BudgetProposalStatus;
use App\Enums\BudgetVotePosition;
use App\Models\BudgetVotePosition as PartyPosition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;

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

    /**
     * What the amendment is about. The Parliament titles most as "Artigo 119.º-A (Subject)";
     * the subject is what a reader looks for, the article is where it goes.
     */
    public function subject(): string
    {
        return preg_match('/\((.+)\)\s*$/u', $this->title, $matches) ? $matches[1] : $this->title;
    }

    /**
     * Where in the budget it goes, when the title says so apart from the subject.
     */
    public function location(): ?string
    {
        return preg_match('/^(.+?)\s*\((.+)\)\s*$/u', $this->title, $matches) ? $matches[1] : null;
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

    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(BudgetTopic::class);
    }

    /**
     * The articles of the Government's bill this amendment changes.
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(BudgetArticle::class)->orderBy('position');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(BudgetVote::class)->orderBy('sequence');
    }

    /**
     * The votes that decided it: the plenary's when it was called up there, the committee's
     * otherwise. A proposal voted in parts has one per part.
     */
    public function finalVotes(): HasMany
    {
        return $this->hasMany(BudgetVote::class)->where('is_final', true)->orderBy('sequence');
    }

    /**
     * Passed against the government: a vote that decided it approved it, or part of it, while
     * every government party voted against. A government split between itself does not count.
     *
     * @param  list<string>  $governmentAcronyms
     */
    public function scopeApprovedAgainstGovernment(Builder $query, array $governmentAcronyms): void
    {
        if ($governmentAcronyms === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $approved = [BudgetProposalStatus::Approved, BudgetProposalStatus::PartiallyApproved];

        $query->whereIn('status', $approved)->whereHas('finalVotes', fn (Builder $votes) => $votes
            ->whereIn('result', $approved)
            ->whereHas('positions', fn (Builder $positions) => $positions
                ->whereNull('deputy')
                ->whereIn('party', $governmentAcronyms)
                ->where('position', BudgetVotePosition::Contra), '=', count($governmentAcronyms)));
    }

    /**
     * The amendments two parties voted differently in the vote that decided them, counted the way
     * the agreement between parties counts them: each party's cell is its position, split when it
     * did not vote all the parts alike; a split cell or an absence leaves the amendment out.
     */
    public function scopeVotedApart(Builder $query, string $first, string $second): void
    {
        $cell = fn (string $party): Builder => PartyPosition::query()
            ->join('budget_votes', 'budget_votes.id', '=', 'budget_vote_positions.budget_vote_id')
            ->where('budget_votes.is_final', true)
            ->whereNotNull('budget_votes.budget_proposal_id')
            ->whereNull('budget_vote_positions.deputy')
            ->where('budget_vote_positions.party', $party)
            ->groupBy('budget_votes.budget_proposal_id')
            ->havingRaw('count(distinct budget_vote_positions.position) = 1')
            ->selectRaw('budget_votes.budget_proposal_id, max(budget_vote_positions.position) as position');

        $query->whereIn('budget_proposals.id', fn (QueryBuilder $apart) => $apart
            ->fromSub($cell($first), 'first_party')
            ->joinSub($cell($second), 'second_party', 'second_party.budget_proposal_id', '=', 'first_party.budget_proposal_id')
            ->whereColumn('first_party.position', '!=', 'second_party.position')
            ->where('first_party.position', '!=', BudgetVotePosition::Ausente->value)
            ->where('second_party.position', '!=', BudgetVotePosition::Ausente->value)
            ->select('first_party.budget_proposal_id'));
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
