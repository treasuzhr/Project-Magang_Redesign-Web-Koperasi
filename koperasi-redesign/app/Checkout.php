<?php

namespace App;

use App\Models\Anggota;
use App\Models\Pesanan;
use App\Models\Produk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Checkout
{
    public const MAX_QUANTITY = 99;

    public const MAX_ITEMS = 100;

    // konfirmasi ke pengurus
    public const PAYMENT_NOTICE = 'Pembayaran dikonfirmasi saat pengambilan';

    public function place(Anggota $anggota, array $items, ?string $note): Pesanan
    {
        return DB::transaction(function () use ($anggota, $items, $note): Pesanan {
            $member = Anggota::whereKey($anggota->id)->lockForUpdate()->firstOrFail();
            abort_unless($member->status === 'aktif', 403);
            $products = Produk::whereIn('id', array_column($items, 'id'))->where('aktif', true)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($products->count() !== count($items)) {
                throw ValidationException::withMessages(['items' => 'Sebagian produk tidak tersedia. Periksa kembali keranjang.']);
            }
            $total = 0;
            foreach ($items as $item) {
                $total += $products[$item['id']]->harga * (int) $item['qty'];
            }
            $order = $member->pesanans()->create([
                'kode' => 'PSN-'.now()->format('Ymd').'-'.Str::upper(Str::random(10)),
                'status' => 'baru', 'total' => $total, 'metode_bayar' => null, 'catatan' => $note, 'tanggal' => now(),
            ]);
            foreach ($items as $item) {
                $product = $products[$item['id']];
                $order->items()->create(['produk_id' => $product->id, 'nama' => $product->nama, 'harga' => $product->harga,
                    'qty' => (int) $item['qty'], 'subtotal' => $product->harga * (int) $item['qty']]);
            }

            return $order;
        }, 3);
    }
}
