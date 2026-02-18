<?php

namespace App\Services;

class VoteParser
{
    public function parse(string $detail): array
    {
        $positions = [];

        // Normalize line breaks
        $detail = preg_replace('/<br\s*\/?>/i', "\n", $detail);

        // Remove all HTML tags
        $clean = strip_tags($detail);

        // Normalize spacing
        $clean = trim(preg_replace('/\s+/', ' ', $clean));

        // Split into logical lines
        $lines = explode("\n", $clean);

        foreach ($lines as $line) {

            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (str_starts_with($line, 'A Favor')) {
                $this->assignPosition($positions, $line, 'favor');
            }

            if (str_starts_with($line, 'Contra')) {
                $this->assignPosition($positions, $line, 'contra');
            }

            if (str_starts_with($line, 'Abstenção')) {
                $this->assignPosition($positions, $line, 'abstencao');
            }
        }

        return $positions;
    }

    private function assignPosition(array &$positions, string $line, string $type): void
    {
        // Remove label
        $line = preg_replace('/^(A Favor:|Contra:|Abstenção:)/u', '', $line);

        // Split by comma
        $parties = explode(',', $line);

        foreach ($parties as $party) {

            $party = trim($party);

            if ($party === '') {
                continue;
            }

            // Remove accidental trailing punctuation
            $party = rtrim($party, '.;');

            $positions[$party] = $type;
        }
    }
}
