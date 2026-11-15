<?php

namespace Tests\Feature;

use App\AdminSavings;
use App\Filament\Resources\Anggotas\Pages\ViewAnggota;
use App\Filament\Resources\Anggotas\RelationManagers\SimpananTransaksisRelationManager;
use App\Filament\Resources\SimpananTransaksis\Pages\ListSimpananTransaksis;
use App\Filament\Resources\SimpananTransaksis\SimpananTransaksiResource;
use App\MemberOverview;
use App\Models\Anggota;
use App\Models\SimpananTransaksi;
use App\Models\User;
use App\SavingsCsvImporter;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AdminSavingsTest extends TestCase
{
    use RefreshDatabase;

    private Anggota $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->member = Anggota::factory()->create(['status' => 'aktif', 'user_id' => User::factory()->create(['must_change_password' => false])->id]);
        $this->actingAs(User::factory()->create(['role' => 'admin', 'must_change_password' => false]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    private function input(array $extra = []): array
    {
        return $extra + ['anggota_id' => $this->member->id, 'jenis' => 'sukarela', 'tipe' => 'setor', 'nominal' => 10000, 'tanggal' => '2025-01-01'];
    }

    public function test_deposit_withdraw_correction_and_member_balances(): void
    {
        $setor = app(AdminSavings::class)->record($this->input());
        app(AdminSavings::class)->record($this->input(['tipe' => 'tarik', 'nominal' => 2000]));
        $this->assertSame(8000, app(MemberOverview::class)->savings($this->member)['summary']['sukarela']);
        $this->expectException(ValidationException::class);
        app(AdminSavings::class)->correct($setor, 'Salah pencatatan');
    }

    public function test_valid_correction_preserves_original_and_reverses_the_amount(): void
    {
        $original = app(AdminSavings::class)->record($this->input(['jenis' => 'pokok']));
        $opposite = app(AdminSavings::class)->correct($original, 'Salah jenis');
        $this->assertSame('tarik', $opposite->tipe);
        $this->assertSame($original->nominal, $opposite->nominal);
        $this->assertStringContainsString('Salah jenis', $opposite->keterangan);
        $this->assertModelExists($original);
        $this->assertSame(0, app(MemberOverview::class)->savings($this->member)['summary']['pokok']);
        Livewire::test(ListSimpananTransaksis::class)->assertCanSeeTableRecords([$original, $opposite])->assertTableActionDoesNotExist('edit')->assertTableActionDoesNotExist('delete');
        $this->assertFalse(SimpananTransaksiResource::canEdit($original));
        $this->assertFalse(SimpananTransaksiResource::canDelete($original));
    }

    public function test_invalid_transactions_leave_balances_unchanged(): void
    {
        app(AdminSavings::class)->record($this->input());
        foreach ([['tipe' => 'tarik', 'nominal' => 10001], ['jenis' => 'pokok', 'tipe' => 'tarik'], ['jenis' => 'wajib', 'tipe' => 'tarik'], ['jenis' => 'khusus', 'tipe' => 'tarik'], ['nominal' => 0], ['nominal' => '1.5'], ['tanggal' => today()->addDay()->toDateString()]] as $invalid) {
            try {
                app(AdminSavings::class)->record($this->input($invalid));
                $this->fail('Transaksi harus ditolak');
            } catch (ValidationException) {
                $this->assertSame(1, SimpananTransaksi::count());
            }
        }
    }

    public function test_csv_validation_duplicates_reimport_and_report(): void
    {
        $nip = $this->member->user->nip;
        $file = UploadedFile::fake()->createWithContent('setoran.csv', "nip,jenis,jumlah,tanggal,keterangan\n{$nip},wajib,5000,2025-02-01,Gaji\n{$nip},wajib,5000,2025-02-01,Ganda\nUNKNOWN,wajib,5,2025-02-01,x\n{$nip},invalid,5,2025-02-01,x\n{$nip},wajib,-5,2025-02-01,x\n");
        $result = app(SavingsCsvImporter::class)->import($file->getRealPath());
        $this->assertSame(1, $result['created']);
        $this->assertSame(4, $result['skipped']);
        $this->assertSame('sudah ada', $result['errors'][0]['reason']);
        $repeat = app(SavingsCsvImporter::class)->import($file->getRealPath());
        $this->assertSame(0, $repeat['created']);
        $this->assertSame(1, SimpananTransaksi::count());
        $this->assertSame(5000, app(MemberOverview::class)->savings($this->member)['summary']['wajib']);
        Livewire::test(ViewAnggota::class, ['record' => $this->member->id])->assertSee('Rp 5.000');
        $this->actingAs($this->member->user)->get('/anggota/simpanan/wajib')->assertOk()->assertViewHas('summary', fn (array $summary): bool => $summary['wajib'] === 5000);
        $this->get('/admin/simpanan')->assertForbidden();
        $this->get(route('admin.simpanan.template'))->assertForbidden();
    }

    public function test_record_action_filters_and_readonly_relation(): void
    {
        Livewire::test(ListSimpananTransaksis::class)->callAction('record', $this->input())->assertHasNoActionErrors();
        Livewire::test(ListSimpananTransaksis::class)->callAction('record', $this->input(['tipe' => 'tarik', 'nominal' => 10001]))->assertHasActionErrors(['nominal']);
        $row = SimpananTransaksi::firstOrFail();
        Livewire::test(ListSimpananTransaksis::class)->set('activeTab', 'setor')->filterTable('jenis', 'sukarela')->searchTable($this->member->user->nip)->assertCanSeeTableRecords([$row]);
        Livewire::test(ListSimpananTransaksis::class)->set('activeTab', 'tarik')->assertCanNotSeeTableRecords([$row]);
        Livewire::test(SimpananTransaksisRelationManager::class, ['ownerRecord' => $this->member, 'pageClass' => ViewAnggota::class])->assertCanSeeTableRecords([$row])->assertTableActionDoesNotExist('edit')->assertTableActionDoesNotExist('delete');
        $this->get(route('admin.simpanan.template'))->assertDownload('templat-setoran.csv');
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('setoran.csv', 'nip,jenis,jumlah,tanggal,keterangan'."\n".$this->member->user->nip.',wajib,10,2025-02-02,Gaji');
        $component = Livewire::test(ListSimpananTransaksis::class)->callAction('importSavings', ['csv' => $file])->assertHasNoActionErrors();
        $this->assertSame(1, $component->get('importResult')['created']);
    }
}
