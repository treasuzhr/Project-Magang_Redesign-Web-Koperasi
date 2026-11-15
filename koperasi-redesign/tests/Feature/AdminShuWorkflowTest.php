<?php

namespace Tests\Feature;

use App\AdminShu;
use App\Filament\Resources\ShuPeriodes\Pages\CreateShuPeriode;
use App\Filament\Resources\ShuPeriodes\Pages\EditShuPeriode;
use App\Filament\Resources\ShuPeriodes\Pages\PreviewShuPeriode;
use App\MemberOverview;
use App\Models\Anggota;
use App\Models\PinjamanJenis;
use App\Models\ShuPeriode;
use App\Models\User;
use App\ShuCalculator;
use Database\Seeders\CooperativeDemoSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AdminShuWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_login_create_preview_csv_recalculate_establish_and_member_results(): void
    {
        $this->withoutVite();
        $this->seed(CooperativeDemoSeeder::class);
        $old = DB::table('shu_anggotas')->sum('shu');
        $count = DB::table('shu_anggotas')->count();
        $this->seed(CooperativeDemoSeeder::class);
        $this->assertEquals($old, DB::table('shu_anggotas')->sum('shu'));
        $this->assertSame($count, DB::table('shu_anggotas')->count());
        User::whereIn('nip', [CooperativeDemoSeeder::ADMIN_NIP, CooperativeDemoSeeder::ANGGOTA_NIP])->update(['must_change_password' => false]);
        $this->withSession(['captcha_answer' => 'AB2CDE'])->post('/login', ['member_id' => CooperativeDemoSeeder::ADMIN_NIP, 'password' => CooperativeDemoSeeder::PASSWORD, 'captcha' => 'AB2CDE'])->assertRedirect('/admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $member = User::where('nip', CooperativeDemoSeeder::ANGGOTA_NIP)->firstOrFail()->anggota;
        $member->pesanans()->create(['kode' => 'UJI-SHU-2026', 'tanggal' => '2026-01-01', 'selesai_pada' => '2026-01-02 12:00:00', 'status' => 'selesai', 'total' => 1000]);
        foreach (['usipa', 'usipa_khusus'] as $code) {
            $loan = $member->pinjamans()->create(['pinjaman_jenis_id' => PinjamanJenis::where('kode', $code)->value('id'), 'kode' => 'UJI-SHU-'.$code, 'jumlah' => 100000, 'tenor_bulan' => 1, 'bunga_persen_per_bulan' => 1, 'metode_bayar' => 'potong_gaji', 'status' => 'lunas', 'tanggal_pengajuan' => '2026-01-01']);
            $loan->angsurans()->create(['angsuran_ke' => 1, 'jatuh_tempo' => '2026-02-01', 'pokok' => 100000, 'bunga' => 1000, 'total' => 101000, 'status' => 'lunas', 'tanggal_bayar' => '2026-02-01']);
        }
        $input = array_replace(AdminShuParametersTest::parameters(), ['tahun' => 2026, 'pool_toko' => 10003, 'pool_usipa' => 5007, 'pool_usipa_khusus' => 3001, 'pool_jasa_umum' => 1703]);
        Livewire::test(CreateShuPeriode::class)->fillForm($input)->call('create')->assertHasNoFormErrors();
        $period = ShuPeriode::where('tahun', 2026)->firstOrFail();
        Livewire::test(PreviewShuPeriode::class, ['record' => $period->id])->callAction('calculate')->assertHasNoActionErrors();
        $period->refresh();
        $this->assertSame(18487, $period->shu_bersih);
        foreach ($period->komponens()->withSum('shuAnggotas', 'shu')->get() as $component) {
            $this->assertSame($component->kode === 'jasa_usipa_anggota' ? 275 : $component->pool_nominal, (int) $component->shu_anggotas_sum_shu);
        }
        $preview = Livewire::test(PreviewShuPeriode::class, ['record' => $period->id])->searchTable(CooperativeDemoSeeder::ANGGOTA_NIP)->assertCanSeeTableRecords([$member]);
        $this->assertSame(1, $preview->instance()->getTableRecords()->total());
        $preview->callAction('csv')->assertFileDownloaded('shu-2026.csv');
        $snapshot = $period->parameter;
        $noOp = app(AdminShu::class)->save($input, $period);
        $this->assertSame($snapshot, $noOp->parameter);
        Livewire::test(EditShuPeriode::class, ['record' => $period->id])->fillForm(['pool_toko' => 12003])->call('save')->assertHasNoFormErrors();
        $this->assertSame(0, $period->shuAnggotas()->count());
        Livewire::test(PreviewShuPeriode::class, ['record' => $period->id])->callAction('calculate')->assertHasNoActionErrors();
        $period->refresh();
        $this->assertSame(20487, $period->shu_bersih);
        Livewire::test(PreviewShuPeriode::class, ['record' => $period->id])->callAction('establish')->assertHasNoActionErrors();
        $expected = (int) $period->shuAnggotas()->where('anggota_id', $member->id)->sum('shu');
        $this->post('/logout')->assertRedirect('/');
        $this->withSession(['captcha_answer' => 'AB2CDE'])->post('/login', ['member_id' => CooperativeDemoSeeder::ANGGOTA_NIP, 'password' => CooperativeDemoSeeder::PASSWORD, 'captcha' => 'AB2CDE'])->assertRedirect('/anggota');
        $this->get('/anggota/shu/2026')->assertOk()->assertViewHas('totalRows', fn (array $rows): bool => $rows[0]['shu'] === $expected);
        $this->assertSame($expected, app(MemberOverview::class)->latestShu($member)['amount']);
        $this->get('/anggota')->assertOk();
        $this->get('/admin/shu')->assertForbidden();
        $this->get('/admin/shu/'.$period->id)->assertForbidden();
    }

    public function test_average_basis_is_snapshotted_and_member_detail_uses_it_after_establishment(): void
    {
        $this->withoutVite();
        $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
        $this->actingAs($admin);
        $member = Anggota::factory()->create(['user_id' => User::factory()->create(['must_change_password' => false])->id]);
        foreach ([['pokok', 120, '2030-01-01'], ['wajib', 120, '2030-12-01']] as [$kind,$amount,$date]) {
            $member->simpananTransaksis()->create(['jenis' => $kind, 'nominal' => $amount, 'tanggal' => $date, 'tipe' => 'setor', 'sumber' => 'manual']);
        }
        $period = app(ShuCalculator::class)->calculate(app(AdminShu::class)->save(array_replace(AdminShuParametersTest::parameters(), ['dasar_saldo' => 'rata_bulanan'])));
        app(AdminShu::class)->establish($period);
        $member->simpananTransaksis()->create(['jenis' => 'pokok', 'nominal' => 999, 'tanggal' => '2030-01-01', 'tipe' => 'setor', 'sumber' => 'manual']);
        $rows = app(MemberOverview::class)->shuDetail($member, 2030)['rows'];
        $this->assertSame(120, $rows[0]['amount']);
        $this->assertSame(10, $rows[1]['amount']);
        $this->assertSame(130, (int) $period->shuAnggotas()->where('kode_komponen', 'simpanan_pokok_wajib')->value('jumlah_dasar'));
    }

    public function test_empty_year_missing_exit_and_invalid_negative_balances_are_handled(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $inactive = Anggota::factory()->create(['status' => 'keluar', 'tanggal_keluar' => null]);
        $period = app(ShuCalculator::class)->calculate(app(AdminShu::class)->save(AdminShuParametersTest::parameters()));
        $this->assertSame(0, $period->shuAnggotas()->count());
        $this->assertSame(0, $period->shu_bersih);
        $this->assertStringContainsString('tanpa tanggal keluar', implode(' ', $period->parameter['report']['warnings']));
        $member = Anggota::factory()->create();
        $member->simpananTransaksis()->create(['jenis' => 'pokok', 'nominal' => 10, 'tanggal' => '2030-01-01', 'tipe' => 'tarik', 'sumber' => 'manual']);
        try {
            app(ShuCalculator::class)->calculate($period);
            $this->fail();
        } catch (ValidationException $e) {
            $this->assertStringContainsString('negatif', implode(' ', array_merge(...array_values($e->errors()))));
        }
        $this->assertSame(0,$period->shuAnggotas()->count());
    }
}
