<?php

namespace App\Enums;

enum BudgetVoteStage: string
{
    case Committee = 'committee';
    case Plenary = 'plenary';

    /**
     * Budget amendments are voted in the budget committee; the ones called up ("avocadas") are
     * voted again in the plenary, and only then does the result say so.
     */
    public static function fromResultLabel(?string $label): self
    {
        return str_contains(mb_strtolower((string) $label), 'plenário') ? self::Plenary : self::Committee;
    }
}
