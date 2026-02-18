<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Initiative extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'title',
        'status',
        'entry_date',
        'final_vote_date',
        'days_to_approval',
        'last_synced_at',
        'author_category',
        'author_party',
        'author_label',
        'initiative_type',
        'initiative_type_label',
    ];

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'final_vote_date' => 'date',
            'days_to_approval' => 'integer',
        ];
    }

    /**
     * @return Attribute<int|null, never>
     */
    protected function daysInProgress(): Attribute
    {
        return Attribute::make(
            get: function (): ?int {
                if ($this->status !== 'in_progress' || ! $this->entry_date) {
                    return null;
                }

                return $this->entry_date->diffInDays(Carbon::now());
            },
        );
    }

    /**
     * @return Attribute<int|null, never>
     */
    protected function daysToRejection(): Attribute
    {
        return Attribute::make(
            get: function (): ?int {
                if ($this->status !== 'rejected' || ! $this->entry_date || ! $this->final_vote_date) {
                    return null;
                }

                return $this->entry_date->diffInDays($this->final_vote_date);
            },
        );
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function initiativeTypeKey(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if (! $this->initiative_type_label) {
                    return null;
                }

                return str($this->initiative_type_label)->ascii()->lower()->replace(' ', '_')->toString();
            },
        );
    }

    public function scopeFilterByStatus(Builder $query, ?string $status): Builder
    {
        if ($status) {
            $query->where('status', $status);
        }

        return $query;
    }

    public function scopeFilterByAuthorCategory(Builder $query, ?string $authorCategory): Builder
    {
        if ($authorCategory) {
            $query->where('author_category', $authorCategory);
        }

        return $query;
    }

    public function scopeFilterBySearch(Builder $query, ?string $search): Builder
    {
        if ($search) {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
