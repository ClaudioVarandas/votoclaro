<?php

namespace App\Enums;

enum BudgetProposalStatus: string
{
    case Approved = 'approved';
    case PartiallyApproved = 'partially_approved';
    case Rejected = 'rejected';
    case Pending = 'pending';
    case Withdrawn = 'withdrawn';
    case Replaced = 'replaced';
    case NotAdmitted = 'not_admitted';
    case Prejudiced = 'prejudiced';

    /**
     * Reads the state the Parliament writes, such as "Rejeitado(a) em Comissão". An amendment
     * called up to the plenary ("Avocado(a)") or sent there is still waiting for its vote.
     * A state it has not used before counts as pending, so the import never stops on one.
     */
    public static function fromLabel(?string $label): self
    {
        $label = mb_strtolower(trim((string) $label));

        return match (true) {
            str_starts_with($label, 'aprovado(a) parcialmente') => self::PartiallyApproved,
            str_starts_with($label, 'aprovado') => self::Approved,
            str_starts_with($label, 'rejeitado') => self::Rejected,
            str_starts_with($label, 'retirado') => self::Withdrawn,
            str_starts_with($label, 'substituído') => self::Replaced,
            str_starts_with($label, 'não admitid') => self::NotAdmitted,
            str_starts_with($label, 'prejudicado') => self::Prejudiced,
            default => self::Pending,
        };
    }
}
