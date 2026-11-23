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

    /**
     * The statuses to filter by, in the order a reader looks for them, among those present.
     *
     * @param  list<string>  $presentValues
     * @return list<self>
     */
    public static function listable(array $presentValues): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $status): bool => in_array($status->value, $presentValues, true),
        ));
    }

    public function isApproved(): bool
    {
        return $this === self::Approved || $this === self::PartiallyApproved;
    }

    public function label(): string
    {
        return __('ui.budget.status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Approved => 'bg-republic-100 text-republic-800 dark:bg-republic-950 dark:text-republic-300',
            self::PartiallyApproved => 'bg-republic-50 text-republic-700 dark:bg-republic-950 dark:text-republic-400',
            self::Rejected => 'bg-parliament-100 text-parliament-800 dark:bg-parliament-950 dark:text-parliament-300',
            self::Pending => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
            self::Withdrawn, self::Replaced, self::NotAdmitted, self::Prejudiced => 'bg-sand-100 text-sand-600 dark:bg-sand-900 dark:text-sand-400',
        };
    }
}
