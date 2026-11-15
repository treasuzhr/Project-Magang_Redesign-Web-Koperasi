<?php

namespace App;

use App\Models\Anggota;
use App\Models\SimpananTransaksi;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavingsCsvImporter
{
    public const CHUNK_SIZE = 100;

    public function import(string $path): array
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        if (! is_file($path) || ! is_readable($path)) {
            throw ValidationException::withMessages(['csv' => 'File CSV tidak dapat dibaca.']);
        }
        $stream = fopen($path, 'rb');
        $result = ['created' => 0, 'skipped' => 0, 'errors' => []];
        try {
            $header = fgetcsv($stream, 0, ',', '"', '');
            if ($header === false) {
                throw ValidationException::withMessages(['csv' => 'CSV kosong.']);
            }
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0] ?? '');
            $header = array_map(fn (?string $value): string => strtolower(trim($value ?? '')), $header);
            if (count($header) !== 5 || count(array_unique($header)) !== 5 || array_diff(['nip', 'jenis', 'jumlah', 'tanggal', 'keterangan'], $header)) {
                throw ValidationException::withMessages(['csv' => 'Kolom CSV harus nip, jenis, jumlah, tanggal, keterangan.']);
            }
            $batch = [];
            $line = 1;
            while (($values = fgetcsv($stream, 0, ',', '"', '')) !== false) {
                $line++;
                if ($line > 10001) {
                    throw ValidationException::withMessages(['csv' => 'CSV maksimal 10000 baris.']);
                }
                if ($values === [null]) {
                    continue;
                }
                $batch[] = ['row' => $line, 'data' => count($values) === 5 && mb_check_encoding(implode('', $values), 'UTF-8') ? array_combine($header, array_map('trim', $values)) : null];
                if (count($batch) === self::CHUNK_SIZE) {
                    $this->chunk($batch, $result);
                    $batch = [];
                }
            }
            if ($batch !== []) {
                $this->chunk($batch, $result);
            }

            return $result;
        } finally {
            fclose($stream);
        }
    }

    private function chunk(array $rows, array &$result): void
    {
        $chunk = DB::transaction(function () use ($rows): array {
            $counts = ['created' => 0, 'skipped' => 0, 'errors' => []];
            foreach ($rows as $row) {
                $data = $row['data'];
                $reason = null;
                if ($data === null) {
                    $reason = 'Jumlah kolom atau encoding tidak sesuai.';
                } else {
                    $member = Anggota::whereHas('user', fn ($query) => $query->where('nip', $data['nip'])->where('role', 'anggota'))->lockForUpdate()->first();
                    if ($member === null) {
                        $reason = 'NIP/NRP tidak dikenal.';
                    } else {
                        try {
                            $validated = app(AdminSavings::class)->validate(['anggota_id' => $member->id, 'jenis' => $data['jenis'], 'tipe' => 'setor', 'nominal' => $data['jumlah'], 'tanggal' => $data['tanggal'], 'keterangan' => $data['keterangan']]);
                            $duplicate = $member->simpananTransaksis()->where('jenis', $validated['jenis'])->where('nominal', $validated['nominal'])->whereDate('tanggal', $validated['tanggal'])->exists();
                            if ($duplicate) {
                                $reason = 'sudah ada';
                            } else {
                                SimpananTransaksi::create($validated + ['sumber' => 'potong_gaji']);
                                $counts['created']++;
                            }
                        } catch (ValidationException $exception) {
                            $reason = implode('; ', $exception->validator->errors()->all());
                        }
                    }
                }
                if ($reason !== null) {
                    $counts['skipped']++;
                    $counts['errors'][] = ['row' => $row['row'], 'reason' => $reason];
                }
            }

            return $counts;
        });
        $result['created'] += $chunk['created'];
        $result['skipped'] += $chunk['skipped'];
        $result['errors'] = array_merge($result['errors'], $chunk['errors']);
    }
}
