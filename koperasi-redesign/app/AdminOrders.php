<?php

namespace App;

use App\Models\Pesanan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminOrders
{
    public const TRANSITIONS = ['baru' => ['diproses', 'batal'], 'diproses' => ['siap', 'batal'], 'siap' => ['selesai', 'batal'], 'selesai' => [], 'batal' => []];

    public function transition(Pesanan $record, string $target): Pesanan
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        DB::transaction(function () use ($record, $target): void {
            $order = Pesanan::query()->lockForUpdate()->findOrFail($record->id);
            if (! in_array($target, self::TRANSITIONS[$order->status] ?? [], true)) {
                throw ValidationException::withMessages(['status' => 'Perubahan status pesanan tidak diizinkan atau status sudah berubah.']);
            }
            $order->update(['status' => $target]);
        });

        return $record->refresh();
    }
}
