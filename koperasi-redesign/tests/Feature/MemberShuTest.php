<?php

namespace Tests\Feature;

use App\MemberOverview;
use App\Models\Anggota;
use App\Models\ShuPeriode;
use App\Models\User;
use Database\Seeders\CooperativeDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberShuTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_totals_match_details_chart_and_dashboard_without_draft(): void
    {
        $this->seed(CooperativeDemoSeeder::class);
        $user = User::where('nip', CooperativeDemoSeeder::ANGGOTA_NIP)->firstOrFail();
        $user->update(['must_change_password' => false]);
        $response = $this->actingAs($user)->get('/anggota/shu')->assertOk();
        $rows = $response->viewData('rows');
        $this->assertNotEmpty($rows);
        $this->assertNotContains(2025, array_column($rows, 'year'));
        $this->assertSame(array_reverse(array_column($rows, 'amount')), array_column($response->viewData('chartData'), 'amount'));
        foreach ($rows as $row) {
            $direct = (int) $user->anggota->shuAnggotas()->whereHas('periode', fn ($q) => $q->where('tahun', $row['year'])->where('status', 'ditetapkan'))->sum('shu');
            $detail = $this->get('/anggota/shu/'.$row['year'])->assertOk()->assertSee('SHU Seluruhnya');
            $this->assertSame($direct, $row['amount']);
            $this->assertSame($direct, $detail->viewData('totalRows')[0]['shu']);
        }
        $this->assertSame($rows[0]['amount'], app(MemberOverview::class)->latestShu($user->anggota)['amount']);
        $this->get('/anggota/shu/2025')->assertNotFound();
    }

    public function test_split_pokok_wajib_and_all_seven_labels_preserve_combined_share(): void
    {
        $member = Anggota::factory()->create(['user_id' => User::factory()->create(['must_change_password' => false])->id]);
        $period = ShuPeriode::create(['tahun' => 2024, 'status' => 'ditetapkan', 'shu_bersih' => 900]);
        $component = $period->komponens()->create(['kode' => 'simpanan_pokok_wajib', 'unit' => 'usipa', 'metode' => 'proporsional', 'pool_nominal' => 900]);
        $component->shuAnggotas()->create(['shu_periode_id' => $period->id, 'anggota_id' => $member->id, 'kode_komponen' => $component->kode, 'jumlah_dasar' => 350000, 'bulan_aktif' => 12, 'shu' => 900]);
        foreach ([['pokok', 100000], ['wajib', 250000]] as [$type, $amount]) {
            $member->simpananTransaksis()->create(['jenis' => $type, 'tipe' => 'setor', 'nominal' => $amount, 'tanggal' => '2024-01-01', 'sumber' => 'manual']);
        }
        $member->simpananTransaksis()->create(['jenis' => 'wajib', 'tipe' => 'setor', 'nominal' => 999999, 'tanggal' => '2025-01-01', 'sumber' => 'manual']);
        $data = $this->actingAs($member->user)->get('/anggota/shu/2024')->assertOk()->viewData('rows');
        $this->assertCount(7, $data);
        $this->assertSame(100000, $data[0]['amount']);
        $this->assertSame('-', $data[0]['shu']);
        $this->assertSame(250000, $data[1]['amount']);
        $this->assertSame(900, $data[1]['shu']);
        $this->assertNull($data[6]['amount']);
        $this->assertSame(['Simpanan Pokok', 'Simpanan Wajib', 'Simpanan Sukarela', 'Jumlah Belanja', 'Jasa Sipa Angg', 'Jasa Sipa Sus', 'Jasa Umum'], array_column($data, 'type'));
    }

    public function test_other_members_allocations_are_not_accessible_and_empty_index_is_valid(): void
    {
        $this->seed(CooperativeDemoSeeder::class);
        $user = User::factory()->create(['must_change_password' => false]);
        Anggota::factory()->create(['user_id' => $user->id]);
        $response = $this->actingAs($user)->get('/anggota/shu')->assertOk()->assertSee('Data SHU belum tersedia.');
        $this->assertSame([], $response->viewData('rows'));
        $this->get('/anggota/shu/2024')->assertNotFound();
        $this->get('/anggota/shu/2020')->assertNotFound();
    }

    public function test_guest_and_admin_cannot_access_shu(): void
    {
        foreach (['/anggota/shu', '/anggota/shu/2024'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
        $this->actingAs(User::factory()->create(['role' => 'admin', 'must_change_password' => false]));
        foreach (['/anggota/shu', '/anggota/shu/2024'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }
}
