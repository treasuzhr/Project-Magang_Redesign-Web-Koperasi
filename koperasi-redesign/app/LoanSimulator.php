<?php

namespace App;

use Carbon\CarbonImmutable;

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

    /** @return array<int, array<string, int|string|null>> */
    public function schedule(int $amount, int $term, string $monthlyRate, CarbonImmutable $disbursedAt): array
    {
        if ($amount < 1 || $term < 1) {
            throw new \InvalidArgumentException('Jumlah dan tenor harus lebih dari nol.');
        }
        $interest = $this->calculate($amount, $term, $monthlyRate)['interest'];
        $regularPrincipal = intdiv($amount, $term);
        $rows = [];
        for ($number = 1; $number <= $term; $number++) {
            $principal = $number === $term ? $amount - $regularPrincipal * ($term - 1) : $regularPrincipal;
            $rows[] = [
                'angsuran_ke' => $number, 'jatuh_tempo' => $disbursedAt->addMonthsNoOverflow($number)->toDateString(),
                'pokok' => $principal, 'bunga' => $interest, 'total' => $principal + $interest,
                'status' => 'belum', 'tanggal_bayar' => null,
            ];
        }

        return $rows;
    }
}
