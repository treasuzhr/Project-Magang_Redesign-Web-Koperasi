<?php

namespace App;

class LoanSimulator
{
    public static function normalizeAmount(mixed $value): ?string
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }
        $value = trim((string) $value);
        $value = preg_replace('/^Rp\s*/i', '', $value);
        if (! preg_match('/^(?:[0-9]+|[0-9]{1,3}(?:\.[0-9]{3})+|[0-9]{1,3}(?:,[0-9]{3})+|[0-9]{1,3}(?: [0-9]{3})+)$/D', $value)) {
            return null;
        }

        return ltrim(str_replace(['.', ',', ' '], '', $value), '0') ?: '0';
    }

    /** @return array{principal: int, interest: int, total: int} */
    public function calculate(int $amount, int $term, string $monthlyRate): array
    {
        $principal = (int) round($amount / $term);
        $interest = (int) round($amount * (float) $monthlyRate / 100);

        return ['principal' => $principal, 'interest' => $interest, 'total' => $principal + $interest];
    }
}
