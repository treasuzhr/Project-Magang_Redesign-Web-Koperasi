<?php

namespace Tests\Feature;

use App\AdminOverview;
use App\Filament\AdminTopbar;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Anggotas\Pages\ListAnggotas;
use App\Filament\Resources\Pesanans\Pages\ListPesanans;
use App\Filament\Resources\Pinjamans\Pages\ListPinjamans;
use App\Filament\Widgets\AdminActions;
use App\Filament\Widgets\AdminChart;
use App\Filament\Widgets\AdminStats;
use App\Filament\Widgets\LoanStatusChart;
use App\Filament\Widgets\OrdersChart;
use App\Filament\Widgets\SavingsChart;
use App\Models\Anggota;
use App\Models\Pesanan;
use App\Models\Pinjaman;
use App\Models\PinjamanJenis;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(now()->setDate(2026, 11, 15)->setTime(12, 0));
        $this->actingAs(User::factory()->create(['name' => 'Admin Pengujian', 'role' => 'admin', 'must_change_password' => false]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    private function loan(Anggota $member, string $status = 'diajukan', string $date = '2026-11-01'): Pinjaman
    {
        $type = PinjamanJenis::firstOrCreate(['kode' => 'usipa'], ['nama' => 'Unit Simpan Pinjam', 'bunga_persen_per_bulan' => 1, 'plafon_maks' => 50000000, 'tenor_maks' => 36, 'butuh_dokumen_akad' => false]);

        return $member->pinjamans()->create(['pinjaman_jenis_id' => $type->id, 'kode' => 'UJI-P-'.(Pinjaman::count() + 1), 'jumlah' => 1000, 'tenor_bulan' => 12, 'bunga_persen_per_bulan' => 1, 'metode_bayar' => 'potong_gaji', 'status' => $status, 'tanggal_pengajuan' => $date]);
    }

    private function order(Anggota $member, string $status = 'baru', string $date = '2026-11-01 10:00:00'): Pesanan
    {
        return $member->pesanans()->create(['kode' => 'UJI-O-'.(Pesanan::count() + 1), 'tanggal' => $date, 'status' => $status, 'total' => 500]);
    }

    public function test_admin_shell_has_grouped_icon_navigation_and_account_only_in_avatar_menu(): void
    {
        $this->get('/admin')->assertOk()->assertSee('Dashboard Admin')->assertSee('Primkop Admin');
        $groups = Filament::getNavigation();
        $this->assertSame(['Utama', 'Keuangan', 'Toko', 'Konten'], array_map(fn ($group) => $group->getLabel(), $groups));
        $labels = [];
        foreach ($groups as $group) {
            foreach ($group->getItems() as $item) {
                $labels[] = $item->getLabel();
                $this->assertNotNull($item->getIcon());
            }
        }
        $this->assertNotContains('Akun saya', $labels);
        $this->assertCount(10, $labels);
        $this->assertTrue(Filament::getPanel('admin')->isSidebarCollapsibleOnDesktop());
        $topbar = Livewire::test(AdminTopbar::class)->assertSee('Buka atau tutup sidebar')->assertDontSee('Primkop Admin');
        $topbar->assertSee('Admin Pengujian')->assertSee('Akun saya')->assertSee('Keluar');
        $this->assertSame([AdminStats::class, AdminActions::class, SavingsChart::class, OrdersChart::class, LoanStatusChart::class], (new Dashboard)->getWidgets());
    }

    public function test_stat_values_and_links_open_the_actual_tab_and_member_filter(): void
    {
        $member = Anggota::factory()->create();
        $inactive = Anggota::factory()->create(['status' => 'keluar']);
        $pending = $this->loan($member);
        $running = $this->loan($member, 'berjalan');
        $new = $this->order($member);
        $done = $this->order($member, 'selesai');
        foreach ([['setor', 100], ['tarik', 30]] as [$direction,$amount]) {
            $member->simpananTransaksis()->create(['jenis' => 'wajib', 'tipe' => $direction, 'nominal' => $amount, 'tanggal' => '2026-11-01', 'sumber' => 'manual']);
        }
        $this->assertSame(['active_members' => 1, 'savings' => 70, 'running_loans' => 1, 'pending_loans' => 1, 'new_orders' => 1], app(AdminOverview::class)->totals());
        $widget = Livewire::test(AdminStats::class)->assertSee('Rp 70');
        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($widget->html());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $stats = (new \DOMXPath($document))->query('//a[contains(@class,"fi-wi-stats-overview-stat")]');
        $this->assertCount(5, $stats);
        foreach ($stats as $index => $stat) {
            $url = $stat->getAttribute('href');
            $this->get($url)->assertOk();
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            if ($index === 0) {
                $this->assertSame('aktif', $query['filters']['status']['value']);
                Livewire::withQueryParams($query)->test(ListAnggotas::class)->assertCanSeeTableRecords([$member])->assertCanNotSeeTableRecords([$inactive]);
            }
            if ($index === 2) {
                $this->assertSame('berjalan', $query['tab']);
                Livewire::withQueryParams($query)->test(ListPinjamans::class)->assertCanSeeTableRecords([$running])->assertCanNotSeeTableRecords([$pending]);
            }
            if ($index === 3) {
                $this->assertSame('diajukan', $query['tab']);
                Livewire::withQueryParams($query)->test(ListPinjamans::class)->assertCanSeeTableRecords([$pending])->assertCanNotSeeTableRecords([$running]);
            }
            if ($index === 4) {
                $this->assertSame('baru', $query['tab']);
                Livewire::withQueryParams($query)->test(ListPesanans::class)->assertCanSeeTableRecords([$new])->assertCanNotSeeTableRecords([$done]);
            }
        }
    }

    public function test_action_tabs_show_total_counts_limit_five_rows_and_link_to_filtered_lists(): void
    {
        $member = Anggota::factory()->create();
        $loans = [];
        $orders = [];
        for ($day = 14; $day >= 8; $day--) {
            $loans[] = $this->loan($member, 'diajukan', sprintf('2026-11-%02d', $day));
            $orders[] = $this->order($member, 'baru', sprintf('2026-11-%02d 10:00:00', $day));
        }
        $running = $this->loan($member, 'berjalan');
        for ($number = 1; $number <= 7; $number++) {
            $running->angsurans()->create(['angsuran_ke' => $number, 'jatuh_tempo' => sprintf('2026-10-%02d', $number), 'pokok' => 100, 'bunga' => 10, 'total' => 110, 'status' => 'belum']);
        }
        foreach ([['lunas', '2026-01-01'], ['belum', '2026-11-15'], ['tunggak', '2026-12-01']] as $i => [$status,$due]) {
            $running->angsurans()->create(['angsuran_ke' => 8 + $i, 'jatuh_tempo' => $due, 'pokok' => 100, 'bunga' => 10, 'total' => 110, 'status' => $status]);
        }

        $this->assertSame(['loans' => 7, 'orders' => 7, 'installments' => 7], app(AdminOverview::class)->actionCounts());
        $this->assertSame(7, $running->angsurans()->where('status', 'belum')->where('jatuh_tempo', '<', today())->count());

        $widget = Livewire::test(AdminActions::class);
        $this->assertSame('loans', $widget->instance()->activeTab);
        $this->assertSame(['loans' => 7, 'orders' => 7, 'installments' => 7], $widget->instance()->counts);
        $this->assertCount(5, $widget->instance()->items);
        $this->assertSame(
            array_map(fn ($loan) => $loan->kode, array_slice(array_reverse($loans), 0, 5)),
            array_map(fn ($item) => explode(' · ', $item['secondary'])[0], $widget->instance()->items),
        );
        $widget->assertSee($loans[6]->kode)->assertDontSee($loans[0]->kode)
            ->assertSee('/admin/pinjaman/'.$loans[6]->id, false)
            ->assertSee('/admin/pinjaman?tab=diajukan', false)
            ->assertSee('Lihat semua (7)');

        $widget->call('selectTab', 'orders');
        $this->assertSame('orders', $widget->instance()->activeTab);
        $this->assertCount(5, $widget->instance()->items);
        $widget->assertSee($orders[6]->kode)->assertDontSee($loans[6]->kode)
            ->assertSee('/admin/pesanan/'.$orders[6]->id, false)
            ->assertSee('/admin/pesanan?tab=baru', false);

        $widget->call('selectTab', 'installments');
        $this->assertSame('installments', $widget->instance()->activeTab);
        $this->assertCount(5, $widget->instance()->items);
        $widget->assertSee('Angsuran 1')->assertSee('/admin/pinjaman/'.$running->id, false)
            ->assertSee('/admin/pinjaman?tab=berjalan', false);

        $this->get('/admin/pinjaman?tab=diajukan')->assertOk();
        $this->get('/admin/pesanan?tab=baru')->assertOk();
        $this->get('/admin/pinjaman?tab=berjalan')->assertOk();
    }

    public function test_action_tabs_default_to_first_nonempty_tab_and_show_category_empty_states(): void
    {
        $member = Anggota::factory()->create();
        $order = $this->order($member);

        $widget = Livewire::test(AdminActions::class);
        $this->assertSame('orders', $widget->instance()->activeTab);
        $this->assertSame(['loans' => 0, 'orders' => 1, 'installments' => 0], $widget->instance()->counts);
        $widget->call('selectTab', 'loans')->assertSee('Tidak ada pengajuan yang perlu diproses');
        $widget->call('selectTab', 'installments')->assertSee('Tidak ada angsuran yang lewat jatuh tempo');

        $order->delete();
        $this->actingAs(User::factory()->create(['name' => 'Admin Kosong', 'role' => 'admin', 'must_change_password' => false]));
        $empty = Livewire::test(AdminActions::class);
        $this->assertSame('loans', $empty->instance()->activeTab);
        $empty->assertSee('Tidak ada pengajuan yang perlu diproses');
        $empty->call('selectTab', 'orders')->assertSee('Tidak ada pesanan baru');
        $empty->call('selectTab', 'installments')->assertSee('Tidak ada angsuran yang lewat jatuh tempo');
    }

    public function test_charts_aggregate_twelve_calendar_months_and_zero_fill_missing_months(): void
    {
        $member = Anggota::factory()->create();
        foreach ([['2025-11-30', 'setor', 999], ['2025-12-01', 'setor', 100], ['2025-12-31', 'tarik', 25], ['2026-11-15', 'setor', 200], ['2026-11-16', 'setor', 888], ['2026-12-01', 'setor', 777]] as [$date,$direction,$amount]) {
            $member->simpananTransaksis()->create(['jenis' => 'sukarela', 'tipe' => $direction, 'nominal' => $amount, 'tanggal' => $date, 'sumber' => 'manual']);
        }
        foreach (['2025-11-30 12:00:00', '2025-12-01 00:00:00', '2026-11-01 12:00:00', '2026-11-15 11:00:00', '2026-11-15 13:00:00'] as $date) {
            $this->order($member, 'baru', $date);
        }
        $this->loan($member, 'berjalan');
        $this->loan($member, 'berjalan');
        $this->loan($member, 'diajukan');
        DB::enableQueryLog();
        DB::flushQueryLog();
        $savings = app(AdminOverview::class)->monthlySavings(2026);
        $orders = app(AdminOverview::class)->monthlyOrders(2026);
        $statuses = app(AdminOverview::class)->loanStatuses();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertCount(3, $queries);
        foreach ($queries as $query) {
            $this->assertStringContainsString('group by', strtolower($query['query']));
        }
        $this->assertSame([2026, 2025], app(AdminOverview::class)->savingsYears());
        $this->assertSame([2026, 2025], app(AdminOverview::class)->orderYears());
        $this->assertCount(12, $savings['labels']);
        $this->assertSame('Jan', $savings['labels'][0]);
        $this->assertSame('Des', $savings['labels'][11]);
        $this->assertSame([0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1088, 777], $savings['setor']);
        $this->assertSame(array_fill(0, 12, 0), $savings['tarik']);
        $this->assertSame([0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, 0], $orders['counts']);
        $this->assertSame([1, 0, 0, 2, 0], $statuses['counts']);
        $this->assertSame($savings['setor'], (new \ReflectionMethod(SavingsChart::class, 'getData'))->invoke(Livewire::test(SavingsChart::class)->instance())['datasets'][0]['data']);
        $this->assertSame($orders['counts'], (new \ReflectionMethod(OrdersChart::class, 'getData'))->invoke(Livewire::test(OrdersChart::class)->instance())['datasets'][0]['data']);
        $this->assertSame($statuses['counts'], (new \ReflectionMethod(LoanStatusChart::class, 'getData'))->invoke(Livewire::test(LoanStatusChart::class)->instance())['datasets'][0]['data']);
    }

    public function test_year_filters_default_to_latest_data_and_only_show_the_selected_year(): void
    {
        $member = Anggota::factory()->create();
        $member->simpananTransaksis()->create(['jenis' => 'sukarela', 'tipe' => 'setor', 'nominal' => 80, 'tanggal' => '2023-12-19', 'sumber' => 'manual']);
        $member->simpananTransaksis()->create(['jenis' => 'wajib', 'tipe' => 'setor', 'nominal' => 120, 'tanggal' => '2025-01-03', 'sumber' => 'manual']);
        $this->order($member, 'baru', '2023-12-25 10:00:00');
        $this->order($member, 'baru', '2025-01-10 10:00:00');

        $savingsChart = Livewire::test(SavingsChart::class)->assertSee('2025')->assertSee('2023');
        $this->assertSame('2025', $savingsChart->instance()->filter);
        $savingsChartData = (new \ReflectionMethod(SavingsChart::class, 'getData'))->invoke($savingsChart->instance());
        $this->assertSame([120, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], $savingsChartData['datasets'][0]['data']);
        $savingsChart->set('filter', '2023');
        $savingsChartData = (new \ReflectionMethod(SavingsChart::class, 'getData'))->invoke($savingsChart->instance());
        $this->assertSame([0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 80], $savingsChartData['datasets'][0]['data']);
        $savingsChart->set('filter', '2040')->assertSee('Belum ada data pada periode ini');

        $ordersChart = Livewire::test(OrdersChart::class)->assertSee('2025')->assertSee('2023');
        $this->assertSame('2025', $ordersChart->instance()->filter);
        $ordersChartData = (new \ReflectionMethod(OrdersChart::class, 'getData'))->invoke($ordersChart->instance());
        $this->assertSame([1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], $ordersChartData['datasets'][0]['data']);
        $ordersChart->set('filter', '2023');
        $ordersChartData = (new \ReflectionMethod(OrdersChart::class, 'getData'))->invoke($ordersChart->instance());
        $this->assertSame([0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1], $ordersChartData['datasets'][0]['data']);
        $ordersChart->set('filter', '2040')->assertSee('Belum ada data pada periode ini');
    }

    public function test_all_chart_widgets_use_object_animation_options(): void
    {
        $animationMethod = new \ReflectionMethod(AdminChart::class, 'getAnimationOptions');

        foreach ([SavingsChart::class, OrdersChart::class, LoanStatusChart::class] as $widget) {
            $component = Livewire::test($widget)->instance();
            $animation = $animationMethod->invoke($component);
            $options = (new \ReflectionMethod($widget, 'getOptions'))->invoke($component)->toHtml();

            $this->assertIsArray($animation);
            $this->assertSame(0, $animation['duration']);
            $this->assertSame(1, preg_match('/animation:\s*\{\s*duration:\s*(\d+)\s*\}/', $options, $matches));
            $this->assertSame($animation['duration'], (int) $matches[1]);
        }
    }

    public function test_empty_action_lists_and_charts_and_member_access_are_safe(): void
    {
        Livewire::test(AdminActions::class)->assertSee('Tidak ada pengajuan yang perlu diproses');
        foreach ([SavingsChart::class, OrdersChart::class] as $widget) {
            Livewire::test($widget)->assertSee('Belum ada data pada periode ini');
        }
        Livewire::test(LoanStatusChart::class)->assertSuccessful();
        $this->actingAs(User::factory()->create(['role' => 'anggota', 'must_change_password' => false]));
        $this->get('/admin')->assertForbidden();
        foreach ([AdminStats::class, AdminActions::class, SavingsChart::class, OrdersChart::class, LoanStatusChart::class] as $widget) {
            Livewire::test($widget)->assertForbidden();
        }
    }
}
