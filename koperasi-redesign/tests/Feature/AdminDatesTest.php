<?php

namespace Tests\Feature;

use App\AdminLoans;
use App\AdminOrders;
use App\MemberOverview;
use App\Models\Pesanan;
use App\Models\Pinjaman;
use App\Models\User;
use Database\Seeders\CooperativeDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_disbursement_and_completion_dates_control_schedules_and_shu_year(): void
    {
        $this->seed(CooperativeDemoSeeder::class);
        $this->actingAs(User::where('role', 'admin')->first());
        $this->travelTo(now()->setDate(2027, 1, 5)->startOfDay());
        $loan = Pinjaman::first();
        $loan->angsurans()->delete();
        $loan->update(['status' => 'disetujui', 'tanggal_disetujui' => '2026-12-20']);
        app(AdminLoans::class)->disburse($loan);
        $this->assertSame('2027-01-05', $loan->tanggal_pencairan->toDateString());
        $this->assertSame('2027-02-05', $loan->angsurans()->orderBy('angsuran_ke')->first()->jatuh_tempo->toDateString());
        $order = Pesanan::first();
        $order->update(['status' => 'siap', 'tanggal' => '2026-12-31', 'selesai_pada' => null]);
        $before = app(MemberOverview::class)->annualSpending($order->anggota, 2027);
        app(AdminOrders::class)->transition($order, 'selesai');
        $this->assertSame('2027-01-05', $order->selesai_pada->toDateString());
        $this->assertSame($before + $order->total, app(MemberOverview::class)->annualSpending($order->anggota, 2027));
        $this->assertFalse($order->anggota->pesanans()->whereKey($order->id)->whereYear('selesai_pada', 2026)->exists());
    }
}
