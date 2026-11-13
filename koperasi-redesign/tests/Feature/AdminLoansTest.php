<?php

namespace Tests\Feature;

use App\AdminLoans;
use App\Filament\Resources\Pinjamans\Pages\ListPinjamans;
use App\Filament\Resources\Pinjamans\Pages\ViewPinjaman;
use App\Filament\Resources\Pinjamans\PinjamanResource;
use App\Filament\Resources\Pinjamans\RelationManagers\AngsuransRelationManager;
use App\LoanSimulator;
use App\MemberOverview;
use App\Models\Anggota;
use App\Models\Pinjaman;
use App\Models\PinjamanJenis;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AdminLoansTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    private function admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'must_change_password' => false]));
    }

    private function loan(): Pinjaman
    {
        $member = Anggota::factory()->create(['user_id' => User::factory()->create(['must_change_password' => false])->id]);
        $kind = PinjamanJenis::firstOrCreate(['kode' => 'usipa'], ['nama' => 'Unit Simpan Pinjam', 'bunga_persen_per_bulan' => 1, 'plafon_maks' => 50000000, 'tenor_maks' => 36, 'butuh_dokumen_akad' => false]);

        return $member->pinjamans()->create(['pinjaman_jenis_id' => $kind->id, 'kode' => fake()->unique()->numerify('ADM-########'), 'jumlah' => 1000001, 'tenor_bulan' => 3, 'bunga_persen_per_bulan' => '1.0000', 'metode_bayar' => 'potong_gaji', 'status' => 'diajukan', 'tanggal_pengajuan' => '2026-01-01']);
    }

    private function assertInvalid(callable $action, string $field): void
    {
        try {
            $action();
            $this->fail('Transisi tidak valid harus ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
    }

    public function test_panel_approval_and_disbursement_create_exact_flat_schedule_and_update_member_pages(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-01-31'));
        $this->admin();
        $loan = $this->loan();
        Livewire::test(ListPinjamans::class)->searchTable($loan->anggota->user->nip)->assertCanSeeTableRecords([$loan])->callTableAction('approve', $loan)->assertHasNoTableActionErrors();
        $this->assertSame('disetujui', $loan->fresh()->status);
        $this->assertSame('2026-01-31', $loan->fresh()->tanggal_disetujui->toDateString());
        $this->assertSame(0, $loan->angsurans()->count());
        $relation = Livewire::test(AngsuransRelationManager::class, ['ownerRecord' => $loan->fresh(), 'pageClass' => ViewPinjaman::class])->assertCountTableRecords(0);
        Livewire::test(ViewPinjaman::class, ['record' => (string) $loan->id])->callAction('disburse')->assertHasNoActionErrors()->assertDispatched('loan-updated');
        $loan->refresh();
        $this->assertSame('berjalan', $loan->status);
        $rows = $loan->angsurans()->orderBy('angsuran_ke')->get();
        $this->assertCount(3, $rows);
        $relation->dispatch('loan-updated')->assertCanSeeTableRecords($rows)->assertTableActionVisible('markPaid', $rows->first());
        $this->assertSame([333333, 333333, 333335], $rows->pluck('pokok')->all());
        $this->assertSame(1000001, $rows->sum('pokok'));
        $this->assertSame([10000, 10000, 10000], $rows->pluck('bunga')->all());
        $this->assertSame(['2026-02-28', '2026-03-31', '2026-04-30'], $rows->map(fn ($row): string => $row->jatuh_tempo->toDateString())->all());
        $this->assertSame(['belum'], $rows->pluck('status')->unique()->values()->all());
        $this->assertFalse(PinjamanResource::canEdit($loan));
        $this->assertFalse(PinjamanResource::canCreate());
        $this->actingAs($loan->anggota->user)->get('/anggota/pinjaman/'.$loan->id)->assertOk()->assertSee('Unit Simpan Pinjam');
        $this->assertSame([1000001, 1000001, 0], array_column($this->get('/anggota')->assertOk()->viewData('stats')['pinjaman'], 'amount'));
        $this->travelBack();
    }

    public function test_invalid_transitions_required_rejection_note_and_duplicate_schedule_are_rejected(): void
    {
        $this->admin();
        $loan = $this->loan();
        $service = app(AdminLoans::class);
        $this->assertInvalid(fn () => $service->disburse($loan), 'status');
        $this->assertInvalid(fn () => $service->reject($loan, '  '), 'catatan_admin');
        $service->approve($loan);
        $this->assertInvalid(fn () => $service->approve($loan), 'status');
        $this->assertInvalid(fn () => $service->reject($loan, 'Terlambat'), 'status');
        $service->disburse($loan);
        $before = $loan->angsurans()->get()->toArray();
        $this->assertInvalid(fn () => $service->disburse($loan), 'status');
        $this->assertSame($before, $loan->angsurans()->get()->toArray());
        $rejected = $this->loan();
        Livewire::test(ListPinjamans::class)->callTableAction('reject', $rejected, ['catatan_admin' => 'Dokumen belum lengkap'])->assertHasNoTableActionErrors();
        $this->assertSame('ditolak', $rejected->fresh()->status);
        $this->assertSame('Dokumen belum lengkap', $rejected->fresh()->catatan_admin);
        $this->assertInvalid(fn () => $service->approve($rejected), 'status');
        $this->assertSame(0, $rejected->angsurans()->count());
    }

    public function test_single_and_bulk_payments_close_the_loan_and_reject_foreign_or_paid_installments(): void
    {
        $this->admin();
        $loan = $this->loan();
        $service = app(AdminLoans::class);
        $service->approve($loan);
        $service->disburse($loan);
        $first = $loan->angsurans()->firstOrFail();
        Livewire::test(AngsuransRelationManager::class, ['ownerRecord' => $loan, 'pageClass' => ViewPinjaman::class])
            ->callTableAction('markPaid', $first, ['tanggal_bayar' => today()->toDateString()])->assertHasNoTableActionErrors();
        $this->assertSame('lunas', $first->fresh()->status);
        $this->assertSame(today()->toDateString(), $first->fresh()->tanggal_bayar->toDateString());
        $this->assertSame('berjalan', $loan->fresh()->status);
        $this->assertInvalid(fn () => $service->markPaid($loan, [$first->id], today()->toDateString()), 'ids');
        $other = $this->loan();
        $service->approve($other);
        $service->disburse($other);
        $this->assertInvalid(fn () => $service->markPaid($loan, [$other->angsurans()->firstOrFail()->id], today()->toDateString()), 'ids');
        $remaining = $loan->angsurans()->where('status', '!=', 'lunas')->get();
        Livewire::test(AngsuransRelationManager::class, ['ownerRecord' => $loan->fresh(), 'pageClass' => ViewPinjaman::class])
            ->callTableBulkAction('markPaid', $remaining, ['tanggal_bayar' => today()->toDateString()])->assertHasNoTableBulkActionErrors();
        $this->assertSame('lunas', $loan->fresh()->status);
        $this->assertSame(3, $loan->angsurans()->where('status', 'lunas')->count());
        $this->assertSame(0, app(MemberOverview::class)->loanSummary($loan->anggota)['active_remaining']);
        $this->actingAs($loan->anggota->user)->get('/anggota/pinjaman/'.$loan->id)->assertOk()->assertSee('Lunas');
        $this->assertSame([0, 1000001, 0], array_column($this->get('/anggota')->assertOk()->viewData('stats')['pinjaman'], 'amount'));
    }

    public function test_schedule_failure_rolls_back_every_row_and_status(): void
    {
        $this->admin();
        $loan = $this->loan();
        app(AdminLoans::class)->approve($loan);
        $rows = (new LoanSimulator)->schedule($loan->jumlah, $loan->tenor_bulan, $loan->bunga_persen_per_bulan, CarbonImmutable::today());
        $this->mock(LoanSimulator::class)->shouldReceive('schedule')->once()->andReturn([$rows[0], $rows[0]]);
        try {
            app(AdminLoans::class)->disburse($loan);
            $this->fail('Jadwal duplikat harus gagal.');
        } catch (QueryException) {
            $this->assertSame(0, $loan->angsurans()->count());
            $this->assertSame('disetujui', $loan->fresh()->status);
        }
        $small = (new LoanSimulator)->schedule(2, 5, '1.0000', CarbonImmutable::today());
        $this->assertSame([0, 0, 0, 0, 2], array_column($small, 'pokok'));
    }

    public function test_admin_only_pages_actions_and_private_akad_download(): void
    {
        Storage::fake('local');
        $loan = $this->loan();
        $loan->update(['dokumen_akad_path' => 'akad/loan.pdf']);
        Storage::disk('local')->put('akad/loan.pdf', 'private-test');
        $this->get('/admin/pinjaman')->assertRedirect('/login');
        $this->admin();
        $this->get('/admin/pinjaman')->assertOk();
        $this->get('/admin/pinjaman/'.$loan->id)->assertOk();
        $this->get(route('admin.pinjaman.akad', $loan))->assertDownload('loan.pdf');
        $loan->update(['dokumen_akad_path' => '../secret.pdf']);
        $this->get(route('admin.pinjaman.akad', $loan))->assertNotFound();
        $this->actingAs($loan->anggota->user);
        foreach (['/admin/pinjaman', '/admin/pinjaman/'.$loan->id, route('admin.pinjaman.akad', $loan)] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->expectException(HttpException::class);
        app(AdminLoans::class)->approve($loan);
    }
}
