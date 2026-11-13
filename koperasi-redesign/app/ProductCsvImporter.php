<?php

namespace App;

use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProductCsvImporter
{
    private const CHUNK_SIZE = 100;

    private const MAX_ROWS = 10000;

    /** @return array{created: int, updated: int, skipped: int, errors: array<int, array{row: int, reason: string}>} */
    public function import(string $path): array
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        if (! is_file($path) || ! is_readable($path)) {
            throw ValidationException::withMessages(['csv' => 'File CSV tidak dapat dibaca.']);
        }
        $stream = fopen($path, 'rb');
        try {
            $header = fgetcsv($stream, 0, ',', '"', '');
            if ($header === false) {
                throw ValidationException::withMessages(['csv' => 'File CSV kosong.']);
            }
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0] ?? '');
            $header = array_map(fn (?string $value): string => strtolower(trim($value ?? '')), $header);
            if (count($header) !== 3 || count(array_unique($header)) !== 3 || array_diff(['nama', 'harga', 'kategori'], $header)) {
                throw ValidationException::withMessages(['csv' => 'Kolom CSV harus nama, harga, kategori.']);
            }
            $positions = array_flip($header);

            return DB::transaction(function () use ($stream, $positions): array {
                $categories = Kategori::query()->orderBy('id')->lockForUpdate()->get(['id', 'nama'])->mapWithKeys(fn (Kategori $category): array => [mb_strtolower(trim($category->nama)) => $category->id])->all();
                $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
                $batch = [];
                $line = 1;
                while (($values = fgetcsv($stream, 0, ',', '"', '')) !== false) {
                    $line++;
                    if ($line > self::MAX_ROWS + 1) {
                        throw ValidationException::withMessages(['csv' => 'CSV maksimal 10000 baris.']);
                    }
                    if ($values === [null]) {
                        continue;
                    }
                    $reason = null;
                    if (count($values) !== 3) {
                        $reason = 'Jumlah kolom tidak sesuai templat.';
                    } elseif (! mb_check_encoding(implode('', $values), 'UTF-8')) {
                        $reason = 'Teks harus menggunakan UTF-8.';
                    } else {
                        $data = ['nama' => trim($values[$positions['nama']]), 'harga' => trim($values[$positions['harga']]), 'kategori' => trim($values[$positions['kategori']])];
                        $validator = Validator::make($data, ['nama' => ['required', 'string', 'max:255'], 'harga' => ['required', 'integer', 'min:0', 'max:'.PHP_INT_MAX], 'kategori' => ['required', 'string', 'max:255']], ['nama.required' => 'Nama produk wajib diisi.', 'nama.max' => 'Nama produk maksimal 255 karakter.', 'harga.required' => 'Harga wajib diisi.', 'harga.integer' => 'Harga harus bilangan bulat rupiah.', 'harga.min' => 'Harga tidak boleh negatif.', 'harga.max' => 'Harga terlalu besar.', 'kategori.required' => 'Kategori wajib diisi.', 'kategori.max' => 'Nama kategori maksimal 255 karakter.']);
                        if ($validator->fails()) {
                            $reason = implode('; ', $validator->errors()->all());
                        } elseif (! isset($categories[mb_strtolower($data['kategori'])])) {
                            $reason = 'Kategori tidak dikenal.';
                        } else {
                            $batch[] = ['nama' => $data['nama'], 'harga' => (int) $data['harga'], 'kategori_id' => $categories[mb_strtolower($data['kategori'])]];
                        }
                    }
                    if ($reason !== null) {
                        $result['skipped']++;
                        $result['errors'][] = ['row' => $line, 'reason' => $reason];
                    }
                    if (count($batch) === self::CHUNK_SIZE) {
                        $this->flushBatch($batch, $result);
                        $batch = [];
                    }
                }
                if ($batch !== []) {
                    $this->flushBatch($batch, $result);
                }

                return $result;
            });
        } finally {
            fclose($stream);
        }
    }

    private function flushBatch(array $rows, array &$result): void
    {
        $names = array_values(array_unique(array_map(fn (array $row): string => mb_strtolower($row['nama']), $rows)));
        $known = Produk::query()->whereIn(DB::raw('LOWER(nama)'), $names)->lockForUpdate()->get(['id', 'nama'])->mapWithKeys(fn (Produk $product): array => [mb_strtolower($product->nama) => $product->nama])->all();
        $batch = [];
        $now = now();
        foreach ($rows as $row) {
            $key = mb_strtolower($row['nama']);
            if (isset($known[$key])) {
                $result['updated']++;
                $row['nama'] = $known[$key];
            } else {
                $result['created']++;
                $known[$key] = $row['nama'];
            }
            $batch[$key] = $row + ['aktif' => true, 'created_at' => $now, 'updated_at' => $now];
        }
        Produk::query()->upsert(array_values($batch), ['nama'], ['harga', 'kategori_id', 'updated_at']);
    }
}
