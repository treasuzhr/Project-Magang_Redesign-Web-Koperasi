<?php

namespace Tests\Feature;

use App\AdminMemberAccounts;
use App\AdminOverview;
use App\Filament\Resources\Anggotas\AnggotaResource;
use App\Filament\Resources\Anggotas\Pages\CreateAnggota;
use App\Filament\Resources\Anggotas\Pages\EditAnggota;
use App\Filament\Resources\Anggotas\Pages\ListAnggotas;
use App\Filament\Widgets\AdminStats;
use App\Models\Anggota;
use App\Models\User;
use Database\Seeders\CooperativeDemoSeeder;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    private function admin(): User
    {
        $user = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
        $this->actingAs($user);

        return $user;
    }

    private function data(array $extra = []): array
    {
        return array_merge(['nip' => 'TEST-ANGGOTA-001', 'nama' => 'Anggota Pengujian', 'nomor_anggota' => 'TEST-001',
            'pangkat_golongan' => 'III/a', 'tanggal_masuk' => '2026-01-01', 'tanggal_keluar' => null, 'status' => 'aktif', 'password' => 'AwalTesting123'], $extra);
    }

    public function test_panel_uses_existing_login_and_blocks_members_and_forces_admin_password_change(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $member = User::factory()->create(['must_change_password' => false]);
        Anggota::factory()->create(['user_id' => $member->id]);
        $this->actingAs($member)->get('/admin')->assertForbidden();
        $this->get('/admin/anggota')->assertForbidden();
        $this->assertFalse($member->canAccessPanel(Filament::getPanel('admin')));
        $admin = $this->admin();
        $this->assertTrue($admin->canAccessPanel(Filament::getPanel('admin')));
        $this->get('/admin')->assertOk()->assertSee('Dashboard Admin')->assertSee('Primkop Admin')->assertSee('Keluar');
        $admin->update(['must_change_password' => true]);
        $this->get('/admin')->assertRedirect('/ganti-password');
        $this->get('/admin/anggota')->assertRedirect('/ganti-password');
        $this->assertFalse(Route::has('filament.admin.auth.login'));
        $this->assertFalse(Route::has('filament.admin.auth.register'));
        $this->assertFalse(Route::has('filament.admin.auth.password-reset.request'));
    }

    public function test_demo_admin_login_uses_nip_captcha_and_panel_logout_works(): void
    {
        $this->seed(CooperativeDemoSeeder::class);
        $user = User::where('nip', CooperativeDemoSeeder::ADMIN_NIP)->firstOrFail();
        $user->update(['must_change_password' => false]);
        $this->withSession(['captcha_answer' => 'ABC123'])->post('/login', ['member_id' => $user->nip, 'password' => CooperativeDemoSeeder::PASSWORD, 'captcha' => 'ABC123'])->assertRedirect('/admin');
        $this->get('/admin')->assertOk();
        $this->post(route('filament.admin.auth.logout'))->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_dashboard_aggregates_seeded_records_in_sql(): void
    {
        $this->seed(CooperativeDemoSeeder::class);
        $this->admin();
        $expected = [
            'active_members' => DB::table('anggotas')->where('status', 'aktif')->count(),
            'savings' => (int) DB::table('simpanan_transaksis')->selectRaw("SUM(CASE WHEN tipe='setor' THEN nominal ELSE -nominal END) AS total")->value('total'),
            'running_loans' => DB::table('pinjamans')->where('status', 'berjalan')->count(),
            'pending_loans' => DB::table('pinjamans')->where('status', 'diajukan')->count(),
            'new_orders' => DB::table('pesanans')->where('status', 'baru')->count(),
        ];
        $this->assertSame($expected, app(AdminOverview::class)->totals());
        Livewire::test(AdminStats::class)->assertSee('Rp '.number_format($expected['savings'], 0, ',', '.'))->assertSee('Anggota aktif')->assertSee('Pesanan baru');
    }

    public function test_create_member_form_hashes_password_and_locks_role(): void
    {
        $this->admin();
        Livewire::test(CreateAnggota::class)->fillForm($this->data())->call('create')->assertHasNoFormErrors();
        $member = Anggota::where('nama', 'Anggota Pengujian')->sole();
        $user = $member->user;
        $this->assertSame('anggota', $user->role);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check('AwalTesting123', $user->password));
        $this->assertNotSame('AwalTesting123', $user->password);
        $this->assertSame($member->nama, $user->name);
        Livewire::test(CreateAnggota::class)->assertFormFieldDoesNotExist('role');
        $forced = app(AdminMemberAccounts::class)->create($this->data(['nip' => 'TEST-002', 'nomor_anggota' => 'TEST-002', 'role' => 'admin', 'must_change_password' => false]));
        $this->assertSame('anggota', $forced->user->role);
        $this->assertTrue($forced->user->must_change_password);
    }

    public function test_member_credentials_fields_do_not_autofill_and_are_empty_on_create(): void
    {
        $this->admin();
        $create = Livewire::test(CreateAnggota::class);
        $formData = $create->get('data');
        $this->assertEmpty($formData['nip'] ?? null);
        $this->assertEmpty($formData['password'] ?? null);

        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($create->html());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new \DOMXPath($document);

        $nip = $xpath->query('//input[@name="cooperative_member_reference"]')->item(0);
        $this->assertNotNull($nip);
        $this->assertSame('', $nip->getAttribute('value'));
        $this->assertSame('off', $nip->getAttribute('autocomplete'));
        $this->assertSame('cooperative_member_reference', $nip->getAttribute('name'));
        $this->assertSame('other', $nip->getAttribute('data-form-type'));

        $password = $xpath->query('//input[@type="password" and @autocomplete="new-password"]')->item(0);
        $this->assertNotNull($password);
        $this->assertSame('', $password->getAttribute('value'));
        $this->assertSame('new-password', $password->getAttribute('autocomplete'));
        $this->assertSame('true', $password->getAttribute('data-lpignore'));
        $this->assertSame('true', $password->getAttribute('data-1p-ignore'));
        $this->assertSame('true', $password->getAttribute('data-bwignore'));
    }

    public function test_edit_member_form_shows_member_nip_but_never_renders_password(): void
    {
        $this->admin();
        $member = app(AdminMemberAccounts::class)->create($this->data());
        $edit = Livewire::test(EditAnggota::class, ['record' => $member->id]);

        $this->assertSame('TEST-ANGGOTA-001', $edit->get('data.nip'));
        $edit->assertDontSee('Kata sandi awal')
            ->assertDontSeeHtml('wire:model="data.password"');
    }

    public function test_member_forms_require_consistent_exit_dates_and_statuses(): void
    {
        $this->admin();

        Livewire::test(CreateAnggota::class)
            ->fillForm($this->data(['status' => 'keluar', 'tanggal_keluar' => '2025-12-31']))
            ->call('create')
            ->assertHasFormErrors(['tanggal_keluar' => 'after_or_equal']);

        Livewire::test(CreateAnggota::class)
            ->fillForm($this->data(['status' => 'aktif', 'tanggal_keluar' => '2026-02-01']))
            ->call('create')
            ->assertHasFormErrors(['status' => 'not_in']);

        Livewire::test(CreateAnggota::class)
            ->fillForm($this->data(['status' => 'pensiun']))
            ->call('create')
            ->assertHasFormErrors(['tanggal_keluar' => 'required']);
    }

    public function test_member_admin_forms_use_indonesian_labels_and_filament_locale_is_panel_scoped(): void
    {
        $this->admin();
        $this->get('/admin/anggota/create')
            ->assertOk()
            ->assertSee('Tambah Anggota')
            ->assertSee('Tambah')
            ->assertSee('Simpan')
            ->assertSee('Simpan & tambah lagi')
            ->assertSee('Batal')
            ->assertSee('Isi jika anggota berhenti (pensiun, pindah, atau mengundurkan diri). Kosongkan jika masih aktif.');
        $this->assertSame('en', app()->getLocale());

        $member = app(AdminMemberAccounts::class)->create($this->data());
        $this->get('/admin/anggota/'.$member->id.'/edit')
            ->assertOk()
            ->assertSee('Ubah Anggota')
            ->assertSee('Ubah')
            ->assertSee('Simpan perubahan')
            ->assertSee('Batal');

        $this->get('/admin/berita/create')->assertOk()->assertSee('Buat Berita');
        $this->assertSame('en', app()->getLocale());
    }

    public function test_duplicate_nip_is_rejected_without_creating_half_an_account(): void
    {
        $this->admin();
        User::factory()->create(['nip' => 'DUPLICATE']);
        $users = User::count();
        Livewire::test(CreateAnggota::class)->fillForm($this->data(['nip' => 'DUPLICATE']))->call('create')->assertHasFormErrors(['nip' => 'unique']);
        $this->assertSame($users, User::count());
        $this->assertDatabaseCount('anggotas', 0);
    }

    public function test_edit_status_keeps_data_and_user_identity_in_sync_and_no_delete_action_exists(): void
    {
        $this->admin();
        $member = app(AdminMemberAccounts::class)->create($this->data());
        $count = Anggota::count();
        $userCount = User::count();
        Livewire::test(EditAnggota::class, ['record' => $member->id])->fillForm(['nama' => 'Nama diperbarui', 'status' => 'pensiun', 'tanggal_keluar' => '2026-05-01'])->call('save')->assertHasNoFormErrors();
        $member->refresh();
        $this->assertSame('pensiun', $member->status);
        $this->assertSame('Nama diperbarui', $member->user->name);
        $this->assertSame($count, Anggota::count());
        $this->assertSame($userCount, User::count());
        $this->assertFalse(AnggotaResource::canDelete($member));
        $this->assertFalse(AnggotaResource::canDeleteAny());
        $data = $this->data(['nama' => 'Nama diperbarui', 'status' => 'pindah', 'role' => 'admin']);
        unset($data['password']);
        app(AdminMemberAccounts::class)->update($member, $data);
        $this->assertSame('anggota', $member->fresh()->user->role);
    }

    public function test_reset_password_action_generates_new_hashed_password_and_requires_change(): void
    {
        $this->admin();
        $member = app(AdminMemberAccounts::class)->create($this->data());
        $member->user->update(['must_change_password' => false]);
        $old = $member->user->password;
        $table = Livewire::test(ListAnggotas::class);
        $actions = $table->instance()->getTable()->getRecordActions();
        $resetGroup = $actions[2];
        $this->assertInstanceOf(ActionGroup::class, $resetGroup);
        $this->assertSame('Aksi lainnya', $resetGroup->getTooltip());
        $this->assertSame('Atur ulang kata sandi', $resetGroup->getActions()[0]->getLabel());
        $this->assertArrayHasKey('resetPassword', $table->instance()->getTable()->getFlatRecordActions());
        $table->callTableAction('resetPassword', $member)->assertNotified();
        $member->refresh();
        $this->assertNotSame($old, $member->user->password);
        $this->assertTrue($member->user->must_change_password);

        $edit = Livewire::test(EditAnggota::class, ['record' => $member->id]);
        $edit->assertSee('Atur ulang kata sandi')->callAction('resetPassword')->assertNotified();

        $this->actingAs($member->user)->get('/admin/anggota/'.$member->id.'/edit')->assertForbidden();

        $password = app(AdminMemberAccounts::class)->resetPassword($member);
        $this->assertTrue(Hash::check($password, $member->fresh()->user->password));
    }

    public function test_table_searches_names_and_nips_and_filters_status(): void
    {
        $this->admin();
        $one = app(AdminMemberAccounts::class)->create($this->data());
        $two = app(AdminMemberAccounts::class)->create($this->data(['nip' => 'NIP-SECOND', 'nama' => 'Orang Kedua', 'nomor_anggota' => 'T-002', 'status' => 'keluar']));
        $table = Livewire::test(ListAnggotas::class);
        $columns = $table->instance()->getTable()->getColumns();
        $this->assertArrayNotHasKey('password', $columns);
        $this->assertArrayNotHasKey('user.password', $columns);
        $this->assertArrayNotHasKey('password_hash', $columns);
        $this->assertArrayHasKey('tanggal_keluar', $columns);
        $this->assertTrue($columns['tanggal_keluar']->isToggledHiddenByDefault());
        $table->assertSee('NIP/NRP')->assertDontSee($one->user->password)->assertDontSee($two->user->password);
        Livewire::test(ListAnggotas::class)->searchTable('TEST-ANGGOTA')->assertCanSeeTableRecords([$one])->assertCanNotSeeTableRecords([$two]);
        Livewire::test(ListAnggotas::class)->searchTable('Orang Kedua')->assertCanSeeTableRecords([$two])->assertCanNotSeeTableRecords([$one]);
        Livewire::test(ListAnggotas::class)->filterTable('status', 'aktif')->assertCanSeeTableRecords([$one])->assertCanNotSeeTableRecords([$two]);
    }
}
