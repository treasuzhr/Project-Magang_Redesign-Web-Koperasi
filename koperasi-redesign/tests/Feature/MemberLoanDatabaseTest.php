<?php

namespace Tests\Feature;

use App\MemberOverview;
use App\Models\Anggota;
use App\Models\Pinjaman;
use App\Models\PinjamanJenis;
use App\Models\User;
use Database\Seeders\CooperativeDemoSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberLoanDatabaseTest extends TestCase
{
    use RefreshDatabase;

    private function member(): Anggota
    {
        return Anggota::factory()->create(['user_id' => User::factory()->create(['must_change_password' => false])->id]);
    }

    private function loan(Anggota $member, string $status = 'berjalan', string $kind = 'usipa', array $extra = []): Pinjaman
    {
        $jenis = PinjamanJenis::firstOrCreate(['kode' => $kind], [
            'nama' => $kind, 'bunga_persen_per_bulan' => 1, 'plafon_maks' => 50000000,
            'tenor_maks' => 36, 'butuh_dokumen_akad' => $kind === 'usipa_khusus',
        ]);

        return $member->pinjamans()->create(array_merge([
            'pinjaman_jenis_id' => $jenis->id, 'kode' => fake()->unique()->numerify('PIN-########'),
            'jumlah' => 1000000, 'tenor_bulan' => 10, 'bunga_persen_per_bulan' => 1,
            'metode_bayar' => 'potong_gaji', 'status' => $status, 'tanggal_pengajuan' => '2025-01-01',
        ], $extra));
    }

    private function installment(Pinjaman $loan, int $number, string $due, string $status = 'belum'): void
    {
        $loan->angsurans()->create([
            'angsuran_ke' => $number, 'jatuh_tempo' => $due, 'pokok' => 100000,
            'bunga' => 10000, 'total' => 110000, 'status' => $status,
            'tanggal_bayar' => $status === 'lunas' ? $due : null,
        ]);
    }

    public function test_owner_filtered_paginated_lists_and_active_panel(): void
    {
        $member = $this->member();
        $other = $this->loan($this->member(), 'berjalan', 'usipa', ['kode' => 'RAHASIA-LAIN']);
        for ($day = 1; $day <= 26; $day++) {
            $this->loan($member, 'berjalan', 'usipa', ['tanggal_pengajuan' => sprintf('2025-01-%02d', $day)]);
        }
        $this->loan($member, 'ditolak', 'toko');
        $response = $this->actingAs($member->user)->get('/anggota/pinjaman/riwayat?status=berjalan&jenis=usipa&per_page=10&page=2')->assertOk()->assertDontSee($other->kode);
        $paginator = $response->viewData('paginator');
        $this->assertSame(26, $paginator->total());
        $this->assertCount(10, $response->viewData('rows'));
        $this->assertSame(range(11, 20), array_column($response->viewData('rows'), 'number'));
        $this->assertSame('2025-01-16', $response->viewData('rows')[0]['date']);
        parse_str(parse_url($paginator->nextPageUrl(), PHP_URL_QUERY), $query);
        $this->assertSame(['status' => 'berjalan', 'jenis' => 'usipa', 'per_page' => '10', 'page' => '3'], $query);
        $response->assertSee('name="status" value="berjalan"', false)->assertSee('Unit Simpan Pinjam')->assertDontSee('data-member-table=', false);
        $active = $this->get('/anggota/pinjaman?per_page=25')->assertOk()->assertDontSee('Ditolak');
        $this->assertCount(25, $active->viewData('activeLoans'));
        $this->get('/anggota/pinjaman/riwayat?jenis=toko&status=ditolak')->assertOk()->assertSee('Toko')->assertSee('Ditolak');
        $this->getJson('/anggota/pinjaman/riwayat?jenis=usp')->assertUnprocessable();
        $this->getJson('/anggota/pinjaman/riwayat?status=unknown')->assertUnprocessable();
    }

    public function test_detail_totals_schedule_and_derived_overdue_do_not_write_to_database(): void
    {
        $this->travelTo(now()->setDate(2025, 5, 15)->startOfDay());
        $member = $this->member();
        $loan = $this->loan($member, 'berjalan', 'usipa_khusus', ['metode_bayar' => 'tunjangan_kinerja']);
        $this->installment($loan, 3, '2025-05-15');
        $this->installment($loan, 1, '2025-03-15', 'lunas');
        $this->installment($loan, 4, '2025-06-15');
        $this->installment($loan, 2, '2025-04-15');
        $before = $loan->angsurans()->orderBy('id')->get()->toArray();
        $response = $this->actingAs($member->user)->get('/anggota/pinjaman/'.$loan->id)->assertOk()
            ->assertSee('USP Khusus')->assertSee('Tunjangan Kinerja')->assertSee('Dokumen akad belum tersedia.')
            ->assertSee('Rp 900.000')->assertSee('Rp 110.000')->assertSee('Tunggak');
        $data = $response->viewData('loan');
        $this->assertSame(110000, $data['paid_total']);
        $this->assertSame(900000, $data['remaining']);
        $this->assertSame(3, $data['remaining_installments']);
        $this->assertSame(1, $data['overdue_count']);
        $this->assertSame([1, 2, 3, 4], array_column($data['schedule'], 'number'));
        $this->assertSame(['Lunas', 'Tunggak', 'Belum', 'Belum'], array_column($data['schedule'], 'status'));
        $this->assertSame([100000], array_values(array_unique(array_column($data['schedule'], 'principal'))));
        $this->assertSame([10000], array_values(array_unique(array_column($data['schedule'], 'interest'))));
        $this->assertSame('-', $data['schedule'][1]['paid_date']);
        $this->assertSame($before, $loan->angsurans()->orderBy('id')->get()->toArray());
        $this->assertSame(900000, app(MemberOverview::class)->loanSummary($member)['active_remaining']);
        $this->travelBack();
    }

    public function test_pending_rejected_approved_and_paid_loans_render_without_synthetic_schedules(): void
    {
        $member = $this->member();
        $this->actingAs($member->user);
        foreach (['diajukan', 'ditolak', 'disetujui'] as $status) {
            $loan = $this->loan($member, $status);
            $response = $this->get('/anggota/pinjaman/'.$loan->id)->assertOk()->assertSee(ucfirst($status))->assertSee('Belum ada jadwal angsuran.');
            $this->assertSame([], $response->viewData('loan')['schedule']);
            $this->assertSame(0, $response->viewData('loan')['paid_total']);
        }
        $loan = $this->loan($member, 'lunas');
        for ($n = 1; $n <= 10; $n++) {
            $this->installment($loan, $n, '2025-01-01', 'lunas');
        }
        $data = $this->get('/anggota/pinjaman/'.$loan->id)->assertOk()->viewData('loan');
        $this->assertSame(0, $data['remaining']);
        $this->assertSame(0, $data['remaining_installments']);
        $this->assertSame(1100000, $data['paid_total']);
    }

    public function test_other_members_loan_and_document_are_404_and_documents_are_private(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('akad/contoh.pdf', 'Dokumen contoh');
        $member = $this->member();
        $loan = $this->loan($member, 'berjalan', 'usipa_khusus', ['dokumen_akad_path' => 'akad/contoh.pdf']);
        $other = $this->loan($this->member(), 'berjalan', 'usipa_khusus', ['dokumen_akad_path' => 'akad/contoh.pdf']);
        $this->actingAs($member->user)->get('/anggota/pinjaman/'.$loan->id)->assertOk()->assertSee('Unduh dokumen akad');
        $this->get('/anggota/pinjaman/'.$loan->id.'/akad')->assertDownload('contoh.pdf');
        $this->get('/anggota/pinjaman/'.$other->id)->assertNotFound();
        $this->get('/anggota/pinjaman/'.$other->id.'/akad')->assertNotFound();
        $this->get('/anggota/pinjaman/999999')->assertNotFound();
        $loan->update(['dokumen_akad_path' => null]);
        $this->get('/anggota/pinjaman/'.$loan->id.'/akad')->assertNotFound();
    }

    public function test_empty_results_guests_and_admin_access(): void
    {
        $member = $this->member();
        foreach (['/anggota/pinjaman', '/anggota/pinjaman/riwayat', '/anggota/pinjaman/1', '/anggota/pinjaman/1/akad'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
        $this->actingAs($member->user)->get('/anggota/pinjaman')->assertOk()->assertSee('Anda belum memiliki pinjaman aktif.');
        $this->get('/anggota/pinjaman/riwayat?status=lunas')->assertOk()->assertSee('Belum ada riwayat pinjaman.');
        $this->actingAs(User::factory()->create(['role' => 'admin', 'must_change_password' => false]));
        foreach (['/anggota/pinjaman', '/anggota/pinjaman/riwayat', '/anggota/pinjaman/1', '/anggota/pinjaman/1/akad'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_seeded_demo_login_list_detail_and_dashboard_totals_agree(): void
    {
        $this->seed(CooperativeDemoSeeder::class);
        $user = User::where('nip', CooperativeDemoSeeder::ANGGOTA_NIP)->firstOrFail();
        $user->update(['must_change_password' => false]);
        $this->withSession(['captcha_answer' => 'ABC123'])->post('/login', ['member_id' => $user->nip, 'password' => CooperativeDemoSeeder::PASSWORD, 'captcha' => 'ABC123'])->assertRedirect('/anggota');
        Model::preventLazyLoading();
        try {
            $response = $this->get('/anggota/pinjaman/riwayat')->assertOk();
            $this->assertSame(5, $response->viewData('paginator')->total());
            foreach ($response->viewData('rows') as $row) {
                $data = $this->get('/anggota/pinjaman/'.$row['id'])->assertOk()->viewData('loan');
                $this->assertSame($row['remaining'], $data['remaining']);
                $this->assertSame(0, $data['remaining']);
                $this->assertCount(10, $data['schedule']);
            }
            $dashboard = $this->get('/anggota')->assertOk();
            $this->assertSame(0, app(MemberOverview::class)->loanSummary($user->anggota)['active_remaining']);
        } finally {
            Model::preventLazyLoading(false);
        }
    }
}
