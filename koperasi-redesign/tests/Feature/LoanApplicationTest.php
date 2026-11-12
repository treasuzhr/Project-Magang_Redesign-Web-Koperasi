<?php

namespace Tests\Feature;

use App\LoanSimulator;
use App\MemberOverview;
use App\Models\Anggota;
use App\Models\Pinjaman;
use App\Models\PinjamanJenis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoanApplicationTest extends TestCase
{
    use RefreshDatabase;

    private Anggota $member;

    private PinjamanJenis $kind;

    protected function setUp(): void
    {
        parent::setUp();
        $this->member = Anggota::factory()->create(['user_id' => User::factory()->create(['must_change_password' => false])->id]);
        foreach (['toko' => 'Toko', 'usipa' => 'Unit Simpan Pinjam', 'usipa_khusus' => 'USP Khusus'] as $code => $label) {
            PinjamanJenis::create(['kode' => $code, 'nama' => $label, 'bunga_persen_per_bulan' => $code === 'toko' ? 2 : 1,
                'plafon_maks' => 50000000, 'tenor_maks' => 36, 'butuh_dokumen_akad' => $code === 'usipa_khusus']);
        }
        $this->kind = PinjamanJenis::where('kode', 'usipa')->firstOrFail();
        $this->actingAs($this->member->user);
    }

    private function payload(array $extra = []): array
    {
        return array_merge(['loan_type' => 'usipa', 'loan_total' => '12.000.000', 'loan_term' => 12, 'payment_method' => 'potong_gaji'], $extra);
    }

    public function test_form_uses_database_types_and_limits_without_adding_an_upload_field(): void
    {
        $this->kind->update(['plafon_maks' => 21000000, 'tenor_maks' => 17, 'bunga_persen_per_bulan' => '1.2500']);
        $response = $this->get('/anggota/pinjaman/ajukan')->assertOk()->assertSee('name="_token"', false)
            ->assertSee('USP Khusus')->assertSee('value="usipa"', false)->assertSee('value="potong_gaji"', false)
            ->assertSee('Simulasi bersifat estimasi, hasil final mengikuti persetujuan koperasi.')
            ->assertDontSee('type="file"', false);
        $this->assertSame(21000000, $response->viewData('loanKindData')['usipa']['plafon_maks']);
        $this->assertSame(17, $response->viewData('loanKindData')['usipa']['tenor_maks']);
        $this->assertSame('1.2500', $response->viewData('loanKindData')['usipa']['bunga_persen_per_bulan']);
    }

    public function test_flat_simulation_uses_database_interest_and_returns_rounded_rupiah(): void
    {
        $expected = ['principal' => 1000000, 'interest' => 120000, 'total' => 1120000];
        $this->assertSame($expected, app(LoanSimulator::class)->calculate(12000000, 12, '1.0000'));
        $this->getJson('/anggota/pinjaman/simulasi?'.http_build_query($this->payload(['interest' => 0])))->assertOk()->assertExactJson($expected);
        $this->getJson('/anggota/pinjaman/simulasi?'.http_build_query($this->payload(['loan_type' => 'toko'])))->assertOk()->assertJsonPath('interest', 240000);
        $this->assertSame(['principal' => 333333, 'interest' => 10000, 'total' => 343333], app(LoanSimulator::class)->calculate(1000000, 3, '1.0000'));
        $this->assertDatabaseCount('pinjamans', 0);
    }

    public function test_valid_post_stores_snapshot_owner_and_pending_only_with_no_installments(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 11));
        $other = Anggota::factory()->create();
        $summary = app(MemberOverview::class)->loanSummary($this->member);
        $this->post('/anggota/pinjaman/ajukan', $this->payload([
            'anggota_id' => $other->id, 'status' => 'berjalan', 'bunga_persen_per_bulan' => 0,
            'principalEstimate' => 1, 'tanggal_disetujui' => '2025-01-01',
        ]))->assertRedirect('/anggota/pinjaman/riwayat')->assertSessionHas('status', 'Pengajuan pinjaman Anda berhasil dikirim.');
        $loan = Pinjaman::sole();
        $this->assertSame($this->member->id, $loan->anggota_id);
        $this->assertSame($this->kind->id, $loan->pinjaman_jenis_id);
        $this->assertSame(12000000, $loan->jumlah);
        $this->assertSame(12, $loan->tenor_bulan);
        $this->assertSame('potong_gaji', $loan->metode_bayar);
        $this->assertSame('diajukan', $loan->status);
        $this->assertSame('1.0000', $loan->bunga_persen_per_bulan);
        $this->assertSame('2026-11-11', $loan->tanggal_pengajuan->toDateString());
        $this->assertNull($loan->tanggal_disetujui);
        $this->assertNull($loan->dokumen_akad_path);
        $this->assertDatabaseCount('angsurans', 0);
        $this->kind->update(['bunga_persen_per_bulan' => '3.0000']);
        $this->assertSame('1.0000', $loan->fresh()->bunga_persen_per_bulan);
        $after = app(MemberOverview::class)->loanSummary($this->member);
        $this->assertSame($summary['disbursed_total'], $after['disbursed_total']);
        $this->assertSame($summary['active_remaining'], $after['active_remaining']);
        $this->get('/anggota/pinjaman/riwayat')->assertOk()->assertSee($loan->kode)->assertSee('Diajukan')->assertSee('Pengajuan pinjaman Anda berhasil dikirim.');
        $this->travelBack();
    }

    public function test_limits_invalid_types_and_methods_preserve_formatted_old_input(): void
    {
        $cases = [
            ['loan_type', 'usp'], ['loan_type', 'tidak-ada'], ['loan_total', 50000001],
            ['loan_total', 0], ['loan_total', '-100'], ['loan_total', 'abc100'],
            ['loan_total', '1.50'], ['loan_total', '999999999999999999999999999'],
            ['loan_total', ['bad']], ['loan_term', 37], ['loan_term', 0], ['loan_term', '1.5'],
            ['payment_method', 'potong-gaji'],
        ];
        foreach ($cases as [$field, $value]) {
            RateLimiter::clear(md5('loan-applicationloan-application:'.$this->member->user_id));
            $this->from('/anggota/pinjaman/ajukan')->post('/anggota/pinjaman/ajukan', $this->payload([$field => $value]))
                ->assertRedirect('/anggota/pinjaman/ajukan')->assertSessionHasErrors($field);
        }
        $this->get('/anggota/pinjaman/ajukan')->assertOk();
        RateLimiter::clear(md5('loan-applicationloan-application:'.$this->member->user_id));
        $this->from('/anggota/pinjaman/ajukan')->post('/anggota/pinjaman/ajukan', $this->payload(['payment_method' => '']))
            ->assertSessionHasInput('loan_total', '12.000.000');
        $this->assertDatabaseCount('pinjamans', 0);
        $this->getJson('/anggota/pinjaman/simulasi?loan_type=usipa&loan_total=50000001&loan_term=12')->assertUnprocessable();
    }

    public function test_duplicate_pending_same_kind_is_rejected_but_other_kinds_are_allowed(): void
    {
        $this->post('/anggota/pinjaman/ajukan', $this->payload())->assertRedirect('/anggota/pinjaman/riwayat');
        $this->from('/anggota/pinjaman/ajukan')->post('/anggota/pinjaman/ajukan', $this->payload())->assertSessionHasErrors('loan_type');
        $this->post('/anggota/pinjaman/ajukan', $this->payload(['loan_type' => 'toko']))->assertRedirect('/anggota/pinjaman/riwayat');
        $this->post('/anggota/pinjaman/ajukan', $this->payload(['loan_type' => 'usipa_khusus', 'payment_method' => 'tunjangan_kinerja']))->assertRedirect('/anggota/pinjaman/riwayat');
        $special = Pinjaman::whereHas('jenis', fn ($q) => $q->where('kode', 'usipa_khusus'))->sole();
        $this->assertNull($special->dokumen_akad_path);
        $this->assertSame('tunjangan_kinerja', $special->metode_bayar);
        $this->assertDatabaseCount('pinjamans', 3);
        $this->assertDatabaseCount('angsurans', 0);
    }

    public function test_submission_is_throttled_after_ten_attempts_per_member(): void
    {
        RateLimiter::clear(md5('loan-applicationloan-application:'.$this->member->user_id));
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/anggota/pinjaman/ajukan', $this->payload(['loan_total' => 0]))->assertUnprocessable();
        }
        $this->post('/anggota/pinjaman/ajukan', $this->payload())->assertStatus(429)->assertSee('Terlalu banyak percobaan');
        $this->assertDatabaseCount('pinjamans', 0);
    }

    public function test_guest_admin_and_inactive_members_cannot_submit(): void
    {
        auth()->logout();
        $this->post('/anggota/pinjaman/ajukan', $this->payload())->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => 'admin', 'must_change_password' => false]))
            ->post('/anggota/pinjaman/ajukan', $this->payload())->assertForbidden();
        $this->member->update(['status' => 'pensiun']);
        $this->actingAs($this->member->user)->post('/anggota/pinjaman/ajukan', $this->payload())->assertRedirect('/login');
        $this->assertDatabaseCount('pinjamans', 0);
    }

    public function test_disbursed_total_only_includes_running_and_paid_loans(): void
    {
        foreach (Pinjaman::STATUSES as $status) {
            $this->member->pinjamans()->create(['kode' => 'TEST-'.$status, 'pinjaman_jenis_id' => $this->kind->id,
                'jumlah' => 1000000, 'tenor_bulan' => 10, 'bunga_persen_per_bulan' => 1,
                'metode_bayar' => 'potong_gaji', 'status' => $status, 'tanggal_pengajuan' => today()]);
        }
        $this->assertSame(2000000, app(MemberOverview::class)->loanSummary($this->member)['disbursed_total']);
    }
}
