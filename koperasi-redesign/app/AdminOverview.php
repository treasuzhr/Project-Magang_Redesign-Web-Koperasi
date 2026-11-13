<?php

namespace App;

use App\Models\Anggota;
use App\Models\Pesanan;
use App\Models\Pinjaman;
use App\Models\SimpananTransaksi;

class AdminOverview
{
    /** @return array{active_members: int, savings: int, running_loans: int, pending_loans: int, new_orders: int} */
    public function totals(): array
    {
        return [
            'active_members' => Anggota::where('status', 'aktif')->count(),
            'savings' => (int) SimpananTransaksi::query()->selectRaw("COALESCE(SUM(CASE WHEN tipe = 'setor' THEN CAST(nominal AS SIGNED) ELSE -CAST(nominal AS SIGNED) END), 0) AS saldo")->value('saldo'),
            'running_loans' => Pinjaman::where('status', 'berjalan')->count(),
            'pending_loans' => Pinjaman::where('status', 'diajukan')->count(),
            'new_orders' => Pesanan::where('status', 'baru')->count(),
        ];
    }
}
