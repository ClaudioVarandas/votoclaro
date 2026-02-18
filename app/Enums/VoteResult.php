<?php

namespace App\Enums;

enum VoteResult: string
{
    case Aprovado = 'Aprovado';
    case Rejeitado = 'Rejeitado';
    case Prejudicado = 'Prejudicado';

    public function color(): string
    {
        return match ($this) {
            self::Aprovado => 'bg-republic-100 text-republic-800 dark:bg-republic-950 dark:text-republic-300',
            self::Rejeitado => 'bg-parliament-100 text-parliament-800 dark:bg-parliament-950 dark:text-parliament-300',
            self::Prejudicado => 'bg-sand-100 text-sand-800 dark:bg-sand-800 dark:text-sand-300',
        };
    }

    public function label(): string
    {
        return __('ui.vote_result.'.$this->value);
    }
}
