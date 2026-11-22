<?php

namespace App\Enums;

enum InitiativeStatus: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';
    case InProgress = 'in_progress';
    case Lapsed = 'lapsed';
    case Withdrawn = 'withdrawn';

    /**
     * @return list<self>
     */
    public static function core(): array
    {
        return [self::Approved, self::Rejected, self::InProgress];
    }

    /**
     * The statuses to offer as filters: the core ones always, lapsed and withdrawn only when some
     * initiatives have them, as happens once a legislature or a government ends.
     *
     * @param  list<string>  $presentValues
     * @return list<self>
     */
    public static function listable(array $presentValues): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $status): bool => in_array($status, self::core(), true)
                || in_array($status->value, $presentValues, true),
        ));
    }

    public function label(): string
    {
        return __('ui.status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Approved => 'bg-republic-100 text-republic-800 dark:bg-republic-950 dark:text-republic-300',
            self::Rejected => 'bg-parliament-100 text-parliament-800 dark:bg-parliament-950 dark:text-parliament-300',
            self::InProgress => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
            self::Lapsed => 'bg-sand-200 text-sand-700 dark:bg-sand-800 dark:text-sand-300',
            self::Withdrawn => 'bg-sand-100 text-sand-600 dark:bg-sand-900 dark:text-sand-400',
        };
    }
}
