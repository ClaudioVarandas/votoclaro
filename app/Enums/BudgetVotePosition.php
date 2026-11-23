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

    public function label(): string
    {
        return __('ui.budget.position.'.$this->value);
    }

    /**
     * Shown in the vote grid next to the colour, so the grid reads without it too.
     */
    public function symbol(): string
    {
        return match ($this) {
            self::Favor => '✓',
            self::Contra => '✗',
            self::Abstencao => '—',
            self::Ausente => '·',
        };
    }
}
