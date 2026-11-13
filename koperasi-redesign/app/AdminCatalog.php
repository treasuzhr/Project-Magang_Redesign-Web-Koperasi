<?php

namespace App;

use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminCatalog
{
    private function authorize(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
    }

    public function saveProduct(array $input, ?Produk $record = null): Produk
    {
        $this->authorize();
        $data = Validator::make($input, [
            'nama' => ['required', 'string', 'max:255', Rule::unique('produks', 'nama')->ignore($record?->id)],
            'harga' => ['required', 'integer', 'min:0', 'max:'.PHP_INT_MAX],
            'kategori_id' => ['nullable', 'integer', Rule::exists('kategoris', 'id')],
            'aktif' => ['required', 'boolean'], 'foto' => ['nullable', 'string', 'max:2048'],
        ], ['nama.required' => 'Nama produk wajib diisi.', 'nama.unique' => 'Nama produk sudah digunakan.', 'harga.required' => 'Harga wajib diisi.', 'harga.integer' => 'Harga harus berupa bilangan bulat rupiah.', 'harga.min' => 'Harga tidak boleh negatif.', 'kategori_id.exists' => 'Kategori tidak tersedia.'])->validate();
        if ($record === null) {
            return Produk::create($data);
        }
        $record->update($data);

        return $record->refresh();
    }

    public function setActive(Produk $record, bool $active): bool
    {
        $this->authorize();
        $record->update(['aktif' => $active]);

        return $record->aktif;
    }

    public function saveCategory(array $input, ?Kategori $record = null): Kategori
    {
        $this->authorize();
        $input['slug'] = Str::slug(is_string($input['nama'] ?? null) ? $input['nama'] : '');
        $data = Validator::make($input, ['nama' => ['required', 'string', 'max:255'], 'slug' => ['required', 'string', 'max:255', Rule::unique('kategoris', 'slug')->ignore($record?->id)]], ['nama.required' => 'Nama kategori wajib diisi.', 'slug.required' => 'Nama kategori harus menghasilkan slug yang valid.', 'slug.unique' => 'Slug kategori sudah digunakan.'])->validate();
        if ($record === null) {
            return Kategori::create($data);
        }
        $record->update($data);

        return $record->refresh();
    }

    public function moveProducts(array $ids, mixed $target): int
    {
        $this->authorize();
        $data = Validator::make(['kategori_id' => $target], ['kategori_id' => ['required', 'integer', Rule::exists('kategoris', 'id')]])->validate();

        return DB::transaction(function () use ($ids, $data): int {
            Kategori::query()->lockForUpdate()->findOrFail($data['kategori_id']);
            $records = Produk::query()->whereKey($ids)->lockForUpdate()->get(['id']);
            abort_unless($records->count() === count(array_unique($ids)), 404);

            return Produk::query()->whereKey($records->modelKeys())->update(['kategori_id' => $data['kategori_id']]);
        });
    }

    public function mergeCategory(Kategori $source, mixed $target): int
    {
        $this->authorize();
        $data = Validator::make(['kategori_id' => $target], ['kategori_id' => ['required', 'integer', Rule::exists('kategoris', 'id'), Rule::notIn([$source->id])]], ['kategori_id.not_in' => 'Kategori tujuan harus berbeda dari kategori asal.'])->validate();

        return DB::transaction(function () use ($source, $data): int {
            $categories = Kategori::query()->whereKey([$source->id, $data['kategori_id']])->orderBy('id')->lockForUpdate()->get();
            abort_unless($categories->count() === 2, 404);
            $count = Produk::query()->where('kategori_id', $source->id)->update(['kategori_id' => $data['kategori_id']]);
            $categories->firstWhere('id', $source->id)->delete();

            return $count;
        });
    }

    public function deleteCategory(Kategori $record): void
    {
        $this->authorize();
        DB::transaction(function () use ($record): void {
            $category = Kategori::query()->lockForUpdate()->findOrFail($record->id);
            if ($category->produks()->exists()) {
                throw ValidationException::withMessages(['kategori' => 'Kategori yang memiliki produk tidak boleh dihapus.']);
            }
            $category->delete();
        });
    }
}
