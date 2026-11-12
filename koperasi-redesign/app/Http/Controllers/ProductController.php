<?php

namespace App\Http\Controllers;

use App\DatabaseProductSource;
use App\Models\Produk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct(private DatabaseProductSource $source) {}

    public function __invoke(Request $request): Response|JsonResponse
    {
        $categories = $this->source->categories();
        $categoryLabels = array_column($categories, 'name', 'slug');
        $input = [];
        foreach (['q' => 'q', 'kategori' => 'category', 'min' => 'min_price', 'max' => 'max_price', 'urut' => 'sort', 'tampilan' => 'view', 'page' => 'page'] as $key => $alias) {
            $value = $request->query($key, $request->query($alias, ''));
            $input[$key] = is_string($value) ? trim($value) : $value;
        }
        foreach (['min', 'max'] as $priceKey) {
            if (is_string($input[$priceKey])) {
                $input[$priceKey] = preg_replace('/[.\s]/u', '', $input[$priceKey]);
            }
        }
        $input['urut'] = $input['urut'] ?: 'name_asc';
        $input['tampilan'] = $input['tampilan'] ?: 'grid';
        $validator = Validator::make($input, [
            'q' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', 'string', 'max:200'],
            'min' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
            'max' => ['nullable', 'integer', 'min:0', 'max:2147483647', ...($input['min'] !== '' && is_scalar($input['min']) && ctype_digit((string) $input['min']) ? ['gte:min'] : [])],
            'urut' => ['required', Rule::in(['name_asc', 'name_desc', 'price_asc', 'price_desc'])],
            'tampilan' => ['required', Rule::in(['grid', 'list'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ], [
            'q.max' => 'Pencarian maksimal 100 karakter.',
            'q.string' => 'Kata kunci pencarian tidak valid.',
            'kategori.in' => 'Kategori tidak tersedia.',
            'kategori.string' => 'Kategori tidak valid.',
            'min.integer' => 'Harga minimum harus berupa rupiah bulat.',
            'max.integer' => 'Harga maksimum harus berupa rupiah bulat.',
            'min.min' => 'Harga minimum tidak boleh negatif.',
            'max.min' => 'Harga maksimum tidak boleh negatif.',
            'min.max' => 'Harga minimum terlalu besar.',
            'max.max' => 'Harga maksimum terlalu besar.',
            'max.gte' => 'Harga minimum tidak boleh lebih besar dari harga maksimum.',
            'urut.in' => 'Pilihan urutan tidak valid.',
            'tampilan.in' => 'Pilihan tampilan tidak valid.',
            'page.integer' => 'Nomor halaman tidak valid.',
            'page.min' => 'Nomor halaman minimal 1.',
        ]);
        $invalid = $validator->fails();
        $errors = (new ViewErrorBag)->put('default', $validator->errors());
        $filters = [];
        foreach (['q', 'kategori', 'min', 'max', 'urut', 'tampilan'] as $key) {
            $filters[$key] = is_scalar($input[$key]) ? (string) $input[$key] : '';
        }
        $filters['urut'] = in_array($filters['urut'], ['name_asc', 'name_desc', 'price_asc', 'price_desc'], true) ? $filters['urut'] : 'name_asc';
        $filters['tampilan'] = $filters['tampilan'] === 'list' ? 'list' : 'grid';
        $isExample = false;
        $sourceTotal = Produk::where('aktif', true)->count();
        $sourceAvailable = $sourceTotal > 0;
        $categoryCounts = array_column($categories, 'products_count', 'slug');
        $page = ! $invalid && $input['page'] !== '' ? (int) $input['page'] : 1;
        $perPage = max(1, min(60, (int) config('product_catalog.per_page', 15)));
        $query = array_filter($filters, static fn (string $value): bool => $value !== '');
        if ($page > 1) {
            $query['page'] = $page;
        }
        $products = $this->source->paginate($filters, $invalid, $page, $perPage)->appends(array_diff_key($query, ['page' => true]));
        abort_if(! $invalid && $page > $products->lastPage(), 404);
        $hasFilters = $filters['q'] !== '' || $filters['kategori'] !== '' || $filters['min'] !== '' || $filters['max'] !== '';
        $activeFilters = [];
        if ($filters['q'] !== '') {
            $activeFilters[] = ['label' => 'Cari: '.$filters['q'], 'remove' => ['q' => null, 'page' => null]];
        }
        if (isset($categoryLabels[$filters['kategori']])) {
            $activeFilters[] = ['label' => 'Kategori: '.$categoryLabels[$filters['kategori']], 'remove' => ['kategori' => null, 'page' => null]];
        }
        if ($filters['min'] !== '' || $filters['max'] !== '') {
            $priceLabel = $filters['min'] !== '' && $filters['max'] !== ''
                ? 'Rp '.number_format((int) $filters['min'], 0, ',', '.').' - Rp '.number_format((int) $filters['max'], 0, ',', '.')
                : ($filters['min'] !== '' ? 'Mulai Rp '.number_format((int) $filters['min'], 0, ',', '.') : 'Hingga Rp '.number_format((int) $filters['max'], 0, ',', '.'));
            $activeFilters[] = ['label' => $priceLabel, 'remove' => ['min' => null, 'max' => null, 'page' => null]];
        }
        $data = compact('categories', 'categoryCounts', 'sourceTotal', 'sourceAvailable', 'isExample', 'products', 'filters', 'query', 'activeFilters', 'hasFilters', 'errors');
        if ($request->expectsJson()) {
            return response()->json(['html' => view('products.catalog', $data)->render(), 'url' => route('products.index', $query), 'invalid' => $invalid], $invalid ? 422 : 200);
        }

        return response()->view('products.index', $data, $invalid ? 422 : 200);
    }
}
