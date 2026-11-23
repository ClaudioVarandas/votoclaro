<?php

namespace App\Enums;

enum BudgetVotePosition: string
{
    case Favor = 'favor';
    case Contra = 'contra';
    case Abstencao = 'abstencao';
    case Ausente = 'ausente';

    public static function fromLabel(string $label): ?self
    {
        return match (mb_strtolower(trim($label))) {
            'favor' => self::Favor,
            'contra' => self::Contra,
            'abstenção', 'abstencao' => self::Abstencao,
            'ausente' => self::Ausente,
            default => null,
        };
    }
}
