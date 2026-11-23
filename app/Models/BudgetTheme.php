<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A theme as the Parliament files a budget amendment: the area of a parliamentary committee.
 * The committees, and so the names, change between legislatures.
 */
class BudgetTheme extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    public function proposals(): BelongsToMany
    {
        return $this->belongsToMany(BudgetProposal::class)->withTimestamps();
    }
}
