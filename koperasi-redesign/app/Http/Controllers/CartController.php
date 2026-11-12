<?php

namespace App\Http\Controllers;

use App\Checkout;
use App\DatabaseProductSource;
use App\Models\Produk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(): View
    {
        return view('cart.index', ['checkout' => false]);
    }

    public function products(Request $request, DatabaseProductSource $source): JsonResponse
    {
        $data = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:'.Checkout::MAX_ITEMS], 'ids.*' => ['required', 'integer', 'min:1', 'distinct']]);
        $products = Produk::with('kategori')->where('aktif', true)->whereIn('id', $data['ids'])->orderBy('id')->get()->map(fn (Produk $product): array => $source->product($product));

        return response()->json(['products' => $products, 'html' => view('cart.items', compact('products'))->render()]);
    }

    public function showCheckout(Request $request): View|RedirectResponse
    {
        if (! $request->user()) {
            $request->session()->put('checkout.return_to_cart', true);

            return redirect()->route('login');
        }
        abort_unless($request->user()->role === 'anggota' && $request->user()->anggota()->where('status', 'aktif')->exists(), 403);
        $token = Str::random(40);
        $request->session()->put('checkout.token', $token);

        return view('cart.index', ['checkout' => true, 'checkoutToken' => $token, 'paymentNotice' => Checkout::PAYMENT_NOTICE]);
    }

    public function store(Request $request, Checkout $checkout): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:'.Checkout::MAX_ITEMS],
            'items.*.id' => ['required', 'integer', 'min:1', 'distinct'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:'.Checkout::MAX_QUANTITY],
            'catatan' => ['nullable', 'string', 'max:1000'], 'checkout_token' => ['required', 'string', 'size:40'],
        ], [
            'items.required' => 'Keranjang masih kosong.', 'items.array' => 'Data keranjang tidak valid.',
            'items.min' => 'Keranjang masih kosong.', 'items.max' => 'Keranjang maksimal 100 jenis produk.',
            'items.*.id.required' => 'Produk tidak valid.', 'items.*.id.integer' => 'Produk tidak valid.',
            'items.*.id.min' => 'Produk tidak valid.', 'items.*.id.distinct' => 'Produk yang sama tidak boleh diulang.',
            'items.*.qty.required' => 'Jumlah produk wajib diisi.', 'items.*.qty.integer' => 'Jumlah produk harus bilangan bulat.',
            'items.*.qty.min' => 'Jumlah produk minimal 1.', 'items.*.qty.max' => 'Jumlah produk maksimal 99.',
            'catatan.string' => 'Catatan harus berupa teks.', 'catatan.max' => 'Catatan maksimal 1000 karakter.',
            'checkout_token.required' => 'Buka kembali halaman checkout.', 'checkout_token.size' => 'Buka kembali halaman checkout.',
        ]);
        $completed = $request->session()->get('checkout.completed', []);
        $token = $data['checkout_token'];
        if (isset($completed[$token])) {
            $order = $request->user()->anggota->pesanans()->findOrFail($completed[$token]);
        } else {
            abort_unless(hash_equals((string) $request->session()->get('checkout.token', ''), $token), 419);
            $order = $checkout->place($request->user()->anggota, $data['items'], $data['catatan'] ?? null);
            $completed[$token] = $order->id;
            $request->session()->put('checkout.completed', array_slice($completed, -20, null, true));
        }

        return redirect()->route('anggota.pesanan.detail', ['id' => $order->id])
            ->with('status', 'Pesanan berhasil dibuat. Silakan ambil di Def.MART setelah status Siap diambil.')
            ->with('checkout.clear_cart', true);
    }
}
