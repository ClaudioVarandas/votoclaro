<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Party extends Model
{
    /** @use HasFactory<\Database\Factories\PartyFactory> */
    use HasFactory;

    protected $fillable = ['acronym', 'name'];

    public function legislatures(): BelongsToMany
    {
        return $this->belongsToMany(Legislature::class)->withTimestamps();
    }
}
