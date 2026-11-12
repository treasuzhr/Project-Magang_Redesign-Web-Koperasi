<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Pesanan;
use App\Models\User;
use Database\Seeders\CooperativeDemoSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberOrdersTest extends TestCase
{
    use RefreshDatabase;

    private function member(): Anggota
    {
        return Anggota::factory()->create(['user_id' => User::factory()->create(['must_change_password' => false])->id]);
    }

    private function order(Anggota $member, string $status = 'baru', string $date = '2025-01-01'): Pesanan
    {
        $order = $member->pesanans()->create(['kode' => fake()->unique()->numerify('ORD-########'), 'status' => $status, 'total' => 7000, 'metode_bayar' => 'potong_gaji', 'tanggal' => $date]);
        $order->items()->create(['nama' => 'Barang contoh', 'harga' => 2000, 'qty' => 2, 'subtotal' => 4000]);
        $order->items()->create(['nama' => 'Barang kedua', 'harga' => 3000, 'qty' => 1, 'subtotal' => 3000]);

        return $order;
    }

    public function test_owner_only_status_filters_server_pagination_and_ordering(): void
    {
        $member = $this->member();
        $other = $this->order($this->member());
        for ($day = 1; $day <= 26; $day++) {
            $this->order($member, 'baru', sprintf('2025-01-%02d', $day));
        }
        $this->order($member, 'selesai');
        $response = $this->actingAs($member->user)->get('/anggota/pesanan?status=baru&per_page=10&page=2')->assertOk();
        $paginator = $response->viewData('paginator');
        $this->assertSame(26, $paginator->total());
        $this->assertCount(10, $response->viewData('rows'));
        $this->assertSame(range(11, 20), array_column($response->viewData('rows'), 'number'));
        $this->assertSame('16 Januari 2025', $response->viewData('rows')[0]['date']);
        $this->assertSame(3, $response->viewData('rows')[0]['item_count']);
        $this->assertNotContains($other->id, array_column($response->viewData('rows'), 'id'));
        parse_str(parse_url($paginator->nextPageUrl(), PHP_URL_QUERY), $query);
        $this->assertSame(['status' => 'baru', 'per_page' => '10', 'page' => '3'], $query);
        $response->assertSee('name="status" value="baru"', false);
        $this->get('/anggota/pesanan?status=batal')->assertOk()->assertSee('Belum ada pesanan.');
        $this->getJson('/anggota/pesanan?status=salah')->assertUnprocessable();
    }

    public function test_detail_snapshot_items_total_and_foreign_order_404_are_read_only(): void
    {
        $member = $this->member();
        $order = $this->order($member, 'diproses');
        $foreign = $this->order($this->member());
        $before = $order->items()->get()->toArray();
        $response = $this->actingAs($member->user)->get('/anggota/pesanan/'.$order->id)->assertOk()->assertSee('Barang contoh')->assertSee('Diproses')->assertSee('Total pesanan')->assertSee('Rp 7.000');
        $detail = $response->viewData('order');
        $this->assertSame(array_sum(array_column($detail['items'], 'total')), $detail['total']);
        $this->assertSame($order->total, $detail['total']);
        $this->get('/anggota/pesanan/'.$foreign->id.'?anggota_id='.$foreign->anggota_id)->assertNotFound();
        $this->get('/anggota/pesanan/999999')->assertNotFound();
        $this->post('/anggota/pesanan/'.$order->id)->assertStatus(405);
        $this->assertSame($before, $order->items()->get()->toArray());
        $empty = $member->pesanans()->create(['kode' => 'EMPTY', 'status' => 'baru', 'total' => 0, 'tanggal' => '2025-01-01', 'metode_bayar' => 'potong_gaji']);
        $this->get('/anggota/pesanan/'.$empty->id)->assertOk()->assertSee('Detail belum tersedia.');
    }

    public function test_demo_orders_eager_load_without_query_per_item(): void
    {
        $this->seed(CooperativeDemoSeeder::class);
        $user = User::where('nip', CooperativeDemoSeeder::ANGGOTA_NIP)->firstOrFail();
        $user->update(['must_change_password' => false]);
        Model::preventLazyLoading();
        try {
            $response = $this->actingAs($user)->get('/anggota/pesanan')->assertOk();
            $this->assertSame($user->anggota->pesanans()->count(), $response->viewData('paginator')->total());
            foreach ($response->viewData('rows') as $row) {
                $detail = $this->get('/anggota/pesanan/'.$row['id'])->assertOk()->viewData('order');
                $this->assertSame($row['total'], $detail['total']);
            }
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_guest_and_admin_cannot_access_orders(): void
    {
        foreach (['/anggota/pesanan', '/anggota/pesanan/1'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
        $this->actingAs(User::factory()->create(['role' => 'admin', 'must_change_password' => false]));
        foreach (['/anggota/pesanan', '/anggota/pesanan/1'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }
}
